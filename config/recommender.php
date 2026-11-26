<?php

return [
    'collections' => [
        'sdg_intervention' => 'SDG Interventions',
        'sdg_indicator' => 'SDG Indicators',
        'nap' => 'Climate NAP 2021-2050',
        'sovereign' => 'Digital/AI Sovereign',
        'draft' => 'Draft Portfolios',
    ],
    'weights' => [
        'topic' => 0.25,
        'focus_area' => 0.25,
        'collection' => 0.25,
        'amount_fit' => 0.25,
    ],
    'indicator_cap' => 3,
        'usd_to_npr' => 133,
    'type_weights' => [
        'donor' => ['topic' => 0.35, 'focus_area' => 0.30, 'collection' => 0.15, 'amount_fit' => 0.20],
        'ngo' => ['topic' => 0.35, 'focus_area' => 0.30, 'collection' => 0.15, 'amount_fit' => 0.20],
        'government' => ['topic' => 0.30, 'focus_area' => 0.30, 'collection' => 0.20, 'amount_fit' => 0.20],
        'bank' => ['topic' => 0.20, 'focus_area' => 0.25, 'collection' => 0.20, 'amount_fit' => 0.35],
        'company' => ['topic' => 0.20, 'focus_area' => 0.30, 'collection' => 0.20, 'amount_fit' => 0.30],
    ],
    'amount_units' => [
        'sdg_intervention' => 'NRs billion',
        'sdg_indicator' => 'NRs billion',
    ],
];
