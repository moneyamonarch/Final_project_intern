<?php
// Demo personas: each `answers` is a complete questionnaire session (same shape the questionnaire stores).
$p = fn (array $a) => ['schema_version' => 1] + $a;

return [
    'ngo_funder' => [
        'label' => 'Development NGO funder',
        'description' => 'SDG 1, 2 and 3; NPR 20M grant; 10+ years; impact-first. Expect interventions and SDG coverage.',
        'answers' => [
            'profile' => $p(['investor_type' => 'ngo', 'origin' => 'nepal', 'experience' => 'some', 'amount' => 20000000, 'currency' => 'NPR',
                'structure' => 'grant', 'horizon' => '10_plus', 'impact_tilt' => 90, 'risk_loss' => '3', 'risk_gamble' => '2', 'risk_comfort' => 4]),
            'topics' => ['sdgs' => [1, 2, 3], 'digital_sovereignty' => false],
            'focus_areas' => [], 'investment_size' => 'no_preference', 'collections' => ['sdg_intervention'],
        ],
    ],
    'impact_investor' => [
        'label' => 'Impact investor',
        'description' => 'SDG 7, 9 and 13; NPR 500M blended finance; 5-10 years; balanced risk. Expect energy and climate items and a suggested mix.',
        'answers' => [
            'profile' => $p(['investor_type' => 'individual', 'origin' => 'north_america', 'experience' => 'first_time', 'amount' => 500000000, 'currency' => 'NPR',
                'structure' => 'blended', 'horizon' => '5_10', 'impact_tilt' => 70, 'risk_loss' => '3', 'risk_gamble' => '2', 'risk_comfort' => 3]),
            'topics' => ['sdgs' => [7, 9, 13], 'digital_sovereignty' => false],
            'focus_areas' => ['Energy & Water Resources', 'Climate, Forests & Disaster Resilience'], 'investment_size' => 'no_preference', 'collections' => [],
        ],
    ],
    'gov_digital' => [
        'label' => 'Government digital planner',
        'description' => 'Digital sovereignty; large public-private budget; 10+ years. Expect the sovereign collection, mostly not yet costed.',
        'answers' => [
            'profile' => $p(['investor_type' => 'government', 'origin' => 'nepal', 'experience' => 'nepal', 'amount' => 5000000000, 'currency' => 'NPR',
                'structure' => 'ppp', 'horizon' => '10_plus', 'impact_tilt' => 60, 'risk_loss' => '3', 'risk_gamble' => '2', 'risk_comfort' => 3]),
            'topics' => ['sdgs' => [], 'digital_sovereignty' => true],
            'focus_areas' => ['Data & Digital Public Infrastructure', 'Sovereign Infrastructure & Connectivity'], 'investment_size' => 'no_preference', 'collections' => ['sovereign'],
        ],
    ],
    'conservative_donor' => [
        'label' => 'Conservative donor',
        'description' => 'Small USD 5,000 grant; 2-5 years; low risk; health and education. Expect broad, diversified results.',
        'answers' => [
            'profile' => $p(['investor_type' => 'donor', 'origin' => 'europe', 'experience' => 'first_time', 'amount' => 5000, 'currency' => 'USD',
                'structure' => 'grant', 'horizon' => '2_5', 'impact_tilt' => 50, 'risk_loss' => '1', 'risk_gamble' => '1', 'risk_comfort' => 1]),
            'topics' => ['sdgs' => [], 'digital_sovereignty' => false],
            'focus_areas' => ['Health, Water & Sanitation', 'Education, Jobs & Skills'], 'investment_size' => 'small', 'collections' => [],
        ],
    ],
    'skip_all' => [
        'label' => 'Skip-everything visitor',
        'description' => 'Every question skipped. Expect the broad-sample fallback with no percentages.',
        'answers' => [
            'profile' => ['schema_version' => 1],
            'topics' => ['sdgs' => [], 'digital_sovereignty' => false],
            'focus_areas' => [], 'investment_size' => 'no_preference', 'collections' => [],
        ],
    ],
    'contradictory' => [
        'label' => 'Contradictory answers',
        'description' => 'Risk answers disagree (withdraw everything, but pick the big gamble). Expect the low-confidence note.',
        'answers' => [
            'profile' => $p(['investor_type' => 'individual', 'origin' => 'india', 'experience' => 'some', 'amount' => 1000000, 'currency' => 'NPR',
                'structure' => 'undecided', 'horizon' => '5_10', 'impact_tilt' => 50, 'risk_loss' => '1', 'risk_gamble' => '4', 'risk_comfort' => 5]),
            'topics' => ['sdgs' => [2, 13], 'digital_sovereignty' => false],
            'focus_areas' => ['Agriculture & Food'], 'investment_size' => 'no_preference', 'collections' => [],
        ],
    ],
];