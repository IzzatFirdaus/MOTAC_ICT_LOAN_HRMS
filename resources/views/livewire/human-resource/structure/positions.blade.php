<div>

  @php
    // Standardized helper path
    $configData = \App\Helpers\Helpers::appClasses();
  @endphp

  @section('title', 'Positions - Structure')

  <div class="demo-inline-spacing">
    <button wire:click.prevent='showNewPositionModal' type="button" class="btn btn-primary"
      data-bs-toggle="modal" data-bs-target="#positionModal">
      <span class="ti-xs ti ti-plus me-1"></span>{{ __('Add New Position') }}
    </button>
  </div>
  <br>
  <div class="card">
    <h5 class="card-header"><i class="ti ti-id-badge-2 ti-lg text-info me-3"></i>{{ __('Positions') }}</h5> <div class="table-responsive text-nowrap">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Associated Grade') }}</th> <th>{{ __('Status') }}</th>           {{-- 'Vacancies Count' is not in the MOTAC 'positions' table design. Confirm if needed. --}}
            {{-- If needed, its source (calculation or new DB field) should be defined. --}}
            {{-- <th>{{ __('Vacancies Count') }}</th> --}}
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($positions as $position)
          <tr>
            <td>{{ $position->id }}</td>
            <td><strong>{{ $position->name }}</strong></td>
            <td>
              {{-- Display associated grade_id from MOTAC design --}}
              {{-- Assuming 'grade' relationship is defined and loaded: $position->grade->name --}}
              @if ($position->grade)
                {{ $position->grade->name }} ({{ __('Gred') }})
              @else
                {{ __('N/A') }}
              @endif
            </td>
            <td>
              {{-- Display is_active status from MOTAC design --}}
              @if ($position->is_active)
                <span class="badge bg-label-success me-1">{{ __('Active') }}</span>
              @else
                <span class="badge bg-label-danger me-1">{{ __('Inactive') }}</span>
              @endif
            </td>
            {{-- <td>{{ $position->vacancies_count }}</td> --}}
            <td>
              <div style="display: flex">
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                  <div class="dropdown-menu">
                    {{-- Ensure modal-position allows editing of description, grade_id, is_active --}}
                    <a wire:click.prevent='showEditPositionModal({{ $position }})' data-bs-toggle="modal" data-bs-target="#positionModal" class="dropdown-item" href=""><i class="ti ti-pencil me-1"></i> {{ __('Edit') }}</a>
                    <a wire:click.prevent='confirmDeletePosition({{ $position->id }})' class="dropdown-item" href=""><i class="ti ti-trash me-1"></i> {{ __('Delete') }}</a>
                  </div>
                </div>
                @if ($confirmedId === $position->id)
                  <button wire:click.prevent='deletePosition({{ $position }})' type="button" class="btn btn-sm btn-danger waves-effect waves-light">{{ __('Sure?') }}</button>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            {{-- Adjusted colspan --}}
            <td colspan="5">
              <div class="mt-2 mb-2" style="text-align: center">
                  <h3 class="mb-1 mx-2">{{ __('Oopsie-doodle!') }}</h3>
                  <p class="mb-4 mx-2">
                    {{ __('No data found, please sprinkle some data in my virtual bowl, and let the fun begin!') }}
                  </p>
                  <button class="btn btn-label-primary mb-4" wire:click.prevent='showNewPositionModal' data-bs-toggle="modal" data-bs-target="#positionModal">
                    {{ __('Add New Position') }}
                  </button>
                  <div>
                    <img src="{{ asset('assets/img/illustrations/page-misc-under-maintenance.png') }}" width="200" class="img-fluid">
                  </div>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

{{-- Modal --}}
{{-- Make sure _partials/_modals/modal-position.blade.php includes fields for:
    name (string)
    grade_id (foreignId, nullable, links to grades.id) - Potentially a select dropdown of Grades
    description (text, nullable)
    is_active (boolean, default: true) - Potentially a checkbox/toggle
--}}
@include('_partials/_modals/modal-position')
</div>
