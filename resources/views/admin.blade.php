@extends('layouts.app')
@section('title', 'Admin | jTech Nepal')
@section('content')
<div class="page-shell">
    <div class="u-head"><p class="u-eyebrow">Read-only</p><h1>Data quality and learning</h1></div>
    <dl class="u-stats">
        <div><dt>Runs stored</dt><dd>{{ $runs }}</dd></div>
        <div><dt>Feedback events</dt><dd>{{ $events->sum() }}</dd></div>
        <div><dt>Synthetic events</dt><dd>{{ $synthetic }}</dd></div>
        <div><dt>Weight versions</dt><dd>{{ $weights->count() }}</dd></div>
    </dl>
    <section class="u-panel"><div class="u-panel-head"><h2>Feedback by type</h2></div><div class="u-panel-body">
        @forelse ($events as $k => $c)<span class="u-chip">{{ $k }}: {{ $c }}</span>@empty<p class="u-note">No events yet.</p>@endforelse
        <p class="u-note">Synthetic events come from the demo seeder and personas. The learning loop is real; the data is not real usage.</p>
    </div></section>
    <section class="u-panel"><div class="u-panel-head"><h2>Weights history</h2></div><div class="u-panel-body u-table-wrap">
        <table class="u-table"><thead><tr><th>Version</th><th>Weights</th><th class="num">Trained on</th><th>Active</th></tr></thead><tbody>
        @forelse ($weights as $w)<tr><td>v{{ $w->version }}</td><td><code>{{ $w->weights }}</code></td><td class="num">{{ $w->trained_on }}</td><td>{{ $w->is_active ? 'yes' : '' }}</td></tr>
        @empty<tr><td colspan="4">Defaults in use. Run <code>php artisan recommender:train</code> after 100 labeled samples.</td></tr>@endforelse
        </tbody></table>
    </div></section>
    <section class="u-panel"><div class="u-panel-head"><h2>Data-quality report</h2><span>portfolios:validate</span></div><div class="u-panel-body">
        <pre class="dq">{{ $dq ? json_encode($dq, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'No report yet. Run php artisan portfolios:validate.' }}</pre>
    </div></section>
</div>
@endsection
