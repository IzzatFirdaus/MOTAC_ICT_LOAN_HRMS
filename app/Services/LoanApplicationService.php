<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Approval;
use App\Models\LoanApplication;
use App\Models\LoanApplicationItem;
use App\Models\LoanTransaction;
use App\Models\LoanTransactionItem as AppLoanTransactionItemModel; // Explicit import
use App\Models\User;
// System Design 5.2
use Illuminate\Auth\Access\AuthorizationException as IlluminateAuthorizationException; // Laravel's standard auth exception
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth; // For default acting user ID
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// For sending notifications
use InvalidArgumentException; // Standard PHP exception
use RuntimeException;         // Standard PHP exception
use Throwable; // Catch all throwables

final class LoanApplicationService
{
  private const LOG_AREA = 'LoanApplicationService: ';
  private ApprovalService $approvalService;
  private LoanTransactionService $loanTransactionService;
  private NotificationService $notificationService;

  private array $defaultLoanApplicationRelations = [
    'user:id,name,email,department_id,position_id,grade_id', //
    'user.department:id,name', //
    'user.position:id,name', //
    'user.grade:id,name', //
    'responsibleOfficer:id,name,email', //
    'supportingOfficer:id,name,email,grade_id', //
    'supportingOfficer.grade:id,name,level', //
    'applicationItems', //
    'approvals.officer:id,name', //
    'loanTransactions.issuingOfficer:id,name', //
    'loanTransactions.receivingOfficer:id,name', //
    'loanTransactions.returningOfficer:id,name', //
    'loanTransactions.returnAcceptingOfficer:id,name', //
  ];

  public function __construct(
    ApprovalService $approvalService,
    LoanTransactionService $loanTransactionService,
    NotificationService $notificationService
  ) {
    $this->approvalService = $approvalService;
    $this->loanTransactionService = $loanTransactionService;
    $this->notificationService = $notificationService;
  }

  public function getApplicationsForUser(User $requestingUser, array $filters = []): LengthAwarePaginator
  {
    Log::debug(self::LOG_AREA . 'Fetching loan applications.', ['requesting_user_id' => $requestingUser->id, 'filters' => $filters]);
    $query = LoanApplication::query()->with($this->defaultLoanApplicationRelations);

    $isPrivilegedUser = $requestingUser->hasAnyRole(['Admin', 'BPM Staff']); //

    if (isset($filters['user_id']) && !empty($filters['user_id'])) {
      if (!$isPrivilegedUser && (int) $filters['user_id'] !== $requestingUser->id) {
        $query->where('user_id', $requestingUser->id);
        Log::warning(self::LOG_AREA . "Unauthorized attempt to filter by user_id, restricted to own.", ['requesting_user_id' => $requestingUser->id, 'target_user_id' => $filters['user_id']]);
      } else {
        $query->where('user_id', (int) $filters['user_id']);
      }
    } elseif (!$isPrivilegedUser) {
      $query->where('user_id', $requestingUser->id);
    }

    if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
      if (in_array($filters['status'], LoanApplication::getStatusKeys(), true)) {
        $query->where('status', $filters['status']);
      } else {
        Log::warning(self::LOG_AREA . "Invalid status filter ignored.", ['status_filter' => $filters['status']]);
      }
    }
    if ($isPrivilegedUser && !empty($filters['supporting_officer_id'])) {
      $query->where('supporting_officer_id', (int) $filters['supporting_officer_id']);
    }
    if (!empty($filters['search_term'])) {
      $term = '%' . $filters['search_term'] . '%';
      $query->where(function ($q) use ($term) {
        $q->where('id', 'like', $term)
          ->orWhere('purpose', 'like', $term)
          ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', $term));
      });
    }

    $orderBy = $filters['order_by'] ?? 'updated_at';
    $orderDirection = $filters['order_direction'] ?? 'desc';
    $validOrderBy = ['id', 'created_at', 'updated_at', 'loan_start_date', 'status', 'purpose'];
    if (!in_array($orderBy, $validOrderBy)) {
      $orderBy = 'updated_at';
    }
    if (!in_array($orderDirection, ['asc', 'desc'])) {
      $orderDirection = 'desc';
    }

    $perPage = isset($filters['per_page']) && is_numeric($filters['per_page']) ? (int) $filters['per_page'] : 15;
    return $query->orderBy($orderBy, $orderDirection)->paginate($perPage);
  }

  /**
   * @param array{responsible_officer_id?: int|null, supporting_officer_id: int, applicant_phone?:string|null, purpose: string, location: string, return_location?: string|null, loan_start_date: string, loan_end_date: string, items: list<array{equipment_type: string, quantity_requested: int, notes?: string|null}>, applicant_confirmation: bool} $validatedData
   */
  public function createAndSubmitApplication(array $validatedData, User $applicant): LoanApplication
  {
    $applicantId = $applicant->id;
    Log::info(self::LOG_AREA . "Creating and submitting loan application.", ['user_id' => $applicantId, 'data_keys' => array_keys($validatedData)]);

    if (empty($validatedData['items'])) {
      throw new InvalidArgumentException(__('Permohonan mesti mempunyai sekurang-kurangnya satu item peralatan.'));
    }
    if (empty($validatedData['supporting_officer_id'])) {
      throw new InvalidArgumentException(__('Pegawai Penyokong mesti dipilih untuk menghantar permohonan.'));
    }
    if (empty($validatedData['applicant_confirmation']) || $validatedData['applicant_confirmation'] !== true) {
      throw new InvalidArgumentException(__('Perakuan pemohon mesti diterima sebelum penghantaran.'));
    }

    /** @var User $supportingOfficer */
    $supportingOfficer = User::with('grade:id,name,level')->find($validatedData['supporting_officer_id']);
    if (!$supportingOfficer) {
      throw new ModelNotFoundException(__('Pegawai Penyokong yang dipilih tidak sah.'));
    }

    $minSupportGradeLevel = (int) config('motac.approval.min_loan_support_grade_level', 41); //
    if (!$supportingOfficer->grade || (int) $supportingOfficer->grade->level < $minSupportGradeLevel) {
      Log::warning(self::LOG_AREA . "Supporting Officer Grade Check Failed.", ['officer_id' => $supportingOfficer->id, 'officer_grade_level' => $supportingOfficer->grade?->level, 'required_level' => $minSupportGradeLevel, 'application_user_id' => $applicantId]);
      throw new InvalidArgumentException(__("Pegawai Penyokong yang dipilih (:name) tidak memenuhi syarat minima gred (Gred :minGrade atau setara). Gred semasa: :currentGrade", [
        'name' => $supportingOfficer->name,
        'minGrade' => $minSupportGradeLevel,
        'currentGrade' => $supportingOfficer->grade?->name ?? __('Tidak Dinyatakan')
      ]));
    }

    return DB::transaction(function () use ($validatedData, $applicant, $supportingOfficer, $applicantId) {
      $applicationData = Arr::except($validatedData, ['items', 'applicant_confirmation']);
      $applicationData['user_id'] = $applicantId;
      $applicationData['responsible_officer_id'] = $validatedData['responsible_officer_id'] ?? $applicantId; //
      $applicationData['status'] = LoanApplication::STATUS_DRAFT; // Initially draft
      $applicationData['applicant_confirmation_timestamp'] = now(); // Set confirmation now
      // applicant_phone is now part of LoanApplication model as per new form, no need for separate user update.

      /** @var LoanApplication $application */
      $application = LoanApplication::create($applicationData);

      foreach ($validatedData['items'] as $item) { //
        $application->applicationItems()->create([
          'equipment_type' => $item['equipment_type'],
          'quantity_requested' => (int) $item['quantity_requested'],
          'notes' => $item['notes'] ?? null,
        ]);
      }

      $application->transitionToStatus(LoanApplication::STATUS_PENDING_SUPPORT, __('Permohonan dihantar untuk semakan pegawai penyokong.'), $applicantId); //
      $application->submitted_at = now(); // Explicitly set submitted_at
      $application->save();

      $this->approvalService->initiateApprovalWorkflow($application, $applicant, Approval::STAGE_LOAN_SUPPORT_REVIEW, $supportingOfficer); //

      $this->notificationService->notifyApplicantApplicationSubmitted($application); //
      $this->notificationService->notifyApproverApplicationNeedsAction($application->approvals()->latest()->first(), $application, $supportingOfficer); // Notify approver

      Log::info(self::LOG_AREA . 'Loan application created and submitted successfully.', ['application_id' => $application->id]);
      return $application->fresh($this->defaultLoanApplicationRelations);
    });
  }

  public function submitApplicationForApproval(LoanApplication $application, User $submitter): LoanApplication
  {
    if (!in_array($application->status, [LoanApplication::STATUS_DRAFT, LoanApplication::STATUS_REJECTED])) { //
      throw new RuntimeException(__('Hanya draf permohonan atau permohonan yang ditolak boleh dihantar semula. Status semasa: :status', ['status' => $application->statusTranslated]));
    }
    if (empty($application->supporting_officer_id)) {
      throw new RuntimeException(__('Pegawai Penyokong mesti ditetapkan sebelum permohonan boleh dihantar. Sila kemaskini draf.'));
    }
    if (empty($application->applicant_confirmation_timestamp)) { //
      throw new RuntimeException(__('Perakuan pemohon mesti diterima sebelum penghantaran. Sila kemaskini draf dan sahkan perakuan.'));
    }

    /** @var User $supportingOfficer */
    $supportingOfficer = User::with('grade:id,name,level')->findOrFail($application->supporting_officer_id);
    $minSupportGradeLevel = (int) config('motac.approval.min_loan_support_grade_level', 41); //
    if (!$supportingOfficer->grade || (int) $supportingOfficer->grade->level < $minSupportGradeLevel) {
      throw new InvalidArgumentException(__("Pegawai Penyokong yang ditetapkan (:name) tidak memenuhi syarat minima gred (Gred :minGrade atau setara). Gred semasa: :currentGrade", [
        'name' => $supportingOfficer->name,
        'minGrade' => $minSupportGradeLevel,
        'currentGrade' => $supportingOfficer->grade?->name ?? __('Tidak Dinyatakan')
      ]));
    }

    Log::info(self::LOG_AREA . "Resubmitting LoanApplication.", ['application_id' => $application->id, 'user_id' => $submitter->id]);
    return DB::transaction(function () use ($application, $submitter, $supportingOfficer) {
      $application->transitionToStatus(LoanApplication::STATUS_PENDING_SUPPORT, __('Permohonan dihantar semula untuk semakan pegawai penyokong.'), $submitter->id); //
      $application->submitted_at = now(); // Update submitted_at on resubmission
      $application->rejection_reason = null; // Clear previous rejection reason
      $application->rejected_by = null;
      $application->rejected_at = null;
      $application->save();

      $existingPendingSupportApproval = $application->approvals()
        ->where('stage', Approval::STAGE_LOAN_SUPPORT_REVIEW)
        ->where('status', Approval::STATUS_PENDING)
        ->where('officer_id', $supportingOfficer->id)
        ->first();

      $approvalTask = $existingPendingSupportApproval;
      if (!$existingPendingSupportApproval) {
        $approvalTask = $this->approvalService->initiateApprovalWorkflow($application, $submitter, Approval::STAGE_LOAN_SUPPORT_REVIEW, $supportingOfficer);
      } else {
        Log::info(self::LOG_AREA . "Existing pending approval task found for resubmission.", ['approval_id' => $existingPendingSupportApproval->id]);
      }

      $this->notificationService->notifyApplicantApplicationSubmitted($application); //
      if ($approvalTask) {
        $this->notificationService->notifyApproverApplicationNeedsAction($approvalTask, $application, $supportingOfficer); // Notify approver
      }


      Log::info(self::LOG_AREA . "LoanApplication resubmitted successfully.", ['application_id' => $application->id, 'status' => $application->status]);
      return $application->fresh($this->defaultLoanApplicationRelations);
    });
  }

  /**
   * @param array{responsible_officer_id?: int|null, supporting_officer_id?: int|null, applicant_phone?:string|null, purpose?: string, location?: string, return_location?: string|null, loan_start_date?: string, loan_end_date?: string, items?: list<array{id?: int|null, equipment_type: string, quantity_requested: int, notes?: string|null, _delete?: bool}>, applicant_confirmation?:bool} $validatedData
   */
  public function updateApplication(LoanApplication $application, array $validatedData, User $user): LoanApplication
  {
    Log::info(self::LOG_AREA . "Updating loan application.", ['application_id' => $application->id, 'user_id' => $user->id, 'data_keys' => array_keys($validatedData)]);

    if (!$application->is_draft) { // Use the accessor
      throw new RuntimeException(__('Hanya draf permohonan yang boleh dikemaskini. Status semasa: :status', ['status' => $application->statusTranslated]));
    }

    if (isset($validatedData['supporting_officer_id']) && (int)$validatedData['supporting_officer_id'] !== (int)$application->supporting_officer_id) {
      /** @var User|null $newSupportingOfficer */
      $newSupportingOfficer = User::with('grade:id,name,level')->find($validatedData['supporting_officer_id']);
      if (!$newSupportingOfficer) {
        throw new ModelNotFoundException(__('Pegawai Penyokong yang dipilih untuk kemaskini tidak sah.'));
      }
      $minSupportGradeLevel = (int) config('motac.approval.min_loan_support_grade_level', 41); //
      if (!$newSupportingOfficer->grade || (int) $newSupportingOfficer->grade->level < $minSupportGradeLevel) {
        throw new InvalidArgumentException(__("Pegawai Penyokong baharu yang dipilih (:name) tidak memenuhi syarat minima gred (Gred :minGrade atau setara). Gred semasa: :currentGrade", [
          'name' => $newSupportingOfficer->name,
          'minGrade' => $minSupportGradeLevel,
          'currentGrade' => $newSupportingOfficer->grade?->name ?? __('Tidak Dinyatakan')
        ]));
      }
    }

    return DB::transaction(function () use ($application, $validatedData, $user) {
      $applicationDataToFill = Arr::except($validatedData, ['items', 'applicant_confirmation']);
      $application->fill($applicationDataToFill);

      if (array_key_exists('applicant_confirmation', $validatedData)) {
        if ($validatedData['applicant_confirmation'] === true && !$application->applicant_confirmation_timestamp) {
          $application->applicant_confirmation_timestamp = now(); //
        } elseif ($validatedData['applicant_confirmation'] === false) { // Allow unchecking
          $application->applicant_confirmation_timestamp = null;
        }
      }
      $application->save();

      if (isset($validatedData['items']) && is_array($validatedData['items'])) {
        $this->syncApplicationItems($application, $validatedData['items']); //
      }
      Log::info(self::LOG_AREA . "Loan application updated successfully.", ['application_id' => $application->id]);
      return $application->fresh($this->defaultLoanApplicationRelations);
    });
  }

  public function deleteApplication(LoanApplication $application, User $user): bool
  {
    Log::info(self::LOG_AREA . "Attempting to delete loan application.", ['application_id' => $application->id, 'user_id' => $user->id]);
    if (!$application->is_draft) { // Use accessor
      Log::warning(self::LOG_AREA . "Attempt to delete non-draft application denied.", ['application_id' => $application->id, 'status' => $application->status]);
      throw new RuntimeException(__('Hanya draf permohonan yang boleh dibuang.'));
    }

    return DB::transaction(function () use ($application) {
      $application->applicationItems()->delete();
      $application->approvals()->delete();
      $deleted = $application->delete();

      if ($deleted) {
        Log::info(self::LOG_AREA . "Loan application and related data soft deleted.", ['application_id' => $application->id]);
      } else {
        Log::warning(self::LOG_AREA . "Soft delete returned false for loan application.", ['application_id' => $application->id]);
        throw new RuntimeException(__('Gagal memadam permohonan.'));
      }
      return (bool) $deleted;
    });
  }

  /**
   * @param array<array{loan_application_item_id: int, equipment_id: int, quantity_issued: int, issue_item_notes?: string|null, accessories_checklist_item?: array|null}> $itemsDetails
   * @param array{receiving_officer_id: int, transaction_date?: string, issue_notes?: string|null} $transactionDetails
   */
  public function createIssueTransaction(LoanApplication $loanApplication, array $itemsDetails, User $issuingOfficer, array $transactionDetails): LoanTransaction
  {
    $appIdLog = $loanApplication->id;
    Log::info(self::LOG_AREA . "Creating issue transaction.", ['application_id' => $appIdLog, 'issuing_officer_id' => $issuingOfficer->id]);

    if (!in_array($loanApplication->status, [LoanApplication::STATUS_APPROVED, LoanApplication::STATUS_PARTIALLY_ISSUED])) { //
      throw new RuntimeException(__("Peralatan hanya boleh dikeluarkan untuk permohonan yang telah diluluskan atau separa dikeluarkan. Status semasa: :status", ['status' => $loanApplication->statusTranslated]));
    }
    if (empty($itemsDetails)) {
      throw new InvalidArgumentException(__('Tiada item peralatan untuk dikeluarkan dalam transaksi ini.'));
    }
    if (empty($transactionDetails['receiving_officer_id'])) {
      throw new InvalidArgumentException(__('Pegawai Penerima mesti dinyatakan.'));
    }

    $serviceItemData = [];
    foreach ($itemsDetails as $item) {
      if (empty($item['equipment_id']) || empty($item['loan_application_item_id']) || !isset($item['quantity_issued']) || (int)$item['quantity_issued'] <= 0) {
        throw new InvalidArgumentException(__('Butiran item pengeluaran tidak lengkap atau kuantiti tidak sah.'));
      }
      $serviceItemData[] = [
        'equipment_id' => (int) $item['equipment_id'],
        'loan_application_item_id' => (int) $item['loan_application_item_id'],
        'quantity' => (int) $item['quantity_issued'],
        'notes' => $item['issue_item_notes'] ?? null,
        'accessories_data' => $item['accessories_checklist_item'] ?? config('motac.loan_accessories_list_default_empty_json', []), //
      ];
    }

    $extraServiceDetails = [
      'receiving_officer_id' => (int) $transactionDetails['receiving_officer_id'],
      'transaction_date' => $transactionDetails['transaction_date'] ?? now()->toDateTimeString(),
      'issue_notes' => $transactionDetails['issue_notes'] ?? null,
      'status' => LoanTransaction::STATUS_ISSUED, //
    ];

    $transaction = $this->loanTransactionService->createTransaction( //
      $loanApplication,
      LoanTransaction::TYPE_ISSUE, //
      $issuingOfficer,
      $serviceItemData,
      $extraServiceDetails
    );
    // Notification after successful transaction
    $transaction = $this->loanTransactionService->createTransaction(
      $loanApplication,
      LoanTransaction::TYPE_ISSUE,
      $issuingOfficer, // This is User $issuingOfficer
      $serviceItemData,
      $extraServiceDetails
    );
    // Notification after successful transaction
    // Pass the issuingOfficer as the third argument (or whatever your NotificationService expects)
    $this->notificationService->notifyApplicantEquipmentIssued($loanApplication, $transaction, $issuingOfficer);
    return $transaction;
  }

  /**
   * @param array<array{loan_transaction_item_id: int, equipment_id: int, quantity_returned: int, condition_on_return: string, item_status_on_return: string, return_item_notes?: string|null, accessories_checklist_item?: array|null}> $itemsDetails
   * @param array{returning_officer_id: int, transaction_date?: string, return_notes?: string|null} $transactionDetails
   */
  public function createReturnTransaction(LoanTransaction $issueTransaction, array $itemsDetails, User $returnAcceptingOfficer, array $transactionDetails): LoanTransaction
  {
    $loanApplication = $issueTransaction->loanApplication()->firstOrFail();
    Log::info(self::LOG_AREA . "Creating return transaction.", ['loan_application_id' => $loanApplication->id, 'issue_transaction_id' => $issueTransaction->id, 'accepting_officer_id' => $returnAcceptingOfficer->id]);

    if (empty($itemsDetails)) {
      throw new InvalidArgumentException(__('Tiada item peralatan untuk dipulangkan dalam transaksi ini.'));
    }
    if (empty($transactionDetails['returning_officer_id'])) {
      throw new InvalidArgumentException(__('Pegawai Yang Memulangkan mesti dinyatakan.'));
    }

    $serviceItemData = [];
    foreach ($itemsDetails as $item) {
      if (empty($item['equipment_id']) || empty($item['loan_transaction_item_id']) || !isset($item['quantity_returned']) || (int)$item['quantity_returned'] <= 0 || empty($item['condition_on_return']) || empty($item['item_status_on_return'])) {
        throw new InvalidArgumentException(__('Butiran item pemulangan tidak lengkap, kuantiti tidak sah, atau status/keadaan tidak dinyatakan.'));
      }
      /** @var \App\Models\LoanTransactionItem $originalIssuedItem */
      $originalIssuedItem = AppLoanTransactionItemModel::findOrFail($item['loan_transaction_item_id']);
      if ($originalIssuedItem->loan_transaction_id !== $issueTransaction->id || (int)$originalIssuedItem->equipment_id !== (int)$item['equipment_id']) {
        throw new InvalidArgumentException(__("Item rujukan pengeluaran (ID: :itemRefId) tidak sepadan atau tidak sah untuk item peralatan (ID: :eqId).", ['itemRefId' => $item['loan_transaction_item_id'], 'eqId' => $item['equipment_id']]));
      }

      $serviceItemData[] = [
        'equipment_id' => (int) $item['equipment_id'],
        'original_loan_transaction_item_id' => (int) $item['loan_transaction_item_id'],
        'loan_application_item_id' => $originalIssuedItem->loan_application_item_id,
        'quantity' => (int) $item['quantity_returned'],
        'condition_on_return' => $item['condition_on_return'], //
        'item_status_on_return' => $item['item_status_on_return'], //
        'notes' => $item['return_item_notes'] ?? null,
        'accessories_data' => $item['accessories_checklist_item'] ?? config('motac.loan_accessories_list_default_empty_json', []), //
      ];
    }

    $extraServiceDetails = [
      'returning_officer_id' => (int) $transactionDetails['returning_officer_id'],
      'transaction_date' => $transactionDetails['transaction_date'] ?? now()->toDateTimeString(),
      'return_notes' => $transactionDetails['return_notes'] ?? null,
      'related_transaction_id' => $issueTransaction->id, //
      'status' => LoanTransaction::STATUS_RETURNED_PENDING_INSPECTION, //
    ];

    $transaction =  $this->loanTransactionService->createTransaction( //
      $loanApplication,
      LoanTransaction::TYPE_RETURN, //
      $returnAcceptingOfficer,
      $serviceItemData,
      $extraServiceDetails
    );
    // Notification after successful transaction
    $transaction =  $this->loanTransactionService->createTransaction(
      $loanApplication,
      LoanTransaction::TYPE_RETURN,
      $returnAcceptingOfficer, // This is User $returnAcceptingOfficer
      $serviceItemData,
      $extraServiceDetails
    );
    // Notification after successful transaction
    // Pass the returnAcceptingOfficer as the third argument
    $this->notificationService->notifyApplicantEquipmentReturned($loanApplication, $transaction, $returnAcceptingOfficer);
    return $transaction;
  }

  public function getActiveLoansSummary(array $filters = []): LengthAwarePaginator
  {
    Log::debug(self::LOG_AREA . 'Fetching summary of active loan applications.', ['filters' => $filters]);
    $query = LoanApplication::query()
      ->whereIn('status', [
        LoanApplication::STATUS_ISSUED, //
        LoanApplication::STATUS_PARTIALLY_ISSUED, //
        LoanApplication::STATUS_OVERDUE //
      ])
      ->with($this->defaultLoanApplicationRelations);

    if (!empty($filters['search_term'])) {
      $term = '%' . $filters['search_term'] . '%';
      $query->where(function ($q) use ($term) {
        $q->where('id', 'like', $term)
          ->orWhere('purpose', 'like', $term)
          ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', $term))
          ->orWhereHas('responsibleOfficer', fn($roq) => $roq->where('name', 'like', $term));
      });
    }
    $orderBy = $filters['order_by'] ?? 'loan_end_date';
    $orderDirection = $filters['order_direction'] ?? 'asc';

    $perPage = isset($filters['per_page']) && is_numeric($filters['per_page']) ? (int) $filters['per_page'] : 15;
    return $query->orderBy($orderBy, $orderDirection)->paginate($perPage);
  }

  public function findLoanApplicationById(int $id, array $with = []): ?LoanApplication
  {
    Log::debug(self::LOG_AREA . "Finding loan application.", ['id' => $id, 'with_relations' => $with]);
    $relationsToLoad = !empty($with) ? array_unique(array_merge($this->defaultLoanApplicationRelations, $with)) : $this->defaultLoanApplicationRelations;

    /** @var LoanApplication|null $application */
    $application = LoanApplication::with($relationsToLoad)->find($id);

    if (!$application) {
      Log::notice(self::LOG_AREA . "Loan application not found.", ['id' => $id]);
      // Consider throwing ModelNotFoundException here if it's usually expected to be found
      // throw new ModelNotFoundException(__('Permohonan pinjaman dengan ID :id tidak ditemui.', ['id' => $id]));
    }
    return $application;
  }

  /**
   * @param list<array{id?:int|null, equipment_type:string, quantity_requested:int, notes?:string|null, _delete?:bool}> $itemsData Array of item data.
   */
  protected function syncApplicationItems(LoanApplication $application, array $itemsData): void
  {
    $existingItemIds = $application->applicationItems()->pluck('id')->all();
    $processedItemIds = [];
    $itemPayloadsToCreate = [];

    foreach ($itemsData as $itemData) {
      if (empty($itemData['equipment_type']) || !isset($itemData['quantity_requested'])) {
        Log::warning(self::LOG_AREA . "Skipping item with missing type or quantity during sync.", ['application_id' => $application->id, 'item_data' => $itemData]);
        continue;
      }
      $quantity = (int) $itemData['quantity_requested'];

      $itemId = isset($itemData['id']) && is_numeric($itemData['id']) ? (int)$itemData['id'] : null;
      $itemPayload = [
        'equipment_type' => $itemData['equipment_type'],
        'quantity_requested' => $quantity,
        'notes' => $itemData['notes'] ?? null,
      ];

      if ($itemId && in_array($itemId, $existingItemIds, true)) { // Existing item
        if (!empty($itemData['_delete']) || $quantity <= 0) {
          // Item will be caught by array_diff and deleted later if quantity is 0 and it existed
          // Or if explicitly marked for deletion
          Log::info(self::LOG_AREA . "Item will be removed if not processed or quantity is zero.", ['item_id' => $itemId, 'application_id' => $application->id]);
        } else {
          LoanApplicationItem::find($itemId)?->update($itemPayload);
          $processedItemIds[] = $itemId;
        }
      } elseif (empty($itemData['_delete']) && $quantity > 0) { // New item to add
        $itemPayloadsToCreate[] = $itemPayload; // Batch create later
      }
    }

    // Create new items
    if (!empty($itemPayloadsToCreate)) {
      $createdItems = $application->applicationItems()->createMany($itemPayloadsToCreate);
      foreach ($createdItems as $createdItem) {
        $processedItemIds[] = $createdItem->id;
      }
    }

    // Delete items that were present before but not in the new $itemsData (and not processed for update/create)
    // Or items that had their quantity reduced to 0 or marked with _delete
    $idsToDelete = array_diff($existingItemIds, $processedItemIds);
    if (!empty($idsToDelete)) {
      $application->applicationItems()->whereIn('id', $idsToDelete)->delete();
      Log::info(self::LOG_AREA . "Removed items no longer in submission or marked for deletion.", ['deleted_ids' => $idsToDelete, 'application_id' => $application->id]);
    }
  }
}
