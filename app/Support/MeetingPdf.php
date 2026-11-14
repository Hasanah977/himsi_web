<?php

namespace App\Support;

use App\Models\Meeting;
use App\Models\MeetingAttendance;

class MeetingPdf extends FinancePdf
{
    private ?Meeting $currentMeeting = null;

    private bool $minutesPage = false;

    public function __construct()
    {
        parent::__construct('Rekap Absensi Rapat Himpunan', '', ['No.', 'Nama Peserta', 'Jabatan / Departemen', 'Kehadiran', 'Waktu Hadir'], [12, 75, 100, 40, 46]);
    }

    private function encode(string $text): string
    {
        return iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: '';
    }

    public function Header(): void
    {
        $this->SetTextColor(27, 35, 79);
        $this->SetFont('Arial', 'B', 17);
        $this->Cell(0, 9, 'HIMSI UNSULBAR', 0, 1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, $this->minutesPage ? 'NOTULA RAPAT HIMPUNAN' : 'REKAP ABSENSI RAPAT HIMPUNAN', 0, 1);
        $this->SetFont('Arial', '', 10);
        if ($meeting = $this->currentMeeting) {
            $this->MultiCell(0, 5.5, $this->encode(Meeting::CATEGORIES[$meeting->category].' | '.$meeting->title));
            $this->MultiCell(0, 5.5, $this->encode('Tanggal: '.$meeting->scheduled_at->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i').' WITA | Tempat: '.$meeting->location));
            $this->Cell(0, 6, $this->encode('Status: '.Meeting::STATUSES[$meeting->status].' | Dicetak: '.now('Asia/Makassar')->format('d/m/Y H:i').' WITA'), 0, 1);
            if (! $this->minutesPage) {
                $summary = $meeting->attendances->countBy('status');
                $this->Cell(0, 7, 'Peserta: '.$meeting->attendances->count().'   Hadir: '.($summary['present'] ?? 0).'   Izin: '.($summary['excused'] ?? 0).'   Tidak hadir: '.($summary['absent'] ?? 0).'   Belum mengisi: '.($summary['pending'] ?? 0), 0, 1);
            }
        }
        $this->Ln(3);
        if (! $this->minutesPage) {
            $this->SetFillColor(27, 35, 79);
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 9);
            foreach (['No.' => 12, 'Nama Peserta' => 75, 'Jabatan / Departemen' => 100, 'Kehadiran' => 40, 'Waktu Hadir' => 46] as $label => $width) {
                $this->Cell($width, 9, $label, 0, 0, 'L', true);
            }
            $this->Ln();
        }
        $this->SetTextColor(40, 48, 65);
        $this->SetFont('Arial', '', 10);
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 110, 130);
        $this->Cell(0, 5, 'Rapat Himpunan | Sekertaris HIMSI | Halaman '.$this->PageNo().' / {nb}', 0, 0, 'R');
    }

    public function meeting(Meeting $meeting): void
    {
        $this->currentMeeting = $meeting;
        $this->minutesPage = false;
        $this->SetAutoPageBreak(false);
        $this->AddPage();
        foreach ($meeting->attendances->sortBy('participant_name')->values() as $index => $attendance) {
            $role = config('superadmin.roles.'.$attendance->participant_role, $attendance->participant_role);
            $department = config('superadmin.departments.'.$attendance->participant_department, '');
            $this->tableRow([(string) ($index + 1), $attendance->participant_name, $role.($department ? ' / '.$department : ''), MeetingAttendance::STATUSES[$attendance->status], $attendance->attended_at?->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i:s') ?? '-'], shade: $index % 2 === 1);
        }
        if ($meeting->minutes) {
            $this->minutesPage = true;
            $this->SetAutoPageBreak(true, 18);
            $this->AddPage();
            $this->MultiCell(0, 6, $this->encode($meeting->minutes));
        }
    }
}
