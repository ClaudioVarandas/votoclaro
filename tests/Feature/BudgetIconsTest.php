<?php

namespace Tests\Feature;

use App\View\Components\BudgetOutcomeIcon;
use App\View\Components\BudgetOutcomeStat;
use App\View\Components\BudgetThemeIcon;
use App\View\Components\ProgressBar;
use App\View\Components\ShareBar;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BudgetIconsTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function themes(): array
    {
        return [
            'health' => ['Saúde', 'heart'],
            'housing' => ['Infraestruturas, Mobilidade e Habitação', 'home'],
            'budget before public administration' => ['Orçamento, Finanças e Administração Pública', 'currency-euro'],
            'rights' => ['Assuntos Constitucionais, Direitos, Liberdades e Garantias', 'scale'],
            'local power' => ['Reforma do Estado e Poder Local', 'building-library'],
            'foreign affairs' => ['Negócios Estrangeiros e Comunidades Portuguesas', 'globe'],
            'a committee renamed in a later legislature' => ['Educação, Ciência, Juventude e Desporto', 'academic-cap'],
            'an unknown theme' => ['Assuntos Parlamentares', 'tag'],
            'a word only inside another one' => ['Marketing', 'tag'],
        ];
    }

    #[DataProvider('themes')]
    public function test_a_theme_gets_the_icon_of_the_words_in_its_name(string $theme, string $icon): void
    {
        $this->assertSame($icon, (new BudgetThemeIcon($theme))->icon());
    }

    public function test_the_theme_icon_draws_its_path_and_is_hidden_from_screen_readers(): void
    {
        $this->blade('<x-budget-theme-icon theme="Saúde" class="size-4" />')
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('class="shrink-0 size-4"', false)
            ->assertSee('M21 8.25c0-2.485', false);
    }

    public function test_each_outcome_has_its_own_color(): void
    {
        $this->assertStringContainsString('republic', (new BudgetOutcomeIcon('approved'))->colorClasses());
        $this->assertStringContainsString('parliament', (new BudgetOutcomeIcon('rejected'))->colorClasses());
        $this->assertStringContainsString('amber', (new BudgetOutcomeIcon('pending'))->colorClasses());
        $this->assertStringContainsString('sand', (new BudgetOutcomeIcon('proposals'))->colorClasses());
    }

    public function test_the_stat_colors_its_number_like_its_icon(): void
    {
        $this->assertStringContainsString('parliament', (new BudgetOutcomeStat('rejected', 'Rejeitadas', 1330))->valueClasses());
        $this->assertStringContainsString('sand', (new BudgetOutcomeStat('proposals', 'Propostas', 2919))->valueClasses());
    }

    public function test_nothing_left_to_vote_is_not_shown_as_a_warning(): void
    {
        $this->assertStringNotContainsString('amber', (new BudgetOutcomeStat('pending', 'Por votar', 0))->valueClasses());
        $this->assertStringContainsString('amber', (new BudgetOutcomeStat('pending', 'Por votar', 3))->valueClasses());
    }

    public function test_the_stat_shows_its_label_and_formatted_number_in_both_sizes(): void
    {
        $this->blade('<dl><x-budget-outcome-stat outcome="approved" label="Aprovadas" :value="1234" /></dl>')
            ->assertSeeInOrder(['<dt', 'Aprovadas', '<dd', '1.234'], false)
            ->assertSee('text-3xl', false);

        $this->blade('<dl><x-budget-outcome-stat compact outcome="approved" label="Aprovadas" :value="1234" /></dl>')
            ->assertSee('1.234')
            ->assertSee('text-lg', false);
    }

    public function test_a_heading_can_name_its_icon(): void
    {
        $this->assertSame('chat', (new BudgetThemeIcon(icon: 'chat'))->icon());
        $this->assertSame('tag', (new BudgetThemeIcon(icon: 'nonexistent'))->icon());
    }

    public function test_the_progress_bar_fills_in_steps_of_five_percent(): void
    {
        $this->assertSame('w-12/20', (new ProgressBar(0.62))->widthClass());
        $this->assertSame('w-0', (new ProgressBar(0))->widthClass());
        $this->assertSame('w-full', (new ProgressBar(1.4))->widthClass(), 'Clamped to full.');
        $this->assertSame('w-0', (new ProgressBar(-0.2))->widthClass(), 'Clamped to empty.');
    }

    public function test_the_progress_bar_takes_a_color_and_falls_back_to_green(): void
    {
        $this->assertStringContainsString('parliament', (new ProgressBar(0.5, 'parliament'))->colorClass());
        $this->assertStringContainsString('sky', (new ProgressBar(0.5, 'blue'))->colorClass());
        $this->assertStringContainsString('republic', (new ProgressBar(0.5, 'purple'))->colorClass());

        $this->blade('<x-progress-bar :share="0.5" color="amber" class="mt-2" />')
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('w-10/20', false)
            ->assertSee('mt-2', false);
    }

    public function test_the_position_bar_fills_exactly_the_full_width(): void
    {
        $segments = (new ShareBar(['favor' => 1, 'contra' => 1, 'abstencao' => 1]))->segments();

        $steps = array_sum(array_map(fn (array $segment): int => (int) (preg_match('#w-(\d+)/20#', $segment['classes'], $m) ? $m[1] : 20), $segments));
        $this->assertSame(20, $steps);
        $this->assertSame(['favor', 'contra', 'abstencao'], array_column($segments, 'position'));
    }

    public function test_the_position_bar_leaves_out_a_position_nobody_took(): void
    {
        $segments = (new ShareBar(['favor' => 0, 'contra' => 0, 'abstencao' => 132]))->segments();

        $this->assertCount(1, $segments);
        $this->assertStringContainsString('w-full', $segments[0]['classes']);
        $this->assertStringContainsString('amber', $segments[0]['classes']);
    }

    public function test_the_position_bar_is_empty_without_votes(): void
    {
        $this->assertSame([], (new ShareBar([]))->segments());
        $this->blade('<x-share-bar :counts="[]" />')->assertDontSee('w-full', false);
    }
}
