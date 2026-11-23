<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\SelectsBudgets;
use App\Services\BudgetTopicService;
use App\Services\LegislatureContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * The import finds the topics on its own; this finds them again without downloading anything,
 * after config/budget_topics.php changes.
 */
class RebuildBudgetTopics extends Command
{
    use SelectsBudgets;

    protected $signature = 'ar:budget:topics {--year= : Year of the State Budget (default: every budget of the current legislature)}';

    protected $description = 'Find again the topics of the State Budget amendments already imported';

    public function handle(LegislatureContext $legislatureContext, BudgetTopicService $topicService): int
    {
        $budgets = $this->budgets($legislatureContext);

        if ($budgets->isEmpty()) {
            $this->error($this->noBudgetMessage());

            return self::FAILURE;
        }

        foreach ($budgets as $budget) {
            $this->info("{$budget->year}: {$topicService->rebuild($budget)} topics found.");
        }

        Artisan::call('cache:clear');

        return self::SUCCESS;
    }
}
