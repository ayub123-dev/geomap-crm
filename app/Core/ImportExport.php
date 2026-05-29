<?php

namespace App\Core;

use RuntimeException;

class ImportExport
{
    public static function loadRowsFromFile($filePath)
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('File import tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($extension, array('csv', 'txt'), true)) {
            return self::loadCsv($filePath);
        }

        if (in_array($extension, array('xlsx', 'xls'), true)) {
            if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                throw new RuntimeException('PhpSpreadsheet belum tersedia. Jalankan: composer require phpoffice/phpspreadsheet');
            }

            return self::loadSpreadsheet($filePath);
        }

        throw new RuntimeException('Format file tidak didukung. Gunakan CSV/XLS/XLSX.');
    }

    public static function downloadCsv($filename, array $headers, array $rows)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            $line = array();
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($output, $line);
        }
        fclose($output);
        exit;
    }

    private static function loadCsv($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new RuntimeException('Gagal membaca file CSV.');
        }

        $header = null;
        $rows = array();
        while (($data = fgetcsv($handle,0,';')) !== false) {
            if ($header === null) {
                $header = array_map('trim', $data);
                continue;
            }

            $row = array();
            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $row[$key] = $data[$index] ?? null;
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private static function loadSpreadsheet($filePath)
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        if (empty($rows)) {
            return array();
        }

        $headerRow = array_shift($rows);
        $headers = array();
        foreach ($headerRow as $columnKey => $value) {
            $header = trim((string) $value);
            if ($header !== '') {
                $headers[$columnKey] = $header;
            }
        }

        $result = array();
        foreach ($rows as $row) {
            $item = array();
            foreach ($headers as $columnKey => $header) {
                $item[$header] = $row[$columnKey] ?? null;
            }
            if (!self::isEmptyRow($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    private static function isEmptyRow(array $row)
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}