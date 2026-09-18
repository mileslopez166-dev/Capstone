<?php

namespace App\Support;

class NumeracyWorksheets
{
    public static function all(): array
    {
        $worksheets = json_decode(file_get_contents(resource_path('data/numeracy-worksheets.json')), true, 512, JSON_THROW_ON_ERROR)['worksheets'];
        $text = json_decode(file_get_contents(resource_path('data/numeracy-text.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($worksheets as &$worksheet) {
            foreach ($worksheet['pages'] as &$page) {
                $page['reading'] = $text[$worksheet['number']][$page['part'] - 1];
                $page['responses'] = WorksheetResponses::forPage($worksheet['number'], $page);
            }
            unset($page);
        }
        return $worksheets;
    }

    public static function find(int $number): array
    {
        return collect(self::all())->firstWhere('number', $number) ?? abort(404);
    }

    public static function pageConfig(array $worksheet): array
    {
        return array_map(fn ($page) => [
            'url' => route('worksheets.image', [$worksheet['number'], $page['part']]),
            'alt' => 'Worksheet '.$worksheet['number'].', Part '.$page['part'],
            'readingOnly' => $page['reading']['reading_only'] ?? false,
            'responses' => $page['responses'],
        ], $worksheet['pages']);
    }

    public static function grids(array $page): array
    {
        $grids = [];
        foreach ($page['reading']['sections'] as $sectionIndex => $section) {
            foreach ($section['items'] ?? [] as $itemIndex => $item) {
                if (is_array($item) && isset($item['grid'])) $grids[$sectionIndex.'-'.$itemIndex] = $item['grid'][0] * $item['grid'][1];
            }
        }
        return $grids;
    }
}
