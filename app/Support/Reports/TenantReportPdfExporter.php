<?php

declare(strict_types=1);

namespace App\Support\Reports;

class TenantReportPdfExporter
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MARGIN_LEFT = 36;
    private const MARGIN_RIGHT = 36;
    private const MARGIN_TOP = 36;
    private const MARGIN_BOTTOM = 42;
    private const CONTENT_WIDTH = self::PAGE_WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT;

    private array $pages = [];

    private int $pageIndex = -1;

    private float $cursorY = 0.0;

    private array $branding = [];

    private ?array $logoImage = null;

    private string $generatedAt = '';

    private array $accentColor = [15, 107, 75];

    private array $accentTint = [232, 243, 238];

    private array $accentSoft = [245, 251, 248];

    private array $lineColor = [213, 222, 231];

    private array $textPrimary = [31, 41, 55];

    private array $textMuted = [100, 116, 139];

    public function build(array $branding, array $metadataRows, array $sections): string
    {
        $this->branding = $branding;
        $this->logoImage = $branding['logo_pdf_image'] ?? null;
        $this->accentColor = $this->rgbFromHex((string) ($branding['accent_hex'] ?? '#0F6B4B'));
        $this->accentTint = $this->rgbFromHex($this->tintHex((string) ($branding['accent_hex'] ?? '#0F6B4B'), 0.86));
        $this->accentSoft = $this->rgbFromHex($this->tintHex((string) ($branding['accent_hex'] ?? '#0F6B4B'), 0.95));
        $this->generatedAt = $this->findMetadataValue($metadataRows, 'Generated At');
        $this->pages = [];
        $this->pageIndex = -1;

        $reportMetadata = array_values(array_filter(
            $metadataRows,
            static fn (array $row): bool => (string) ($row['label'] ?? '') !== 'Tenant'
        ));

        $this->startPage(true);
        $this->drawMetadataPanel($reportMetadata);
        $this->drawSummaryPanel($sections[0]['rows'] ?? []);

        foreach (array_slice($sections, 1) as $section) {
            $this->drawSectionTable($section);
        }

        return $this->assemblePdfDocument();
    }

    private function startPage(bool $fullHeader): void
    {
        $this->pageIndex++;
        $this->pages[$this->pageIndex] = [];
        $topY = self::PAGE_HEIGHT - self::MARGIN_TOP;
        $brandName = strtoupper((string) ($this->branding['tenant_name'] ?? 'PayMonitor'));
        $title = 'COOPERATIVE LENDING REPORT';

        if ($fullHeader) {
            $logoWidth = (int) ($this->logoImage['display_width'] ?? 0);
            $logoHeight = (int) ($this->logoImage['display_height'] ?? 0);
            $logoTop = $topY - 2;

            if ($this->logoImage !== null && $logoWidth > 0 && $logoHeight > 0) {
                $this->drawImage(self::MARGIN_LEFT, $logoTop, $logoWidth, $logoHeight);
            }

            $textX = $this->logoImage !== null
                ? self::MARGIN_LEFT + $logoWidth + 18
                : self::MARGIN_LEFT;
            $contactLine = implode(' | ', array_values(array_filter([
                trim((string) ($this->branding['address'] ?? '')),
                trim((string) ($this->branding['contact_number'] ?? '')),
                trim((string) ($this->branding['contact_email'] ?? '')),
            ])));

            $this->drawText($textX, $topY - 8, $brandName, 'F2', 18, $this->textPrimary);

            if (($this->branding['tagline'] ?? '') !== '') {
                $this->drawText($textX, $topY - 28, (string) $this->branding['tagline'], 'F1', 10, $this->textMuted);
            }

            if ($contactLine !== '') {
                $this->drawText($textX, $topY - 44, $contactLine, 'F1', 9, $this->textMuted);
            }

            $this->drawText(self::PAGE_WIDTH - self::MARGIN_RIGHT, $topY - 8, $title, 'F2', 14, $this->accentColor, 'right');
            $this->drawText(self::PAGE_WIDTH - self::MARGIN_RIGHT, $topY - 28, 'Lending performance, collections, and borrower exposure', 'F1', 9, $this->textMuted, 'right');

            if ($this->generatedAt !== '') {
                $this->drawText(self::PAGE_WIDTH - self::MARGIN_RIGHT, $topY - 44, 'Generated '.$this->generatedAt, 'F1', 9, $this->textMuted, 'right');
            }

            $this->drawRect(self::MARGIN_LEFT, $topY - 58, self::CONTENT_WIDTH, 4, $this->accentColor);
            $this->cursorY = $topY - 76;

            return;
        }

        $this->drawText(self::MARGIN_LEFT, $topY - 6, $brandName, 'F2', 12, $this->textPrimary);
        $this->drawText(self::PAGE_WIDTH - self::MARGIN_RIGHT, $topY - 6, $title, 'F2', 10, $this->accentColor, 'right');

        if ($this->generatedAt !== '') {
            $this->drawText(self::PAGE_WIDTH - self::MARGIN_RIGHT, $topY - 20, 'Generated '.$this->generatedAt, 'F1', 8, $this->textMuted, 'right');
        }

        $this->drawRect(self::MARGIN_LEFT, $topY - 28, self::CONTENT_WIDTH, 2, $this->accentColor);
        $this->cursorY = $topY - 44;
    }

    private function drawMetadataPanel(array $metadataRows): void
    {
        if ($metadataRows === []) {
            return;
        }

        $requiredHeight = 24 + (ceil(count($metadataRows) / 2) * 46) + 8;
        $this->ensureSpace($requiredHeight);
        $this->drawSectionBand('REPORT DETAILS');

        $chunks = array_chunk($metadataRows, 2);
        $cardWidth = (self::CONTENT_WIDTH - 12) / 2;
        $cardHeight = 38;

        foreach ($chunks as $pair) {
            $topY = $this->cursorY;

            foreach ($pair as $index => $item) {
                $x = self::MARGIN_LEFT + (($cardWidth + 12) * $index);
                $this->drawRect($x, $topY, $cardWidth, $cardHeight, $this->accentTint, $this->lineColor);
                $this->drawText($x + 8, $topY - 12, strtoupper((string) ($item['label'] ?? '')), 'F2', 7, $this->textMuted);

                $valueLines = $this->wrapText((string) ($item['value'] ?? ''), $cardWidth - 16, 10);
                $this->drawTextLines($x + 8, $topY - 24, $valueLines, 'F2', 10, 11, $this->textPrimary, 'left', $cardWidth - 16);
            }

            $this->cursorY -= ($cardHeight + 8);
        }

        $this->cursorY -= 8;
    }

    private function drawSummaryPanel(array $summaryRows): void
    {
        if ($summaryRows === []) {
            return;
        }

        $this->ensureSpace(184);
        $this->drawSectionBand('PORTFOLIO SUMMARY');

        $cardGap = 10;
        $cardWidth = (self::CONTENT_WIDTH - ($cardGap * 2)) / 3;
        $cardHeight = 56;
        $chunks = array_chunk($summaryRows, 3);

        foreach ($chunks as $chunkIndex => $chunk) {
            $topY = $this->cursorY;

            if ($chunkIndex === count($chunks) - 1 && count($chunk) === 1) {
                $this->drawMetricCard(
                    self::MARGIN_LEFT,
                    $topY,
                    self::CONTENT_WIDTH,
                    52,
                    (string) ($chunk[0][0] ?? ''),
                    (string) ($chunk[0][1] ?? '')
                );
                $this->cursorY -= 60;

                continue;
            }

            foreach ($chunk as $index => $metric) {
                $x = self::MARGIN_LEFT + (($cardWidth + $cardGap) * $index);
                $this->drawMetricCard(
                    $x,
                    $topY,
                    $cardWidth,
                    $cardHeight,
                    (string) ($metric[0] ?? ''),
                    (string) ($metric[1] ?? '')
                );
            }

            $this->cursorY -= ($cardHeight + 10);
        }

        $this->cursorY -= 4;
    }

    private function drawMetricCard(float $x, float $topY, float $width, float $height, string $label, string $value): void
    {
        $this->drawRect($x, $topY, $width, $height, [255, 255, 255], $this->lineColor);
        $this->drawRect($x, $topY, $width, 4, $this->accentColor);

        $labelLines = array_slice($this->wrapText(strtoupper($label), $width - 16, 8), 0, 2);
        $this->drawTextLines($x + 8, $topY - 11, $labelLines, 'F2', 8, 10, $this->textMuted, 'left', $width - 16);
        $this->drawText($x + 8, $topY - ($height - 20), $value, 'F2', 15, $this->textPrimary);
    }

    private function drawSectionTable(array $section): void
    {
        $title = (string) ($section['title'] ?? '');
        $headers = array_values($section['headers'] ?? []);
        $rows = array_values($section['rows'] ?? []);
        $layout = $this->tableLayout($title, count($headers));

        $this->ensureSpace(54);
        $this->drawSectionBand($title);
        $this->drawTableHeaderRow($headers, $layout['widths'], $layout['alignments']);

        foreach ($rows as $rowIndex => $row) {
            $row = array_values($row);
            $filledValues = array_values(array_filter($row, static fn ($value): bool => trim((string) $value) !== ''));

            if (count($filledValues) === 1 && count($row) > 1) {
                $message = (string) $filledValues[0];
                $rowHeight = max(24, count($this->wrapText($message, self::CONTENT_WIDTH - 16, 9)) * 11 + 10);

                if ($this->cursorY - $rowHeight < self::MARGIN_BOTTOM) {
                    $this->startPage(false);
                    $this->drawSectionBand($title.' (continued)');
                    $this->drawTableHeaderRow($headers, $layout['widths'], $layout['alignments']);
                }

                $this->drawTableCell(self::MARGIN_LEFT, $this->cursorY, self::CONTENT_WIDTH, $rowHeight, $message, 9, 'center', $this->accentSoft);
                $this->cursorY -= $rowHeight;

                continue;
            }

            $wrappedCells = [];
            $rowHeight = 24;

            foreach ($row as $cellIndex => $cellValue) {
                $width = $layout['widths'][$cellIndex] ?? (self::CONTENT_WIDTH / max(count($row), 1));
                $lines = $this->wrapText((string) $cellValue, $width - 12, 9);
                $wrappedCells[$cellIndex] = $lines;
                $rowHeight = max($rowHeight, (count($lines) * 11) + 10);
            }

            if ($this->cursorY - $rowHeight < self::MARGIN_BOTTOM) {
                $this->startPage(false);
                $this->drawSectionBand($title.' (continued)');
                $this->drawTableHeaderRow($headers, $layout['widths'], $layout['alignments']);
            }

            $fill = $rowIndex % 2 === 0 ? [255, 255, 255] : $this->accentSoft;
            $x = self::MARGIN_LEFT;

            foreach ($row as $cellIndex => $cellValue) {
                $width = $layout['widths'][$cellIndex] ?? (self::CONTENT_WIDTH / max(count($row), 1));
                $align = $layout['alignments'][$cellIndex] ?? 'left';
                $this->drawTableCell($x, $this->cursorY, $width, $rowHeight, (string) $cellValue, 9, $align, $fill);
                $x += $width;
            }

            $this->cursorY -= $rowHeight;
        }

        $this->cursorY -= 14;
    }

    private function drawSectionBand(string $title): void
    {
        $bandHeight = 22;
        $this->drawRect(self::MARGIN_LEFT, $this->cursorY, self::CONTENT_WIDTH, $bandHeight, $this->accentColor, $this->accentColor);
        $this->drawText(self::MARGIN_LEFT + 8, $this->cursorY - 14, $title, 'F2', 10, [255, 255, 255]);
        $this->cursorY -= $bandHeight;
    }

    private function drawTableHeaderRow(array $headers, array $widths, array $alignments): void
    {
        $height = 22;
        $x = self::MARGIN_LEFT;

        foreach ($headers as $index => $header) {
            $width = $widths[$index] ?? (self::CONTENT_WIDTH / max(count($headers), 1));
            $align = $alignments[$index] ?? 'left';
            $this->drawTableCell($x, $this->cursorY, $width, $height, (string) $header, 9, $align, $this->accentTint, true);
            $x += $width;
        }

        $this->cursorY -= $height;
    }

    private function drawTableCell(float $x, float $topY, float $width, float $height, string $text, int $fontSize, string $align, array $fill, bool $bold = false): void
    {
        $this->drawRect($x, $topY, $width, $height, $fill, $this->lineColor);
        $lines = $this->wrapText($text, $width - 12, $fontSize);
        $font = $bold ? 'F2' : 'F1';
        $color = $bold ? $this->textPrimary : $this->textPrimary;
        $startY = $topY - ($bold ? 14 : 13);
        $this->drawTextLines($x + 6, $startY, $lines, $font, $fontSize, 11, $color, $align, $width - 12);
    }

    private function ensureSpace(float $height): void
    {
        if ($this->cursorY - $height < self::MARGIN_BOTTOM) {
            $this->startPage(false);
        }
    }

    private function drawImage(float $x, float $topY, float $width, float $height): void
    {
        $bottomY = $topY - $height;
        $this->pages[$this->pageIndex][] = 'q';
        $this->pages[$this->pageIndex][] = sprintf('%.2F 0 0 %.2F %.2F %.2F cm', $width, $height, $x, $bottomY);
        $this->pages[$this->pageIndex][] = '/Im1 Do';
        $this->pages[$this->pageIndex][] = 'Q';
    }

    private function drawRect(float $x, float $topY, float $width, float $height, ?array $fill = null, ?array $stroke = null): void
    {
        $bottomY = $topY - $height;
        $commands = ['q'];

        if ($fill !== null) {
            $commands[] = $this->pdfColor($fill, false);
        }

        if ($stroke !== null) {
            $commands[] = $this->pdfColor($stroke, true);
            $commands[] = '1 w';
        }

        $commands[] = sprintf('%.2F %.2F %.2F %.2F re', $x, $bottomY, $width, $height);
        $commands[] = $fill !== null && $stroke !== null
            ? 'B'
            : ($fill !== null ? 'f' : 'S');
        $commands[] = 'Q';

        foreach ($commands as $command) {
            $this->pages[$this->pageIndex][] = $command;
        }
    }

    private function drawText(float $x, float $y, string $text, string $font, int $size, array $color, string $align = 'left'): void
    {
        $normalized = $this->escapePdfText($text);
        $textX = $x;

        if ($align !== 'left') {
            $width = $this->estimateTextWidth($text, $size);
            $textX = $align === 'right'
                ? $x - $width
                : $x - ($width / 2);
        }

        $this->pages[$this->pageIndex][] = 'BT';
        $this->pages[$this->pageIndex][] = '/'.$font.' '.$size.' Tf';
        $this->pages[$this->pageIndex][] = $this->pdfColor($color, false);
        $this->pages[$this->pageIndex][] = sprintf('1 0 0 1 %.2F %.2F Tm', $textX, $y);
        $this->pages[$this->pageIndex][] = '('.$normalized.') Tj';
        $this->pages[$this->pageIndex][] = 'ET';
    }

    private function drawTextLines(float $x, float $topBaselineY, array $lines, string $font, int $size, int $leading, array $color, string $align, float $width): void
    {
        foreach ($lines as $index => $line) {
            $baselineY = $topBaselineY - ($leading * $index);
            $drawX = match ($align) {
                'right' => $x + $width,
                'center' => $x + ($width / 2),
                default => $x,
            };

            $this->drawText($drawX, $baselineY, $line, $font, $size, $color, $align);
        }
    }

    private function assemblePdfDocument(): string
    {
        $pageCount = count($this->pages);
        $fontRegularObject = 3 + ($pageCount * 2);
        $fontBoldObject = $fontRegularObject + 1;
        $imageObject = $this->logoImage !== null ? $fontBoldObject + 1 : null;
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
        ];
        $pageReferences = [];

        foreach ($this->pages as $index => $commands) {
            $pageObject = 3 + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageReferences[] = $pageObject.' 0 R';
            $stream = implode("\n", $commands);

            $resources = '/Font << /F1 '.$fontRegularObject.' 0 R /F2 '.$fontBoldObject.' 0 R >>';

            if ($imageObject !== null) {
                $resources .= ' /XObject << /Im1 '.$imageObject.' 0 R >>';
            }

            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << '.$resources.' >> /Contents '.$contentObject.' 0 R >>';
            $objects[$contentObject] = "<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageReferences).'] /Count '.$pageCount.' >>';
        $objects[$fontRegularObject] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[$fontBoldObject] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        if ($imageObject !== null && $this->logoImage !== null) {
            $imageData = $this->logoImage['data'];

            $objects[$imageObject] = "<< /Type /XObject /Subtype /Image /Width {$this->logoImage['pixel_width']} /Height {$this->logoImage['pixel_height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($imageData)." >>\nstream\n{$imageData}\nendstream";
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $objectNumber => $body) {
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $objectNumber." 0 obj\n".$body."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $objectCount = max(array_keys($objects));
        $pdf .= "xref\n0 ".($objectCount + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($objectNumber = 1; $objectNumber <= $objectCount; $objectNumber++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectNumber]);
        }

        $pdf .= "trailer\n<< /Size ".($objectCount + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function tableLayout(string $title, int $columnCount): array
    {
        return match ($title) {
            'LOAN RELEASES BY TYPE' => [
                'widths' => [220, 65, 119, 119],
                'alignments' => ['left', 'center', 'right', 'right'],
            ],
            'COLLECTIONS BY MONTH' => [
                'widths' => [230, 110, 183],
                'alignments' => ['left', 'center', 'right'],
            ],
            'OVERDUE LOANS' => [
                'widths' => [150, 90, 95, 75, 113],
                'alignments' => ['left', 'center', 'right', 'center', 'center'],
            ],
            'TOP 10 BORROWERS BY OUTSTANDING BALANCE' => [
                'widths' => [95, 180, 100, 148],
                'alignments' => ['left', 'left', 'center', 'right'],
            ],
            default => [
                'widths' => array_fill(0, $columnCount, self::CONTENT_WIDTH / max($columnCount, 1)),
                'alignments' => array_fill(0, $columnCount, 'left'),
            ],
        };
    }

    private function wrapText(string $value, float $width, int $fontSize): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        if ($normalized === '') {
            return [''];
        }

        $maxCharacters = max((int) floor($width / max($fontSize * 0.5, 1)), 1);
        $wrapped = wordwrap($normalized, $maxCharacters, "\n", true);

        return explode("\n", $wrapped);
    }

    private function estimateTextWidth(string $value, int $fontSize): float
    {
        $normalized = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);
        $length = strlen($normalized === false ? $value : $normalized);

        return $length * ($fontSize * 0.48);
    }

    private function escapePdfText(string $value): string
    {
        $normalized = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);

        if ($normalized === false) {
            $normalized = $value;
        }

        return str_replace(
            ['\\', '(', ')', "\r"],
            ['\\\\', '\(', '\)', ''],
            $normalized,
        );
    }

    private function pdfColor(array $color, bool $stroke): string
    {
        return sprintf(
            '%.3F %.3F %.3F %s',
            $color[0] / 255,
            $color[1] / 255,
            $color[2] / 255,
            $stroke ? 'RG' : 'rg'
        );
    }

    private function findMetadataValue(array $metadataRows, string $label): string
    {
        foreach ($metadataRows as $row) {
            if ((string) ($row['label'] ?? '') === $label) {
                return (string) ($row['value'] ?? '');
            }
        }

        return '';
    }

    private function tintHex(string $hex, float $ratio): string
    {
        $hex = strtoupper(ltrim($hex, '#'));
        $hex = preg_match('/^[0-9A-F]{6}$/', $hex) === 1 ? $hex : '0F6B4B';
        $ratio = max(0, min(1, $ratio));
        $components = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

        $tinted = array_map(
            static fn (int $component): int => (int) round($component + ((255 - $component) * $ratio)),
            $components,
        );

        return sprintf('%02X%02X%02X', $tinted[0], $tinted[1], $tinted[2]);
    }

    private function rgbFromHex(string $hex): array
    {
        $hex = strtoupper(ltrim($hex, '#'));
        $hex = preg_match('/^[0-9A-F]{6}$/', $hex) === 1 ? $hex : '0F6B4B';

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
