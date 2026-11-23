<?php

namespace App\Services;

/**
 * Finds what the amendments of a budget are about from the words of their subjects: the words and
 * word pairs that come up in many of them, once the budget's jargon, plurals and synonyms are set
 * aside (see config/budget_topics.php). Nobody keeps a list of topics: they follow the titles.
 */
class BudgetTopicExtractor
{
    private const CONNECTORS = ['de', 'da', 'do', 'das', 'dos'];

    /** @var array<string, true> */
    private array $stopwords;

    /** @var array<string, true> */
    private array $generic;

    /** @var list<string> the topic of each synonym, by the index its token carries */
    private array $synonymLabels = [];

    private ?string $synonymPattern = null;

    /** @var array<string, int> the index of each synonym, by its lower-case name */
    private array $synonymIndex = [];

    /**
     * @param  array{min_proposals?: int, pair_share?: float, stopwords?: list<string>, generic?: list<string>, synonyms?: array<string, string>}  $config
     */
    public function __construct(private array $config = [])
    {
        $this->config = $config ?: (array) config('budget_topics');
        $this->stopwords = array_fill_keys(array_map(mb_strtolower(...), $this->config['stopwords'] ?? []), true);
        $this->generic = array_fill_keys(array_map(mb_strtolower(...), $this->config['generic'] ?? []), true);

        $synonyms = $this->config['synonyms'] ?? [];
        uksort($synonyms, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        if ($synonyms !== []) {
            $this->synonymLabels = array_values($synonyms);
            $this->synonymPattern = '/(?<![\p{L}\d])('.implode('|', array_map(fn (string $name): string => preg_quote($name, '/'), array_keys($synonyms))).')(?![\p{L}\d])/iu';
            $this->synonymIndex = array_flip(array_map(mb_strtolower(...), array_keys($synonyms)));
        }
    }

    /**
     * @param  iterable<int, string>  $subjects  subject by proposal id
     * @return list<array{slug: string, label: string, proposal_ids: list<int>}> most common first
     */
    public function extract(iterable $subjects): array
    {
        $words = [];
        $pairs = [];

        foreach ($subjects as $proposalId => $subject) {
            $tokens = $this->tokens($subject);

            foreach ($this->wordsOf($tokens) as $key => $word) {
                $words[$key]['ids'][$proposalId] = true;
                $words[$key]['labels'][$word['label']] = ($words[$key]['labels'][$word['label']] ?? 0) + 1;
                $words[$key]['generic'] = $word['generic'];
            }

            foreach ($this->pairsOf($tokens) as $key => $pair) {
                $pairs[$key]['ids'][$proposalId] = true;
                $pairs[$key]['labels'][$pair['label']] = ($pairs[$key]['labels'][$pair['label']] ?? 0) + 1;
                $pairs[$key]['words'] = $pair['words'];
            }
        }

        $minimum = (int) ($this->config['min_proposals'] ?? 5);
        $share = (float) ($this->config['pair_share'] ?? 0.6);
        $topics = [];
        $absorbed = [];

        foreach ($pairs as $key => $pair) {
            $count = count($pair['ids']);
            $wordCounts = array_map(fn (string $word): int => count($words[$word]['ids'] ?? []), $pair['words']);

            if ($count < $minimum || $count < $share * min($wordCounts)) {
                continue;
            }

            $topics[$key] = $pair;

            foreach ($pair['words'] as $index => $word) {
                if ($count >= $share * $wordCounts[$index]) {
                    $absorbed[$word] = true;
                }
            }
        }

        foreach ($words as $key => $word) {
            if (count($word['ids']) >= $minimum && ! $word['generic'] && ! isset($absorbed[$key])) {
                $topics[$key] = $word;
            }
        }

        $result = [];

        foreach ($topics as $topic) {
            arsort($topic['labels']);
            $label = $this->display((string) array_key_first($topic['labels']));
            $slug = str($label)->slug()->toString();

            if ($slug === '' || isset($result[$slug])) {
                continue;
            }

            $result[$slug] = ['slug' => $slug, 'label' => $label, 'proposal_ids' => array_keys($topic['ids'])];
        }

        usort($result, fn (array $a, array $b): int => [count($b['proposal_ids']), $a['label']] <=> [count($a['proposal_ids']), $b['label']]);

        return $result;
    }

    /**
     * The words of a subject, a synonym standing as one token ("§3§").
     *
     * @return list<string>
     */
    private function tokens(string $subject): array
    {
        if ($this->synonymPattern !== null) {
            $subject = preg_replace_callback(
                $this->synonymPattern,
                fn (array $match): string => ' §'.$this->synonymIndex[mb_strtolower($match[1])].'§ ',
                $subject,
            );
        }

        preg_match_all('/§\d+§|\p{L}[\p{L}\-.ºª]*/u', $subject, $matches);

        return array_values(array_filter(array_map(fn (string $token): string => rtrim($token, '.-'), $matches[0])));
    }

    /**
     * @param  list<string>  $tokens
     * @return array<string, array{label: string, generic: bool}> by key
     */
    private function wordsOf(array $tokens): array
    {
        $words = [];

        foreach ($tokens as $token) {
            if ($word = $this->word($token)) {
                $words[$word['key']] = ['label' => $word['label'], 'generic' => $word['generic']];
            }
        }

        return $words;
    }

    /**
     * Two words in a row ("saúde mental"), or joined by "de" ("abono de família").
     *
     * @param  list<string>  $tokens
     * @return array<string, array{label: string, words: array{string, string}}>
     */
    private function pairsOf(array $tokens): array
    {
        $pairs = [];
        $count = count($tokens);

        for ($i = 0; $i < $count - 1; $i++) {
            $first = $this->word($tokens[$i], allowSynonym: false);

            if ($first === null) {
                continue;
            }

            $next = $tokens[$i + 1];
            $connector = in_array(mb_strtolower($next), self::CONNECTORS, true) && $i + 2 < $count ? mb_strtolower($next) : null;
            $second = $this->word($connector !== null ? $tokens[$i + 2] : $next, allowSynonym: false);

            if ($second === null || $second['key'] === $first['key']) {
                continue;
            }

            $key = implode(' ', array_filter([$first['key'], $connector, $second['key']]));
            $pairs[$key] = [
                'label' => implode(' ', array_filter([$first['label'], $connector, $second['label']])),
                'words' => [$first['key'], $second['key']],
            ];
        }

        return $pairs;
    }

    /**
     * @return array{key: string, label: string, generic: bool}|null
     */
    private function word(string $token, bool $allowSynonym = true): ?array
    {
        if (preg_match('/^§(\d+)§$/', $token, $match)) {
            if (! $allowSynonym) {
                return null;
            }

            $label = $this->synonymLabels[(int) $match[1]];

            return ['key' => '§'.mb_strtolower($label), 'label' => $label, 'generic' => false];
        }

        if (preg_match('/^[ºª]/u', $token)) {
            return null;
        }

        $lower = mb_strtolower($token);
        $singular = $this->singular($lower);

        if (mb_strlen($lower) < 3 || isset($this->stopwords[$lower]) || isset($this->stopwords[$singular])) {
            return null;
        }

        $isAcronym = mb_strlen($token) <= 5 && $token === mb_strtoupper($token);

        return [
            'key' => $singular,
            'label' => $isAcronym ? $token : $lower,
            'generic' => isset($this->generic[$lower]) || isset($this->generic[$singular]),
        ];
    }

    /**
     * A rough singular, enough to count "escolas" with "escola" and "pensões" with "pensão".
     */
    private function singular(string $word): string
    {
        return match (true) {
            str_ends_with($word, 'ões'), str_ends_with($word, 'ães') => mb_substr($word, 0, -3).'ão',
            str_ends_with($word, 'ais') => mb_substr($word, 0, -3).'al',
            str_ends_with($word, 'éis'), str_ends_with($word, 'eis') => mb_substr($word, 0, -3).'el',
            (bool) preg_match('/[aeiouáéíóúâêô][rzs]es$/u', $word) => mb_substr($word, 0, -2),
            str_ends_with($word, 'ns') => mb_substr($word, 0, -2).'m',
            (bool) preg_match('/[aeiou]s$/', $word) => mb_substr($word, 0, -1),
            default => $word,
        };
    }

    private function display(string $label): string
    {
        return mb_strtoupper(mb_substr($label, 0, 1)).mb_substr($label, 1);
    }
}
