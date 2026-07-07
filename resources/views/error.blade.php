@extends('minimax::layout')

@section('title', $code)
@section('subtitle', 'Error')

@section('content')
    <div class="err">
        <div class="code">{{ $code }}</div>
        <h2>{{ $heading }}</h2>
        <p>{{ $message }}</p>
        @if (! empty($body))
            <pre style="text-align:left; max-width:520px; margin:0 auto 1.5rem;">{{ is_string($body) ? $body : json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif
        <a class="btn ghost" href="{{ $home }}">← Back to dashboard</a>
    </div>
@endsection
