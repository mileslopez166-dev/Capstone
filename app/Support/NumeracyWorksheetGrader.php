<?php

namespace App\Support;

class NumeracyWorksheetGrader
{
    public static function scoreableCount(array $worksheet): int
    {
        return collect($worksheet['pages'] ?? [])
            ->sum(fn (array $page): int => collect($page['responses'] ?? [])->whereNotNull('game')->count());
    }

    public static function grade(array $worksheet, array $submittedPages): ?array
    {
        $hasStructuredResponses = collect($submittedPages)
            ->contains(fn (array $page): bool => ! empty($page['responses'] ?? []));
        if (! $hasStructuredResponses) {
            return null;
        }

        $items = [];

        foreach ($worksheet['pages'] ?? [] as $pageIndex => $page) {
            foreach ($page['responses'] ?? [] as $key => $definition) {
                $game = $definition['game'] ?? null;
                if (! is_array($game)) {
                    return null;
                }

                $response = $submittedPages[$pageIndex]['responses'][$key] ?? [];
                $result = self::evaluate($game, is_array($response) ? $response : []);
                [$sectionIndex, $itemIndex] = array_map('intval', explode('-', (string) $key, 2));
                $source = $page['reading']['sections'][$sectionIndex]['items'][$itemIndex] ?? '';
                $question = is_array($source) ? (string) ($source['text'] ?? '') : (string) $source;

                $items[] = [
                    'label' => (string) ($definition['label'] ?? count($items) + 1),
                    'question' => $question,
                    ...$result,
                ];
            }
        }

        if ($items === []) {
            return null;
        }

        $correct = count(array_filter($items, fn (array $item): bool => $item['is_correct']));

        return [
            'correct' => $correct,
            'incorrect' => count($items) - $correct,
            'total' => count($items),
            'items' => $items,
        ];
    }

    private static function evaluate(array $game, array $response): array
    {
        $timedOut = filter_var($response['__timed_out'] ?? false, FILTER_VALIDATE_BOOL);

        return match ($game['type'] ?? null) {
            'archer' => self::evaluateDivisors($game, $response, $timedOut),
            'rocket' => self::evaluateDivisibility($game, $response, $timedOut),
            'puzzle' => self::evaluateMissingDigit($game, $response, $timedOut),
            default => throw new \LogicException('Unsupported numeracy worksheet game.'),
        };
    }

    private static function evaluateDivisors(array $game, array $response, bool $timedOut): array
    {
        $options = array_map('strval', $game['options'] ?? []);
        $expected = array_values(array_filter($options, fn (string $option): bool => (int) $option !== 0 && (int) $game['number'] % (int) $option === 0));
        $selected = array_values(array_unique(array_map('strval', is_array($response['divisors'] ?? null) ? $response['divisors'] : [])));
        sort($expected, SORT_NUMERIC);
        sort($selected, SORT_NUMERIC);

        return self::result(
            ! $timedOut && $selected === $expected,
            $timedOut ? 'Time expired' : self::listAnswer($selected),
            self::listAnswer($expected),
            $timedOut
        );
    }

    private static function evaluateDivisibility(array $game, array $response, bool $timedOut): array
    {
        $expected = (int) $game['divisor'] !== 0 && (int) $game['number'] % (int) $game['divisor'] === 0 ? 'Yes' : 'No';
        $selected = trim((string) ($response['choice'] ?? ''));

        return self::result(
            ! $timedOut && strcasecmp($selected, $expected) === 0,
            $timedOut ? 'Time expired' : ($selected !== '' ? $selected : 'No answer'),
            $expected,
            $timedOut
        );
    }

    private static function evaluateMissingDigit(array $game, array $response, bool $timedOut): array
    {
        $accepted = [];
        foreach (range(0, 9) as $digit) {
            if ($digit === 0 && str_starts_with((string) $game['pattern'], '_') && strlen((string) $game['pattern']) > 1) {
                continue;
            }
            $number = (int) str_replace('_', (string) $digit, (string) $game['pattern']);
            if ((int) $game['divisor'] !== 0 && $number % (int) $game['divisor'] === 0) {
                $accepted[] = (string) $digit;
                if ($game['smallest'] ?? false) {
                    break;
                }
            }
        }
        $selected = trim((string) ($response['digit'] ?? ''));

        return self::result(
            ! $timedOut && in_array($selected, $accepted, true),
            $timedOut ? 'Time expired' : ($selected !== '' ? $selected : 'No answer'),
            self::listAnswer($accepted, ' or '),
            $timedOut
        );
    }

    private static function result(bool $correct, string $studentAnswer, string $correctAnswer, bool $timedOut): array
    {
        return [
            'student_answer' => $studentAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $correct,
            'timed_out' => $timedOut,
        ];
    }

    private static function listAnswer(array $values, string $separator = ', '): string
    {
        return $values === [] ? 'None' : implode($separator, $values);
    }
}
