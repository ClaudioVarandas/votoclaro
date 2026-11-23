<?php

namespace Tests\Unit;

use App\Services\BudgetTopicExtractor;
use PHPUnit\Framework\TestCase;

class BudgetTopicExtractorTest extends TestCase
{
    public function test_a_word_in_enough_subjects_is_a_topic_and_a_rare_one_is_not(): void
    {
        $topics = $this->extract([
            1 => 'Reforço das bombeiras',
            2 => 'Bombeiras voluntárias',
            3 => 'Apoio às bombeiras',
            4 => 'Gatos de rua',
        ]);

        $this->assertSame([['slug' => 'bombeiras', 'label' => 'Bombeiras', 'proposal_ids' => [1, 2, 3]]], $topics);
    }

    public function test_plurals_count_with_their_singular(): void
    {
        $topics = $this->extract([
            1 => 'Pensão mínima',
            2 => 'Atualização das pensões',
            3 => 'Pensões antigas',
        ]);

        $this->assertSame(['pensões' => [1, 2, 3]], $this->byLabel($topics, lower: true));
    }

    public function test_synonyms_count_as_one_topic_whatever_the_name_used(): void
    {
        $topics = $this->extract([
            1 => 'Taxa reduzida de IVA',
            2 => 'Código do Imposto sobre o Valor Acrescentado',
            3 => 'Iva da eletricidade',
        ], ['synonyms' => ['imposto sobre o valor acrescentado' => 'IVA', 'valor acrescentado' => 'IVA', 'iva' => 'IVA']]);

        $this->assertSame([1, 2, 3], $this->byLabel($topics)['IVA']);
    }

    public function test_the_budget_jargon_is_never_a_topic(): void
    {
        $topics = $this->extract([
            1 => 'Alteração ao artigo',
            2 => 'Alteração do mapa',
            3 => 'Alteração da verba',
        ], ['stopwords' => ['alteração', 'artigo', 'mapa', 'verba', 'ao', 'do', 'da']]);

        $this->assertSame([], $topics);
    }

    public function test_a_pair_that_holds_most_of_a_word_replaces_it(): void
    {
        $topics = $this->extract([
            1 => 'Saúde mental nas escolas',
            2 => 'Rede de saúde mental',
            3 => 'Saúde mental dos jovens',
            4 => 'Saúde oral',
            5 => 'Saúde materna',
            6 => 'Saúde pública',
        ]);

        $byLabel = $this->byLabel($topics);
        $this->assertSame([1, 2, 3], $byLabel['Saúde mental']);
        $this->assertSame([1, 2, 3, 4, 5, 6], $byLabel['Saúde']);
        $this->assertArrayNotHasKey('Mental', $byLabel);
    }

    public function test_a_pair_can_be_joined_by_de(): void
    {
        $topics = $this->extract([
            1 => 'Abono de família',
            2 => 'Aumento do abono de família',
            3 => 'Abono de família pré-natal',
        ], ['stopwords' => ['aumento', 'do', 'de']]);

        $this->assertSame([1, 2, 3], $this->byLabel($topics)['Abono de família']);
    }

    public function test_a_generic_word_is_no_topic_alone_but_makes_one_in_a_pair(): void
    {
        $topics = $this->extract([
            1 => 'Proteção civil',
            2 => 'Meios da proteção civil',
            3 => 'Proteção civil municipal',
            4 => 'Proteção de dados',
            5 => 'Proteção animal',
        ], ['generic' => ['proteção', 'meio', 'municipal'], 'stopwords' => ['da', 'de']]);

        $byLabel = $this->byLabel($topics);
        $this->assertSame([1, 2, 3], $byLabel['Proteção civil']);
        $this->assertArrayNotHasKey('Proteção', $byLabel);
    }

    public function test_an_acronym_keeps_its_capitals_and_article_numbers_are_left_out(): void
    {
        $topics = $this->extract([
            1 => 'Artigo 119.º-A (AIMA)',
            2 => 'Reforço da AIMA',
            3 => 'AIMA e 12.º-B',
        ], ['stopwords' => ['artigo', 'reforço', 'da', 'e']]);

        $this->assertSame(['AIMA'], array_column($topics, 'label'));
    }

    public function test_topics_come_most_common_first(): void
    {
        $topics = $this->extract([
            1 => 'Ferrovia',
            2 => 'Ferrovia',
            3 => 'Ferrovia e portos',
            4 => 'Portos',
        ], ['min_proposals' => 2]);

        $this->assertSame(['Ferrovia', 'Portos'], array_column($topics, 'label'));
    }

    /**
     * @param  array<int, string>  $subjects
     * @param  array<string, mixed>  $config
     * @return list<array{slug: string, label: string, proposal_ids: list<int>}>
     */
    private function extract(array $subjects, array $config = []): array
    {
        $config += ['min_proposals' => 3, 'pair_share' => 0.6, 'stopwords' => ['de', 'da', 'das', 'dos', 'do', 'nas', 'às', 'e', 'apoio', 'reforço', 'atualização', 'rede'], 'generic' => [], 'synonyms' => []];

        return (new BudgetTopicExtractor($config))->extract($subjects);
    }

    /**
     * @param  list<array{slug: string, label: string, proposal_ids: list<int>}>  $topics
     * @return array<string, list<int>>
     */
    private function byLabel(array $topics, bool $lower = false): array
    {
        return collect($topics)->mapWithKeys(fn (array $topic): array => [$lower ? mb_strtolower($topic['label']) : $topic['label'] => $topic['proposal_ids']])->all();
    }
}
