@extends('template')

@section('content')
    <div class="container py-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Riwayat Penyakit</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="fas fa-plus"></i> Diagnosa Penyakit
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Foto X-Ray</th>
                                <th>Hasil Diagnosa</th>
                                <th>Confidence</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($diagnoses as $index => $diagnosis)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="text-start">{{ $diagnosis->nama }}</td>
                                    <td class="text-center">
                                        <img src="{{ $diagnosis->image_url }}" alt="X-Ray" class="img-thumbnail"
                                            style="width: 80px; height: 80px; object-fit: cover;" data-bs-toggle="modal"
                                            data-bs-target="#imageModal{{ $diagnosis->id }}" role="button">
                                    </td>
                                    <td class="text-start">
                                        <span class="badge bg-{{ $diagnosis->diagnosis_type['color'] }} fs-6">
                                            <i class="fas {{ $diagnosis->diagnosis_type['icon'] }}"></i>
                                            {{ $diagnosis->diagnosis_type['label'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $diagnosis->confidence_color }} fs-6">
                                            {{ $diagnosis->confidence }}%
                                        </span>
                                    </td>
                                    <td class="text-start">
                                        <small>{{ $diagnosis->formatted_date }}</small>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                            data-bs-target="#detailModal{{ $diagnosis->id }}">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="imageModal{{ $diagnosis->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">XLUNGS - {{ $diagnosis->nama }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <img src="{{ $diagnosis->image_url }}" alt="X-Ray"
                                                    class="img-fluid rounded">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="detailModal{{ $diagnosis->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detail Diagnosa - {{ $diagnosis->nama }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <img src="{{ $diagnosis->image_url }}" alt="X-Ray"
                                                            class="img-fluid rounded">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Hasil Analisis AI:</h6>
                                                        <div class="mb-3">
                                                            <strong>Diagnosa Utama:</strong>
                                                            <span
                                                                class="badge bg-{{ $diagnosis->diagnosis_type['color'] }} ms-2">
                                                                {{ $diagnosis->diagnosis_type['label'] }}
                                                            </span>
                                                        </div>

                                                        <div class="mb-3">
                                                            <strong>Confidence Level:</strong>
                                                            <div class="progress mt-1">
                                                                <div class="progress-bar bg-{{ $diagnosis->confidence_color }}"
                                                                    style="width: {{ $diagnosis->confidence }}%">
                                                                    {{ $diagnosis->confidence }}%
                                                                </div>
                                                            </div>
                                                        </div>

                                                        @if ($diagnosis->ai_result && isset($diagnosis->ai_result['all_predictions']))
                                                            <div class="mb-3">
                                                                <strong>Semua Prediksi:</strong>
                                                                <div class="mt-2">
                                                                    @foreach ($diagnosis->ai_result['all_predictions'] as $disease => $percentage)
                                                                        <div class="d-flex justify-content-between mb-1">
                                                                            <span>{{ $disease }}</span>
                                                                            <span>{{ $percentage }}%</span>
                                                                        </div>
                                                                        <div class="progress mb-2" style="height: 8px;">
                                                                            <div class="progress-bar"
                                                                                style="width: {{ $percentage }}%"></div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <div class="mb-3">
                                                            <strong>Tanggal Analisis:</strong>
                                                            <span>{{ $diagnosis->formatted_date }}</span>
                                                        </div>

                                                        @if ($diagnosis->explanation)
                                                            <div class="mb-3">
                                                                <strong>Penjelasan AI:</strong>
                                                                <div class="mt-2 p-3 bg-light rounded">
                                                                    {{ $diagnosis->explanation }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada data diagnosa</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Diagnosa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="diagnosisForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Nama Pasien</label>
                                <input type="text" name="nama" class="form-control"
                                    placeholder="Masukkan nama pasien" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Foto X-Ray</label>
                                <input type="file" name="xray_image" class="form-control" accept="image/*" required>
                                <small class="text-muted">Format: JPG, PNG. Maksimal 10MB</small>
                            </div>
                        </div>
                        <div id="imagePreview" class="mb-3" style="display: none;">
                            <label class="form-label">Preview:</label>
                            <img id="previewImg" src="" class="img-fluid rounded" style="max-height: 300px;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" id="loadingSpinner"></span>
                            <span id="submitText">Analisis & Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                    <h5>Menganalisis X-Ray...</h5>
                    <p class="text-muted">AI sedang memproses gambar Anda. Mohon tunggu sebentar.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('input[name="xray_image"]').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImg').attr('src', e.target.result);
                        $('#imagePreview').show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#imagePreview').hide();
                }
            });

            $('#diagnosisForm').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = $('#submitBtn');
                const loadingSpinner = $('#loadingSpinner');
                const submitText = $('#submitText');

                submitBtn.prop('disabled', true);
                loadingSpinner.removeClass('d-none');
                submitText.text('Menganalisis...');

                $('#modalTambah').modal('hide');
                $('#loadingModal').modal('show');

                $.ajax({
                    url: '{{ route('xray.store') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#loadingModal').modal('hide');

                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                html: `
                            <div class="text-start">
                                <p><strong>Nama:</strong> ${response.data.nama}</p>
                                <p><strong>Diagnosa:</strong> 
                                    <span class="badge bg-primary">${response.ai_result.predicted_disease}</span>
                                </p>
                                <p><strong>Confidence:</strong> ${response.ai_result.confidence}%</p>
                            </div>
                        `,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#loadingModal').modal('hide');

                        let errorMessage = 'Terjadi kesalahan pada server';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        loadingSpinner.addClass('d-none');
                        submitText.text('Analisis & Simpan');
                    }
                });
            });

            $('#modalTambah').on('hidden.bs.modal', function() {
                $(this).find('form')[0].reset();
                $('#imagePreview').hide();
            });
        });
    </script>
@endpush
