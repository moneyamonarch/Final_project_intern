@php($s = config('sdgs.'.(int) $n))
<span class="sdg-chip" @if ($s) style="--c:{{ $s['color'] }}" title="SDG {{ (int) $n }}: {{ $s['name'] }}" @endif>
    <b>{{ (int) $n }}</b>{{ $s ? $s['name'] : 'SDG '.(int) $n }}
</span>
