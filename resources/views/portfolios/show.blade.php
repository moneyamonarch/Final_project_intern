@extends('layouts.app')

@section('title', $portfolio->title.' | jTech Nepal')

@section('content')
    <div class="page-shell detail-shell">
        <a class="back-link" href="{{ route('portfolios.index', request()->query()) }}">&larr; Back to browse</a>

        <article class="detail-card">
            <div class="detail-meta-row">
                <span class="collection-tag" data-collection="{{ $portfolio->dataset }}">
                    {{ $collectionNames[$portfolio->dataset] ?? \Illuminate\Support\Str::headline($portfolio->dataset) }}
                </span>
                @if ($portfolio->is_overview)
                    <span class="overview-tag">Overview</span>
                @endif
                <span class="portfolio-id">Portfolio {{ $portfolio->portfolio_id }}</span>
            </div>

            <h1>{{ $portfolio->title }}</h1>
            <p class="detail-summary">{{ $portfolio->summary ?: 'No summary provided.' }}</p>

            <dl class="detail-grid">
                <div><dt>Portfolio ID</dt><dd>{{ $portfolio->portfolio_id }}</dd></div>
                <div><dt>Dataset</dt><dd>{{ $portfolio->dataset }}</dd></div>
                <div><dt>Code</dt><dd>{{ $portfolio->code }}</dd></div>
                <div><dt>SDG number</dt><dd>{{ $portfolio->sdg_num === null ? 'Not provided' : 'SDG '.$portfolio->sdg_num }}</dd></div>
                <div><dt>Focus group</dt><dd>{{ $portfolio->focus_group }}</dd></div>
                <div><dt>Focus area</dt><dd>{{ $portfolio->focus_area }}</dd></div>
                <div><dt>Category</dt><dd>{{ $portfolio->category }}</dd></div>
                <div><dt>Layer</dt><dd>{{ $portfolio->layer ?: 'Not provided' }}</dd></div>
                <div>
                    <dt>Total</dt>
                    <dd>
                        @if ($portfolio->total === null)
                            Not provided
                        @else
                            {{ number_format((float) $portfolio->total, 2) }}{{ ! empty($amountUnits[$portfolio->dataset]) ? ' '.$amountUnits[$portfolio->dataset] : '' }}
                        @endif
                    </dd>
                </div>
                <div><dt>Overview record</dt><dd>{{ $portfolio->is_overview ? 'Yes' : 'No' }}</dd></div>
                <div class="detail-document">
                    <dt>Document</dt>
                    <dd>
                        @if ($documentUrl)
                            <a href="{{ $documentUrl }}" target="_blank" rel="noopener noreferrer">View document</a>
                        @else
                            Not provided
                        @endif
                    </dd>
                </div>
            </dl>
        </article>

        @if ($similarPortfolios->isNotEmpty())
            <section class="similar-section" aria-labelledby="similar-title">
                <div class="section-heading-row">
                    <div>
                        <p class="eyebrow eyebrow-muted">Related records</p>
                        <h2 id="similar-title">Similar portfolios</h2>
                    </div>
                    <span class="similar-count">{{ $similarPortfolios->count() }} shown</span>
                </div>
                <div class="similar-grid">
                    @foreach ($similarPortfolios as $similar)
                        <article class="portfolio-card similar-card">
                            <span class="collection-tag" data-collection="{{ $similar->dataset }}">
                                {{ $collectionNames[$similar->dataset] ?? \Illuminate\Support\Str::headline($similar->dataset) }}
                            </span>
                            <h3><a href="{{ route('portfolios.show', array_merge(['portfolio' => $similar->portfolio_id], request()->query())) }}">{{ $similar->title }}</a></h3>
                            <p class="portfolio-category"><span>Category</span> {{ $similar->category }}</p>
                            @if ($similar->sdg_num !== null)
                                <p class="portfolio-facts"><span>SDG {{ $similar->sdg_num }}</span></p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
