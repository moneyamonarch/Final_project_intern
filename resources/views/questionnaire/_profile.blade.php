@foreach ($profileStep['questions'] as $q)
    @php($val = data_get($answers, 'profile.'.$q['key']))
    <fieldset class="question-group">
        <legend>{{ $q['label'] }}</legend>
        @if (! empty($q['help']))<p class="field-note">{{ $q['help'] }}</p>@endif
        @if ($q['type'] === 'single')
            <div class="question-options">
                @foreach ($q['options'] as $value => $text)
                    <label class="question-option" for="q-{{ $q['key'] }}-{{ $value }}">
                        <input id="q-{{ $q['key'] }}-{{ $value }}" type="radio" name="{{ $q['key'] }}" value="{{ $value }}" @checked((string) $val === (string) $value)>
                        <span>{{ $text }}</span>
                    </label>
                @endforeach
            </div>
        @elseif ($q['type'] === 'number')
            <input class="text-input" type="number" min="0" step="any" name="{{ $q['key'] }}" value="{{ old($q['key'], $val) }}" inputmode="numeric">
        @else
            @php($lo = $q['type'] === 'scale5' ? 1 : 0)
            @php($hi = $q['type'] === 'scale5' ? 5 : 100)
            <input type="range" min="{{ $lo }}" max="{{ $hi }}" name="{{ $q['key'] }}" value="{{ old($q['key'], $val ?? intdiv($lo + $hi, 2)) }}" oninput="this.nextElementSibling.value=this.value" aria-label="{{ $q['label'] }}">
            <output>{{ old($q['key'], $val ?? intdiv($lo + $hi, 2)) }}</output>
        @endif
        @error($q['key'])<p class="field-note" role="alert">{{ $message }}</p>@enderror
    </fieldset>
@endforeach