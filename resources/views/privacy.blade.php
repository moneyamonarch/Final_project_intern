@extends('layouts.app')
@section('title', 'Privacy | jTech Nepal')
@section('content')
<div class="page-shell mx">
    <div class="u-head"><p class="u-eyebrow">Privacy</p><h1>What this site stores</h1></div>
    <p>Your questionnaire answers are kept in a session cookie. When you open results, an anonymous record of the answers-derived profile, the ranked list shown and any feedback you give (helpful, not helpful, save, click) is stored.</p>
    <ul>
        <li>No names, emails or IP addresses are stored by the application.</li>
        <li>Records are deleted automatically after 180 days.</li>
        <li>Shared links show only the ranked list and expire after 30 days.</li>
        <li>Feedback is used to adjust scoring weights only after at least 100 labeled samples. Demo profiles are flagged as synthetic.</li>
    </ul>
    <p class="u-note">Illustrative decision support, not financial advice.</p>
</div>
@endsection
