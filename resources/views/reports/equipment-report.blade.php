<div>
    {{-- @section('title', __('Laporan Peralatan ICT')) --}}
     <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4">
        <h1 class="h2 fw-semibold text-dark mb-2 mb-sm-0">{{ __('Laporan Peralatan ICT') }}</h1>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-3">
            <h5 class="card-title mb-3"><i class="ti ti-filter me-1"></i>{{ __('Saringan Laporan') }}</h5>
            <form wire:submit.prevent="applyFilters">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label for="repEqSearch" class="form-label">{{ __('Carian (Tag, Siri, Model, Jenama)') }}</label>
                        <input type="text" wire:model.defer="searchTerm" id="repEqSearch" class="form-control form-control-sm" placeholder="{{__('Masukkan kata kunci...')}}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="repEqAssetType" class="form-label">{{ __('Jenis Aset') }}</label>
                        <select wire:model.defer="filterAssetType" id="repEqAssetType" class="form-select form-select-sm">
                            <option value="">{{ __('Semua Jenis') }}</option>
                            @foreach($assetTypeOptions as $key => $label) {{-- From Equipment model constants --}}
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="repEqStatus" class="form-label">{{ __('Status Operasi') }}</label>
                        <select wire:model.defer="filterStatus" id="repEqStatus" class="form-select form-select-sm">
                            <option value="">{{ __('Semua Status') }}</option>
                             @foreach($statusOptions as $key => $label) {{-- From Equipment model constants --}}
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="repEqCond" class="form-label">{{ __('Kondisi Fizikal') }}</label>
                        <select wire:model.defer="filterCondition" id="repEqCond" class="form-select form-select-sm">
                            <option value="">{{ __('Semua Kondisi') }}</option>
                            @foreach($conditionStatusOptions as $key => $label) {{-- From Equipment model constants --}}
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="repEqDept" class="form-label">{{ __('Jabatan Pemilik') }}</label>
                        <select wire:model.defer="filterDepartmentId" id="repEqDept" class="form-select form-select-sm">
                            <option value="">{{ __('Semua Jabatan') }}</option>
                            @foreach($departmentOptions as $id => $name) {{-- From Departments table --}}
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-12 mt-3 mt-lg-0">
                        <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Saring') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2" wire:click="setSortBy('tag_id')" role="button" style="cursor:pointer;">{{ __('Tag ID') }} @if($sortBy === 'tag_id') <i class="ti ti-arrows-sort"></i> @else <i class="ti ti-arrow-autofit-up text-muted"></i> @endif</th>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2">{{ __('Jenis Aset') }}</th>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2" wire:click="setSortBy('brand')" role="button" style="cursor:pointer;">{{ __('Jenama') }} @if($sortBy === 'brand') <i class="ti ti-arrows-sort"></i> @else <i class="ti ti-arrow-autofit-up text-muted"></i> @endif</th>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2" wire:click="setSortBy('model')" role="button" style="cursor:pointer;">{{ __('Model') }} @if($sortBy === 'model') <i class="ti ti-arrows-sort"></i> @else <i class="ti ti-arrow-autofit-up text-muted"></i> @endif</th>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2">{{ __('No. Siri') }}</th>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2" wire:click="setSortBy('status')" role="button" style="cursor:pointer;">{{ __('Status Operasi') }} @if($sortBy === 'status') <i class="ti ti-arrows-sort"></i> @else <i class="ti ti-arrow-autofit-up text-muted"></i> @endif</th>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2" wire:click="setSortBy('condition_status')" role="button" style="cursor:pointer;">{{ __('Kondisi') }} @if($sortBy === 'condition_status') <i class="ti ti-arrows-sort"></i> @else <i class="ti ti-arrow-autofit-up text-muted"></i> @endif</th>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2">{{ __('Jabatan Pemilik') }}</th>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2" wire:click="setSortBy('purchase_date')" role="button" style="cursor:pointer;">{{ __('Tarikh Beli') }} @if($sortBy === 'purchase_date') <i class="ti ti-arrows-sort"></i> @else <i class="ti ti-arrow-autofit-up text-muted"></i> @endif</th>
                        <th class="small text-uppercase text-muted fw-medium px-3 py-2">{{ __('Lokasi Semasa') }}</th>
                    </tr>
                </thead>
                 <tbody>
                    <tr wire:loading.class.delay="opacity-50 table-loading-row" class="transition-opacity">
                        <td colspan="10" class="p-0" style="border:none;">
                             <div wire:loading.flex class="progress" style="height: 2px; width: 100%;" role="progressbar" aria-label="Loading...">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                            </div>
                        </td>
                    </tr>
                    @forelse ($reportData as $item)
                        <tr wire:key="eq-report-{{ $item->id }}">
                            <td class="px-3 py-2 small text-dark fw-medium">{{ $item->tag_id }}</td>
                            <td class="px-3 py-2 small text-muted">{{ $assetTypeOptions[$item->asset_type] ?? __(Str::title(str_replace('_', ' ', $item->asset_type))) }}</td>
                            <td class="px-3 py-2 small text-muted">{{ $item->brand ?? '-' }}</td>
                            <td class="px-3 py-2 small text-muted">{{ $item->model ?? '-' }}</td>
                            <td class="px-3 py-2 small text-muted">{{ $item->serial_number }}</td>
                            <td class="px-3 py-2 small">
                                <span class="badge rounded-pill {{ \App\Helpers\Helpers::getStatusColorClass($item->status, 'bootstrap_badge') }}">
                                    {{ $statusOptions[$item->status] ?? __(Str::title(str_replace('_', ' ', $item->status))) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 small">
                                 <span class="badge rounded-pill {{ \App\Helpers\Helpers::getStatusColorClass($item->condition_status, 'bootstrap_badge_condition') }}"> {{-- Assuming a different color scheme for condition --}}
                                    {{ $conditionStatusOptions[$item->condition_status] ?? __(Str::title(str_replace('_', ' ', $item->condition_status))) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 small text-muted">{{ $item->department?->name ?? '-' }}</td>
                            <td class="px-3 py-2 small text-muted">{{ $item->purchase_date ? Carbon\Carbon::parse($item->purchase_date)->translatedFormat(config('app.date_format_my', 'd/m/Y')) : '-' }}</td>
                            <td class="px-3 py-2 small text-muted">{{ $item->current_location ?: ($item->department?->name ?? '-') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-3 py-5 text-center">
                                <div class="d-flex flex-column align-items-center text-muted small">
                                    <i class="ti ti-report-analytics fs-1 mb-2 text-secondary"></i>
                                    {{ __('Tiada data laporan ditemui untuk saringan ini.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reportData->hasPages())
            <div class="card-footer bg-light border-top d-flex justify-content-center py-2">
                {{ $reportData->links() }}
            </div>
        @endif
    </div>
</div>
