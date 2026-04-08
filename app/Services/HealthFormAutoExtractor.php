<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Parser;

class HealthFormAutoExtractor
{
    /**
     * Common form labels that should never be interpreted as values.
     *
     * @var string[]
     */
    private array $knownLabels = [
        'voorletter',
        'roepnaam',
        'voornaam',
        'achternaam',
        'adres',
        'postcode',
        'woonplaats',
        'geboortedatum',
        'geboorteplaats',
        'telefoonnummer',
        'mobiel moeder',
        'mobiel vader',
        'relatie tot scout',
        'e-mailadres',
        'naam',
        'polisnummer',
        'verzekering',
        'huisarts',
        'tandarts',
        'bijvoorbeeld',
    ];

    public function extract(UploadedFile $file): array
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($file->getRealPath());
        $text = (string) $pdf->getText();
        $lines = $this->lines($text);
        $form = $this->extractFromFormFields($pdf);

        $roepnaam = $form['roepnaam'] ?? $this->extractValue($lines, ['Roepnaam', 'Voornaam']);
        $lastName = $form['last_name'] ?? $this->extractValue($lines, ['Achternaam']);
        $address = $form['address'] ?? $this->extractValue($lines, ['Adres']);
        $postalCode = $form['postal_code'] ?? $this->extractValue($lines, ['Postcode']);
        $city = $form['city'] ?? $this->extractValue($lines, ['Woonplaats']);
        $birthdayRaw = $form['birthday'] ?? $this->extractValue($lines, ['Geboortedatum']);
        $phoneMother = $form['phone_mother'] ?? $this->extractValue($lines, ['Mobiel moeder', 'Telefoon moeder']);
        $phoneFather = $form['phone_father'] ?? $this->extractValue($lines, ['Mobiel vader', 'Telefoon vader']);
        $emailParents = $form['email_parents'] ?? $this->extractValue($lines, ['E-mailadres ouders', 'Emailadres ouders', 'E mailadres ouders']);
        $notes = $form['bijzonderheden'] ?? $this->extractValue($lines, ['Zijn er zaken die belangrijk zijn voor de leiding om te weten', 'Overig']);
        $section = $form['section'] ?? $this->detectSection($lines);

        return [
            'section' => $section,
            'roepnaam' => $this->clean($roepnaam),
            'first_name' => $this->clean($roepnaam),
            'last_name' => $this->clean($lastName),
            'address' => $this->clean($address),
            'postal_code' => $this->clean($postalCode),
            'city' => $this->clean($city),
            'birthday' => $this->normalizeDate($birthdayRaw),
            'phone_mother' => $this->clean($phoneMother),
            'phone_father' => $this->clean($phoneFather),
            'email_parents' => $this->clean($emailParents),
            'bijzonderheden' => $this->clean($notes),
            'raw_text' => $text,
        ];
    }

    private function extractFromFormFields(Document $pdf): array
    {
        $fieldValues = [];
        $selectedSection = null;

        foreach ($pdf->getObjects() as $object) {
            if (! method_exists($object, 'getDetails')) {
                continue;
            }

            $details = $object->getDetails(false);
            if (! is_array($details)) {
                continue;
            }

            $fieldName = trim((string) ($details['T'] ?? ''));
            $value = trim((string) ($details['V'] ?? ''));
            $appearance = trim((string) ($details['AS'] ?? ''));

            if ($selectedSection === null) {
                $selectedSection = $this->mapSectionValue($value) ?? $this->mapSectionValue($appearance);
            }

            if ($fieldName === '' || $value === '') {
                continue;
            }

            $fieldValues[$fieldName] = $value;
        }

        return [
            'section' => $selectedSection,
            'roepnaam' => $this->formValueContains($fieldValues, ['roepnaam']),
            'last_name' => $this->formValueContains($fieldValues, ['achternaam']),
            'address' => $this->formValueContains($fieldValues, ['adres'], ['emailadres', 'e-mailadres']),
            'postal_code' => $this->formValueContains($fieldValues, ['postcode']),
            'city' => $this->formValueContains($fieldValues, ['woonplaats']),
            'birthday' => $this->formValueContains($fieldValues, ['geboortedatum']),
            'phone_mother' => $this->formValueContains($fieldValues, ['mobiel moeder']),
            'phone_father' => $this->formValueContains($fieldValues, ['mobiel vader']),
            'email_parents' => $this->formValueContains($fieldValues, ['emailadres ouders', 'e-mailadres ouders']),
            'bijzonderheden' => $this->formValueContains($fieldValues, ['text13', 'belangrijk zijn voor de leiding', 'overig']),
        ];
    }

    private function formValue(array $fieldValues, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $fieldValues)) {
                continue;
            }
            $value = trim((string) $fieldValues[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function formValueContains(array $fieldValues, array $needles, array $rejectNeedles = []): ?string
    {
        $normalized = [];
        foreach ($fieldValues as $key => $value) {
            $k = strtolower(trim((string) $key));
            $normalized[$k] = trim((string) $value);
        }

        foreach ($needles as $needle) {
            $n = strtolower($needle);
            foreach ($normalized as $key => $value) {
                if (! str_contains($key, $n)) {
                    continue;
                }
                $rejected = false;
                foreach ($rejectNeedles as $rejectNeedle) {
                    if (str_contains($key, strtolower($rejectNeedle))) {
                        $rejected = true;
                        break;
                    }
                }
                if ($rejected) {
                    continue;
                }
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function mapSectionValue(?string $value): ?string
    {
        $v = strtolower(trim((string) $value));
        if ($v === '' || $v === 'off') {
            return null;
        }

        return match (true) {
            str_contains($v, 'bever') => 'bevers',
            str_contains($v, 'dolf') => 'dolfijnen',
            str_contains($v, 'zeeverkenner') => 'zeeverkenners',
            str_contains($v, 'wilde') && str_contains($v, 'vaart') => 'wilde_vaart',
            str_contains($v, 'loods') => 'loodsen',
            default => null,
        };
    }

    private function detectSection(array $lines): ?string
    {
        $sections = [
            'bevers' => ['bevers'],
            'dolfijnen' => ['dolfijnen'],
            'zeeverkenners' => ['zeeverkenners'],
            'wilde_vaart' => ['wilde vaart', 'wilde_vaart'],
            'loodsen' => ['loodsen'],
        ];

        foreach ($lines as $line) {
            $low = strtolower($line);
            if (! str_contains($low, 'speltak') && ! str_contains($low, 'inschrijving nieuw lid') && ! preg_match('/[xX✓]/u', $line)) {
                continue;
            }
            foreach ($sections as $section => $needles) {
                foreach ($needles as $needle) {
                    if (str_contains($low, $needle)) {
                        return $section;
                    }
                }
            }
        }

        foreach ($lines as $line) {
            $low = strtolower($line);
            foreach ($sections as $section => $needles) {
                foreach ($needles as $needle) {
                    if (preg_match('/[xX✓].*'.preg_quote($needle, '/').'|'.preg_quote($needle, '/').'.*[xX✓]/u', $low)) {
                        return $section;
                    }
                }
            }
        }

        // Practical fallback for PDFs where the selected option text disappears in extraction.
        // If exactly one of the 5 sections is missing from the text layer, treat that as selected.
        $present = [];
        foreach ($lines as $line) {
            $low = strtolower($line);
            foreach ($sections as $section => $needles) {
                foreach ($needles as $needle) {
                    if (str_contains($low, $needle)) {
                        $present[$section] = true;
                        break;
                    }
                }
            }
        }
        $missing = array_values(array_diff(array_keys($sections), array_keys($present)));
        if (count($missing) === 1) {
            return $missing[0];
        }

        return null;
    }

    private function extractValue(array $lines, array $labels): ?string
    {
        $normalizedLabels = array_map(fn ($label) => strtolower($label), $labels);

        foreach ($lines as $index => $line) {
            $lineLow = strtolower($line);
            foreach ($normalizedLabels as $label) {
                $labelPattern = '/^\s*'.preg_quote($label, '/').'(\b|[:\s]|$)/i';
                if (! preg_match($labelPattern, $lineLow)) {
                    continue;
                }

                $parts = preg_split('/'.preg_quote($label, '/').'/i', $line, 2);
                $remainder = isset($parts[1]) ? trim((string) $parts[1], " \t:-\xC2\xA0") : '';
                if ($this->looksLikeValue($remainder)) {
                    return $remainder;
                }

                for ($i = $index + 1; $i <= min($index + 3, count($lines) - 1); $i++) {
                    $candidate = trim($lines[$i]);
                    if (! $this->looksLikeValue($candidate)) {
                        continue;
                    }
                    $isAnotherLabel = false;
                    foreach ($normalizedLabels as $otherLabel) {
                        if (str_contains(strtolower($candidate), $otherLabel)) {
                            $isAnotherLabel = true;
                            break;
                        }
                    }
                    if (! $isAnotherLabel) {
                        if ($this->isLabelLike($candidate)) {
                            continue;
                        }

                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    private function normalizeDate(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $v, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }
        if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $v, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return null;
    }

    private function lines(string $text): array
    {
        $rawLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));

        return array_values(array_filter(array_map(
            fn ($line) => $this->normalizeLine($line),
            $rawLines
        ), fn ($line) => $line !== '' && ! preg_match('/^--\s*\d+\s*of\s*\d+\s*--$/i', $line)));
    }

    private function normalizeLine(string $line): string
    {
        // Remove low control chars (including NUL) that appear in iOS-filled PDFs.
        $line = preg_replace('/[\x00-\x1F\x7F]+/', '', $line) ?? $line;
        $line = preg_replace('/\s+/', ' ', $line) ?? $line;

        return trim($line);
    }

    private function looksLikeValue(?string $value): bool
    {
        $v = trim((string) $value);
        if ($v === '' || mb_strlen($v) < 2) {
            return false;
        }
        if (preg_match('/^(ja|nee)$/i', $v)) {
            return false;
        }

        return true;
    }

    private function clean(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        if (str_starts_with($v, '(')) {
            return null;
        }
        if ($this->isLabelLike($v)) {
            return null;
        }
        if (str_starts_with(strtolower($v), 'wat wordt er van de leiding verwacht')) {
            return null;
        }

        return $v;
    }

    private function isLabelLike(string $value): bool
    {
        $low = strtolower(trim($value));

        foreach ($this->knownLabels as $label) {
            if (str_contains($low, $label)) {
                return true;
            }
        }

        return false;
    }
}
