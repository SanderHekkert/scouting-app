<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;

class ReceiptOcrService
{
    /**
     * OCR-assist met lichte heuristiek:
     * - PDF: probeert tekst uit te lezen
     * - Afbeelding: fallback op bestandsnaam
     * - Vult alleen voorstellen, nooit definitieve waarden
     *
     * @return array{
     *   amount:float|null,
     *   declared_at:string|null,
     *   description_total:string|null,
     *   line_items:array<int, array{name:string,quantity:string,amount:string,vat:string}>
     * }
     */
    public function extractSuggestions(UploadedFile $file): array
    {
        $name = trim((string) $file->getClientOriginalName());
        $text = $this->extractText($file);
        $amount = $this->extractTotalAmount($text);
        $declaredAt = $this->extractPurchaseDate($text);
        $lineItems = [];

        if ($amount === null && preg_match('/(\d+[.,]\d{2})/', $text ?: $name, $matches) === 1) {
            $amount = (float) str_replace(',', '.', $matches[1]);
        }

        if ($declaredAt === null && preg_match('/(20\d{2})[-_]?([01]\d)[-_]?([0-3]\d)/', $name, $matches) === 1) {
            $declaredAt = sprintf('%s-%s-%s', $matches[1], $matches[2], $matches[3]);
        }

        if ($text !== '') {
            $lineItems = $this->extractLineItemsFromText($text);
        }

        if ($amount === null && $lineItems !== []) {
            $amount = array_reduce($lineItems, function (float $carry, array $row): float {
                $rowAmount = (float) str_replace(',', '.', (string) ($row['amount'] ?? '0'));

                return $carry + $rowAmount;
            }, 0.0);
        }

        if ($lineItems === []) {
            $lineItems = [[
                'name' => 'OCR regel (controleer handmatig)',
                'quantity' => '1',
                'amount' => $amount !== null ? number_format($amount, 2, '.', '') : '',
                'vat' => '21',
            ]];
        }

        return [
            'amount' => $amount,
            'declared_at' => $declaredAt,
            'description_total' => $name !== '' ? "OCR voorstel op basis van bestandsnaam: {$name}" : null,
            'line_items' => $lineItems,
        ];
    }

    private function extractText(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === 'pdf') {
            try {
                $parser = new PdfParser;
                $pdf = $parser->parseFile((string) $file->getRealPath());
                $text = trim((string) $pdf->getText());
                if ($text !== '') {
                    return $text;
                }
            } catch (\Throwable) {
                // noop; probeer nog via tesseract
            }
        }

        $tesseract = trim((string) shell_exec('command -v tesseract 2>/dev/null'));
        if ($tesseract === '') {
            return '';
        }

        $path = (string) $file->getRealPath();
        if ($path === '') {
            return '';
        }

        [$ocrPath, $cleanupPath] = $this->prepareImageForOcr($path, $extension);
        $escapedPath = escapeshellarg($ocrPath);
        $output = shell_exec("{$tesseract} {$escapedPath} stdout -l nld+eng 2>/dev/null");
        if ($cleanupPath !== null && is_file($cleanupPath)) {
            @unlink($cleanupPath);
        }

        return trim((string) $output);
    }

    /**
     * @return array{0:string,1:string|null} [ocrPath, cleanupPath]
     */
    private function prepareImageForOcr(string $path, string $extension): array
    {
        if (! in_array($extension, ['heic', 'heif'], true)) {
            return [$path, null];
        }

        $tempJpg = tempnam(sys_get_temp_dir(), 'ocr-heic-');
        if ($tempJpg === false) {
            return [$path, null];
        }
        $jpgPath = "{$tempJpg}.jpg";
        @unlink($tempJpg);

        $escapedInput = escapeshellarg($path);
        $escapedOutput = escapeshellarg($jpgPath);

        $magick = trim((string) shell_exec('command -v magick 2>/dev/null'));
        if ($magick !== '') {
            shell_exec("{$magick} {$escapedInput}[0] -quality 92 {$escapedOutput} 2>/dev/null");
            if (is_file($jpgPath)) {
                return [$jpgPath, $jpgPath];
            }
        }

        $sips = trim((string) shell_exec('command -v sips 2>/dev/null'));
        if ($sips !== '') {
            shell_exec("{$sips} -s format jpeg {$escapedInput} --out {$escapedOutput} >/dev/null 2>&1");
            if (is_file($jpgPath)) {
                return [$jpgPath, $jpgPath];
            }
        }

        return [$path, null];
    }

    /**
     * @return array<int, array{name:string,quantity:string,amount:string,vat:string}>
     */
    private function extractLineItemsFromText(string $text): array
    {
        $items = [];
        $lines = preg_split('/\R/u', $text) ?: [];
        foreach ($lines as $line) {
            $normalized = trim((string) $line);
            if ($normalized === '') {
                continue;
            }

            if (preg_match('/\b(totaal|subtotaal|total|pin|betaling|te betalen|afgerond)\b/i', $normalized) === 1) {
                continue;
            }

            if (preg_match('/^(.+?)\s+(\d+[.,]\d{2})$/u', $normalized, $matches) !== 1) {
                continue;
            }

            $name = trim((string) $matches[1]);
            $amount = str_replace(',', '.', (string) $matches[2]);
            if ($name === '') {
                continue;
            }

            $items[] = [
                'name' => mb_substr($name, 0, 120),
                'quantity' => '1',
                'amount' => $amount,
                'vat' => '21',
            ];
        }

        return array_slice($items, 0, 30);
    }

    private function extractTotalAmount(string $text): ?float
    {
        if ($text === '') {
            return null;
        }

        $patterns = [
            '/(?:totaal|total|te betalen)\D{0,20}(\d+[.,]\d{2})/iu',
            '/(?:pin|betaald|voldaan)\D{0,20}(\d+[.,]\d{2})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                return (float) str_replace(',', '.', (string) $matches[1]);
            }
        }

        return null;
    }

    private function extractPurchaseDate(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        $patterns = [
            '/\b([0-3]?\d)[\/\-.]([01]?\d)[\/\-.](20\d{2})\b/u', // 09-04-2026
            '/\b(20\d{2})[\/\-.]([01]?\d)[\/\-.]([0-3]?\d)\b/u', // 2026-04-09
        ];

        foreach ($patterns as $i => $pattern) {
            if (preg_match($pattern, $text, $matches) !== 1) {
                continue;
            }

            if ($i === 0) {
                $day = str_pad((string) $matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad((string) $matches[2], 2, '0', STR_PAD_LEFT);
                $year = (string) $matches[3];
            } else {
                $year = (string) $matches[1];
                $month = str_pad((string) $matches[2], 2, '0', STR_PAD_LEFT);
                $day = str_pad((string) $matches[3], 2, '0', STR_PAD_LEFT);
            }

            if (checkdate((int) $month, (int) $day, (int) $year)) {
                return "{$year}-{$month}-{$day}";
            }
        }

        return null;
    }
}
