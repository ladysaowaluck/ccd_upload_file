<?php

namespace App\Services\Excel;

use Illuminate\Support\Collection;


class PurePhpExcelReader
{
    
    /**
     * Read cell coordinate map from the primary worksheet.
     *
     * @param string $filePath
     * @return array<string, string>
     */
    public function readCells(string $filePath): array
    {
        $entries = $this->extractZipEntries($filePath);
        $strings = $this->parseSharedStrings($entries['xl/sharedStrings.xml'] ?? '');

        return $this->parseCells($entries['xl/worksheets/sheet1.xml'] ?? '', $strings);
    }

    /**
     * Read tabular rows from the primary worksheet using the specified header row (1-based).
     * Returns a Collection of associative arrays with slugified column header keys.
     *
     * @param string $filePath
     * @param int $headerRow
     * @return Collection<int, array<string, mixed>>
     */
    public function readRowsWithHeading(string $filePath, int $headerRow = 1): Collection
    {
        $entries = $this->extractZipEntries($filePath);
        $strings = $this->parseSharedStrings($entries['xl/sharedStrings.xml'] ?? '');
        $sheetXmlContent = $entries['xl/worksheets/sheet1.xml'] ?? '';

        if (empty($sheetXmlContent)) {
            return collect();
        }

        $sheetXml = simplexml_load_string($sheetXmlContent);
        if ($sheetXml === false || !isset($sheetXml->sheetData->row)) {
            return collect();
        }

        $headers = [];
        $rows = [];

        foreach ($sheetXml->sheetData->row as $row) {
            $rNum = (int) $row['r'];
            $rowData = [];

            foreach ($row->c as $cell) {
                $coord = (string) $cell['r'];
                $col = preg_replace('/[0-9]/', '', $coord);
                $type = (string) $cell['t'];
                $val = (string) $cell->v;

                if ($type === 's' && isset($strings[(int) $val])) {
                    $val = $strings[(int) $val];
                }

                $rowData[$col] = $val;
            }

            if ($rNum === $headerRow) {
                foreach ($rowData as $col => $headerName) {
                    $key = strtolower(preg_replace('/[^A-Za-z0-9_]/', '_', trim((string) $headerName)));
                    $headers[$col] = $key;
                }
            } elseif ($rNum > $headerRow) {
                $mappedRow = [];
                foreach ($headers as $col => $key) {
                    $mappedRow[$key] = $rowData[$col] ?? null;
                }
                $rows[] = $mappedRow;
            }
        }

        return collect($rows);
    }

    /**
     * Unpack ZIP entries from .xlsx using End of Central Directory and gzinflate without ZipArchive.
     *
     * @param string $filePath
     * @return array<string, string>
     */
    protected function extractZipEntries(string $filePath): array
    {
        $data = file_get_contents($filePath);
        if ($data === false) {
            return [];
        }

        $pos = strrpos($data, "\x50\x4b\x05\x06");
        if ($pos === false) {
            return [];
        }

        $eocd = unpack('vdisk/vdiskStart/vnumEntries/vtotalEntries/VcdSize/VcdOffset/vcommentLen', substr($data, $pos + 4, 18));
        if (!$eocd || empty($eocd['totalEntries'])) {
            return [];
        }

        $offset = $eocd['cdOffset'];
        $entries = [];

        for ($i = 0; $i < $eocd['totalEntries']; $i++) {
            if (substr($data, $offset, 4) !== "\x50\x4b\x01\x02") {
                break;
            }

            $h = unpack('vverMade/vverNeeded/vflag/vmethod/vtime/vdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen/vcommLen/vdisk/vintAttr/VextAttr/VlocalOffset', substr($data, $offset + 4, 42));
            $name = substr($data, $offset + 46, $h['nameLen']);
            $offset += 46 + $h['nameLen'] + $h['extraLen'] + $h['commLen'];

            $cleanName = str_replace('\\', '/', $name);

            $lh = unpack('vverNeeded/vflag/vmethod/vtime/vdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen', substr($data, $h['localOffset'] + 4, 26));
            $dataOffset = $h['localOffset'] + 30 + $lh['nameLen'] + $lh['extraLen'];
            $raw = substr($data, $dataOffset, $h['compSize']);

            $entries[$cleanName] = ($h['method'] === 8) ? @gzinflate($raw) : $raw;
        }

        return $entries;
    }

    /**
     * Parse shared strings XML into an indexed array.
     *
     * @param string $xmlContent
     * @return array<int, string>
     */
    protected function parseSharedStrings(string $xmlContent): array
    {
        $strings = [];
        if (!empty($xmlContent)) {
            $sstXml = @simplexml_load_string($xmlContent);
            if ($sstXml !== false && isset($sstXml->si)) {
                foreach ($sstXml->si as $si) {
                    if (isset($si->t)) {
                        $strings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) ($r->t ?? '');
                        }
                        $strings[] = $text;
                    } else {
                        $strings[] = '';
                    }
                }
            }
        }
        return $strings;
    }

    /**
     * Parse cell coordinate map from sheet XML.
     *
     * @param string $xmlContent
     * @param array<int, string> $strings
     * @return array<string, string>
     */
    protected function parseCells(string $xmlContent, array $strings): array
    {
        $cells = [];
        if (!empty($xmlContent)) {
            $sheetXml = @simplexml_load_string($xmlContent);
            if ($sheetXml !== false && isset($sheetXml->sheetData->row)) {
                foreach ($sheetXml->sheetData->row as $row) {
                    foreach ($row->c as $cell) {
                        $coord = (string) $cell['r'];
                        $type = (string) $cell['t'];
                        $val = (string) $cell->v;

                        if ($type === 's' && isset($strings[(int) $val])) {
                            $val = $strings[(int) $val];
                        }

                        $cells[$coord] = $val;
                    }
                }
            }
        }
        return $cells;
    }
}
