{{-- resources/views/partials/report-filters.blade.php --}}
@props(['reportRoute', 'statuses' => [], 'users' => [], 'departments' => []])

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <h4 class="h5 fw-semibold mb-3">{{ __('Tapis Laporan') }}</h4>

        <form method="GET" action="{{ route($reportRoute) }}">
            <div class="row g-3">
                {{-- Date Range Filter --}}
                <div class="col-md-6 col-lg-4">
                    <label for="start_date" class="form-label small fw-medium">{{ __('Tarikh Mula:') }}</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm"
                           value="{{ request('start_date') }}">
                </div>

                <div class="col-md-6 col-lg-4">
                    <label for="end_date" class="form-label small fw-medium">{{ __('Tarikh Tamat:') }}</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm"
                           value="{{ request('end_date') }}">
                </div>

                {{-- Status Filter --}}
                @if (!empty($statuses))
                    <div class="col-md-6 col-lg-4">
                        <label for="status" class="form-label small fw-medium">{{ __('Status:') }}</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            <option value="">- {{ __('Semua Status') }} -</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" {{ request('status') === (string)$value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- User Filter --}}
                @if (!empty($users))
                    <div class="col-md-6 col-lg-4">
                        <label for="user_id" class="form-label small fw-medium">{{ __('Pengguna:') }}</label>
                        <select name="user_id" id="user_id" class="form-select form-select-sm">
                            <option value="">- {{ __('Semua Pengguna') }} -</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}"
                                        {{ (int) request('user_id') === $user->id ? 'selected' : '' }}>
                                    {{ $user->name ?? ($user->full_name ?? 'N/A') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Department Filter --}}
                @if (!empty($departments))
                    <div class="col-md-6 col-lg-4">
                        <label for="department_id" class="form-label small fw-medium">{{ __('Bahagian/Unit:') }}</label>
                        <select name="department_id" id="department_id" class="form-select form-select-sm">
                            <option value="">- {{ __('Semua Bahagian/Unit') }} -</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}"
                                        {{ (int) request('department_id') === $department->id ? 'selected' : '' }}>
                                    {{ $department->name ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Filter and Reset Buttons --}}
                <div class="col-12 mt-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                        <i class="bi bi-funnel-fill me-1"></i>
                        {{ __('Tapis') }}
                    </button>
                    <a href="{{ route($reportRoute) }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        {{ __('Set Semula') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
