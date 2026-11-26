<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f2ec">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <meta property="og:site_name" content="jTech Nepal">
    <meta name="description" content="@yield('description', 'Match Nepal’s public development, climate and digital-sovereignty portfolios to an investor profile.')">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'jTech | Nepal Portfolio Guide')">
    <meta property="og:description" content="@yield('description', 'Ranked, explainable portfolio matches for Nepal.')">
    <title>@yield('title', 'jTech | Nepal Portfolio Guide')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ux.css') }}">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>

    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('home') }}" aria-label="jTech home">
                <span class="brand-mark" aria-hidden="true">j</span>
                <span class="brand-name">jTech <span class="brand-country">Nepal</span></span>
            </a>
            <nav class="u-nav" aria-label="Main navigation">
                <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>Home</a>
                <a href="{{ route('portfolios.index') }}" @if (request()->routeIs('portfolios.*')) aria-current="page" @endif>Portfolios</a>
                <a href="{{ route('method') }}" @if (request()->routeIs('method')) aria-current="page" @endif>Method</a>
                @if (session()->has('questionnaire'))
                    <a href="{{ route('recommendations.index') }}" @if (request()->routeIs('recommendations.*')) aria-current="page" @endif>Results</a>
                @endif
                @if (count(session('saved_portfolios', [])) > 0)
                    <a href="{{ route('saved.index') }}" @if (request()->routeIs('saved.*')) aria-current="page" @endif>Saved ({{ count(session('saved_portfolios', [])) }})</a>
                @endif
                <a class="u-cta" href="{{ route('questionnaire.start') }}">{{ session()->has('questionnaire') ? 'Start over' : 'Start' }}</a>
            </nav>
        </div>
    </header>

    <main id="main-content">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="footer-inner u-foot">
            <div>
                <h2>About</h2>
                <p>Illustrative decision support over the jTech portfolio dataset. Not financial advice. Amounts are as stated in the source data, in the units shown.</p>
            </div>
            <div>
                <h2>Explore</h2>
                <ul>
                    <li><a href="{{ route('portfolios.index') }}">Browse portfolios</a></li>
                    <li><a href="{{ route('questionnaire.start') }}">Questionnaire</a></li>
                </ul>
            </div>
            <div>
                <h2>Trust</h2>
                <ul>
                    <li><a href="{{ route('method') }}">How scoring works</a></li>
                    <li><a href="{{ route('method') }}#gaps">Known data gaps</a></li>
                    <li><a href="{{ route('privacy') }}">Privacy</a></li>
                </ul>
            </div>
        </div>
    </footer>
</body>
</html>
