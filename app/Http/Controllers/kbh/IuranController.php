<?php

namespace App\Http\Controllers\kbh;

use App\Http\Controllers\Controller;
use App\Models\KbhIuranPembayaran;
use App\Models\NotifikasiHimsi;
use App\Models\User;
use App\Support\FinanceLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class IuranController extends Controller
{
    private const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request)
    {
        $request->validate(['tahun' => ['nullable', 'integer', 'between:2026,2100']]);
        $tahun = $request->integer('tahun', now()->year);
        $nominal = (int) config('himsi_iuran.nominal_per_bulan', 5000);
        $riwayat = KbhIuranPembayaran::query()
            ->where('user_id', $request->user()->id)->where('tahun', $tahun)
            ->get()->keyBy('bulan');
        $jumlahLunas = $riwayat->where('status', 'lunas')->count();

        return view('kbh.iuran.index', [
            'duesPrefix' => $request->user()->role === 'anggota_departemen' ? 'member.dues' : 'kbh.iuran',
            'duesLayout' => $request->user()->role === 'anggota_departemen' ? 'administration.layout' : 'layouts.kbh',
            'tahun' => $tahun, 'bulan' => self::BULAN, 'nominal' => $nominal,
            'riwayat' => $riwayat, 'jumlahLunas' => $jumlahLunas,
        ]);
    }

    public function bayar(Request $request, int $bulan)
    {
        abort_unless(isset(self::BULAN[$bulan]), 404);
        $request->validate(['tahun' => ['nullable', 'integer', 'between:2026,2100']]);
        $tahun = $request->integer('tahun', now()->year);
        $pembayaran = KbhIuranPembayaran::where('user_id', $request->user()->id)
            ->where('tahun', $tahun)->where('bulan', $bulan)->first();

        return view('kbh.iuran.bayar', [
            'duesPrefix' => $request->user()->role === 'anggota_departemen' ? 'member.dues' : 'kbh.iuran',
            'duesLayout' => $request->user()->role === 'anggota_departemen' ? 'administration.layout' : 'layouts.kbh',
            'bulan' => $bulan, 'namaBulan' => self::BULAN[$bulan], 'tahun' => $tahun,
            'nominal' => (int) config('himsi_iuran.nominal_per_bulan', 5000),
            'pembayaran' => $pembayaran,
        ]);
    }

    public function store(Request $request, int $bulan)
    {
        abort_unless(isset(self::BULAN[$bulan]), 404);
        $request->validate([
            'bukti_transfer' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'konfirmasi' => ['required', 'accepted'],
            'catatan_anggota' => ['nullable', 'string', 'max:200'],
        ], [
            'bukti_transfer.required' => 'Unggah bukti transfer terlebih dahulu.',
            'konfirmasi.accepted' => 'Konfirmasi pembayaran harus dicentang.',
        ]);

        $request->validate(['tahun' => ['nullable', 'integer', 'between:2026,2100']]);
        $tahun = $request->integer('tahun', now()->year);
        $userId = $request->user()->id;
        $nominal = (int) config('himsi_iuran.nominal_per_bulan', 5000);
        $berkas = $request->file('bukti_transfer');
        $pathBaru = $berkas->store('kbh/iuran/'.$tahun.'/'.$userId, 'local');
        $pathLama = null;
        $catatanAnggota = $request->input('catatan_anggota');
        try {
            DB::transaction(function () use ($tahun, $bulan, $userId, $nominal, $berkas, $pathBaru, $catatanAnggota, &$pathLama): void {
                // Serialisasi kiriman ganda untuk anggota yang sama.
                DB::table('users')->where('id', $userId)->lockForUpdate()->first();
                $data = KbhIuranPembayaran::where('user_id', $userId)
                    ->where('tahun', $tahun)->where('bulan', $bulan)
                    ->lockForUpdate()->first();

                if ($data && $data->status !== 'ditolak') {
                    throw ValidationException::withMessages([
                        'bukti_transfer' => 'Iuran bulan ini sudah dikirim atau sudah lunas. Tidak perlu mengirim ulang.',
                    ]);
                }

                if ($data) {
                    $pathLama = $data->bukti_path;
                    $data->update([
                        'nominal' => $nominal,
                        'status' => 'menunggu_verifikasi',
                        'bukti_path' => $pathBaru,
                        'bukti_nama' => $berkas->getClientOriginalName(),
                        'bukti_mime' => $berkas->getMimeType(),
                        'catatan_anggota' => $catatanAnggota,
                        'catatan_bendahara' => null,
                        'diverifikasi_oleh' => null,
                        'diverifikasi_pada' => null,
                    ]);
                } else {
                    KbhIuranPembayaran::create([
                        'user_id' => $userId, 'tahun' => $tahun, 'bulan' => $bulan,
                        'nominal' => $nominal, 'status' => 'menunggu_verifikasi',
                        'bukti_path' => $pathBaru,
                        'bukti_nama' => $berkas->getClientOriginalName(),
                        'bukti_mime' => $berkas->getMimeType(),
                        'catatan_anggota' => $catatanAnggota,
                    ]);
                }
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($pathBaru);
            throw $e;
        }
        if ($pathLama && $pathLama !== $pathBaru) {
            Storage::disk('local')->delete($pathLama);
        }

        User::where('role', 'bendahara_umum')
            ->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim(
                (int) $id, 'Bukti Iuran Masuk',
                'Ada pembayaran iuran '.self::BULAN[$bulan].' '.$tahun.' yang perlu diverifikasi.',
                route('bendahara.iuran.index', [], false), 'iuran'
            ));

        return redirect(FinanceLedger::duesUrl($request->user(), $tahun))
            ->with('success', 'Bukti transfer dikirim. Status menunggu verifikasi Bendahara; belum Lunas.');
    }
}
