<section class="personas" aria-labelledby="personas-title">
    <div class="hx-section">
        <h2 id="personas-title">Try a sample profile</h2>
        <p class="u-note">One click loads a demo investor and opens ranked results. These are sample inputs, not real visitors.</p>
    </div>
    <div class="px-grid">
        @foreach (config('personas') as $key => $persona)
            <form class="px-card" method="POST" action="{{ route('demo.start', $key) }}">
                @csrf
                <h3>{{ $persona['label'] }}</h3>
                <p>{{ $persona['description'] }}</p>
                <button class="button" type="submit">Open this profile &rarr;</button>
            </form>
        @endforeach
    </div>
</section>
