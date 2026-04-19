<?php

declare(strict_types=1);

namespace App\Support\Reports;

use RuntimeException;
use ZipArchive;

class TenantReportExcelExporter
{
    public function build(array $branding, array $metadataRows, array $sections): string
    {
        $logoImage = $branding['logo_excel_image'] ?? null;
        $accentHex = $this->normalizeHex((string) ($branding['accent_hex'] ?? '#0F6B4B'));
        $sheetXml = $this->buildWorksheetXml($branding, $metadataRows, $sections, $logoImage !== null);
        $stylesXml = $this->buildStylesXml($accentHex);
        $files = [
            '[Content_Types].xml' => $this->buildContentTypesXml($logoImage),
            '_rels/.rels' => $this->buildRootRelationshipsXml(),
            'docProps/app.xml' => $this->buildAppPropertiesXml(),
            'docProps/core.xml' => $this->buildCorePropertiesXml(),
            'xl/workbook.xml' => $this->buildWorkbookXml(),
            'xl/_rels/workbook.xml.rels' => $this->buildWorkbookRelationshipsXml(),
            'xl/styles.xml' => $stylesXml,
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        if ($logoImage !== null) {
            $files['xl/worksheets/_rels/sheet1.xml.rels'] = $this->buildWorksheetRelationshipsXml();
            $files['xl/drawings/drawing1.xml'] = $this->buildDrawingXml($logoImage);
            $files['xl/drawings/_rels/drawing1.xml.rels'] = $this->buildDrawingRelationshipsXml($logoImage);
            $files['xl/media/logo.'.$logoImage['extension']] = $logoImage['data'];
        }

        if ($this->canUseZipArchive()) {
            return $this->buildWithZipArchive($files);
        }

        return $this->buildWithoutZipArchive($files);
    }

    protected function canUseZipArchive(): bool
    {
        return class_exists(ZipArchive::class);
    }

    private function buildWithZipArchive(array $files): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'tenant-report-xlsx-');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to create a temporary file for the Excel export.');
        }

        $zip = new ZipArchive();
        $opened = $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            @unlink($tempPath);

            throw new RuntimeException('Unable to create the Excel export archive.');
        }

        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }

        $zip->close();

        $content = file_get_contents($tempPath);
        @unlink($tempPath);

        if (! is_string($content)) {
            throw new RuntimeException('Unable to read the generated Excel export.');
        }

        return $content;
    }

    private function buildWithoutZipArchive(array $files): string
    {
        $archive = '';
        $centralDirectory = '';
        $offset = 0;
        $timestamp = $this->zipTimestamp();

        foreach ($files as $path => $content) {
            $path = str_replace('\\', '/', $path);
            $content = (string) $content;
            $crc = crc32($content);
            $size = strlen($content);
            $pathLength = strlen($path);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                $timestamp['time'],
                $timestamp['date'],
                $crc,
                $size,
                $size,
                $pathLength,
                0,
            );

            $archive .= $localHeader.$path.$content;

            $centralDirectory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $timestamp['time'],
                $timestamp['date'],
                $crc,
                $size,
                $size,
                $pathLength,
                0,
                0,
                0,
                0,
                0,
                $offset,
            ).$path;

            $offset += strlen($localHeader) + $pathLength + $size;
        }

        $endOfCentralDirectory = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            count($files),
            count($files),
            strlen($centralDirectory),
            strlen($archive),
            0,
        );

        return $archive.$centralDirectory.$endOfCentralDirectory;
    }

    private function zipTimestamp(): array
    {
        $dateTime = now();
        $year = max((int) $dateTime->format('Y'), 1980);
        $month = (int) $dateTime->format('n');
        $day = (int) $dateTime->format('j');
        $hour = (int) $dateTime->format('G');
        $minute = (int) $dateTime->format('i');
        $second = (int) $dateTime->format('s');

        return [
            'date' => (($year - 1980) << 9) | ($month << 5) | $day,
            'time' => ($hour << 11) | ($minute << 5) | intdiv($second, 2),
        ];
    }

    private function buildWorksheetXml(array $branding, array $metadataRows, array $sections, bool $hasLogo): string
    {
        $rows = [];
        $merges = [];
        $rowIndex = 1;
        $titleMergeEnd = $hasLogo ? 'D' : 'F';
        $brandingLines = array_values(array_filter([
            trim((string) ($branding['tagline'] ?? '')),
            implode(' | ', array_values(array_filter([
                trim((string) ($branding['address'] ?? '')),
                trim((string) ($branding['contact_number'] ?? '')),
                trim((string) ($branding['contact_email'] ?? '')),
            ]))),
        ]));

        $rows[] = $this->buildRow($rowIndex, [
            $this->buildStringCell('A'.$rowIndex, (string) ($branding['tenant_name'] ?? 'PayMonitor'), 1),
        ], 26);
        $merges[] = 'A'.$rowIndex.':'.$titleMergeEnd.$rowIndex;
        $rowIndex++;

        $rows[] = $this->buildRow($rowIndex, [
            $this->buildStringCell('A'.$rowIndex, $brandingLines[0] ?? 'Lending cooperative report', 2),
        ], 18);
        $merges[] = 'A'.$rowIndex.':'.$titleMergeEnd.$rowIndex;
        $rowIndex++;

        $rows[] = $this->buildRow($rowIndex, [
            $this->buildStringCell('A'.$rowIndex, $brandingLines[1] ?? '', 2),
        ], 18);
        $merges[] = 'A'.$rowIndex.':'.$titleMergeEnd.$rowIndex;
        $rowIndex++;

        $rows[] = $this->buildRow($rowIndex, [
            $this->buildStringCell('A'.$rowIndex, 'COOPERATIVE LENDING REPORT', 3),
        ], 22);
        $merges[] = 'A'.$rowIndex.':F'.$rowIndex;
        $rowIndex += 2;

        $rows[] = $this->buildRow($rowIndex, [
            $this->buildStringCell('A'.$rowIndex, 'REPORT DETAILS', 6),
        ], 20);
        $merges[] = 'A'.$rowIndex.':F'.$rowIndex;
        $rowIndex++;

        $metadataPairs = array_chunk($metadataRows, 2);

        foreach ($metadataPairs as $pair) {
            $cells = [];
            $rowHeight = 20;

            if (isset($pair[0])) {
                $cells[] = $this->buildStringCell('A'.$rowIndex, (string) $pair[0]['label'], 4);
                $cells[] = $this->buildStringCell('B'.$rowIndex, (string) $pair[0]['value'], 5);
                $merges[] = 'B'.$rowIndex.':C'.$rowIndex;
                $rowHeight = max($rowHeight, $this->estimateSheetRowHeight((string) $pair[0]['value'], 24));
            }

            if (isset($pair[1])) {
                $cells[] = $this->buildStringCell('D'.$rowIndex, (string) $pair[1]['label'], 4);
                $cells[] = $this->buildStringCell('E'.$rowIndex, (string) $pair[1]['value'], 5);
                $merges[] = 'E'.$rowIndex.':F'.$rowIndex;
                $rowHeight = max($rowHeight, $this->estimateSheetRowHeight((string) $pair[1]['value'], 24));
            }

            $rows[] = $this->buildRow($rowIndex, $cells, $rowHeight);
            $rowIndex++;
        }

        $summaryRows = $sections[0]['rows'] ?? [];
        $summaryChunks = array_chunk($summaryRows, 3);
        $rowIndex++;

        $rows[] = $this->buildRow($rowIndex, [
            $this->buildStringCell('A'.$rowIndex, 'PORTFOLIO SUMMARY', 6),
        ], 20);
        $merges[] = 'A'.$rowIndex.':F'.$rowIndex;
        $rowIndex++;

        foreach ($summaryChunks as $chunkIndex => $summaryChunk) {
            $titleCells = [];
            $valueCells = [];
            $groupStarts = ['A', 'C', 'E'];
            $groupEnds = ['B', 'D', 'F'];
            $valueRowHeight = 28;

            foreach ($summaryChunk as $index => $metric) {
                $startColumn = $groupStarts[$index];
                $endColumn = $groupEnds[$index];
                $label = (string) ($metric[0] ?? '');
                $value = (string) ($metric[1] ?? '');

                $titleCells[] = $this->buildStringCell($startColumn.$rowIndex, $label, 7);
                $valueCells[] = $this->buildStringCell($startColumn.($rowIndex + 1), $value, 8);
                $merges[] = $startColumn.$rowIndex.':'.$endColumn.$rowIndex;
                $merges[] = $startColumn.($rowIndex + 1).':'.$endColumn.($rowIndex + 1);
                $valueRowHeight = max($valueRowHeight, $this->estimateSheetRowHeight($value, 20));
            }

            if ($chunkIndex === count($summaryChunks) - 1 && count($summaryChunk) === 1) {
                array_pop($merges);
                array_pop($merges);
                $titleCells = [$this->buildStringCell('A'.$rowIndex, (string) ($summaryChunk[0][0] ?? ''), 7)];
                $valueCells = [$this->buildStringCell('A'.($rowIndex + 1), (string) ($summaryChunk[0][1] ?? ''), 8)];
                $merges[] = 'A'.$rowIndex.':F'.$rowIndex;
                $merges[] = 'A'.($rowIndex + 1).':F'.($rowIndex + 1);
            }

            $rows[] = $this->buildRow($rowIndex, $titleCells, 20);
            $rows[] = $this->buildRow($rowIndex + 1, $valueCells, $valueRowHeight);
            $rowIndex += 3;
        }

        foreach (array_slice($sections, 1) as $section) {
            $rows[] = $this->buildRow($rowIndex, [
                $this->buildStringCell('A'.$rowIndex, (string) ($section['title'] ?? ''), 6),
            ], 20);
            $merges[] = 'A'.$rowIndex.':F'.$rowIndex;
            $rowIndex++;

            $headerCells = [];
            $headers = array_values($section['headers'] ?? []);

            foreach ($headers as $headerIndex => $header) {
                $headerCells[] = $this->buildStringCell($this->columnName($headerIndex + 1).$rowIndex, (string) $header, 9);
            }

            $rows[] = $this->buildRow($rowIndex, $headerCells, 20);
            $rowIndex++;

            foreach (($section['rows'] ?? []) as $row) {
                $row = array_values($row);
                $filledValues = array_values(array_filter($row, static fn ($value): bool => trim((string) $value) !== ''));

                if (count($filledValues) === 1 && count($row) > 1) {
                    $message = (string) $filledValues[0];
                    $rows[] = $this->buildRow($rowIndex, [
                        $this->buildStringCell('A'.$rowIndex, $message, 12),
                    ], max(24, $this->estimateSheetRowHeight($message, 64)));
                    $merges[] = 'A'.$rowIndex.':F'.$rowIndex;
                    $rowIndex++;

                    continue;
                }

                $bodyCells = [];
                $estimatedRowHeight = 20;

                foreach ($row as $cellIndex => $cellValue) {
                    $column = $this->columnName($cellIndex + 1);
                    $style = $this->isNumericLike((string) $cellValue) ? 11 : 10;
                    $bodyCells[] = $this->buildStringCell($column.$rowIndex, (string) $cellValue, $style);
                    $estimatedRowHeight = max(
                        $estimatedRowHeight,
                        $this->estimateSheetRowHeight((string) $cellValue, $this->sheetWrapWidthForColumn($cellIndex))
                    );
                }

                $rows[] = $this->buildRow($rowIndex, $bodyCells, $estimatedRowHeight);
                $rowIndex++;
            }

            $rowIndex++;
        }

        $lastRow = max($rowIndex - 1, 1);
        $drawingXml = $hasLogo ? '<drawing r:id="rId1"/>' : '';
        $mergeXml = $merges === []
            ? ''
            : '<mergeCells count="'.count($merges).'">'.implode('', array_map(
                static fn (string $range): string => '<mergeCell ref="'.$range.'"/>',
                $merges
            )).'</mergeCells>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="A1:F'.$lastRow.'"/>'
            .'<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="10" topLeftCell="A11" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="18"/>'
            .'<cols>'
            .'<col min="1" max="1" width="26" customWidth="1"/>'
            .'<col min="2" max="2" width="18" customWidth="1"/>'
            .'<col min="3" max="3" width="18" customWidth="1"/>'
            .'<col min="4" max="4" width="18" customWidth="1"/>'
            .'<col min="5" max="5" width="18" customWidth="1"/>'
            .'<col min="6" max="6" width="18" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .$mergeXml
            .$drawingXml
            .'<pageMargins left="0.35" right="0.35" top="0.55" bottom="0.55" header="0.3" footer="0.3"/>'
            .'</worksheet>';
    }

    private function buildStylesXml(string $accentHex): string
    {
        $accent = 'FF'.$accentHex;
        $accentLight = 'FF'.$this->tintHex($accentHex, 0.86);
        $cardFill = 'FF'.$this->tintHex($accentHex, 0.93);
        $white = 'FFFFFFFF';
        $dark = 'FF1F2937';
        $darkMuted = 'FF64748B';
        $border = 'FFD5DEE7';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="7">'
            .'<font><sz val="11"/><color rgb="'.$dark.'"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="18"/><color rgb="FF0F172A"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><sz val="10"/><color rgb="'.$darkMuted.'"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="12"/><color rgb="'.$white.'"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="16"/><color rgb="FF0F172A"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="10"/><color rgb="'.$white.'"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="6">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="'.$accent.'"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="'.$accentLight.'"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="'.$cardFill.'"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="'.$white.'"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="'.$border.'"/></left><right style="thin"><color rgb="'.$border.'"/></right><top style="thin"><color rgb="'.$border.'"/></top><bottom style="thin"><color rgb="'.$border.'"/></bottom><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="13">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="6" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="6" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function buildContentTypesXml(?array $logoImage): string
    {
        $logoDefault = $logoImage !== null
            ? '<Default Extension="'.$logoImage['extension'].'" ContentType="'.$logoImage['mime_type'].'"/>'
            : '';
        $drawingOverride = $logoImage !== null
            ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .$logoDefault
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .$drawingOverride
            .'</Types>';
    }

    private function buildRootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function buildAppPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>PayMonitor</Application>'
            .'</Properties>';
    }

    private function buildCorePropertiesXml(): string
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>Lending Report</dc:title>'
            .'<dc:creator>PayMonitor</dc:creator>'
            .'<cp:lastModifiedBy>PayMonitor</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function buildWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="25600" windowHeight="14400"/></bookViews>'
            .'<sheets><sheet name="Lending Report" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function buildWorkbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function buildWorksheetRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
            .'</Relationships>';
    }

    private function buildDrawingXml(array $logoImage): string
    {
        $width = max((int) ($logoImage['display_width'] ?? 120), 1) * 9525;
        $height = max((int) ($logoImage['display_height'] ?? 60), 1) * 9525;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .'<xdr:oneCellAnchor>'
            .'<xdr:from><xdr:col>4</xdr:col><xdr:colOff>95250</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>95250</xdr:rowOff></xdr:from>'
            .'<xdr:ext cx="'.$width.'" cy="'.$height.'"/>'
            .'<xdr:pic>'
            .'<xdr:nvPicPr><xdr:cNvPr id="1" name="Tenant Logo"/><xdr:cNvPicPr/></xdr:nvPicPr>'
            .'<xdr:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
            .'<xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
            .'</xdr:pic>'
            .'<xdr:clientData/>'
            .'</xdr:oneCellAnchor>'
            .'</xdr:wsDr>';
    }

    private function buildDrawingRelationshipsXml(array $logoImage): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo.'.$logoImage['extension'].'"/>'
            .'</Relationships>';
    }

    private function buildRow(int $index, array $cells, int $height): string
    {
        return '<row r="'.$index.'" ht="'.$height.'" customHeight="1">'.implode('', $cells).'</row>';
    }

    private function buildStringCell(string $reference, string $value, int $style): string
    {
        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xmlEscape($value).'</t></is></c>';
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1);
    }

    private function normalizeHex(string $hex): string
    {
        $trimmed = strtoupper(ltrim($hex, '#'));

        if (strlen($trimmed) === 3) {
            return preg_replace('/(.)/', '$1$1', $trimmed) ?? '0F6B4B';
        }

        return preg_match('/^[0-9A-F]{6}$/', $trimmed) === 1 ? $trimmed : '0F6B4B';
    }

    private function tintHex(string $hex, float $ratio): string
    {
        $hex = $this->normalizeHex($hex);
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

    private function estimateSheetRowHeight(string $value, int $characterWidth): int
    {
        $length = max(strlen($value), 1);
        $lines = max((int) ceil($length / max($characterWidth, 1)), 1);

        return max(20, (int) (($lines * 14) + 6));
    }

    private function sheetWrapWidthForColumn(int $columnIndex): int
    {
        return match ($columnIndex) {
            0 => 30,
            1 => 14,
            2, 3 => 16,
            default => 18,
        };
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function isNumericLike(string $value): bool
    {
        $trimmed = trim(str_replace([',', 'P'], '', $value));

        return $trimmed !== '' && is_numeric($trimmed);
    }
}
