<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Support\FinanceLedger;
use App\Support\FinancePdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinanceExportController extends Controller
{
    public function __invoke(Request $request, string $format): Response
    {
        abort_unless(in_array($request->user()->role, ['bendahara_umum', 'ketua_umum', 'koordinator_pengawas', 'super_admin']), 403);
        abort_unless(in_array($format, ['pdf', 'xlsx']), 404);
        $request->validate(['kind' => ['nullable', 'in:income,expense'], 'scope' => ['nullable', 'in:monitoring']]);
        $query = app(FinanceController::class)->filtered($request)->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->input('kind')))->orderBy('occurred_on')->orderBy('id');
        $rows = [['Tanggal', 'Referensi', 'Kategori', 'Keterangan', 'Pemasukan (Rp)', 'Pengeluaran (Rp)']];
        foreach ($query->cursor() as $e) {
            $rows[] = [$e->occurred_on->format('Y-m-d'), $e->reference, $e->category, $e->description, $e->kind === 'income' ? $e->amount : 0, $e->kind === 'expense' ? $e->amount : 0];
        }
        if ($request->input('scope') === 'monitoring') {
            $rows = [['Departemen', 'Alokasi (Rp)', 'Dicairkan (Rp)', 'Dikembalikan (Rp)', 'Pemakaian Bersih (Rp)', 'Sisa Alokasi (Rp)']];
            foreach (app(FinanceController::class)->monitorRows() as $r) {
                $rows[] = [$r['name'], $r['allocated'], $r['paid'], $r['returned'], $r['used'], $r['remaining']];
            }
        }
        if ($format === 'pdf') {
            $monitoring = $request->input('scope') === 'monitoring';
            $title = $monitoring ? 'Monitoring Anggaran Departemen' : match ($request->input('kind')) {
                'income' => 'Laporan Pemasukan', 'expense' => 'Laporan Pengeluaran', default => 'Laporan Keuangan'
            };
            $pdf = new FinancePdf($title, $request->input('month') ?: config('finance.start').' s.d. '.config('finance.end'), $rows[0], $monitoring ? [48, 45, 45, 45, 45, 45] : [23, 41, 32, 97, 40, 40]);
            $pdf->AddPage();
            $totals = $monitoring ? ['TOTAL', 0, 0, 0, 0, 0] : ['TOTAL', '', '', '', 0, 0];
            foreach (array_slice($rows, 1) as $index => $row) {
                $pdf->tableRow($row, false, $index % 2 === 1);
                foreach ($row as $column => $value) {
                    if (is_int($value) || is_float($value)) {
                        $totals[$column] += $value;
                    }
                }
            }
            if (count($rows) === 1) {
                $pdf->tableRow(['Tidak ada data', '', '', '', '', '']);
            }
            $pdf->tableRow($totals, true);
            $bytes = $pdf->Output('S');
            FinanceLedger::audit('ekspor-pdf', $title.' diunduh. '.(count($rows) - 1).' baris; periode '.($request->input('month') ?: config('finance.start').' - '.config('finance.end')).'.');

            return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="HIMSI-Laporan-Keuangan.pdf"', 'Cache-Control' => 'private, no-store']);
        }

        $path = tempnam(sys_get_temp_dir(), 'himsi-xlsx-');
        unlink($path);
        $path .= '.zip';
        try {
            $zip = new \PharData($path, 0, null, \Phar::ZIP);
            $zip['[Content_Types].xml'] = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
            $zip['_rels/.rels'] = '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
            $zip['xl/workbook.xml'] = '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Buku Kas HIMSI" sheetId="1" r:id="rId1"/></sheets></workbook>';
            $zip['xl/_rels/workbook.xml.rels'] = '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
            $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="3" width="22" customWidth="1"/><col min="4" max="4" width="60" customWidth="1"/><col min="5" max="6" width="22" customWidth="1"/></cols><sheetData>';
            foreach ($rows as $i => $row) {
                $xml .= '<row r="'.($i + 1).'">';
                foreach ($row as $j => $value) {
                    $ref = chr(65 + $j).($i + 1);
                    $xml .= is_int($value) ? '<c r="'.$ref.'"><v>'.$value.'</v></c>' : '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value), ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
                }$xml .= '</row>';
            }
            $zip['xl/worksheets/sheet1.xml'] = $xml.'</sheetData></worksheet>';
            unset($zip);
            $bytes = file_get_contents($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        FinanceLedger::audit('ekspor-excel', 'Ekspor '.($request->input('scope') ?: ($request->input('kind') === 'expense' ? 'Pengeluaran' : 'Pemasukan / Keuangan')).' Excel; '.(count($rows) - 1).' baris.');

        return response($bytes, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="HIMSI-Laporan-Keuangan.xlsx"', 'Cache-Control' => 'private, no-store']);
    }
}
