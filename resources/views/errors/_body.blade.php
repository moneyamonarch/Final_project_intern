@extends('layouts.app')

@section('title', $title.' | jTech Nepal')

@section('content')
    <div class="page-shell">
        <section class="empty-state" aria-live="polite">
            <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span> {{ $code }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ $text }}</p>
            <div class="empty-actions">
                <a class="button" href="{{ route('home') }}">Go home</a>
                <a class="text-link" href="{{ route('questionnaire.show', ['step' => 1]) }}">Go to the questionnaire</a>
            </div>
        </section>
    </div>
@endsection
