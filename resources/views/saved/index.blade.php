@extends('layouts.app')

@section('title', 'Saved portfolios | jTech Nepal')

@section('content')
<div class="page-shell">
    <div class="u-head">
        <p class="u-eyebrow">Saved</p>
        <h1>Your saved portfolios</h1>
        <p>Saved in this browser session. Select several to remove them together.</p>
    </div>

    @if (session('status'))<div class="u-banner" role="status"><p>{{ session('status') }}</p></div>@endif
    @if ($errors->any())<div class="u-banner" role="alert"><p>{{ $errors->first() }}</p></div>@endif

    @if ($portfolios->isEmpty())
        <section class="u-panel"><div class="u-panel-body">
            <p style="margin-top:0">Nothing saved yet. Tick portfolios on your results page and use “Save selected”.</p>
            <a class="button" href="{{ route('recommendations.index') }}">Go to results</a>
        </div></section>
    @else
        <form method="post" action="{{ route('saved.destroy') }}">
            @csrf @method('DELETE')
            <div class="u-table-wrap">
                <table class="u-table">
                    <thead><tr>
                        <th scope="col"><label><input type="checkbox" id="sv-all"> All</label></th>
                        <th scope="col">Portfolio</th><th scope="col">Collection</th><th scope="col" class="num">Amount</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($portfolios as $p)
                        <tr>
                            <td><input type="checkbox" name="remove[]" value="{{ $p->portfolio_id }}" aria-label="Select {{ $p->title }}"></td>
                            <td><a class="text-link" href="{{ route('portfolios.show', $p->portfolio_id) }}">{{ $p->title }}</a>
                                @if ($p->sdg_num !== null) <small>SDG {{ $p->sdg_num }}</small> @endif</td>
                            <td>{{ $collectionNames[$p->dataset] ?? $p->dataset }}</td>
                            <td class="num">@if ($p->total === null) Not yet costed @else {{ number_format((float) $p->total, 2) }} {{ $amountUnits[$p->dataset] ?? '' }} @endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p style="margin-top:14px">
                <button class="button" type="submit">Remove selected</button>
                <button class="button button-secondary" type="submit" name="all" value="1">Clear all</button>
                <a class="text-link" style="margin-left:14px" href="{{ route('recommendations.index') }}">Back to results</a>
            </p>
        </form>
        <script>
        (function(){var a=document.getElementById('sv-all'),b=[].slice.call(document.querySelectorAll('input[name="remove[]"]'));
        a.addEventListener('change',function(){b.forEach(function(x){x.checked=a.checked})});})();
        </script>
    @endif
</div>
@endsection
