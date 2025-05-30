{{-- resources/views/livewire/resource-management/my-applications/loan/index.blade.php --}}
<div>
    @section('title', __('Status Permohonan Pinjaman Saya'))

    {{-- Page Header --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4">
        <h1 class="h2 fw-semibold text-dark mb-2 mb-sm-0">{{ __('Senarai Permohonan Pinjaman Saya') }}</h1>
        <a href="{{ route('loan-applications.create') }}"
            class="btn btn-primary d-inline-flex align-items-center text-uppercase small fw-semibold mt-2 mt-sm-0 px-3 py-2">
            <i class="ti ti-briefcase {{ app()->getLocale() === 'ar' ? 'ms-2' : 'me-2' }}"></i>
            {{ __('Mohon Pinjaman Baru') }}
        </a>
    </div>

    {{-- Alerts --}}
    @include('_partials._alerts.alert-general')

    {{-- Filters and Search --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="loanSearchTerm" class="form-label">{{ __('Carian (ID, Tujuan, Lokasi)') }}</label>
                    <input wire:model.live.debounce.300ms="searchTerm" type="text" id="loanSearchTerm"
                        placeholder="{{ __('Masukkan kata kunci...') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <label for="loanFilterStatus" class="form-label">{{ __('Tapis mengikut Status') }}</label>
                    <select wire:model.live="filterStatus" id="loanFilterStatus" class="form-select form-select-sm">
                        {{-- Uses $statusOptions passed from the component's render method --}}
                        @foreach ($statusOptions as $key => $label)
                            <option value="{{ $key }}">{{ __($label) }}</option> {{-- Ensure labels are translatable or already translated --}}
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Loan Applications Table --}}
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="small text-uppercase text-muted fw-medium px-3 py-2">
                            {{ __('ID Permohonan') }}</th>
                        <th scope="col" class="small text-uppercase text-muted fw-medium px-3 py-2">
                            {{ __('Tujuan') }}</th>
                        <th scope="col" class="small text-uppercase text-muted fw-medium px-3 py-2">
                            {{ __('Tarikh Mula Pinjam') }}</th>
                        <th scope="col" class="small text-uppercase text-muted fw-medium px-3 py-2">
                            {{ __('Tarikh Hantar Balik') }}</th>
                        <th scope="col" class="small text-uppercase text-muted fw-medium px-3 py-2">
                            {{ __('Status') }}</th>
                        <th scope="col" class="text-end small text-uppercase text-muted fw-medium px-3 py-2">
                            {{ __('Tindakan') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr wire:loading.class.delay="opacity-50" class="transition-opacity">
                        <td colspan="6" class="p-0">
                            <div wire:loading.flex class="progress" style="height: 2px; width: 100%;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                    </tr>
                    {{-- Uses $applications passed from the component's render method --}}
                    @forelse ($applications as $application)
                        <tr wire:key="loan-app-{{ $application->id }}">
                            <td class="px-3 py-2 align-middle small text-dark fw-medium">#{{ $application->id }}</td>
                            <td class="px-3 py-2 align-middle small text-muted"
                                style="max-width: 300px; white-space: normal;">
                                {{ Str::limit($application->purpose, 70) }}
                            </td>
                            <td class="px-3 py-2 align-middle small text-muted">
                                {{ $application->loan_start_date ? Carbon\Carbon::parse($application->loan_start_date)->translatedFormat(config('app.date_format_my', 'd/m/Y')) : 'N/A' }}
                            </td>
                            <td class="px-3 py-2 align-middle small text-muted">
                                {{ $application->loan_end_date ? Carbon\Carbon::parse($application->loan_end_date)->translatedFormat(config('app.date_format_my', 'd/m/Y')) : 'N/A' }}
                            </td>
                            <td class="px-3 py-2 align-middle small">
                                {{-- Assuming Helpers class and status_translated accessor/method exist --}}
                                <span class="badge rounded-pill {{ App\Helpers\Helpers::getStatusColorClass($application->status ?? 'default') }}">
                                    {{ __($application->status_translated ?? Str::studly($application->status)) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 align-middle text-end">
                                <a href="{{ route('loan-applications.show', $application->id) }}"
                                    class="btn btn-sm btn-outline-primary border-0 p-1"
                                    title="{{ __('Lihat Detail') }}">
                                    <i class="ti ti-eye fs-6"></i>
                                </a>
                                @if ($application->status === \App\Models\LoanApplication::STATUS_DRAFT)
                                    @can('update', $application)
                                        <a href="{{ route('loan-applications.edit', $application->id) }}"
                                            class="btn btn-sm btn-outline-secondary border-0 p-1 ms-1"
                                            title="{{ __('Kemaskini Draf') }}">
                                            <i class="ti ti-pencil fs-6"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $application)
                                        <button
                                            wire:click="$dispatch('open-delete-modal', { id: {{ $application->id }}, modelClass: 'App\\Models\\LoanApplication', itemDescription: '{{ __('Permohonan Pinjaman #') . $application->id }}' })"
                                            type="button" class="btn btn-sm btn-outline-danger border-0 p-1 ms-1"
                                            title="{{ __('Padam Draf') }}">
                                            <i class="ti ti-trash fs-6"></i>
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-5 text-center">
                                <div class="d-flex flex-column align-items-center text-muted small">
                                    <i class="ti ti-briefcase-off fs-1 mb-2 text-secondary"></i>
                                    {{ __('Tiada rekod permohonan pinjaman ditemui.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($applications->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $applications->links() }}
        </div>
    @endif

    {{-- Modal placeholder for delete confirmation if you use one --}}
    {{-- <livewire:components.confirmation-modal event-to-open="open-delete-modal" ... /> --}}
</div>
