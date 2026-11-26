<?php
// Profile questions (steps 1-3). Add a question here and it renders, validates and is stored.
// type: single | number | scale (0-100) | scale5 (1-5). Skipped = null.
return [
    'schema_version' => 1,
    'profile_steps' => [
        1 => ['section' => 'About you', 'title' => 'Who is investing?',
            'description' => 'Tell us who you are so results can be explained in your terms. Every question can be skipped.',
            'questions' => [
                ['key' => 'investor_type', 'type' => 'single', 'label' => 'Which best describes you?', 'options' => [
                    'individual' => 'Individual / diaspora investor', 'company' => 'Private company',
                    'bank' => 'Bank or financial institution', 'ngo' => 'NGO',
                    'donor' => 'Development partner or donor', 'government' => 'Government agency']],
                ['key' => 'origin', 'type' => 'single', 'label' => 'Country or region of origin', 'options' => [
                    'nepal' => 'Nepal', 'india' => 'India', 'asia' => 'Other Asia', 'europe' => 'Europe',
                    'north_america' => 'North America', 'other' => 'Elsewhere']],
                ['key' => 'experience', 'type' => 'single', 'label' => 'Your experience with development or infrastructure funding', 'options' => [
                    'first_time' => 'First time', 'some' => 'Some experience elsewhere', 'nepal' => 'Have invested in Nepal before']],
            ]],
        2 => ['section' => 'Capacity & timing', 'title' => 'How much, in what form, for how long?',
            'description' => 'An approximate figure is enough. Amounts are used for context, not as a hard filter.',
            'questions' => [
                ['key' => 'amount', 'type' => 'number', 'label' => 'Approximate amount you could commit', 'help' => 'Whole number, e.g. 50000000'],
                ['key' => 'currency', 'type' => 'single', 'label' => 'Currency', 'options' => ['NPR' => 'NPR', 'USD' => 'USD']],
                ['key' => 'structure', 'type' => 'single', 'label' => 'Preferred investment structure', 'options' => [
                    'equity' => 'Equity', 'debt' => 'Debt / loan', 'grant' => 'Grant', 'blended' => 'Blended finance',
                    'ppp' => 'Public-private partnership', 'undecided' => 'Not decided']],
                ['key' => 'horizon', 'type' => 'single', 'label' => 'Investment horizon', 'options' => [
                    'lt2' => 'Under 2 years', '2_5' => '2-5 years', '5_10' => '5-10 years', '10_plus' => '10 years or more']],
            ]],
        3 => ['section' => 'Priorities & risk', 'title' => 'What matters most, and how do you handle risk?',
            'description' => 'These shape how your profile is described and how criteria are weighted.',
            'questions' => [
                ['key' => 'impact_tilt', 'type' => 'scale', 'label' => 'Impact vs financial sustainability', 'help' => '0 = financial sustainability first, 100 = impact first'],
                ['key' => 'risk_loss', 'type' => 'single', 'label' => 'Your investment loses 20% in a year. You would...', 'options' => [
                    '1' => 'Withdraw everything', '2' => 'Withdraw part of it', '3' => 'Hold and wait', '4' => 'Invest more']],
                ['key' => 'risk_gamble', 'type' => 'single', 'label' => 'Which would you choose?', 'options' => [
                    '1' => 'A sure small return', '2' => 'A likely moderate return with some risk', '4' => 'A large but uncertain return']],
                ['key' => 'risk_comfort', 'type' => 'scale5', 'label' => 'I accept uncertain outcomes when the potential impact is higher', 'help' => '1 = strongly disagree, 5 = strongly agree'],
            ]],
        4 => ['section' => 'Behavior', 'title' => 'How do you make funding decisions?', 'description' => 'Illustrative behavioral questions. Every question can be skipped.', 'questions' => [
            ['key'=>'loss_sting','type'=>'scale5','label'=>'A loss hurts me more than an equal gain pleases me','help'=>'1 = strongly disagree, 5 = strongly agree'],
            ['key'=>'patience','type'=>'single','label'=>'NPR 100,000 now; what minimum would make you wait a year?','options'=>['1'=>'NPR 105,000','2'=>'NPR 120,000','3'=>'NPR 150,000','4'=>'I would take it now']],
            ['key'=>'herding','type'=>'scale5','label'=>'I prefer to fund what other funders already back','help'=>'1 = strongly disagree, 5 = strongly agree'],
            ['key'=>'calib_fact','type'=>'single','label'=>'How many portfolio records are in this catalogue?','options'=>['a'=>'About 100','b'=>'About 900','c'=>'About 5,000']],
            ['key'=>'calib_conf','type'=>'scale5','label'=>'How confident are you in that answer?','help'=>'1 = guessing, 5 = certain'],
        ]],
    ],
];