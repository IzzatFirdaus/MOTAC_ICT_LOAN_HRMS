<?php

namespace App\Notifications;

use App\Models\LoanApplication;
use App\Models\User; // Added for type hinting
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Class EquipmentReturnReminderNotification
 *
 * Notification sent to the applicant (and potentially responsible officer) to remind them
 * about an upcoming, present, or overdue equipment return date for their loan application.
 * This notification emphasizes the return date and location.
 * This notification can be sent via email and optionally stored in the database.
 * Consolidates previous EquipmentReturnReminderNotification and EquipmentOverdueNotification.
 */
class EquipmentReturnReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private LoanApplication $loanApplication;
    private int $daysUntilReturn; // Positive for upcoming, 0 for today, negative for overdue

    public function __construct(LoanApplication $loanApplication, int $daysUntilReturn)
    {
        $this->loanApplication = $loanApplication->loadMissing(['user', 'responsibleOfficer']);
        $this->daysUntilReturn = $daysUntilReturn;
    }

    public function getLoanApplication(): LoanApplication
    {
        return $this->loanApplication;
    }

    public function getDaysUntilReturn(): int
    {
        return $this->daysUntilReturn;
    }

    public function via(User $notifiable): array // Type hinted $notifiable
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $loanApplication = $this->getLoanApplication();
        $daysUntilReturn = $this->getDaysUntilReturn();

        $applicantName = $loanApplication->user?->name ?? $notifiable->name ?? __('Pemohon');
        $applicationId = $loanApplication->id ?? 'N/A';
        // $responsibleOfficerName = $loanApplication->responsibleOfficer?->name ?? __('Pegawai Bertanggungjawab');
        $expectedReturnDate = $this->formatDate($loanApplication->loan_end_date);
        $returnLocation = $loanApplication->return_location ?? __('Unit ICT, Bahagian Pengurusan Maklumat');


        $subject = '';
        $greeting = __('Salam Sejahtera, :name,', ['name' => $applicantName]);
        $level = 'line'; // Default level

        $mailMessage = (new MailMessage());

        if ($daysUntilReturn > 0) {
            $subject = __("Peringatan: Pulangan Peralatan Dalam :days Hari Lagi - Permohonan #:id", ['days' => $daysUntilReturn, 'id' => $applicationId]);
            $mailMessage->line(__("Ini adalah peringatan mesra bahawa peralatan yang dipinjam di bawah Permohonan Pinjaman Peralatan ICT **#:id** perlu dipulangkan dalam masa **:days hari lagi**.", ['id' => $applicationId, 'days' => $daysUntilReturn]));
        } elseif ($daysUntilReturn === 0) {
            $subject = __("Peringatan: Pulangan Peralatan Hari Ini - Permohonan #:id", ['id' => $applicationId]);
            $mailMessage->line(__("Ini adalah peringatan bahawa peralatan yang dipinjam di bawah Permohonan Pinjaman Peralatan ICT **#:id** perlu dipulangkan **HARI INI**.", ['id' => $applicationId]));
        } else { // Overdue
            $daysOverdue = abs($daysUntilReturn);
            $subject = __("PERHATIAN: Peralatan Pinjaman LEWAT Dipulangkan (:days Hari) - Permohonan #:id", ['days' => $daysOverdue, 'id' => $applicationId]);
            $mailMessage->error(); // Mark the email as important/error level for overdue
            $mailMessage->line(__("Peralatan yang dipinjam di bawah Permohonan Pinjaman Peralatan ICT **#:id** telah **LEWAT DIPULANGKAN** selama **:days hari**.", ['id' => $applicationId, 'days' => $daysOverdue]));
            $level = 'error';
        }

        $mailMessage->subject($subject)
            ->greeting($greeting)
            ->line(__("Tarikh pemulangan yang dijangka adalah pada **:date**.", ['date' => $expectedReturnDate]));

        if ($level === 'error') {
            $mailMessage->line(__("Sila pulangkan peralatan tersebut dengan kadar **SEGERA** di **:loc**.", ['loc' => $returnLocation]));
        } else {
            $mailMessage->line(__("Sila pastikan peralatan dipulangkan di **:loc** pada atau sebelum tarikh tersebut.", ['loc' => $returnLocation]));
        }

        $mailMessage->line(''); // Spacing


        $applicationUrl = '#';
        // Standardized route: Use a consistent route name for showing loan applications.
        // Assuming 'resource-management.my-applications.loan.show' or similar from other notifications.
        $routeName = 'resource-management.my-applications.loan.show';
        if ($loanApplication->id && Route::has($routeName)) {
            try {
                $applicationUrl = route($routeName, ['loan_application' => $loanApplication->id]);
            } catch (\Exception $e) {
                Log::error('Error generating URL for EquipmentReturnReminderNotification (toMail): '.$e->getMessage(), [
                    'exception' => $e,
                    'application_id' => $loanApplication->id ?? null,
                    'route_name' => $routeName
                ]);
                $applicationUrl = '#'; // Fallback
            }
        }

        if ($applicationUrl !== '#') {
            $mailMessage->action(__('Lihat Permohonan'), $applicationUrl);
        }
        $mailMessage->line(''); // Spacing

        if ($level === 'error') {
            $mailMessage->line(__('Kegagalan memulangkan peralatan dalam tempoh yang ditetapkan boleh menyebabkan tindakan selanjutnya diambil. Kerjasama anda amat dihargai.'));
        } else {
            $mailMessage->line(__('Kerjasama anda dalam memulangkan peralatan mengikut jadual amat dihargai.'));
        }


        return $mailMessage->salutation(__('Sekian, harap maklum.'));
    }

    public function toArray(User $notifiable): array
    {
        $loanApplication = $this->getLoanApplication();
        $daysUntilReturn = $this->getDaysUntilReturn();

        $applicationId = $loanApplication->id ?? null;
        $expectedReturnDate = $this->formatDate($loanApplication->loan_end_date);
        $returnLocation = $loanApplication->return_location ?? __('Unit ICT, Bahagian Pengurusan Maklumat');

        $status = 'return_reminder';
        $subject = __("Peringatan Pulangan Peralatan");
        $message = "";
        $icon = 'ti ti-calendar-event';

        if ($daysUntilReturn > 0) {
            $subject = __("Peringatan: Peralatan Perlu Dipulangkan Dalam :days Hari", ['days' => $daysUntilReturn]);
            $message = __("Peralatan untuk Permohonan #:id perlu dipulangkan dalam masa :days hari lagi (:date).", ['id' => $applicationId ?? 'N/A', 'days' => $daysUntilReturn, 'date' => $expectedReturnDate]);
        } elseif ($daysUntilReturn === 0) {
            $subject = __("Peringatan: Peralatan Perlu Dipulangkan Hari Ini");
            $message = __("Peralatan untuk Permohonan #:id perlu dipulangkan hari ini (:date).", ['id' => $applicationId ?? 'N/A', 'date' => $expectedReturnDate]);
        } else {
            $daysOverdue = abs($daysUntilReturn);
            $status = 'overdue';
            $subject = __("PERHATIAN: Peralatan Lewat Dipulangkan (:days Hari)", ['days' => $daysOverdue]);
            $message = __("Peralatan untuk Permohonan #:id telah lewat dipulangkan :days hari. Tarikh pulang jangkaan: :date.", ['id' => $applicationId ?? 'N/A', 'days' => $daysOverdue, 'date' => $expectedReturnDate]);
            $icon = 'ti ti-alarm-snooze';
        }
        if ($applicationId) {
            $subject .= __(" (#:id)", ['id' => $applicationId]);
        }


        $applicationUrl = '#';
        $routeName = 'resource-management.my-applications.loan.show';
        if ($applicationId && Route::has($routeName)) {
            try {
                $applicationUrl = route($routeName, ['loan_application' => $applicationId]);
            } catch (\Exception $e) {
                Log::error('Error generating URL for EquipmentReturnReminderNotification (toArray): '.$e->getMessage(), [
                    'exception' => $e,
                    'application_id' => $applicationId,
                    'route_name' => $routeName
                ]);
                $applicationUrl = '#'; // Fallback
            }
        }


        return [
            'loan_application_id' => $applicationId,
            'applicant_id' => $loanApplication->user_id ?? null,
            'responsible_officer_id' => $loanApplication->responsible_officer_id ?? null,
            'status' => $status,
            'days_value' => $daysUntilReturn, // Use a consistent key, value can be pos/neg/zero
            'subject' => $subject,
            'message' => $message,
            'url' => ($applicationUrl !== '#') ? $applicationUrl : null,
            'expected_return_date' => $expectedReturnDate,
            'return_location' => $returnLocation,
            'icon' => $icon,
        ];
    }

    private function formatDate($date): string
    {
        if ($date instanceof Carbon) {
            return $date->format(config('app.date_format_my', 'd/m/Y'));
        }
        if (is_string($date)) {
            try {
                return Carbon::parse($date)->format(config('app.date_format_my', 'd/m/Y'));
            } catch (\Exception $e) {
                // Log::error("Error parsing date string for formatting: {$date} - {$e->getMessage()}");
                return __('Tidak dinyatakan');
            }
        }
        return __('Tidak dinyatakan');
    }
}
