<?php

namespace App\Http\Requests\Kpum;

use App\Models\KpumCandidate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && ($this->user()->isSuperAdmin() || $this->user()->role === 'panitia_kpum');
    }

    public function rules(): array
    {
        $candidate = $this->route('candidate');
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'nim' => ['required', 'alpha_dash', 'max:40', Rule::unique('kpum_candidates')->where('election_id', 1)->ignore($candidate?->id)],
            'number' => ['required', 'integer', 'min:1', 'max:99', Rule::unique('kpum_candidates')->where('election_id', 1)->ignore($candidate?->id)],
            'angkatan' => ['required', 'integer', 'between:2000,'.now()->year],
            'ipk' => ['required', 'numeric', 'between:0,4'],
            'vision' => ['required', 'string', 'max:200'],
            'mission' => ['required', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
        foreach (KpumCandidate::documentLabels() as $key => $label) {
            $rules['documents.'.$key] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:3072'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'unique' => ':attribute sudah digunakan.', 'max' => ':attribute melebihi batas yang diizinkan.', 'mimes' => 'Format :attribute tidak didukung.'];
    }

    public function attributes(): array
    {
        return ['name' => 'Nama', 'nim' => 'NIM', 'number' => 'Nomor urut', 'vision' => 'Visi', 'mission' => 'Misi'];
    }
}
