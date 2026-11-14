<?php

namespace App\Services;

use App\Models\SuratKeluar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NomorSuratService
{
    public function tetapkan(SuratKeluar $surat): SuratKeluar
    {
        return DB::transaction(function () use ($surat) {

            // Serialisasi pemberian nomor menggunakan baris database.
            DB::table('surat_keluars')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $surat = SuratKeluar::query()
                ->lockForUpdate()
                ->findOrFail($surat->getKey());

            if ($surat->status !== 'disetujui') {
                throw new \LogicException(
                    'Nomor hanya dapat diberikan pada surat yang disetujui.'
                );
            }

            if (filled($surat->nomor_surat)) {
                return $surat;
            }

            $tanggal = $surat->tanggal_surat
                ? Carbon::parse($surat->tanggal_surat)
                : now();

            $tahun = (int) $tanggal->year;
            $bulan = $this->bulanRomawi((int) $tanggal->month);

            $jenis = strtoupper((string) $surat->jenis_surat);
            $lingkup = strtoupper((string) $surat->kode_lingkup);
            $penerbit = strtoupper((string) $surat->kode_penerbit);

            if (! $jenis || ! $lingkup || ! $penerbit) {
                throw new \LogicException(
                    'Jenis, lingkup, dan penerbit surat wajib diisi.'
                );
            }

            $awalan = match ($surat->kategori_penomoran) {
                'keputusan' => 1,
                'reguler' => 2,
                'kepanitiaan' => 3,
                default => throw new \LogicException(
                    'Kategori penomoran surat belum ditentukan.'
                ),
            };

            // Nomor urut terpisah berdasarkan kategori dan tahun.
            $nomorTerbesar = SuratKeluar::withTrashed()
                ->where('kategori_penomoran', $surat->kategori_penomoran)
                ->whereYear('tanggal_surat', $tahun)
                ->whereNotNull('nomor_urut')
                ->max('nomor_urut');

            $urutan = ((int) $nomorTerbesar) + 1;

            $nomorSurat = sprintf(
                '%d.%03d/%s/%s/%s/HIMSI FT-UNSULBAR/%s/%d',
                $awalan,
                $urutan,
                $jenis,
                $lingkup,
                $penerbit,
                $bulan,
                $tahun
            );

            $surat->nomor_urut = $urutan;
            $surat->nomor_surat = $nomorSurat;
            $surat->save();

            return $surat;
        }, 3);
    }

    private function bulanRomawi(int $bulan): string
    {
        $romawi = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        return $romawi[$bulan]
            ?? throw new \InvalidArgumentException('Bulan tidak valid.');
    }
}
