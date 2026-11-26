@extends('layouts.app')

@section('title', 'How it works | jTech Nepal')

@section('content')
<div class="page-shell mx">
    <div class="u-head">
        <p class="u-eyebrow">Method</p>
        <h1>How scoring works</h1>
        <p>What the data is, how a score is built, and what is known to be incomplete.</p>
    </div>

    <h2>Data</h2>
    <p>{{ number_format($total) }} records in {{ count($counts) }} collections. Amounts are shown in the unit stated by the source; a collection with no stated unit is never converted.</p>
    <div class="u-table-wrap">
        <table class="u-table">
            <thead><tr><th scope="col">Collection</th><th scope="col" class="num">Records</th><th scope="col">Unit</th></tr></thead>
            <tbody>
                @foreach ($counts as $dataset => $c)
                    <tr><td>{{ $names[$dataset] ?? $dataset }}</td><td class="num">{{ number_format($c->n) }}</td><td>{{ $units[$dataset] ?? 'not stated in source' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2>Scoring</h2>
    <p>Each portfolio is scored against the criteria you answered. A criterion you skipped is not requested, so it does not count for or against a record.</p>
    <ul>
        <li><strong>SDG / topic</strong> (weight {{ number_format($weights['topic'] ?? 0, 2) }} before profile adjustment): the record’s SDG is one of your chosen goals.</li>
        <li><strong>Focus area</strong> (weight {{ number_format($weights['focus_area'] ?? 0, 2) }}): the record’s focus area is one you selected.</li>
        <li><strong>Amount size</strong> (weight {{ number_format($weights['amount_fit'] ?? 0, 2) }}): the stated amount sits in your preferred size band within its collection.</li>
        <li><strong>Collection</strong> is a filter only and earns no points.</li>
    </ul>
    <p>The score is points earned divided by points requested, then reduced when a record lacks data for a question you answered: <code>score × √(applicable weight ÷ requested weight)</code>. The card’s “data” meter shows that completeness. Criteria that could not be scored are labelled “not scored” and are never counted as zero matches. The ranked list is then re-ranked for diversity, which is stronger for conservative or loss-averse profiles.</p>

    <h2>Risk and the suggested mix</h2>
    <p>The records are public programmes and funding needs, not tradable assets: there are no market prices or expected returns. “Risk” in your profile affects weights and diversity, not a return forecast. The suggested mix only uses records with a stated amount in a known unit, caps one record at 25% and one collection at 40% of your budget, and may leave budget unallocated. It is an illustration.</p>

    <h2>What this version does not do</h2>
    <ul>
        <li>No learning from feedback is active. Weights come from configuration and your profile.</li>
        <li>SDG matching is by exact goal; related-goal credit is not applied.</li>
        <li>Horizon and structure describe you but do not change ranking, because records carry no horizon or risk data.</li>
    </ul>

    <h2 id="gaps">Known data gaps</h2>
    <div class="u-table-wrap">
        <table class="u-table">
            <thead><tr><th scope="col">Collection</th><th scope="col" class="num">No amount</th><th scope="col" class="num">No SDG</th></tr></thead>
            <tbody>
                @foreach ($counts as $dataset => $c)
                    <tr><td>{{ $names[$dataset] ?? $dataset }}</td><td class="num">{{ number_format($c->no_amount) }}</td><td class="num">{{ number_format($c->no_sdg) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="u-note">Rows without an amount appear as “Not yet costed”. Rows without an SDG cannot match an SDG question.</p>

    <h2>Disclaimer</h2>
    <p>Illustrative decision support. Not financial advice. Behavioral notes are not a validated psychometric instrument.</p>
</div>
@endsection
