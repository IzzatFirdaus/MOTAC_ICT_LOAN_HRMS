<?php

namespace App\Livewire\ResourceManagement\Admin\BPM;

use App\Models\Equipment;
use App\Models\LoanApplication;
use App\Models\LoanApplicationItem;
use App\Models\LoanTransaction;
use App\Models\User;
use App\Services\LoanTransactionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class ProcessIssuance extends Component
{
    use AuthorizesRequests;

    public LoanApplication $loanApplication;
    public array $allAccessoriesList = [];
    public $availableEquipment = [];
    public Collection $potentialRecipients;
    public array $issueItems = [];
    public $receiving_officer_id;
    public $transaction_date;
    public string $issue_notes = '';

    protected function messages(): array
    {
        return [
            'issueItems.min' => __('Sekurang-kurangnya satu baris item mesti disediakan untuk pengeluaran.'),
            'issueItems.*.equipment_id.required' => __('Sila pilih satu peralatan spesifik (Tag ID) untuk Baris #:position.'),
            'issueItems.*.equipment_id.distinct' => __('Peralatan yang sama (Tag ID) tidak boleh dipilih lebih dari sekali.'),
            'receiving_officer_id.required' => __('Sila pilih pegawai yang menerima peralatan.'),
            'transaction_date.required' => __('Sila tetapkan tarikh pengeluaran.'),
        ];
    }

    protected function rules(): array
    {
        $loanApplicationId = $this->loanApplication->id;

        return [
            'issueItems' => ['required', 'array', 'min:1'],
            'issueItems.*.loan_application_item_id' => [
                'required', 'integer', Rule::exists('loan_application_items', 'id')->where('loan_application_id', $loanApplicationId),
            ],
            'issueItems.*.equipment_id' => [
                'required', 'distinct', Rule::exists('equipment', 'id')->where('status', Equipment::STATUS_AVAILABLE),
            ],
            'issueItems.*.accessories_checklist' => ['nullable', 'array'],
            'receiving_officer_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'issue_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function mount(int $loanApplicationId): void
    {
        // Eager load all necessary relationships for the form
        $this->loanApplication = LoanApplication::with([
            'loanApplicationItems',
            'user',
            'responsibleOfficer' // ++ ADDED: Eager load the responsible officer
        ])->findOrFail($loanApplicationId);

        $this->authorize('processIssuance', $this->loanApplication);

        $this->allAccessoriesList = config('motac.loan_accessories_list', []);
        $this->transaction_date = now()->format('Y-m-d');

        // ++ EDITED: Create a collection of potential recipients for the dropdown ++
        $this->potentialRecipients = collect([$this->loanApplication->user, $this->loanApplication->responsibleOfficer])
            ->filter() // Remove any null values (if no responsible officer)
            ->unique('id'); // Ensure no duplicates if user and officer are the same

        // Default the receiving officer to the original applicant
        $this->receiving_officer_id = $this->loanApplication->user_id;

        // Pre-populate issue items based on the balance to be issued
        foreach ($this->loanApplication->loanApplicationItems as $approvedItem) {
            $balanceToIssue = ($approvedItem->quantity_approved ?? 0) - ($approvedItem->quantity_issued ?? 0);
            if ($balanceToIssue > 0) {
                for ($i = 0; $i < $balanceToIssue; $i++) {
                    $this->issueItems[] = [
                        'loan_application_item_id' => $approvedItem->id,
                        'equipment_type' => $approvedItem->equipment_type,
                        'equipment_id' => null,
                        'accessories_checklist' => [],
                    ];
                }
            }
        }
    }

    public function submitIssue(LoanTransactionService $loanTransactionService)
    {
        $this->authorize('createIssue', [LoanTransaction::class, $this->loanApplication]);

        if (empty($this->issueItems)) {
            $this->addError('issueItems', __('Tiada baki peralatan untuk dikeluarkan bagi permohonan ini.'));
            return;
        }

        $validatedData = $this->validate();
        $issuingOfficer = Auth::user();

        try {
            // Prepare data payloads for the service
            $itemsPayload = collect($validatedData['issueItems'])->map(function ($item) {
                return [
                    'equipment_id' => $item['equipment_id'],
                    'loan_application_item_id' => $item['loan_application_item_id'],
                    'quantity_issued' => 1,
                    'accessories_checklist_item' => $item['accessories_checklist'] ?? [],
                ];
            })->toArray();

            $transactionDetails = [
                'receiving_officer_id' => $validatedData['receiving_officer_id'],
                'transaction_date' => $validatedData['transaction_date'],
                'issue_notes' => $validatedData['issue_notes'] ?? null,
            ];

            // This service method was refactored to accept a clearer set of arguments
            $loanTransactionService->processNewIssue(
                $this->loanApplication,
                $itemsPayload,
                $issuingOfficer,
                $transactionDetails
            );

            session()->flash('success', __('Rekod pengeluaran peralatan telah berjaya disimpan.'));
            return $this->redirectRoute('loan-applications.show', ['loan_application' => $this->loanApplication->id], navigate: true);

        } catch (Throwable $e) {
            Log::error('Error in ProcessIssuance@submitIssue: '.$e->getMessage(), ['exception' => $e]);
            session()->flash('error', __('Gagal merekodkan pengeluaran disebabkan ralat sistem: ').$e->getMessage());
        }
    }

    public function render()
    {
        // Load equipment needed for the form dropdowns
        $requestedTypes = collect($this->issueItems)->pluck('equipment_type')->filter()->unique()->toArray();

        $this->availableEquipment = Equipment::where('status', Equipment::STATUS_AVAILABLE)
            ->whereIn('asset_type', $requestedTypes)
            ->orderBy('brand')
            ->orderBy('model')
            ->get(['id', 'tag_id', 'asset_type', 'brand', 'model']);

        return view('livewire.resource-management.admin.bpm.process-issuance')->title(__('Proses Pengeluaran Peralatan'));
    }
}
