<?php
// Minimaler XLSX-Writer (ein Blatt) auf Basis von ZipArchive, ohne externe Bibliothek.

declare(strict_types=1);

function xlsx_col(int $index): string
{
    $col = '';
    for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
        $col = chr(65 + ($n - 1) % 26) . $col;
    }
    return $col;
}

function xlsx_escape(string $s): string
{
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/** Excel-Seriennummer (Tage seit 1899-12-30) aus der lokalen Wandzeit. */
function xlsx_date_serial(DateTimeInterface $dt): float
{
    $utc = new DateTimeZone('UTC');
    $wall = new DateTimeImmutable($dt->format('Y-m-d H:i:s'), $utc);
    $epoch = new DateTimeImmutable('1899-12-30 00:00:00', $utc);
    return ($wall->getTimestamp() - $epoch->getTimestamp()) / 86400;
}

/**
 * @param list<string> $headers
 * @param list<list<string|int|DateTimeInterface>> $rows
 * @param list<float|int> $columnWidths
 */
function xlsx_write(string $path, string $sheetName, array $headers, array $rows, array $columnWidths): void
{
    $lastCol = xlsx_col(max(count($headers), 1) - 1);
    $lastRow = count($rows) + 1;

    $cell = static function (string $ref, mixed $value, int $style = 0): string {
        $s = $style > 0 ? " s=\"$style\"" : '';
        if ($value instanceof DateTimeInterface) {
            return "<c r=\"$ref\" s=\"2\"><v>" . sprintf('%.10F', xlsx_date_serial($value)) . '</v></c>';
        }
        if (is_int($value) || is_float($value)) {
            return "<c r=\"$ref\"$s><v>$value</v></c>";
        }
        return "<c r=\"$ref\"$s t=\"inlineStr\"><is><t xml:space=\"preserve\">" . xlsx_escape((string) $value) . '</t></is></c>';
    };

    $sheetRows = '<row r="1">';
    foreach ($headers as $i => $header) {
        $sheetRows .= $cell(xlsx_col($i) . '1', $header, 1);
    }
    $sheetRows .= '</row>';
    foreach ($rows as $r => $row) {
        $rowNum = $r + 2;
        $sheetRows .= "<row r=\"$rowNum\">";
        foreach (array_values($row) as $i => $value) {
            $sheetRows .= $cell(xlsx_col($i) . $rowNum, $value);
        }
        $sheetRows .= '</row>';
    }

    $cols = '';
    foreach ($columnWidths as $i => $width) {
        $n = $i + 1;
        $cols .= "<col min=\"$n\" max=\"$n\" width=\"$width\" customWidth=\"1\"/>";
    }

    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $rel = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $now = gmdate('Y-m-d\TH:i:s\Z');

    $parts = [
        '[Content_Types].xml' => $head . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>',
        '_rels/.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . "<Relationship Id=\"rId1\" Type=\"$rel/officeDocument\" Target=\"xl/workbook.xml\"/>"
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . "<Relationship Id=\"rId3\" Type=\"$rel/extended-properties\" Target=\"docProps/app.xml\"/>"
            . '</Relationships>',
        'xl/workbook.xml' => $head . "<workbook xmlns=\"$ns\" xmlns:r=\"$rel\">"
            . '<sheets><sheet name="' . xlsx_escape($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">'
            . xlsx_escape("'" . $sheetName . "'") . "!\$A\$1:\$$lastCol\$$lastRow</definedName></definedNames>"
            . '</workbook>',
        'xl/_rels/workbook.xml.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . "<Relationship Id=\"rId1\" Type=\"$rel/worksheet\" Target=\"worksheets/sheet1.xml\"/>"
            . "<Relationship Id=\"rId2\" Type=\"$rel/styles\" Target=\"styles.xml\"/>"
            . '</Relationships>',
        'xl/styles.xml' => $head . "<styleSheet xmlns=\"$ns\">"
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="dd.mm.yyyy hh:mm"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>',
        'xl/worksheets/sheet1.xml' => $head . "<worksheet xmlns=\"$ns\" xmlns:r=\"$rel\">"
            . "<dimension ref=\"A1:$lastCol$lastRow\"/>"
            . '<sheetViews><sheetView workbookViewId="0" tabSelected="1">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . ($cols !== '' ? "<cols>$cols</cols>" : '')
            . "<sheetData>$sheetRows</sheetData>"
            . "<autoFilter ref=\"A1:$lastCol$lastRow\"/>"
            . '</worksheet>',
        'docProps/core.xml' => $head . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
            . ' xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . xlsx_escape($sheetName) . '</dc:title><dc:creator>VKU-Basel</dc:creator>'
            . "<dcterms:created xsi:type=\"dcterms:W3CDTF\">$now</dcterms:created>"
            . "<dcterms:modified xsi:type=\"dcterms:W3CDTF\">$now</dcterms:modified>"
            . '</cp:coreProperties>',
        'docProps/app.xml' => $head . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
            . '<Application>VKU-Basel</Application></Properties>',
    ];

    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("XLSX kann nicht geschrieben werden: $path");
    }
    foreach ($parts as $name => $content) {
        $zip->addFromString($name, $content);
    }
    $zip->close();
}
