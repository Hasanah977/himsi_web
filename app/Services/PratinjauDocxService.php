<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Http\Response;
use ZipArchive;

/**
 * Pratinjau DOCX tanpa mengubah file asli dan tanpa konversi ke PDF.
 * Tampilan berupa teks dan tabel sederhana; layout kompleks Word tidak dijamin identik.
 */
final class PratinjauDocxService
{
    public static function lihat(string $file, string $ekstensi): Response
    {
        if ($ekstensi === 'doc') {
            return self::halaman(
                'Pratinjau Word (.doc)',
                '<div class="pesan">File Word lama (.doc) belum dapat ditampilkan langsung oleh penampil ini. '
                .'Dokumen tetap aman dan dapat dibuka melalui tombol <strong>Unduh Dokumen</strong> di halaman sebelumnya. '
                .'Tidak ada unduhan otomatis.</div>'
            );
        }

        if (! class_exists(ZipArchive::class) || ! class_exists(DOMDocument::class)) {
            return self::halaman(
                'Pratinjau Word (.docx)',
                '<div class="pesan">Ekstensi PHP ZIP/XML belum tersedia pada server, sehingga isi DOCX belum bisa ditampilkan. '
                .'Hubungi pengelola untuk mengaktifkan ekstensi <strong>zip</strong> dan <strong>dom</strong> pada PHP Laragon.</div>'
            );
        }

        $zip = new ZipArchive;
        if ($zip->open($file) !== true) {
            return self::halaman('Pratinjau Word', '<div class="pesan">Berkas DOCX tidak dapat dibaca.</div>');
        }

        try {
            $info = $zip->statName('word/document.xml');
            if (! $info || ($info['size'] ?? 0) > 8 * 1024 * 1024) {
                return self::halaman('Pratinjau Word', '<div class="pesan">Dokumen terlalu besar atau tidak mempunyai isi DOCX yang valid.</div>');
            }
            $xml = $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }

        if (! is_string($xml) || $xml === '') {
            return self::halaman('Pratinjau Word', '<div class="pesan">Isi DOCX tidak ditemukan.</div>');
        }

        $dom = new DOMDocument;
        $old = libxml_use_internal_errors(true);
        try {
            $parsed = $dom->loadXML($xml, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
        if (! $parsed) {
            return self::halaman('Pratinjau Word', '<div class="pesan">Isi DOCX tidak dapat diproses.</div>');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $body = $xpath->query('//w:body')->item(0);
        if (! $body) {
            return self::halaman('Pratinjau Word', '<div class="pesan">Tidak ada isi dokumen untuk ditampilkan.</div>');
        }

        $html = '';
        foreach ($body->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            if ($node->localName === 'p') {
                $html .= self::paragraf($node, $xpath);
            } elseif ($node->localName === 'tbl') {
                $html .= '<table class="docx-tabel">';
                foreach ($xpath->query('./w:tr', $node) as $baris) {
                    $html .= '<tr>';
                    foreach ($xpath->query('./w:tc', $baris) as $kolom) {
                        $html .= '<td>';
                        foreach ($xpath->query('./w:p', $kolom) as $p) {
                            $html .= self::paragraf($p, $xpath);
                        }
                        $html .= '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</table>';
            }
            if (strlen($html) > 1500000) {
                $html .= '<p class="pesan">Pratinjau dipersingkat karena dokumen panjang. File aslinya tetap lengkap.</p>';
                break;
            }
        }

        return self::halaman(
            'Pratinjau Word (.docx) — HIMSI',
            '<div class="keterangan">Pratinjau teks dan tabel DOCX tanpa konversi. '
            .'Tata letak kompleks, gambar, dan kop surat mungkin tidak tampil persis seperti di Microsoft Word. '
            .'File aslinya tidak diubah atau diunduh.</div>'
            .'<article class="kertas">'.($html ?: '<p>Tidak ada teks yang bisa ditampilkan.</p>').'</article>'
        );
    }

    private static function paragraf(DOMNode $p, DOMXPath $xpath): string
    {
        $teks = '';
        foreach ($xpath->query('.//w:t | .//w:br | .//w:tab', $p) as $item) {
            if ($item->localName === 't') {
                $teks .= htmlspecialchars($item->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } elseif ($item->localName === 'br') {
                $teks .= '<br>';
            } else {
                $teks .= '&emsp;';
            }
        }

        return '<p>'.($teks === '' ? '&nbsp;' : $teks).'</p>';
    }

    private static function halaman(string $judul, string $isi): Response
    {
        $judulEsc = htmlspecialchars($judul, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<!doctype html><html lang="id"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<title>'.$judulEsc.'</title><style>'
            .'*{box-sizing:border-box}body{background:#eef2f8;color:#172454;margin:0;font-family:Arial,Helvetica,sans-serif;line-height:1.5}'
            .'header{padding:14px 20px;background:#101b4c;color:#fff;font-weight:700}'
            .'main{max-width:900px;margin:18px auto;padding:0 14px}'
            .'.keterangan,.pesan{border:1px solid #d9e3f3;background:#fff;border-radius:10px;padding:14px;margin-bottom:15px;font-size:14px}'
            .'.kertas{background:white;box-shadow:0 3px 14px #0f172a15;padding:35px;min-height:250px;overflow-wrap:anywhere}'
            .'.kertas p{margin:0 0 12px;white-space:pre-wrap;color:#1f2937}'
            .'.docx-tabel{border-collapse:collapse;width:100%;margin:15px 0}.docx-tabel td{border:1px solid #cbd5e1;padding:8px;vertical-align:top}'
            .'@media(max-width:600px){.kertas{padding:16px}}'
            .'</style></head><body><header>'.$judulEsc.'</header><main>'.$isi.'</main></body></html>';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; frame-ancestors 'self'; base-uri 'none'; form-action 'none'",
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
