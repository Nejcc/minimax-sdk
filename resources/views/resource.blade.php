@extends('minimax::layout')

@section('title', $label)
@section('subtitle', 'Resource listing'.($fake ? ' · demo data' : ''))

@section('content')
    @if ($fake)
        <div class="banner"><strong>FAKE MODE</strong> — showing canned {{ strtolower($label) }}.</div>
    @endif

    @php
        // Columns = scalar keys from the first row.
        $columns = [];
        if (! empty($rows)) {
            foreach ($rows[0] as $k => $v) {
                if (is_scalar($v) || is_null($v)) {
                    $columns[] = $k;
                }
            }
        }
    @endphp

    <div class="panel">
        <h3>{{ $label }} · {{ count($rows) }}</h3>

        @if (empty($rows))
            <div style="padding:2rem 1.25rem; color:var(--muted)">No {{ strtolower($label) }} returned.</div>
        @else
            <div style="overflow-x:auto">
                <table class="grid">
                    <thead>
                        <tr>@foreach ($columns as $col)<th>{{ $col }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                @foreach ($columns as $col)
                                    <td class="{{ is_numeric($row[$col] ?? null) ? 'num' : '' }}">{{ $row[$col] ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <a class="btn ghost" style="margin:0" href="{{ url($prefix) }}">← Dashboard</a>
@endsection
