{{-- resources/views/emails/application-submitted-notification.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Tindakan Diperlukan: Permohonan Baru Dihantar') }}</title>
    <style>
        body { font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; line-height: 1.6; color: #212529; background-color: #f8f9fa; margin: 0; padding: 20px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 25px 35px; border-radius: 0.375rem; border: 1px solid #dee2e6; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        h1, h2 { color: #1A202C; margin-top: 0; margin-bottom: 0.75rem; font-size: 24px; }
        h3 { color: #1A202C; margin-top: 0; margin-bottom: 0.5rem; font-size: 18px; }
        p { margin-bottom: 1rem; }
        ul { margin-bottom: 1rem; padding-left: 20px; } /* Basic ul styling */
        li { margin-bottom: 0.25rem; }
        .footer { margin-top: 25px; font-size: 0.875em; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px; text-align: center; }
        .alert-details { margin-top: 20px; padding: 1rem; border: 1px solid transparent; border-radius: 0.375rem; margin-bottom: 1rem; }
        .alert-info { color: #055160; background-color: #cff4fc; border-color: #9eeaf9; } /* Using info for notification details */
        .alert-info p { margin-bottom: 0.5rem; }
        .button { display: inline-block; font-weight: 400; line-height: 1.5; color: #ffffff !important; text-align: center; text-decoration: none; vertical-align: middle; cursor: pointer; border: 1px solid transparent; padding: 0.375rem 0.75rem; font-size: 1rem; border-radius: 0.375rem; }
        .button-primary { background-color: #0d6efd; border-color: #0d6efd; }
    </style>
</head>
<body>
    <div class="email-container">
        <h2>{{ __('Tindakan Diperlukan') }}</h2>
        <p>{{ __('Salam Sejahtera') }} {{ $approverName ?? '' }},</p>
        <p>{{ __('Permohonan baru telah dihantar dan memerlukan tindakan kelulusan anda.') }}</p>

        <div class="alert-details alert-info">
            <h3 style="margin-top:0;">{{ __('Maklumat Permohonan') }}</h3>
            <p><strong>{{ __('Jenis Permohonan') }}:</strong>
                @if ($application instanceof \App\Models\EmailApplication)
                    {{ __('Permohonan E-mel') }}
                @elseif ($application instanceof \App\Models\LoanApplication)
                    {{ __('Permohonan Pinjaman Peralatan') }}
                @else
                    {{ __('Jenis Tidak Diketahui') }}
                @endif
            </p>
            <p><strong>{{ __('Pemohon') }}:</strong> {{ $application->user->name ?? 'N/A' }}</p>
            <p><strong>{{ __('Tarikh Hantar') }}:</strong> {{ $application->created_at?->format(config('app.datetime_format_my','d/m/Y H:i A')) ?? 'N/A' }}</p>

            @if ($application instanceof \App\Models\EmailApplication)
                <p><strong>{{ __('Tujuan') }}:</strong> {{ $application->purpose ?? ($application->application_reason_notes ?? 'N/A') }}</p>
                <p><strong>{{ __('Emel Dicadangkan') }}:</strong> {{ $application->proposed_email ?? 'N/A' }}</p>
                 @if ($application->isGroupEmailRequest())
                    <p><strong>{{ __('Jenis') }}:</strong> Permohonan Group E-mel</p>
                    <p><strong>{{ __('Alamat Group E-mel Dicadangkan') }}:</strong> {{ $application->group_email ?? 'N/A' }}</p>
                    <p><strong>{{ __('Nama Admin/EO/CC Group') }}:</strong> {{ $application->group_admin_name ?? ($application->contact_person_name ?? 'N/A') }}</p>
                    <p><strong>{{ __('E-mel Admin/EO/CC Group') }}:</strong> {{ $application->group_admin_email ?? ($application->contact_person_email ?? 'N/A') }}</p>
                @endif
            @elseif ($application instanceof \App\Models\LoanApplication)
                <p><strong>{{ __('Tarikh Pinjaman') }}:</strong> {{ $application->loan_start_date?->format(config('app.date_format_my','d/m/Y')) ?? 'N/A' }}</p>
                <p><strong>{{ __('Tarikh Pemulangan Dijangka') }}:</strong> {{ $application->loan_end_date?->format(config('app.date_format_my','d/m/Y')) ?? 'N/A' }}</p>
                @if ($application->applicationItems->isNotEmpty())
                    <p><strong>{{ __('Peralatan yang Dimohon') }}:</strong></p>
                    <ul>
                        @foreach ($application->applicationItems as $item)
                            <li>
                                {{ $item->equipment_type ?? __('Peralatan Tidak Diketahui') }}
                                ({{ __('Kuantiti') }}: {{ $item->quantity_requested ?? 1 }})
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif

            @if (isset($reviewUrl))
                <p style="text-align: center; margin-top: 20px;">
                    <a href="{{ $reviewUrl }}" class="button button-primary">{{ __('Semak Permohonan') }}</a>
                </p>
            @else
                <p style="text-align: center; margin-top: 20px;">
                    {{ __('Sila log masuk ke Sistem Pengurusan Sumber MOTAC untuk menyemak permohonan ini.') }}
                </p>
            @endif
        </div>

        <p>{{ __('Sila semak permohonan ini dan ambil tindakan yang sewajarnya.') }}</p>
        <p>{{ __('Terima kasih.') }}</p>
        <p>{{ __('Yang benar,') }}</p>
        <p>{{ config('app.name', 'Sistem Pengurusan Sumber MOTAC') }}</p>

        <div class="footer">
            <p>{{ __('Ini adalah e-mel automatik, sila jangan balas.') }}</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'MOTAC') }}. {{ __('Hak cipta terpelihara.') }}</p>
        </div>
    </div>
</body>
</html>
