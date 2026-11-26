<?php

namespace App\Console\Commands;

use App\Models\Portfolio;
use App\Support\Facets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SplFileObject;

class ImportPortfolios extends Command
{
    protected $signature = 'portfolios:import
        {--path= : CSV path (default database/data/nde_portfolios_clean.csv)}
        {--dry-run : Validate and report only; write nothing}
        {--prune : Delete rows that are not in the CSV}';

    protected $description = 'Validate and import portfolio records from the clean CSV file';

    private const REQUIRED = [
        'portfolio_id', 'dataset', 'code', 'title', 'summary', 'sdg_num', 'focus_group',
        'focus_area', 'category', 'layer', 'total', 'is_overview', 'document_url',
    ];

    /** Phrases that mark a generated (non-specific) summary. */
    public const TEMPLATE_MARKERS = [
        'Combines conventional programme investment',
        'upgraded with digital monitoring and AI analytics',
    ];

    public function handle(): int
    {
        $path = $this->option('path') ?: database_path('data/nde_portfolios_clean.csv');

        if (! is_readable($path)) {
            $this->error("CSV file is missing or unreadable: {$path}");

            return self::FAILURE;
        }

        [$records, $errors, $warnings] = $this->readAndValidate($path);

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        if ($errors !== []) {
            $this->error(count($errors).' problem(s) found. Nothing was written.');
            $this->table(['Problem'], array_map(static fn ($e) => [$e], array_slice($errors, 0, 100)));
            if (count($errors) > 100) {
                $this->line('... and '.(count($errors) - 100).' more.');
            }

            return self::FAILURE;
        }

        $counts = [];
        foreach ($records as $record) {
            $counts[$record['dataset']] = ($counts[$record['dataset']] ?? 0) + 1;
        }
        ksort($counts);
        foreach ($counts as $dataset => $count) {
            $this->line("{$dataset}: {$count}");
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: '.count($records).' rows are valid. Nothing was written.');

            return self::SUCCESS;
        }

        $update = array_values(array_diff(array_keys($records[0] ?? []), ['portfolio_id']));
        $pruned = 0;

        DB::transaction(function () use ($records, $update, &$pruned): void {
            foreach (array_chunk($records, 100) as $chunk) {
                Portfolio::upsert($chunk, ['portfolio_id'], $update);
            }

            if ($this->option('prune')) {
                $pruned = Portfolio::whereNotIn('portfolio_id', array_column($records, 'portfolio_id'))->delete();
            }
        });

        Facets::flush();
        $this->info('Imported or updated '.count($records).' portfolio rows.'.($pruned ? " Pruned {$pruned}." : ''));

        return self::SUCCESS;
    }

    /** @return array{0: array<int, array>, 1: string[], 2: string[]} records, errors, warnings */
    private function readAndValidate(string $path): array
    {
        $errors = [];
        $warnings = [];
        $records = [];

        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

        $headers = $file->fgetcsv();
        if (! is_array($headers) || $headers === [null]) {
            return [[], ['The CSV has no header row.'], []];
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers = array_map(static fn ($h) => trim((string) $h), $headers);

        if (count($headers) !== count(array_unique($headers))) {
            $errors[] = 'The CSV header has duplicate column names.';
        }
        if ($missing = array_diff(self::REQUIRED, $headers)) {
            $errors[] = 'The CSV header is missing required column(s): '.implode(', ', $missing).'.';
        }
        if ($extra = array_diff($headers, self::REQUIRED)) {
            $warnings[] = 'Ignoring unknown column(s): '.implode(', ', $extra).'.';
        }
        if ($errors !== []) {
            return [[], $errors, $warnings];
        }

        $datasets = array_keys(config('recommender.collections', []));
        $units = config('recommender.amount_units', []);
        $seenIds = [];
        $seenCodes = [];
        $n = 0;

        while (! $file->eof()) {
            $row = $file->fgetcsv();
            if ($row === false || $row === null || $row === [null]) {
                continue;
            }
            $n++;

            if (count($row) !== count($headers)) {
                $errors[] = "Record {$n}: expected ".count($headers).' columns, found '.count($row).'.';

                continue;
            }

            $r = array_combine($headers, array_map(fn ($v) => $this->clean($v), $row));
            $r = array_intersect_key($r, array_flip(self::REQUIRED));
            $where = "Record {$n} (".($r['code'] ?? '?').')';

            if ($r['portfolio_id'] === null || ! ctype_digit($r['portfolio_id']) || (int) $r['portfolio_id'] < 1) {
                $errors[] = "{$where}: invalid portfolio_id.";
            } elseif (isset($seenIds[$r['portfolio_id']])) {
                $errors[] = "{$where}: duplicate portfolio_id {$r['portfolio_id']}.";
            } else {
                $seenIds[$r['portfolio_id']] = true;
            }

            if ($r['code'] === null) {
                $errors[] = "{$where}: missing code.";
            } elseif (isset($seenCodes[$r['code']])) {
                $errors[] = "{$where}: duplicate code.";
            } else {
                $seenCodes[$r['code']] = true;
            }

            if ($r['dataset'] === null || ! in_array($r['dataset'], $datasets, true)) {
                $errors[] = "{$where}: unknown dataset '".($r['dataset'] ?? '')."' (expected one of ".implode(', ', $datasets).').';
            }

            foreach (['title', 'focus_group', 'focus_area', 'category'] as $required) {
                if ($r[$required] === null) {
                    $errors[] = "{$where}: missing {$required}.";
                }
            }

            if (! in_array($r['is_overview'], ['0', '1'], true)) {
                $errors[] = "{$where}: is_overview must be 0 or 1.";
            }

            if ($r['sdg_num'] !== null) {
                if (! ctype_digit($r['sdg_num']) || (int) $r['sdg_num'] < 1 || (int) $r['sdg_num'] > 17) {
                    $errors[] = "{$where}: sdg_num must be a whole number from 1 to 17.";
                } else {
                    $r['sdg_num'] = (int) $r['sdg_num'];
                }
            }

            if ($r['total'] !== null) {
                if (! is_numeric($r['total']) || (float) $r['total'] < 0 || (float) $r['total'] >= 1e12) {
                    $errors[] = "{$where}: total must be a number from 0 up to 999999999999.";
                }
            }

            if ($r['document_url'] !== null) {
                $scheme = strtolower((string) parse_url($r['document_url'], PHP_URL_SCHEME));
                if (! filter_var($r['document_url'], FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
                    $errors[] = "{$where}: document_url is not a valid http(s) URL.";
                }
            }

            // Derived, normalised columns
            $r['title_normalized'] = $r['title'] === null ? null
                : trim((string) preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($r['title']))));
            $r['summary_is_template'] = $r['summary'] !== null && Str::contains($r['summary'], self::TEMPLATE_MARKERS);
            $r['amount_unit'] = $r['total'] !== null ? ($units[$r['dataset']] ?? null) : null;
            $r['amount_status'] = $r['total'] !== null ? 'source' : 'unavailable';
            $isSdg = in_array($r['dataset'], ['sdg_intervention', 'sdg_indicator'], true);
            $r['amount_currency'] = $r['total'] !== null && $isSdg ? 'NPR' : null;
            $r['amount_npr_bn'] = $r['total'] !== null && $isSdg ? $r['total'] : null; // NAP unit unconfirmed: left null
            $r['amount_basis'] = $r['dataset'] === 'sdg_indicator' ? 'allocated_equal_share' : null;
            $r['source_hash'] = sha1(json_encode($r));
            $r['imported_at'] = now();
            $r['portfolio_id'] = ctype_digit((string) $r['portfolio_id']) ? (int) $r['portfolio_id'] : $r['portfolio_id'];
            $r['is_overview'] = $r['is_overview'] === '1';

            $records[] = $r;
        }

        if ($records === [] && $errors === []) {
            $errors[] = 'The CSV has no data rows.';
        }

        return [$records, $errors, $warnings];
    }

    /** Trim, normalise Unicode (NFC) and collapse runs of spaces; blank becomes null. */
    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;
        if (class_exists(\Normalizer::class)) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_C) ?: $value;
        }
        $value = trim((string) preg_replace('/[ \t\x{00A0}]+/u', ' ', $value));

        return $value === '' ? null : $value;
    }
}
