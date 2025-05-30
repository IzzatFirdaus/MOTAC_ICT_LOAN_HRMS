{{-- contact-us.blade.php --}}
<div>
    {{-- @section('title', __('Hubungi Kami')) --}}

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white"> {{-- Assuming Bootstrap bg-primary --}}
                        <h4 class="mb-0 text-white"><i class="ti ti-message-circle-2-filled me-2"></i>{{ __('Hubungi Bahagian Pengurusan Maklumat (BPM), MOTAC') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="lead">{{ __('Untuk sebarang pertanyaan, bantuan teknikal, atau maklum balas berkaitan Sistem Pengurusan Sumber Bersepadu MOTAC (Emel/ID Pengguna & Pinjaman Peralatan ICT), sila hubungi kami melalui maklumat di bawah:') }}</p>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h5><i class="ti ti-map-pin-filled me-2 text-primary"></i>{{ __('Alamat') }}</h5>
                                <address class="mb-0">
                                    Bahagian Pengurusan Maklumat (BPM)<br>
                                    Kementerian Pelancongan, Seni dan Budaya Malaysia (MOTAC)<br>
                                    {{-- Update with actual address details as per MOTAC --}}
                                    Aras 5, Blok D4, Kompleks D,<br>
                                    Pusat Pentadbiran Kerajaan Persekutuan,<br>
                                    62505 Putrajaya,<br>
                                    Wilayah Persekutuan Putrajaya,<br>
                                    Malaysia.
                                </address>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h5><i class="ti ti-phone-call me-2 text-primary"></i>{{ __('Telefon') }}</h5>
                                <p class="mb-2">Unit Operasi Rangkaian dan Khidmat Pengguna (Helpdesk BPM):<br>
                                   {{-- Replace with actual number --}}
                                   +603-8000 8000 (samb. xxxx) </p>

                                <h5><i class="ti ti-mail-filled me-2 text-primary"></i>{{ __('E-mel') }}</h5>
                                <p class="mb-0"><a href="mailto:bpm.helpdesk@motac.gov.my">bpm.helpdesk@motac.gov.my</a> </p>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3"><i class="ti ti-alarm-filled me-2 text-primary"></i>{{ __('Waktu Operasi Meja Bantuan') }}</h5>
                        <p class="mb-1"><strong>{{ __('Isnin - Jumaat:') }}</strong> 8:30 PG - 5:00 PTG</p>
                        <p class="mb-0"><strong>{{ __('Tutup:') }}</strong> {{ __('Sabtu, Ahad & Cuti Umum Malaysia') }}</p>

                        {{-- Optional: Simple Contact Form - Requires Livewire component logic --}}
                        {{-- If implementing, ensure the Livewire component `sendMessage` method exists.
                        <hr class="my-4">
                        <h5 class="mb-3"><i class="ti ti-message-plus me-2 text-primary"></i>{{ __('Hantar Mesej Pantas') }}</h5>
                        <form wire:submit.prevent="sendMessage">
                            <div class="mb-3">
                                <label for="contactName" class="form-label">{{ __('Nama Anda') }}</label>
                                <input type="text" wire:model.defer="contactName" class="form-control @error('contactName') is-invalid @enderror" id="contactName" required>
                                @error('contactName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="contactEmail" class="form-label">{{ __('Emel Anda') }}</label>
                                <input type="email" wire:model.defer="contactEmail" class="form-control @error('contactEmail') is-invalid @enderror" id="contactEmail" required>
                                @error('contactEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="contactMessage" class="form-label">{{ __('Mesej Anda') }}</label>
                                <textarea wire:model.defer="contactMessage" class="form-control @error('contactMessage') is-invalid @enderror" id="contactMessage" rows="4" required></textarea>
                                @error('contactMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading wire:target="sendMessage" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                {{ __('Hantar Mesej') }}
                            </button>
                            @if(session()->has('contact_form_message'))
                                <div class="alert alert-success mt-3 py-2">{{ session('contact_form_message') }}</div>
                            @endif
                        </form>
                        --}}
                    </div>
                    <div class="card-footer text-muted small">
                       {{ __('Sila pastikan anda memberikan maklumat yang lengkap untuk memudahkan pihak kami memberi bantuan.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
