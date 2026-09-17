<?php

namespace App\Support;

use App\Models\AssessmentProgress;
use App\Models\AssessmentSubmission;

class PracticeSuggestions
{
    public static function forSubmission(AssessmentSubmission $submission): array
    {
        $assessment = $submission->assessment;
        $questions = [];
        foreach ($assessment->manual_questions ?? [] as $index => $question) {
            $answer = $submission->answers[$index] ?? null;
            if (isset($question['correct_answer'], $question['answers'][$question['correct_answer']])
                && $answer !== $question['correct_answer']) {
                $questions['q'.$index] = [
                    'question' => $question['question'], 'answers' => $question['answers'],
                    'correct_answer' => $question['correct_answer'], 'original_answer' => $answer,
                ];
            }
        }

        $words = [];
        $story = $assessment->story_description ?: $assessment->instructions;
        $state = AssessmentProgress::where('submission_id', $submission->id)->value('state') ?? [];
        $marks = $state['word_marks'] ?? [];
        $wordIndex = 0;
        // Match the assessment reader's paragraph/sentence/token order exactly.
        foreach (preg_split('/\R{2,}/u', trim($story ?? '')) as $paragraph) {
            if (trim($paragraph) === '') {
                continue;
            }
            preg_match_all('/[^.!?]+[.!?]+|[^.!?]+$/u', $paragraph, $matches);
            foreach ($matches[0] ?: [$paragraph] as $sentence) {
                $sentence = trim($sentence);
                if ($sentence === '') {
                    continue;
                }
                foreach (preg_split('/(\s+)/u', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE) as $token) {
                    if (trim($token) === '') {
                        continue;
                    }
                    $mark = (int) ($marks[$wordIndex++] ?? 0);
                    $word = preg_replace('/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/u', '', $token);
                    if ($assessment->assessment_type !== 'oral_reading' || !in_array($mark, [1, 2], true) || $word === '') {
                        continue;
                    }
                    $key = mb_strtolower($word);
                    if (!isset($words[$key]) || $words[$key]['mark'] < $mark) {
                        $words[$key] = ['word' => $word, 'context' => $sentence, 'mark' => $mark];
                    }
                }
            }
        }

        $candidates = $questions;
        foreach (array_values($words) as $index => $word) {
            $candidates['w'.$index] = $word;
        }

        return $candidates;
    }
}
