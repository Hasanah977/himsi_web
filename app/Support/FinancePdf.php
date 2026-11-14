<?php

namespace App\Support;

class FinancePdf extends \FPDF
{
    private string $heading;

    private string $period;

    private array $columns;

    private array $widths;

    public function __construct(string $heading, string $period, array $columns, array $widths)
    {
        parent::__construct('L', 'mm', 'A4');
        $this->heading = $heading;
        $this->period = $period;
        $this->columns = $columns;
        $this->widths = $widths;
        $this->SetMargins(12, 12, 12);
        $this->SetAutoPageBreak(false);
        $this->AliasNbPages();
    }

    private function textValue(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', str_replace(["\r", "\t"], ['', ' '], $text));

        return $encoded === false ? '' : $encoded;
    }

    public function Header(): void
    {
        $this->SetTextColor(27, 35, 79);
        $this->SetFont('Arial', 'B', 17);
        $this->Cell(0, 9, 'HIMSI UNSULBAR', 0, 1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 7, $this->textValue($this->heading), 0, 1);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 7, $this->textValue('Periode '.$this->period.' | Dicetak '.now()->format('d/m/Y H:i')), 0, 1);
        $this->Ln(3);
        $this->SetFillColor(27, 35, 79);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        foreach ($this->columns as $index => $column) {
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $this->widths[$index], 13, 'F');
            $this->MultiCell($this->widths[$index], 5, $this->textValue($column), 0, 'L');
            $this->SetXY($x + $this->widths[$index], $y);
        }
        $this->Ln(13);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(40, 48, 65);
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(110, 120, 140);
        $this->Cell(0, 5, 'Keuangan HIMSI | Halaman '.$this->PageNo().' / {nb}', 0, 0, 'R');
    }

    private function wrap(string $text, float $width): array
    {
        $lines = [];
        $line = '';
        foreach (str_split($this->textValue($text)) as $char) {
            if ($char === "\n") {
                $lines[] = $line;
                $line = '';
            } elseif ($this->GetStringWidth($line.$char) > $width - 4) {
                $space = strrpos($line, ' ');
                if ($space !== false && $space > 0) {
                    $lines[] = substr($line, 0, $space);
                    $line = ltrim(substr($line, $space + 1).$char);
                } else {
                    $lines[] = $line;
                    $line = $char;
                }
            } else {
                $line .= $char;
            }
        }
        $lines[] = $line;

        return $lines;
    }

    public function tableRow(array $values, bool $total = false, bool $shade = false): void
    {
        $this->SetFont('Arial', $total ? 'B' : '', 8);
        $lines = [];
        foreach ($values as $index => $value) {
            $lines[] = $this->wrap(is_numeric($value) && ! is_string($value) ? number_format($value, 0, ',', '.') : (string) $value, $this->widths[$index]);
        }
        $offset = 0;
        $count = max(array_map('count', $lines));
        while ($offset < $count) {
            if ($this->GetY() + 10 > 192) {
                $this->AddPage();
            }
            $this->SetFont('Arial', $total ? 'B' : '', 8);
            $capacity = max(1, (int) floor((192 - $this->GetY() - 4) / 4.5));
            $take = min($count - $offset, $capacity);
            $height = $take * 4.5 + 4;
            $this->SetDrawColor(220, 225, 233);
            $this->SetFillColor(...($total ? [230, 235, 245] : ($shade ? [247, 249, 252] : [255, 255, 255])));
            foreach ($lines as $index => $cell) {
                $x = $this->GetX();
                $y = $this->GetY();
                $this->Rect($x, $y, $this->widths[$index], $height, 'DF');
                $this->SetXY($x + 1, $y + 2);
                $this->MultiCell($this->widths[$index] - 2, 4.5, implode("\n", array_slice($cell, $offset, $take)), 0, is_int($values[$index]) || is_float($values[$index]) ? 'R' : 'L');
                $this->SetXY($x + $this->widths[$index], $y);
            }
            $this->Ln($height);
            $offset += $take;
        }
    }
}
