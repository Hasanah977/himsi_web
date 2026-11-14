<ol class="hp-steps" aria-label="Tahapan pendaftaran">
    @foreach(['Data Diri', 'Upload Berkas', 'Konfirmasi'] as $label)
        <li data-hp-step="{{ $loop->iteration }}" class="{{ $step === $loop->iteration ? 'is-current' : ($step > $loop->iteration ? 'is-done' : '') }}" @if($step === $loop->iteration) aria-current="step" @endif>
            <span class="hp-step-number">{{ $loop->iteration }}</span><span>{{ $label }}</span>
        </li>
    @endforeach
</ol>
