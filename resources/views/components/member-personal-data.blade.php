@if($registration)
<div class="member-personal-data">
    @foreach(\App\Support\MemberProfile::labels() as $key => $label)
        <div class="personal-data-item"><small class="d-block text-secondary">{{ $label }}</small><strong>{{ $key === 'birth_date' ? ($registration->birth_date?->format('d-m-Y') ?? '—') : ($registration->{$key} ?? '—') }}</strong></div>
    @endforeach
</div>
@endif
