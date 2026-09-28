<?php

namespace App\Support;

class WorksheetGames
{
    public const NAMES = [
        'rocket' => 'Rocket Launch',
        'puzzle' => 'Number Puzzle',
        'archer' => 'Divisibility Archer',
    ];

    public static function namesForWorksheet(array $worksheet): array
    {
        return collect($worksheet['pages'])->flatMap(fn ($page) => array_values($page['responses']))
            ->pluck('game.type')->filter(fn ($type) => isset(self::NAMES[$type]))->unique()
            ->map(fn ($type) => self::NAMES[$type])->values()->all();
    }

    public static function forItem(string $text, string $instruction, array $fields): ?array
    {
        $field = $fields[0] ?? [];
        $base = ['field' => $field['key'] ?? '', 'instruction' => $instruction];
        if (($field['key'] ?? '') === 'divisors' && preg_match('/^[\d,]+$/', $text)) {
            return $base + ['type' => 'archer', 'number' => (int) str_replace(',', '', $text), 'options' => $field['options']];
        }
        if (($field['type'] ?? '') === 'radio' && ($field['options'] ?? []) === ['Yes', 'No']
            && preg_match('/^([\d,]+)\s*(?:and|;)\s*(\d+)$/', $text, $match)) {
            return $base + ['type' => 'rocket', 'number' => (int) str_replace(',', '', $match[1]),
                'divisor' => (int) $match[2], 'reason' => in_array('reason', array_column($fields, 'key'), true)];
        }
        if (($field['type'] ?? '') === 'digit' && preg_match('/^([\d,_]+) \(divisible by (\d+)\)$/', $text, $match)
            && substr_count($match[1], '_') === 1) {
            return $base + ['type' => 'puzzle', 'pattern' => str_replace(',', '', $match[1]), 'divisor' => (int) $match[2],
                'smallest' => stripos($instruction, 'smallest') !== false];
        }

        return null;
    }
}
