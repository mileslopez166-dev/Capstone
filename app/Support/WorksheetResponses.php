<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorksheetResponses
{
    public static function forPage(int $number, array $page): array
    {
        $items = [];
        foreach ($page['reading']['sections'] as $sectionIndex => $section) {
            foreach ($section['items'] ?? [] as $index => $item) {
                $label = is_array($item) ? ($item['label'] ?? ($section['start'] ?? 1) + $index) : ($section['start'] ?? 1) + $index;
                $items[$sectionIndex.'-'.$index] = ['label' => (string) $label, 'fields' => self::fields($number, $page['part'], $sectionIndex, $index)];
            }
        }
        return $items;
    }

    private static function field(string $key, string $label, string $type = 'text', array $extra = []): array
    {
        return array_merge(compact('key', 'label', 'type'), $extra);
    }

    private static function fields(int $number, int $part, int $section, int $index): array
    {
        $f = fn ($key, $label, $type = 'text', $extra = []) => self::field($key, $label, $type, $extra);
        $work = $f('working', 'Solution', 'textarea');
        $result = $f('answer', 'Final answer', 'number');
        $gcf = $f('gcf', 'GCF', 'number');
        $lcm = $f('lcm', 'LCM', 'number');
        $yesNo = $f('choice', 'Divisible?', 'radio', ['options' => ['Yes', 'No']]);
        $fraction = [$f('whole', 'Whole number (if needed)', 'integer'), $f('numerator', 'Numerator', 'integer'), $f('denominator', 'Denominator', 'integer', ['min' => 1])];
        $volume = [$f('volume', 'Volume', 'number'), $f('unit', 'Volume unit', 'select', ['options' => ['cubic mm', 'cubic cm', 'cubic m', 'cubic inches', 'cubic feet']])];
        $drawing = $f('drawing', 'Drawing', 'drawing');
        $divisors = fn ($values) => [$f('divisors', 'Divisible by', 'checkbox', ['options' => array_map('strval', $values)])];
        if ($number === 1) return $part === 2 && $section === 1 ? [$f('digit', 'Missing digit', 'digit')] : $divisors([2, 3, 5, 9, 10]);
        if ($number === 2) return $divisors($part === 2 && $section === 1 ? [2, 3, 4, 5, 6, 8, 9, 10] : [4, 6, 8]);
        if ($number === 3) return [$yesNo, $f('reason', 'Justification', 'textarea')];
        if ($number === 4) return $part === 1 && $section === 0 ? [$yesNo] : $divisors([2, 3, 4, 5, 6, 8, 9, 10, 11, 12]);
        if ($number >= 5 && $number <= 8) return [$work, $result];
        if ($number === 9) return [$f('working', 'Continuous division', 'textarea'), $gcf];
        if ($number === 10 || ($number === 11 && $part === 1)) return [$work, $lcm];
        if ($number === 11) return [$f('product', 'Product', 'number'), $gcf, $lcm];
        if ($number === 12) return [$f('working', 'Division', 'textarea'), $gcf, $lcm];
        if ($number === 13) {
            if ($part === 1 || $section === 0) return [$gcf, $lcm, $work];
            $labels = [
                ['Number of bouquets', 'Flowers of each color per bouquet'], ['Seconds until all blink together'],
                ['Least number of toys'], ['Number of bags', 'Contents of each bag'],
                ['Number of stickers', 'Number of glitter pens'], ['Number of baskets', 'Fruits in each basket'],
                ['Time at the same bus stop'],
            ];
            return [...array_map(fn ($label, $i) => $f('answer'.$i, $label), $labels[$index], array_keys($labels[$index])), $work];
        }
        if (in_array($number, [14, 19])) return $fraction;
        if (($number === 17 || $number === 23) && $part === 2) return [$work, $f('answer', 'Answer with units')];
        if ($number >= 15 && $number <= 24) {
            $needsWork = !in_array($number, [17, 18, 24]) && !($number === 20 && $part === 1) && !($number === 23 && $part === 1);
            return $needsWork ? [...$fraction, $work] : $fraction;
        }
        if ($number === 25) {
            $kinds = [1 => ['sides', 'sides', 'sides', 'name', 'sides', 'draw'], 2 => ['sides', 'draw', 'sides', 'name', 'draw', 'sides'], 3 => ['name', 'draw', 'sides', 'sides', 'sides', 'draw']];
            return match ($kinds[$part][$index]) {
                'draw' => [$drawing], 'name' => [$f('name', 'Polygon name')], default => [$f('sides', 'Number of sides', 'integer', ['min' => 1])],
            };
        }
        if ($number === 26) return [$f('choice', 'Figure type', 'radio', ['options' => ['Regular', 'Irregular']])];
        if ($number >= 27 && $number <= 29) {
            $twelve = $number === 29 && $section === 1;
            $fields = [$f('hour', $twelve ? 'Hour (1-12)' : 'Hour (00-23)', 'integer', ['min' => $twelve ? 1 : 0, 'max' => $twelve ? 12 : 23]), $f('minute', 'Minute (00-59)', 'integer', ['min' => 0, 'max' => 59])];
            return $twelve ? [...$fields, $f('period', 'AM / PM', 'select', ['options' => ['AM', 'PM']])] : $fields;
        }
        if ($number === 30 || $number === 31) return [$f('area', 'Area', 'number'), $f('unit', 'Area unit', 'select', ['options' => ['square mm', 'square cm', 'square m', 'square inches', 'square feet', 'square units']]), $work];
        if ($number === 32) {
            if (($part === 1 && $section === 3) || ($part === 2 && $section === 2)) return [$drawing, $f('labels', 'Dimension labels'), ...$volume];
            if ($part === 1 && $section === 1) return [$f('length', 'Length with units'), $f('width', 'Width with units'), $f('height', 'Height with units'), ...$volume];
            return [...$volume, $work];
        }
        if ($number === 33 || $number === 35) return [$f('temperature', 'Temperature (Celsius)', 'number')];
        if ($number === 34) return [$f('temperature', 'Temperature mark', 'temperature', ['min' => -30, 'max' => 50])];
        throw new \LogicException('Missing worksheet response format.');
    }

    public static function validate(array $responses, array $definitions): array
    {
        abort_unless(count($responses) <= count($definitions), 422, 'Invalid worksheet items.');
        foreach ($responses as $key => $values) {
            abort_unless(isset($definitions[$key]) && is_array($values), 422, 'Invalid worksheet item.');
            $fields = collect($definitions[$key]['fields'])->keyBy('key');
            abort_unless(!array_diff(array_keys($values), $fields->keys()->all()), 422, 'Invalid answer field.');
            $rules = [];
            foreach ($fields as $name => $field) {
                $type = $field['type'];
                if ($type === 'drawing') {
                    $rules[$name] = ['sometimes', 'array', 'max:100'];
                    $rules[$name.'.*'] = ['array:color,width,points'];
                    $rules[$name.'.*.color'] = ['required', Rule::in(['#174d97', '#252c35', '#d73742'])];
                    $rules[$name.'.*.width'] = ['required', 'numeric', 'between:1,12'];
                    $rules[$name.'.*.points'] = ['required', 'array', 'min:1', 'max:1000'];
                    $rules[$name.'.*.points.*'] = ['array', 'size:2'];
                    $rules[$name.'.*.points.*.*'] = ['required', 'numeric', 'between:0,1'];
                } elseif ($type === 'checkbox') {
                    $rules[$name] = ['sometimes', 'array', 'max:'.count($field['options'])];
                    $rules[$name.'.*'] = ['string', 'distinct', Rule::in($field['options'])];
                } else {
                    $limit = $type === 'textarea' ? 2000 : 200;
                    $rules[$name] = ['sometimes', 'nullable', 'string', function ($attribute, $value, $fail) use ($limit) {
                        if (is_string($value) && mb_strlen($value) > $limit) $fail('This answer is too long.');
                    }];
                    if (isset($field['options'])) $rules[$name][] = Rule::in($field['options']);
                    if (in_array($type, ['integer', 'temperature'])) $rules[$name][] = 'regex:/^-?\d+$/';
                    if ($type === 'digit') $rules[$name][] = 'regex:/^\d$/';
                    if ($type === 'number') $rules[$name][] = 'numeric';
                    foreach (['min', 'max'] as $bound) {
                        if (isset($field[$bound])) {
                            $rules[$name][] = 'numeric';
                            $rules[$name][] = $bound.':'.$field[$bound];
                        }
                    }
                }
            }
            $values = Validator::make($values, $rules)->validate();
            foreach ($values as $name => &$value) {
                if ($value === null) $value = '';
                if (!is_array($value)) continue;
                abort_unless(array_is_list($value), 422, 'Invalid answer list.');
                if ($fields[$name]['type'] === 'drawing') foreach ($value as $stroke) {
                    abort_unless(array_is_list($stroke['points']), 422, 'Invalid drawing.');
                    foreach ($stroke['points'] as $point) abort_unless(array_keys($point) === [0, 1], 422, 'Invalid drawing point.');
                }
            }
            unset($value);
            $responses[$key] = $values;
        }
        return $responses;
    }

    public static function hasAnswer(array $responses): bool
    {
        foreach ($responses as $fields) foreach ($fields as $value) {
            if (is_array($value) ? count($value) > 0 : trim((string) $value) !== '') return true;
        }
        return false;
    }
}
