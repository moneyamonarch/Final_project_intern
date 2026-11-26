<?php

namespace App\Console\Commands;

use App\Models\Portfolio;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateKeywords extends Command
{
    protected $signature = 'portfolios:keywords';

    protected $description = 'Fill portfolios.keywords from title, category and focus area (derived, not source data)';

    private const STOP = ['and', 'the', 'for', 'of', 'in', 'to', 'a', 'on', 'with', 'by', 'at', 'from', 'nepal', 'support', 'program', 'programme'];

    public function handle(): int
    {
        $n = 0;
        Portfolio::query()->orderBy('portfolio_id')->each(function (Portfolio $p) use (&$n): void {
            $text = Str::lower(Str::ascii($p->title.' '.$p->category.' '.$p->focus_area));
            $words = array_filter(preg_split('/[^a-z0-9]+/', $text), fn ($w) => strlen($w) > 3 && ! in_array($w, self::STOP, true));
            $p->keywords = array_slice(array_values(array_unique($words)), 0, 10);
            $p->save();
            $n++;
        });
        $this->info("Keywords written for $n rows.");

        return self::SUCCESS;
    }
}
