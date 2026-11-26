@extends('layouts.app')

@section('title', 'Compare portfolios | jTech Nepal')

@section('content')
    <div class="page-shell">
        <div class="page-heading">
            <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span> Compare</p>
            <h1>Compare portfolios</h1>
        </div>

        @if ($portfolios->count() < 2)
            <section class="empty-state" aria-live="polite">
                <h2>Pick at least two</h2>
                <p>Tick two or three portfolios on the results page, then press Compare selected.</p>
                <div class="empty-actions"><a class="button" href="{{ route('recommendations.index') }}">Back to results</a></div>
            </section>
        @else
            <div class="table-scroll">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th scope="col">&nbsp;</th>
                            @foreach ($portfolios as $p)
                                <th scope="col"><a href="{{ route('portfolios.show', $p->portfolio_id) }}">{{ $p->title }}</a></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr><th scope="row">Collection</th>@foreach ($portfolios as $p)<td>{{ $collectionNames[$p->dataset] ?? $p->dataset }}</td>@endforeach</tr>
                        <tr><th scope="row">SDG</th>@foreach ($portfolios as $p)<td>{{ $p->sdg_num !== null ? 'SDG '.$p->sdg_num : 'Not provided' }}</td>@endforeach</tr>
                        <tr><th scope="row">Focus area</th>@foreach ($portfolios as $p)<td>{{ $p->focus_area ?: 'Not provided' }}</td>@endforeach</tr>
                        <tr><th scope="row">Category</th>@foreach ($portfolios as $p)<td>{{ $p->category ?: 'Not provided' }}</td>@endforeach</tr>
                        <tr>
                            <th scope="row">Amount</th>
                            @foreach ($portfolios as $p)
                                <td>
                                    @if ($p->total === null)
                                        Not yet costed
                                    @else
                                        {{ number_format((float) $p->total, 2) }} {{ $amountUnits[$p->dataset] ?? '(unit not stated in source)' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row">Document</th>
                            @foreach ($portfolios as $p)
                                <td>
                                    @if ($p->document_url && preg_match('#^https?://#i', $p->document_url))
                                        <a href="{{ $p->document_url }}" rel="noopener noreferrer">Open document</a>
                                    @else
                                        Not provided
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr><th scope="row">Summary</th>@foreach ($portfolios as $p)<td>{{ $p->summary ?: 'No summary provided.' }}</td>@endforeach</tr>
                    </tbody>
                </table>
            </div>
            <div class="results-footer">
                <a class="text-link" href="{{ route('recommendations.index') }}">Back to results</a>
            </div>
        @endif
    </div>
@endsection