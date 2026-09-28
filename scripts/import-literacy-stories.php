<?php

require __DIR__.'/../vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

function nodeText(Node $node): string
{
    $text = '';
    $walker = $node->walker();
    while ($event = $walker->next()) {
        if (! $event->isEntering()) continue;
        $child = $event->getNode();
        if ($child instanceof Text) $text .= $child->getLiteral();
        if ($child instanceof Newline) $text .= "\n";
    }
    return trim($text);
}

$source = isset($argv[1]) && $argv[1] !== '--check' ? $argv[1] : __DIR__.'/../resources/data/literacy-assessment-source.md';
$environment = new Environment();
$environment->addExtension(new League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());
$environment->addExtension(new GithubFlavoredMarkdownExtension());
$document = (new MarkdownParser($environment))->parse(file_get_contents($source));
$stories = [];
$index = -1;
$level = null;
foreach ($document->children() as $node) {
    if ($node instanceof Heading) {
        $heading = nodeText($node);
        if (preg_match('/^Story \d+$/', $heading)) {
            $index++;
            $stories[$index] = ['id' => '', 'story_title' => '', 'story_description' => '', 'question_sets' => [], 'answer_guide' => []];
            $level = null;
        } elseif (preg_match('/^(Frustration|Instructional|Independent|Advanced)\b/', $heading, $match)) {
            $level = strtolower($match[1]);
        } else {
            $level = null;
        }
    }
    if ($index < 0) continue;
    if ($node instanceof Paragraph && preg_match('/^Story Title: (.+)\nStory Description:\n(.+)$/s', nodeText($node), $match)) {
        $stories[$index]['story_title'] = trim($match[1]);
        $stories[$index]['id'] = Illuminate\Support\Str::slug($match[1]);
        $stories[$index]['story_description'] = trim($match[2]);
    }
    if ($node instanceof FencedCode && $level) {
        $questions = [];
        foreach (preg_split('/\n\s*\n/', trim($node->getLiteral())) as $block) {
            if (! preg_match('/^Question: (.+)\nA\. (.+)\nB\. (.+)\nC\. (.+)\nD\. (.+)\nCorrect Answer: ([ABCD])\s*$/u', $block, $m)) {
                throw new RuntimeException('Invalid question block in story '.($index + 1));
            }
            $questions[] = ['question' => $m[1], 'answers' => ['A' => $m[2], 'B' => $m[3], 'C' => $m[4], 'D' => $m[5]], 'correct_answer' => $m[6], 'difficulty' => $level];
        }
        if (count($questions) !== 8 || isset($stories[$index]['question_sets'][$level])) throw new RuntimeException('Expected one eight-question set per level.');
        $stories[$index]['question_sets'][$level] = $questions;
    }
    if ($node instanceof Table) {
        $walker = $node->walker();
        while ($event = $walker->next()) {
            if (! $event->isEntering() || ! ($event->getNode() instanceof TableRow)) continue;
            $cells = [];
            foreach ($event->getNode()->children() as $cell) $cells[] = nodeText($cell);
            if (! ctype_digit($cells[0] ?? '')) continue;
            if (count($cells) !== 5) throw new RuntimeException('Expected five answer-guide columns.');
            $stories[$index]['answer_guide'][] = ['number' => (int) $cells[0], 'difficulty' => strtolower(explode('-', explode(' ', $cells[1])[0])[0]), 'skill' => $cells[2], 'correct_answer' => $cells[3], 'explanation' => $cells[4]];
        }
    }
}

$total = 0;
foreach ($stories as $i => $story) {
    $expected = $i === 0 ? 32 : 24;
    $questions = array_merge(...array_values($story['question_sets']));
    if (! $story['story_title'] || ! $story['story_description'] || count($questions) !== $expected || count($story['answer_guide']) !== $expected) throw new RuntimeException('Incomplete story '.($i + 1));
    foreach ($questions as $q => $question) {
        $guide = $story['answer_guide'][$q];
        if ($guide['number'] !== $q + 1 || $guide['correct_answer'] !== $question['correct_answer'] || $guide['difficulty'] !== $question['difficulty']) throw new RuntimeException('Answer-guide mismatch.');
    }
    $total += count($questions);
}
if (count($stories) !== 5 || $total !== 128) throw new RuntimeException('Expected five stories and 128 questions.');
$json = json_encode($stories, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
if (in_array('--check', $argv, true)) {
    if ($json !== file_get_contents(__DIR__.'/../resources/data/literacy-stories.json')) throw new RuntimeException('Story library differs from the source.');
} else {
    file_put_contents(__DIR__.'/../resources/data/literacy-stories.json', $json);
}
echo "Verified five stories, 128 questions, difficulty groups and answer guides.\n";
