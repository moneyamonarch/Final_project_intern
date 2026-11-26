
@extends('layouts.app')

@php
    $questions = [
        1 => ['Topics', 'What topics interest you?', 'Choose any SDG goals in the data and/or digital sovereignty.'],
        2 => ['Focus areas', 'Which areas matter to you?', 'Choose one or more areas. The choices come from the portfolio data.'],
        3 => ['Investment size', 'What size of investment are you considering?', 'Choose a relative amount band, or leave amount out of your recommendations.'],
        4 => ['Collections', 'Which collections should we include?', 'Choose one or more collections, or keep all collections in scope.'],
    ];

    if ($legacy >= 1) {
        [$eyebrow, $questionTitle, $questionDescription] = $questions[$legacy];
    } else {
        [$eyebrow, $questionTitle, $questionDescription] = [
            $profileStep['section'],
            $profileStep['title'],
            $profileStep['description']
        ];
    }

    $selectedSdgs = data_get($answers, 'topics.sdgs', []);
    $selectedAreas = data_get($answers, 'focus_areas', []);
    $selectedSize = data_get($answers, 'investment_size', '');
    $selectedCollections = data_get($answers, 'collections', []);
@endphp

@section('title', $eyebrow.' | jTech Nepal')

@section('content')
    <div class="page-shell questionnaire-shell">
        <section class="questionnaire-card" aria-labelledby="question-title">

            <div class="questionnaire-topline">
                <a class="back-link" href="{{ $step === 1 ? route('home') : route('questionnaire.show', ['step' => $step - 1]) }}">
                    &larr; {{ $step === 1 ? 'Home' : 'Previous question' }}
                </a>
                <span class="question-count">Question {{ $step }} of {{ $total }}</span>
            </div>

            <div class="progress-track"
                 role="progressbar"
                 aria-label="Questionnaire progress"
                 aria-valuemin="1"
                 aria-valuemax="{{ $total }}"
                 aria-valuenow="{{ $step }}"
                 aria-valuetext="Question {{ $step }} of {{ $total }}">
                <span style="width: {{ $progress }}%"></span>
            </div>

            <p class="eyebrow">
                <span class="eyebrow-dot" aria-hidden="true"></span>{{ $eyebrow }}
            </p>

            <h1 id="question-title">{{ $questionTitle }}</h1>
            <p class="question-description">{{ $questionDescription }}</p>

            @if ($errors->any())
                <div class="validation-message" role="alert">
                    <p>Please check your selection and try again.</p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('questionnaire.store', ['step' => $step]) }}">
                @csrf

                @if ($legacy <= 0)
                    @include('questionnaire._profile')

                @elseif ($legacy === 1)
                    <fieldset class="question-group">
                        <legend>SDG goals present in the data</legend>

                        @if ($sdgNumbers === [])
                            <p class="field-note">There are no SDG values in the current portfolio data.</p>
                        @else
                            <div class="question-options option-grid">
                                @foreach ($sdgNumbers as $sdgNumber)
                                    <label class="question-option" for="topic-sdg-{{ $sdgNumber }}">
                                        <input
                                            id="topic-sdg-{{ $sdgNumber }}"
                                            type="checkbox"
                                            name="sdgs[]"
                                            value="{{ $sdgNumber }}"
                                            @checked(in_array((int) $sdgNumber, $selectedSdgs, true))
                                        >
                                        <span>SDG {{ $sdgNumber }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </fieldset>

                    <fieldset class="question-group">
                        <legend>Other topic</legend>
                        <label class="question-option" for="topic-digital-sovereignty">
                            <input
                                id="topic-digital-sovereignty"
                                type="checkbox"
                                name="digital_sovereignty"
                                value="1"
                                @checked(data_get($answers, 'topics.digital_sovereignty', false))
                            >
                            <span>Digital sovereignty</span>
                        </label>
                    </fieldset>

                @elseif ($legacy === 2)
                    @forelse ($focusGroups as $groupIndex => $areaRows)
                        <fieldset class="question-group">
                            <legend>{{ $groupIndex }}</legend>

                            <div class="question-options">
                                @foreach ($areaRows as $index => $areaRow)
                                    <label class="question-option" for="focus-area-{{ $loop->parent->index }}-{{ $index }}">
                                        <input
                                            id="focus-area-{{ $loop->parent->index }}-{{ $index }}"
                                            type="checkbox"
                                            name="focus_areas[]"
                                            value="{{ $areaRow->focus_area }}"
                                            @checked(in_array($areaRow->focus_area, $selectedAreas, true))
                                        >
                                        <span>{{ $areaRow->focus_area }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @empty
                        <p class="field-note">There are no focus areas in the current portfolio data.</p>
                    @endforelse

                @elseif ($legacy === 3)
                    <fieldset class="question-group">
                        <legend>Choose an amount band</legend>

                        <div class="question-options">
                            <label class="question-option" for="size-small">
                                <input
                                    id="size-small"
                                    type="radio"
                                    name="investment_size"
                                    value="small"
                                    @checked($selectedSize === 'small')
                                >
                                <span>
                                    <strong>Small</strong>
                                    <small>Lower third of amounts within each collection</small>
                                </span>
                            </label>

                            <label class="question-option" for="size-medium">
                                <input
                                    id="size-medium"
                                    type="radio"
                                    name="investment_size"
                                    value="medium"
                                    @checked($selectedSize === 'medium')
                                >
                                <span>
                                    <strong>Medium</strong>
                                    <small>Middle third of amounts within each collection</small>
                                </span>
                            </label>

                            <label class="question-option" for="size-large">
                                <input
                                    id="size-large"
                                    type="radio"
                                    name="investment_size"
                                    value="large"
                                    @checked($selectedSize === 'large')
                                >
                                <span>
                                    <strong>Large</strong>
                                    <small>Top third of amounts within each collection</small>
                                </span>
                            </label>

                            <label class="question-option" for="size-none">
                                <input
                                    id="size-none"
                                    type="radio"
                                    name="investment_size"
                                    value="no_preference"
                                    @checked($selectedSize === 'no_preference')
                                >
                                <span>
                                    <strong>No preference</strong>
                                    <small>Do not use amount when ranking portfolios</small>
                                </span>
                            </label>
                        </div>

                        <p class="field-note">
                            Amount bands are calculated separately for each collection.
                            NAP amounts have no stated unit, so they are compared only within that collection.
                        </p>
                    </fieldset>

                @else
                    <fieldset class="question-group">
                        <legend>Available collections</legend>

                        <div class="question-options">
                            @foreach ($datasets as $dataset)
                                <label class="question-option" for="collection-{{ $dataset }}">
                                    <input
                                        id="collection-{{ $dataset }}"
                                        type="checkbox"
                                        name="collections[]"
                                        value="{{ $dataset }}"
                                        @checked(in_array($dataset, $selectedCollections, true))
                                    >
                                    <span>
                                        {{ $collectionNames[$dataset] ?? \Illuminate\Support\Str::headline($dataset) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <div class="question-actions">
                    <button class="button button-secondary" type="submit" name="_skip" value="1" formnovalidate>
                        Skip this question
                    </button>

                    <button class="button" type="submit">
                        {{ $step === $total ? 'See recommendations' : 'Continue' }}
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </form>

            @if ($step === 1)
                @include('partials.personas')
            @endif
        </section>
    </div>
@endsection