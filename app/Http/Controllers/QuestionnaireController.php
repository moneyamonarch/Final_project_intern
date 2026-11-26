<?php

namespace App\Http\Controllers;

use App\Support\Facets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionnaireController extends Controller
{
    private const FILTER_STEPS = 4; // SDGs, focus areas, size, collections (the original questionnaire)

    private function profileSteps(): array
    {
        return config('questionnaire.profile_steps', []);
    }

    private function totalSteps(): int
    {
        return count($this->profileSteps()) + self::FILTER_STEPS;
    }

    public function start(Request $request): RedirectResponse
    {
        $request->session()->forget('questionnaire');

        return redirect()->route('questionnaire.show', ['step' => 1]);
    }

    /** Profile steps first, then the original filter steps. */
    public function show(Request $request, int $step = 1): View|RedirectResponse
    {
        $total = $this->totalSteps();
        if ($step < 1 || $step > $total) {
            return redirect()->route('questionnaire.show', ['step' => 1]);
        }

        $offset = count($this->profileSteps());
        $legacy = $step - $offset;      // 1..4 for the original steps, <= 0 for profile steps
        $sdgNumbers = [];
        $focusGroups = collect();
        $datasets = [];

        if ($legacy === 1) {
            $sdgNumbers = Facets::sdgNumbers();
        }

        if ($legacy === 2) {
            $focusGroups = Facets::focusGroups();
        }

        if ($legacy === 4) {
            $datasets = collect(Facets::datasets())
                ->sortBy(function (string $dataset): int {
                    $position = array_search($dataset, array_keys(config('recommender.collections', [])), true);

                    return $position === false ? PHP_INT_MAX : $position;
                })->values()->all();
        }

        return view('questionnaire.step', [
            'step' => $step,
            'total' => $total,
            'legacy' => $legacy,
            'profileStep' => $this->profileSteps()[$step] ?? null,
            'progress' => (int) (($step / $total) * 100),
            'answers' => $request->session()->get('questionnaire', []),
            'sdgNumbers' => $sdgNumbers,
            'focusGroups' => $focusGroups,
            'datasets' => $datasets,
            'collectionNames' => config('recommender.collections', []),
        ]);
    }

    public function store(Request $request, int $step): RedirectResponse
    {
        $total = $this->totalSteps();
        if ($step < 1 || $step > $total) {
            return redirect()->route('questionnaire.show', ['step' => 1]);
        }

        $offset = count($this->profileSteps());
        $legacy = $step - $offset;
        $skipped = $request->boolean('_skip');
        $answers = $request->session()->get('questionnaire', []);

        if ($legacy <= 0) {
            $profile = $answers['profile'] ?? ['schema_version' => config('questionnaire.schema_version', 1)];
            $validated = $skipped ? [] : $request->validate(['_skip' => ['nullable', 'boolean']] + $this->profileRules($step));

            foreach ($this->profileSteps()[$step]['questions'] as $q) {
                $v = $validated[$q['key']] ?? null;
                $profile[$q['key']] = ($v === '' ? null : $v);   // null = skipped
            }
            $answers['profile'] = $profile;
            $request->session()->put('questionnaire', $answers);

            return $this->next($answers, $step);
        }

        $validated = $skipped ? [] : $this->validateStep($request, $legacy);

        if ($legacy === 1) {
            $answers['topics'] = [
                'sdgs' => array_values(array_unique(array_map('intval', $validated['sdgs'] ?? []))),
                'digital_sovereignty' => ! $skipped && $request->boolean('digital_sovereignty'),
            ];
        }

        if ($legacy === 2) {
            $answers['focus_areas'] = array_values(array_unique($validated['focus_areas'] ?? []));
        }

        if ($legacy === 3) {
            $answers['investment_size'] = $validated['investment_size'] ?? 'no_preference';
        }

        if ($legacy === 4) {
            $answers['collections'] = array_values(array_unique($validated['collections'] ?? []));
            $answers['completed'] = true;
        }

        $request->session()->put('questionnaire', $answers);

        return $this->next($answers, $step);
    }

    /** After saving: completed visitors go straight back to results; others continue. */
    private function next(array $answers, int $step): RedirectResponse
    {
        if (! empty($answers['completed']) || $step >= $this->totalSteps()) {
            return redirect()->route('recommendations.index');
        }

        return redirect()->route('questionnaire.show', ['step' => $step + 1]);
    }

    /** Validation rules for a profile step, generated from config/questionnaire.php. */
    private function profileRules(int $step): array
    {
        $rules = [];
        foreach ($this->profileSteps()[$step]['questions'] as $q) {
            $rules[$q['key']] = match ($q['type']) {
                'single' => ['nullable', Rule::in(array_map('strval', array_keys($q['options'])))],
                'number' => ['nullable', 'numeric', 'min:0', 'max:1000000000000'],
                'scale' => ['nullable', 'integer', 'between:0,100'],
                'scale5' => ['nullable', 'integer', 'between:1,5'],
            };
        }

        return $rules;
    }

    private function validateStep(Request $request, int $legacy): array
    {
        $commonRules = ['_skip' => ['nullable', 'boolean']];

        if ($legacy === 1) {
            $sdgNumbers = Facets::sdgNumbers();

            return $request->validate($commonRules + [
                'sdgs' => ['nullable', 'array'],
                'sdgs.*' => ['integer', Rule::in($sdgNumbers)],
                'digital_sovereignty' => ['nullable', 'boolean'],
            ]);
        }

        if ($legacy === 2) {
            $focusAreas = Facets::focusAreas();

            return $request->validate($commonRules + [
                'focus_areas' => ['nullable', 'array'],
                'focus_areas.*' => ['string', Rule::in($focusAreas)],
            ]);
        }

        if ($legacy === 3) {
            return $request->validate($commonRules + [
                'investment_size' => ['nullable', Rule::in(['small', 'medium', 'large', 'no_preference'])],
            ]);
        }

        $datasets = Facets::datasets();

        return $request->validate($commonRules + [
            'collections' => ['nullable', 'array'],
            'collections.*' => ['string', Rule::in($datasets)],
        ]);
    }
}