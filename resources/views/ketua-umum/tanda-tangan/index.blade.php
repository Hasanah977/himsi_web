@extends('layouts.ketua-umum')

@section('content')
<div class="container-fluid py-3">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Tanda Tangan Digital</h2>
        <p class="text-muted mb-0">
            Buat dan kelola tanda tangan elektronik Ketua Umum HIMSI.
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-2">
                        <i class="bi bi-pen me-2"></i>Buat Tanda Tangan
                    </h5>

                    <p class="text-muted small">
                        Gunakan mouse atau layar sentuh untuk menggambar tanda tangan.
                    </p>

                    <div class="border rounded-3 bg-white overflow-hidden">
                        <canvas id="signatureCanvas"
                                style="width:100%;height:260px;display:block;touch-action:none;cursor:crosshair;">
                        </canvas>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" id="clearSignature"
                                class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1"></i>Hapus
                        </button>

                        <button type="button" id="previewSignature"
                                class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Pratinjau
                        </button>

                        <form id="signatureForm"
                              method="POST"
                              action="{{ route('ketua-umum.tanda-tangan.simpan') }}">
                            @csrf

                            <input type="hidden"
                                   id="signatureData"
                                   name="tanda_tangan">

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Simpan Tanda Tangan
                            </button>
                        </form>
                    </div>

                    <p class="small text-muted mt-3 mb-0">
                        Klik Simpan Tanda Tangan setelah selesai menggambar.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-card-image me-2"></i>Pratinjau Tanda Tangan
                    </h5>

                    <div class="border rounded-3 bg-white p-3 text-center">
                        <img id="signaturePreview"
                             alt="Pratinjau tanda tangan"
                             class="img-fluid {{ isset($tandaTangan) && $tandaTangan ? '' : 'd-none' }}"
                             style="max-height:180px;">

                        <p id="previewPlaceholder"
                           class="text-muted my-5 {{ isset($tandaTangan) && $tandaTangan ? 'd-none' : '' }}">
                            Belum ada pratinjau tanda tangan.
                        </p>

                        @if (isset($tandaTangan) && $tandaTangan)
                            <p class="small text-success mt-2 mb-0">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                Tanda tangan sudah tersimpan.
                            </p>
                        @endif
                    </div>

                    <div class="mt-4">
                        <div class="fw-semibold">
                            {{ auth()->user()->nama_lengkap }}
                        </div>
                        <div class="text-muted small">Ketua Umum HIMSI</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('signatureCanvas');
    const ctx = canvas.getContext('2d');
    const preview = document.getElementById('signaturePreview');
    const placeholder = document.getElementById('previewPlaceholder');

    const savedSignaturePath = @json(
        isset($tandaTangan) && $tandaTangan
            ? route('ketua-umum.tanda-tangan.gambar')
            : null
    );

    if (savedSignaturePath) {
        preview.src = savedSignaturePath;
    }

    let drawing = false;
    let hasSignature = false;

    function resizeCanvas() {
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        const ratio = window.devicePixelRatio || 1;

        canvas.width = Math.round(width * ratio);
        canvas.height = Math.round(height * ratio);

        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.strokeStyle = '#172454';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        hasSignature = false;
    }

    function getPosition(event) {
        const rect = canvas.getBoundingClientRect();

        return {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top
        };
    }

    canvas.addEventListener('pointerdown', function (event) {
        drawing = true;
        hasSignature = true;

        canvas.setPointerCapture(event.pointerId);

        const pos = getPosition(event);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    });

    canvas.addEventListener('pointermove', function (event) {
        if (!drawing) return;

        const pos = getPosition(event);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    });

    function stopDrawing() {
        drawing = false;
    }

    canvas.addEventListener('pointerup', stopDrawing);
    canvas.addEventListener('pointercancel', stopDrawing);

    document.getElementById('clearSignature').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
        hasSignature = false;
        preview.removeAttribute('src');
        preview.classList.add('d-none');
        placeholder.classList.remove('d-none');
    });

    document.getElementById('previewSignature').addEventListener('click', function () {
        if (!hasSignature) {
            alert('Silakan buat tanda tangan terlebih dahulu.');
            return;
        }

        preview.src = canvas.toDataURL('image/png');
        preview.classList.remove('d-none');
        placeholder.classList.add('d-none');
    });

    const signatureForm = document.getElementById('signatureForm');
    const signatureData = document.getElementById('signatureData');

    if (signatureForm && signatureData) {
        signatureForm.addEventListener('submit', function (event) {
            if (!hasSignature) {
                event.preventDefault();
                alert('Silakan gambar tanda tangan terlebih dahulu.');
                return;
            }

            signatureData.value = canvas.toDataURL('image/png');

            if (!signatureData.value.startsWith('data:image/png;base64,')) {
                event.preventDefault();
                alert('Gagal menyiapkan gambar tanda tangan.');
                return;
            }
        });
    }
    resizeCanvas();
});
</script>
@endpush