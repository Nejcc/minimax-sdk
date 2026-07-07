@extends('minimax::layout')

@section('title', 'Dashboard')
@section('subtitle', 'Overview & configuration')

@section('content')
    @if ($fake)
        <div class="banner">
            <strong>FAKE MODE</strong> — no real Minimax calls. Fill credentials and set
            <code>MINIMAX_FAKE=false</code> to go live.
        </div>
    @endif

    <div class="tiles">
        <div class="tile">
            <div class="label">Mode</div>
            <div class="val {{ $fake ? 'warn' : 'good' }}">{{ $fake ? 'Fake' : 'Live' }}</div>
        </div>
        <div class="tile">
            <div class="label">Localization</div>
            <div class="val">{{ strtoupper($loc) }}</div>
        </div>
        <div class="tile">
            <div class="label">Credentials</div>
            <div class="val {{ $credsReady ? 'good' : 'bad' }}">{{ $credsReady ? 'Ready' : 'Missing' }}</div>
        </div>
        <div class="tile">
            <div class="label">Organisation</div>
            <div class="val {{ $orgId ? '' : 'warn' }}">{{ $orgId ?: '—' }}</div>
        </div>
    </div>

    <div class="panel">
        <h3>Configuration</h3>
        <table class="env">
            @foreach ($env as $key => $row)
                <tr>
                    <td class="k">{{ $key }}</td>
                    <td class="v">
                        @if ($row['set'])
                            <span class="pill on">set</span> <span class="mono">{{ $row['value'] }}</span>
                        @else
                            <span class="pill off">missing</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <a class="btn ghost" style="margin:0" href="{{ url($prefix.'/diagnostics') }}">Run live diagnostics →</a>
@endsection
