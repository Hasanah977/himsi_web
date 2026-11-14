<?php

namespace App\Services;

use App\Models\NotifikasiHimsi;
use App\Models\SuratKeluar;
use App\Models\User;

class NotifikasiSuratService
{
    public static function ketuaMenunggu(SuratKeluar $surat): void
    {
        $url = route('ketua-umum.persetujuan-surat.show', $surat, false);
        User::where('role', 'ketua_umum')
            ->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Pengajuan Surat Keluar',
                'Surat '.$surat->perihal.' menunggu keputusan Ketua Umum.', $url, 'persetujuan-surat'));
    }

    public static function sekretarisKeputusan(SuratKeluar $surat, string $status): void
    {
        User::where('role', 'sekretaris_umum')
            ->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Keputusan Surat Keluar',
                'Surat '.$surat->perihal.' '.$status.'.', route('sekretaris.surat-keluar.show', $surat, false), 'surat-keluar'));
    }

    public static function pendampingMenunggu(SuratKeluar $surat): void
    {
        if ($surat->penandatangan_pendamping_id) {
            NotifikasiHimsi::kirim($surat->penandatangan_pendamping_id, 'Persetujuan Tanda Tangan',
                'Tanda tangan Anda diminta untuk surat '.$surat->perihal.'.',
                route('tanda-tangan.persetujuan.show', $surat, false), 'tanda-tangan');
        }
    }
}
