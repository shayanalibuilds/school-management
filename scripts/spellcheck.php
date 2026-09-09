#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Spell-check the human copy of the project with aspell (en_US).
 *
 * Scans the README, every docs/*.md file and the visible text of all
 * blade views, while ignoring code: fenced blocks, inline code, URLs,
 * Volt/blade PHP, HTML tags and identifier-style attribute values.
 *
 * Words are checked in lowercase (aspell's dictionary lookups are case
 * sensitive), so .aspell.en_US.pws stores lowercase vocabulary and one
 * entry covers every casing the copy may use.
 *
 * Usage:
 *   composer test:spell        (via composer.json)
 *   php scripts/spellcheck.php
 *
 * Exit codes: 0 = clean, 1 = unknown words found or aspell missing.
 */
const FALLBACK_BIN = '/home/z/.local/bin/aspell';

$root = dirname(__DIR__);

// Runs as a standalone script: pull in the composer autoloader so the
// symfony/polyfill-php84 functions (mb_trim, mb_ltrim) exist on PHP 8.3.
if (is_file($autoload = $root.'/vendor/autoload.php')) {
    require $autoload;
}

$files = collectFiles($root);
[$wordsByFile, $originals, $linesByFile] = extractWords($root, $files);

$unknown = runAspell(array_keys($wordsByFile), $root);
if ($unknown === null) {
    fwrite(STDERR, "aspell with the en_US dictionary is required but was not found.\n");
    fwrite(STDERR, 'Install aspell + aspell-en (Debian/Ubuntu: apt install aspell aspell-en).'.PHP_EOL);

    exit(1);
}

$total = 0;
$report = [];
foreach ($unknown as $bad) {
    foreach ($wordsByFile[$bad] ?? [] as $loc) {
        $report[$loc][] = $originals[$bad] ?? $bad;
        $total++;
    }
}

if ($report === []) {
    echo 'PASS No misspellings found in '.count($files).' files (aspell en_US).'.PHP_EOL;

    exit(0);
}

foreach ($report as $location => $words) {
    foreach (array_unique($words) as $word) {
        [$file, $line] = explode(':', $location);
        echo '  '.$word.'  —  '.$file.':'.$line.': '.mb_trim(mb_substr($linesByFile[$location], 0, 96)).PHP_EOL;
    }
}
echo PHP_EOL.'FAIL '.$total.' misspelling occurrence(s) across '.count($report).' location(s).'.PHP_EOL;
echo 'Fix the copy, or extend .aspell.en_US.pws for legitimate project vocabulary.'.PHP_EOL;

exit(1);

/** @return array<int, string> */
function collectFiles(string $root): array
{
    $paths = array_values(array_filter(array_map(
        fn (string $f): string => str_replace($root.'/', '', $f),
        array_merge(
            glob($root.'/*.md') ?: [],
            glob($root.'/docs/**/*.md') ?: [],
            glob($root.'/resources/views/**/*.blade.php') ?: [],
        ),
    )));
    sort($paths);

    return $paths;
}

/**
 * Extract checkable words per file, keeping every original line intact so
 * reported line numbers match the file. Returns:
 *   [ lowercase word => ["file:line", ...], lowercase word => original,
 *     "file:line" => original line ]
 *
 * @return array{0: array<string, list<string>>, 1: array<string, string>, 2: array<string, string>}
 */
function extractWords(string $root, array $files): array
{
    $wordsByFile = [];
    $originals = [];
    $linesByFile = [];

    foreach ($files as $file) {
        $text = file_get_contents($root.'/'.$file);

        $text = str_contains($file, '.blade.php')
            ? stripBlade($text)
            : stripMarkdown($text);

        foreach (preg_split("/\r\n|\n|\r/", $text) ?: [] as $i => $line) {
            $location = $file.':'.($i + 1);
            $linesByFile[$location] = $line;

            preg_match_all("/[A-Za-z][A-Za-z'’\-]{1,}/", $line, $matches);
            foreach ($matches[0] as $word) {
                $key = mb_strtolower($word);
                $wordsByFile[$key][] = $location;
                $originals[$key] ??= $word;
            }
        }
    }

    return [$wordsByFile, $originals, $linesByFile];
}

/** Strip markdown syntax but keep prose and line structure. */
function stripMarkdown(string $text): string
{
    $text = preg_replace('/```.*?```/s', '', $text) ?? $text;              // fenced blocks
    $text = preg_replace('/~~~.*?~~~/s', '', $text) ?? $text;
    $text = preg_replace('/`[^`\n]*`/', ' ', $text) ?? $text;              // inline code
    $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', ' ', $text) ?? $text;   // images
    $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text) ?? $text; // links -> label
    $text = preg_replace('/<[^>]+>/', ' ', $text) ?? $text;                // raw HTML
    $text = preg_replace('/https?:\/\/\S+/', ' ', $text) ?? $text;         // bare URLs
    $text = preg_replace('/^\s{0,3}(#{1,6})\s+/m', ' ', $text) ?? $text;   // headings
    $text = preg_replace('/^\s*>\s?/m', ' ', $text) ?? $text;              // quotes
    $text = preg_replace('/^\s*[-*+]\s+(?=\S)/m', ' ', $text) ?? $text;    // bullets
    $text = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $text) ?? $text;      // bold
    $text = preg_replace('/^\s*\|.*\|\s*$/m', ' ', $text) ?? $text;        // table rows (noisy)
    $text = preg_replace('/^\s*[-=]{3,}\s*$/m', ' ', $text) ?? $text;      // hr / setext
    $text = preg_replace('/\{#[^#]*#\}/', ' ', $text) ?? $text;            // kramdown attrs

    return $text;
}

/** Strip Volt/blade/HTML syntax but keep visible copy and line structure. */
function stripBlade(string $text): string
{
    // Volt SFCs open with a <?php class header that has no closing tag;
    // everything before the first HTML tag at column 0 is code.
    if (preg_match('/^<\?php/', mb_ltrim($text))) {
        $text = (string) preg_replace('/^.*?^<[a-zA-Z!]/sm', '<', $text);
    }

    $text = preg_replace('/<\?php.*?\?>/s', '', $text) ?? $text;               // inline php blocks
    $text = preg_replace('/\{\{--.*?--\}\}/s', '', $text) ?? $text;            // blade comments
    $text = preg_replace('/@php(.*?)@endphp/s', '', $text) ?? $text;           // php directives
    $text = preg_replace('/@php\s*\([^)]*\)/s', '', $text) ?? $text;
    $text = preg_replace('/@verbatim(.*?)@endverbatim/s', '', $text) ?? $text;
    $text = preg_replace('/\{\{\{.*?\}\}\}/s', ' ', $text) ?? $text;           // raw echo
    $text = preg_replace('/\{\{.*?\}\}/s', ' ', $text) ?? $text;               // echo
    $text = preg_replace('/\{!!.*?!!\}/s', ' ', $text) ?? $text;
    $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $text) ?? $text;
    $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $text) ?? $text;

    // Attribute values: drop identifier-heavy ones, keep copy-bearing ones.
    $keepCopy = 'alt|aria-label|title|placeholder';
    $text = preg_replace_callback(
        '/(\s[\w:.-]+)=("[^"]*"|\'[^\']*\')/i',
        fn (array $m): string => preg_match('/^('.$keepCopy.')$/i', $m[1])
            ? $m[0]
            : ' '.$m[1].'=""',
        $text,
    ) ?? $text;

    $text = preg_replace('/<[^>]+>/', ' ', $text) ?? $text;                    // remaining tags
    $text = preg_replace('/^([ \t]*)@(\w+)\s*(\((?:[^()]|\([^()]*\))*\))?/m', '$1', $text) ?? $text; // directives
    $text = preg_replace('/@(\w+)\s*(\((?:[^()]|\([^()]*\))*\))?/', ' ', $text) ?? $text; // inline leftovers

    return $text;
}

/** Run aspell over the lowercase words; return the set of rejected words. */
function runAspell(array $words, string $root): ?array
{
    $binary = findAspell();
    if ($binary === null) {
        return null;
    }

    $cmd = escapeshellarg($binary)
        .' list --lang=en_US --encoding=utf-8'
        .' --personal='.escapeshellarg($root.'/.aspell.en_US.pws');

    $process = proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    if (! is_resource($process)) {
        return null;
    }

    fwrite($pipes[0], implode("\n", $words));
    fclose($pipes[0]);

    $out = stream_get_contents($pipes[1]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return array_values(array_filter(explode("\n", mb_trim($out))));
}

function findAspell(): ?string
{
    foreach ([getenv('ASPELL_BIN'), 'aspell', FALLBACK_BIN] as $candidate) {
        if (empty($candidate)) {
            continue;
        }
        $test = str_contains($candidate, '/')
            ? $candidate
            : mb_trim((string) shell_exec('command -v '.escapeshellarg($candidate).' 2>/dev/null'));
        if ($test !== '' && is_executable($test)) {
            return $test;
        }
    }

    return null;
}
