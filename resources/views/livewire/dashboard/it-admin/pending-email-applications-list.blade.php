{{-- resources/views/livewire/dashboard/it-admin/pending-email-applications-list.blade.php --}}
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>{{ __('Tarikh Permohonan') }}</th>
                <th>{{ __('Pemohon') }}</th>
                <th>{{ __('Jenis') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applications as $application)
                @if (Route::has('resource-management.email-applications-admin.show'))
                    <tr onclick="window.location='{{ route('resource-management.email-applications-admin.show', $application->id) }}';"
                        style="cursor: pointer;">
                        <td>{{ \App\Helpers\Helpers::formatDate($application->created_at, 'date_my') }}</td>
                        <td>{{ $application->user->name ?? __('N/A') }}</td>
                        <td>{{ $application->application_type_label }}</td>
                        <td>
                            <x-resource-status-panel :resource="$application" statusAttribute="status" type="email_application"
                                :showIcon="true" />
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        {{ __('Tiada permohonan yang memerlukan tindakan anda pada masa ini.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3 px-3">
        {{ $applications->links() }}
    </div>
</div>
