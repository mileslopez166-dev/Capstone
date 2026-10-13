<?php

namespace App\Support;

class SectionCatalog
{
    private const SECTIONS = [
        'section_a' => 'Magalang',
        'section_b' => 'Masunurin',
        'section_c' => 'Matapat',
        'section_d' => 'Masipag',
    ];

    public static function labels(): array
    {
        return array_values(self::SECTIONS);
    }

    public static function targetKeys(): array
    {
        return array_keys(self::SECTIONS);
    }

    public static function targets(): array
    {
        return self::SECTIONS;
    }

    public static function targetKeyForLabel(?string $label): ?string
    {
        if ($label === null) {
            return null;
        }

        $key = array_search($label, self::SECTIONS, true);

        return $key === false ? null : $key;
    }

    public static function labelForTarget(?string $target): ?string
    {
        return self::SECTIONS[$target] ?? null;
    }

    public static function displayTarget(?string $target): string
    {
        return match ($target) {
            'all', null => 'All Sections',
            default => self::labelForTarget($target) ?? 'Unassigned',
        };
    }
}
