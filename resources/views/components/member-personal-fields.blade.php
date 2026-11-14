<div class="hp-form-section"><header><span class="hp-section-icon"><i class="bi bi-people"></i></span><div><h2>B. Identitas &amp; Keluarga</h2><p>Lengkapi data identitas diri dan informasi keluarga.</p></div></header><div class="hp-grid hp-grid-three">
@foreach(\App\Support\MemberProfile::labels() as $key => $label)
    @if($key === 'address_street')</div></div><div class="hp-form-section"><header><span class="hp-section-icon"><i class="bi bi-geo-alt"></i></span><div><h2>C. Alamat Asal (Sesuai KTP)</h2><p>Lengkapi alamat sesuai dengan KTP Anda.</p></div></header><div class="hp-grid hp-grid-three">@endif
    @php($optional = in_array($key, ['nik', 'nisn', 'npwp', 'address_postcode'], true))
    <div class="hp-field-{{ $key }} {{ $key === 'address_street' ? 'hp-wide' : '' }}">
        <label for="hp-{{ $key }}">{{ $label }} {{ $optional ? '(opsional)' : '*' }}</label>
        <div class="hp-input-icon"><i class="bi bi-{{ match($key) { 'birth_place'=>'geo-alt', 'birth_date'=>'calendar3', 'semester'=>'stack', 'gender'=>'gender-ambiguous', 'religion'=>'moon', 'guardian_name'=>'people', 'guardian_phone'=>'telephone', 'address_street'=>'house', 'address_rt','address_rw'=>'hash', 'address_province'=>'map', 'address_postcode'=>'envelope', default=>'person-vcard' } }}"></i>
        @if(in_array($key, ['gender', 'religion'], true))
            <select id="hp-{{ $key }}" name="{{ $key }}" required>
                <option value="">Pilih {{ strtolower($label) }}</option>
                @foreach($key === 'gender' ? ['Laki-laki', 'Perempuan'] : ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu', 'Kepercayaan lainnya'] as $option)
                    <option value="{{ $option }}" @selected(old($key) === $option)>{{ $option }}</option>
                @endforeach
            </select>
        @else
            <input id="hp-{{ $key }}" name="{{ $key }}" value="{{ old($key) }}"
                type="{{ match($key) { 'birth_date' => 'date', 'semester' => 'number', 'guardian_phone' => 'tel', default => 'text' } }}"
                @if($key === 'birth_date') max="{{ now()->subDay()->toDateString() }}" @endif
                @if($key === 'semester') min="1" max="20" @endif
                @if(in_array($key, ['address_rt', 'address_rw'])) inputmode="numeric" pattern="[0-9]{1,3}" maxlength="3"
                @elseif($key === 'nik') inputmode="numeric" pattern="[0-9]{16}" maxlength="16"
                @elseif($key === 'nisn') inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                @elseif($key === 'address_postcode') inputmode="numeric" pattern="[0-9]{5}" maxlength="5"
                @elseif($key === 'guardian_phone') pattern="\+?[0-9]{9,15}" maxlength="16"
                @else maxlength="255" @endif @required(!$optional)>
        @endif
        </div>
        @error($key)<small class="text-danger">{{ $message }}</small>@enderror
    </div>
@endforeach

</div></div>
