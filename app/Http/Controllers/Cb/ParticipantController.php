<?php

namespace App\Http\Controllers\Cb;

use App\Http\Controllers\Controller;
use App\Models\CbParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::in(array_keys(CbParticipant::labels()))], 'period' => ['nullable', 'string', 'max:40']]);
        $query = CbParticipant::query();
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%')->orWhere('nim', 'like', '%'.$request->input('q').'%'));
        }
        foreach (['status', 'period'] as $key) {
            if ($request->filled($key)) {
                $query->where($key, $request->input($key));
            }
        }

        return view('cb.participants', ['participants' => $query->latest()->paginate(15)->withQueryString(), 'periods' => CbParticipant::distinct()->orderByDesc('period')->pluck('period')]);
    }

    public function create(): View
    {
        return view('cb.participant-form', ['participant' => new CbParticipant]);
    }

    public function edit(CbParticipant $participant): View
    {
        return view('cb.participant-form', compact('participant'));
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, new CbParticipant);
    }

    public function update(Request $request, CbParticipant $participant): RedirectResponse
    {
        return $this->save($request, $participant);
    }

    private function save(Request $request, CbParticipant $participant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nim' => ['required', 'alpha_dash', 'max:30', Rule::unique('cb_participants')->where('period', $request->input('period'))->ignore($participant->id)],
            'period' => ['required', 'string', 'max:40'], 'angkatan' => ['required', 'integer', 'between:2000,'.now()->year],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
        ]);
        DB::transaction(function () use ($participant, $data) {
            if ($participant->exists) {
                $participant = CbParticipant::whereKey($participant->id)->lockForUpdate()->firstOrFail();
                if ($participant->period !== $data['period'] && DB::table('cb_attendances')->where('participant_id', $participant->id)->exists()) {
                    throw ValidationException::withMessages(['period' => 'Periode peserta yang sudah mengikuti kegiatan tidak dapat diubah.']);
                }
            }
            $participant->fill($data)->save();
            DashboardController::record('Data peserta CB disimpan', $participant->name.' · '.$participant->nim);
        });

        return redirect()->route('cb.participants')->with('success', 'Data peserta CB tersimpan.');
    }

    public function select(Request $request, CbParticipant $participant): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['accepted', 'rejected'])], 'selection_note' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($data, $participant) {
            $participant = CbParticipant::whereKey($participant->id)->lockForUpdate()->firstOrFail();
            if ($data['status'] === 'rejected' && DB::table('cb_attendances')->where('participant_id', $participant->id)->exists()) {
                throw ValidationException::withMessages(['status' => 'Peserta sudah terdaftar dalam kegiatan. Keputusan tidak dapat diubah menjadi ditolak.']);
            }
            $participant->update($data);
            DashboardController::record('Seleksi peserta CB', $participant->name.' · '.$participant->nim, CbParticipant::labels()[$data['status']].': '.$data['selection_note']);
        });

        return back()->with('success', 'Hasil seleksi peserta CB disimpan.');
    }
}
