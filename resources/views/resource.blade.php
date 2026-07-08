@extends('minimax::layout')

@section('title', $label)
@section('subtitle', 'Resource listing'.($fake ? ' · demo data' : ''))
@section('contentClass', 'wide')

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

    @if (request('ok'))
        <div class="banner" style="border-color:#16794033;background:#16794015;color:#16a34a">{{ request('ok') }}</div>
    @elseif (request('error'))
        <div class="banner" style="border-color:#b91c1c33;background:#b91c1c15;color:#ef4444">{{ request('error') }}</div>
    @endif

    @if ($slug === 'issuedinvoices')
        <form class="panel" method="post" action="{{ url($prefix.'/resources/issuedinvoices/issue') }}"
              style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;padding:1rem 1.25rem">
            <label style="display:flex;flex-direction:column;gap:.35rem;font-size:.85rem;color:var(--muted)">
                Izdaj test račun za naročilo ID
                <input type="number" name="order_id" required min="1"
                       style="padding:.5rem .65rem;border:1px solid var(--border,#334);border-radius:6px;background:transparent;color:inherit">
            </label>
            <button class="btn" type="submit" style="margin:0">Izdaj račun</button>
        </form>
    @endif

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
