<?php

namespace App\Services;

class NotionExportPropertyTypeInferrer
{
    public function infer(string $column, array $samples, bool $isTitle = false): string
    {
        if ($isTitle) {
            return 'title';
        }

        if ($known = $this->knownSystemType($column)) {
            return $known;
        }

        $samples = $this->normalizeSamples($samples);

        if ($samples !== []) {
            return $this->inferFromSamples($column, $samples);
        }

        return $this->fallbackFromColumnName($column);
    }

    /** @param list<string|null> $samples */
    private function normalizeSamples(array $samples): array
    {
        $normalized = [];

        foreach ($samples as $sample) {
            $sample = trim((string) $sample);
            if ($sample !== '') {
                $normalized[] = $sample;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function knownSystemType(string $column): ?string
    {
        $lower = strtolower(trim($column));

        return match ($lower) {
            'name' => 'title',
            'created time' => 'created_time',
            'created by' => 'created_by',
            'last edited' => 'last_edited_time',
            'last edited time' => 'last_edited_time',
            'last edited by' => 'last_edited_by',
            'files & media', 'files and media' => 'files',
            default => null,
        };
    }

    /** @param list<string> $samples */
    private function inferFromSamples(string $column, array $samples): string
    {
        $votes = [];

        foreach ($samples as $sample) {
            $type = $this->classifySample($column, $sample);
            $votes[$type] = ($votes[$type] ?? 0) + 1;
        }

        arsort($votes);

        $winner = array_key_first($votes);

        if ($winner === 'rich_text') {
            return $this->refinePlainTextType($column, $samples);
        }

        return $winner;
    }

    private function classifySample(string $column, string $sample): string
    {
        if ($this->isRelationValue($sample)) {
            return 'relation';
        }

        if ($this->isRollupValue($sample)) {
            return 'rollup';
        }

        if ($this->isCheckboxValue($sample)) {
            return 'checkbox';
        }

        if (filter_var($sample, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        if ($this->isUrlValue($sample)) {
            return 'url';
        }

        if ($this->isPhoneValue($sample)) {
            return 'phone_number';
        }

        if ($this->isDateValue($sample) && $this->columnSuggestsDate($column)) {
            return 'date';
        }

        if ($this->isNumberValue($sample)) {
            return 'number';
        }

        return 'rich_text';
    }

    /** @param list<string> $samples */
    private function refinePlainTextType(string $column, array $samples): string
    {
        if ($this->looksLikeMultiSelect($samples)) {
            return 'multi_select';
        }

        if ($this->columnSuggestsSelect($column)) {
            return 'select';
        }

        if ($this->columnSuggestsPerson($column)) {
            return 'person';
        }

        if ($this->columnSuggestsStatus($column)) {
            return 'status';
        }

        return 'rich_text';
    }

    private function isRelationValue(string $value): bool
    {
        $parts = $this->splitRelationParts($value);

        if ($parts === []) {
            return false;
        }

        foreach ($parts as $part) {
            if (! preg_match('/^.+\s\((?:https?:\/\/[^\s)]+|\S+\.md)\)$/u', $part)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    private function splitRelationParts(string $value): array
    {
        $parts = preg_split('/,\s+(?=[^,]+ \()/', $value) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn (string $part) => $part !== ''));
    }

    private function isRollupValue(string $value): bool
    {
        return (bool) preg_match('/^\d+\s+.+\s-\s/u', $value);
    }

    private function isCheckboxValue(string $value): bool
    {
        return in_array(strtolower($value), ['yes', 'no'], true);
    }

    private function isUrlValue(string $value): bool
    {
        return (bool) preg_match('/^https?:\/\//i', $value);
    }

    private function isPhoneValue(string $value): bool
    {
        return (bool) preg_match('/^\+?[\d\s().-]{7,}$/', $value)
            && preg_match('/\d{3}/', $value);
    }

    private function isDateValue(string $value): bool
    {
        return (bool) preg_match('/^(?:January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2},\s+\d{4}/i', $value)
            || (bool) preg_match('/^\d{4}-\d{2}-\d{2}/', $value);
    }

    private function isNumberValue(string $value): bool
    {
        return (bool) preg_match('/^-?(?:\$)?[\d,]+(?:\.\d+)?%?$/', $value);
    }

    /** @param list<string> $samples */
    private function looksLikeMultiSelect(array $samples): bool
    {
        foreach ($samples as $sample) {
            if (! str_contains($sample, ',')) {
                continue;
            }

            if ($this->isRelationValue($sample)) {
                return false;
            }

            $parts = array_map('trim', explode(',', $sample));
            if (count($parts) > 1 && count(array_filter($parts)) > 1) {
                return true;
            }
        }

        return false;
    }

    private function columnSuggestsDate(string $column): bool
    {
        $lower = strtolower($column);

        return str_contains($lower, 'date')
            || str_contains($lower, 'timeframe')
            || str_contains($lower, 'deadline')
            || str_contains($lower, 'due')
            || str_contains($lower, 'start')
            || str_contains($lower, 'end');
    }

    private function columnSuggestsSelect(string $column): bool
    {
        $lower = strtolower($column);

        if (preg_match('/[\x{1F300}-\x{1FAFF}]/u', $column)) {
            return false;
        }

        return (bool) preg_match('/\b(type|status|priority|category|stage|level|state|kind|rating|zone|region|language|industry|role|title|tag)\b/i', $lower);
    }

    private function columnSuggestsPerson(string $column): bool
    {
        $lower = strtolower($column);

        return str_contains($lower, 'owner')
            || str_contains($lower, 'assignee')
            || str_contains($lower, 'poc')
            || str_contains($lower, 'lead')
            || str_contains($lower, 'manager');
    }

    private function columnSuggestsStatus(string $column): bool
    {
        return str_contains(strtolower($column), 'status');
    }

    private function fallbackFromColumnName(string $column): string
    {
        $lower = strtolower($column);

        if ($known = $this->knownSystemType($column)) {
            return $known;
        }

        if ($lower === 'archive' || str_contains($lower, 'featured') || str_contains($lower, 'important')) {
            return 'checkbox';
        }

        if (str_contains($lower, 'email')) {
            return 'email';
        }

        if (str_contains($lower, 'phone') || str_contains($column, '☎')) {
            return 'phone_number';
        }

        if ($lower === 'website' || ($lower === 'url' && ! str_contains($lower, 'database'))) {
            return 'url';
        }

        if (str_ends_with($lower, ' count')) {
            return 'rollup';
        }

        if (str_contains($lower, 'total value') || str_contains($lower, ' balance')) {
            return 'number';
        }

        if (str_starts_with($lower, 'contacts ') && ! str_contains($lower, 'count')) {
            return 'relation';
        }

        if (str_contains($lower, 'self card')
            || $lower === 'parent item'
            || $lower === 'sub-item'
            || str_contains($lower, 'database link')) {
            return 'relation';
        }

        if (preg_match('/[\x{1F300}-\x{1FAFF}]/u', $column)) {
            return 'relation';
        }

        if (str_contains($lower, 'value') || str_contains($lower, 'amount') || str_contains($lower, 'price')) {
            return 'number';
        }

        if ($this->columnSuggestsDate($column)) {
            return 'date';
        }

        if ($this->columnSuggestsStatus($column)) {
            return 'status';
        }

        if ($this->columnSuggestsPerson($column)) {
            return 'person';
        }

        if ($this->columnSuggestsSelect($column)) {
            return 'select';
        }

        return 'rich_text';
    }
}
