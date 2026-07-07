@extends('minimax::layout')

@section('title', 'Diagnostics')
@section('subtitle', 'Live connectivity checks')

@section('content')
    @if ($fake)
        <div class="banner"><strong>FAKE MODE</strong> — checks run against canned fixtures.</div>
    @endif

    <div class="panel">
        <h3>Connectivity</h3>
        <ul class="checks">
            @foreach ($checks as $name => $check)
                @php $ok = $check['ok'] ?? null; @endphp
                <li>
                    <span class="dot {{ $ok === true ? 'ok' : ($ok === false ? 'fail' : 'skip') }}">
                        {{ $ok === true ? '✓' : ($ok === false ? '✕' : '–') }}
                    </span>
                    <div style="min-width:0">
                        <div class="name">{{ $name }}</div>
                        <div class="detail">{{ $check['detail'] }}</div>
                        @if (! empty($check['body']))
                            <pre>{{ is_string($check['body']) ? $check['body'] : json_encode($check['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
