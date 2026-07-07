@php
    $loc = $loc ?? config('minimax.localization');
    $fake = $fake ?? config('minimax.fake');
    $prefix = $prefix ?? trim((string) config('minimax.admin_prefix'), '/');
    $here = request()->path();
    $nav = [
        ['label' => 'Dashboard', 'href' => url($prefix), 'active' => $here === $prefix],
        ['label' => 'Diagnostics', 'href' => url($prefix.'/diagnostics'), 'active' => str_ends_with($here, '/diagnostics')],
    ];
    $resources = [];
    foreach ((array) config('minimax.resources') as $slug => $label) {
        $resources[] = [
            'label' => $label,
            'href' => url($prefix.'/resources/'.$slug),
            'active' => str_ends_with($here, '/resources/'.$slug),
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minimax SDK · @yield('title', 'Admin')</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0e0f13; --panel: #171a21; --panel2: #0d0f14; --line: #242a35;
            --text: #e6e8eb; --muted: #8b93a1; --dim: #6b7280;
            --brand1: #ff5a2c; --brand2: #ff9d2c; --accent: #6fd3ff;
            --sidebar: 248px;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; }
        body {
            font: 15px/1.5 ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: var(--bg); color: var(--text);
            display: grid; grid-template-columns: var(--sidebar) 1fr; min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        code { background: var(--panel2); padding: .1rem .4rem; border-radius: 5px; color: var(--brand2); }

        /* Sidebar */
        aside {
            background: #101218; border-right: 1px solid var(--line);
            display: flex; flex-direction: column; padding: 1.1rem .8rem; position: sticky; top: 0; height: 100vh;
        }
        .brand { display: flex; align-items: center; gap: .65rem; padding: .25rem .5rem 1.1rem; }
        .brand .logo { width: 36px; height: 36px; border-radius: 10px; flex: none;
            background: linear-gradient(135deg, var(--brand1), var(--brand2)); display: grid; place-items: center;
            font-weight: 800; color: #fff; }
        .brand b { font-size: .98rem; } .brand span { display: block; font-size: .72rem; color: var(--muted); font-weight: 400; }
        .navlabel { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: var(--dim);
            padding: .9rem .75rem .35rem; font-weight: 700; }
        nav a { display: flex; align-items: center; justify-content: space-between; gap: .5rem;
            padding: .55rem .75rem; border-radius: 9px; color: var(--muted); font-size: .9rem; }
        nav a:hover { background: #171a22; color: var(--text); }
        nav a.active { background: #1c2030; color: #fff; }
        nav a.active::before { content: ''; position: absolute; }
        nav a.disabled { color: #4b515e; cursor: default; }
        nav a.disabled:hover { background: none; }
        .soon { font-size: .6rem; padding: .05rem .35rem; border-radius: 999px; background: #23262f; color: #6b7280; }
        .side-foot { margin-top: auto; padding: .75rem; font-size: .72rem; color: var(--dim); }

        /* Main */
        main { display: flex; flex-direction: column; min-width: 0; }
        .topbar { display: flex; align-items: center; gap: .9rem; padding: 1rem 1.75rem;
            border-bottom: 1px solid var(--line); position: sticky; top: 0; background: rgba(14,15,19,.85);
            backdrop-filter: blur(8px); z-index: 5; }
        .topbar h1 { margin: 0; font-size: 1.1rem; }
        .topbar .sub { font-size: .8rem; color: var(--muted); }
        .badge { font-size: .72rem; padding: .28rem .6rem; border-radius: 999px;
            background: #16202a; color: var(--accent); border: 1px solid #24343f; }
        .badge.fake { background: #3a2a12; color: #ffb84d; border-color: #5a4420; }
        .spacer { margin-left: auto; }
        .content { padding: 1.5rem 1.75rem; max-width: 1000px; }

        /* Panels */
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 14px; margin-bottom: 1.25rem; overflow: hidden; }
        .panel > h3 { margin: 0; padding: .9rem 1.25rem; font-size: .78rem; text-transform: uppercase; letter-spacing: .06em;
            color: var(--dim); border-bottom: 1px solid var(--line); font-weight: 700; }
        .banner { padding: .8rem 1rem; border-radius: 11px; font-size: .84rem; margin-bottom: 1.25rem;
            background: #2a2110; border: 1px solid #5a4420; color: #ffcf8a; }

        /* tiles */
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
        .tile { background: var(--panel); border: 1px solid var(--line); border-radius: 14px; padding: 1rem 1.1rem; }
        .tile .label { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: var(--dim); }
        .tile .val { font-size: 1.4rem; font-weight: 700; margin-top: .3rem; }
        .tile .val.good { color: #43d17e; } .tile .val.bad { color: #ff6b81; } .tile .val.warn { color: #ffb84d; }

        /* env table */
        .env { width: 100%; border-collapse: collapse; }
        .env td { padding: .6rem 1.25rem; font-size: .84rem; border-top: 1px solid var(--line); }
        .env tr:first-child td { border-top: none; }
        .env .k { color: var(--muted); font-family: ui-monospace, Menlo, monospace; white-space: nowrap; }
        .env .v { text-align: right; }
        .mono { font-family: ui-monospace, Menlo, monospace; color: #c7cdd6; }
        .pill { font-size: .7rem; padding: .14rem .55rem; border-radius: 999px; font-weight: 600; }
        .pill.on { background: #113524; color: #43d17e; } .pill.off { background: #3a1720; color: #ff6b81; }

        /* data grid */
        table.grid { width: 100%; border-collapse: collapse; font-size: .86rem; }
        table.grid th { text-align: left; padding: .6rem 1.25rem; color: var(--dim); font-size: .72rem;
            text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); white-space: nowrap; }
        table.grid td { padding: .65rem 1.25rem; border-top: 1px solid var(--line); }
        table.grid td.num { font-family: ui-monospace, Menlo, monospace; color: var(--accent); }
        table.grid tbody tr:hover { background: #1b1f28; }

        /* checks */
        ul.checks { list-style: none; margin: 0; padding: .25rem 0; }
        ul.checks li { display: flex; gap: .85rem; padding: .95rem 1.25rem; align-items: flex-start; }
        ul.checks li + li { border-top: 1px solid var(--line); }
        .dot { width: 20px; height: 20px; border-radius: 50%; flex: none; display: grid; place-items: center;
            font-size: .8rem; font-weight: 700; margin-top: 1px; }
        .dot.ok { background: #113524; color: #43d17e; } .dot.fail { background: #3a1720; color: #ff6b81; }
        .dot.skip { background: #2a2d36; color: #9aa3b2; }
        .name { font-weight: 600; } .detail { color: var(--muted); font-size: .86rem; word-break: break-word; }
        pre { margin: .45rem 0 0; padding: .6rem .75rem; background: var(--panel2); border: 1px solid var(--line);
            border-radius: 8px; font-size: .76rem; color: #c7cdd6; overflow: auto; max-height: 200px; }
        .btn { display: inline-block; padding: .6rem 1rem; border-radius: 10px; margin: 1rem 1.25rem;
            background: linear-gradient(135deg, var(--brand1), var(--brand2)); color: #fff; font-size: .88rem; font-weight: 600; }
        .btn.ghost { background: #16202a; color: var(--accent); border: 1px solid #24343f; }
        .btn:hover { filter: brightness(1.08); }

        /* error page */
        .err { text-align: center; padding: 4rem 1.5rem; }
        .err .code { font-size: 5rem; font-weight: 800; letter-spacing: -.03em; line-height: 1;
            background: linear-gradient(135deg, var(--brand1), var(--brand2)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .err h2 { margin: .5rem 0 .5rem; font-size: 1.3rem; }
        .err p { color: var(--muted); margin: 0 auto 1.5rem; max-width: 460px; }

        @media (max-width: 720px) {
            body { grid-template-columns: 1fr; }
            aside { position: static; height: auto; flex-direction: row; align-items: center; flex-wrap: wrap; }
            aside nav { display: flex; gap: .25rem; } .navlabel, .side-foot { display: none; }
        }
    </style>
</head>
<body>
    <aside>
        <a href="{{ url($prefix) }}" class="brand">
            <span class="logo">m</span>
            <span><b>Minimax</b><span>SDK Admin</span></span>
        </a>

        <div class="navlabel">General</div>
        <nav>
            @foreach ($nav as $item)
                <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'active' : '' }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="navlabel">Resources</div>
        <nav>
            @foreach ($resources as $res)
                <a href="{{ $res['href'] }}" class="{{ $res['active'] ? 'active' : '' }}">{{ $res['label'] }}</a>
            @endforeach
        </nav>

        <div class="side-foot">
            {{ $fake ? 'Fake mode' : 'Live' }} · {{ strtoupper($loc) }}<br>
            Local environment only
        </div>
    </aside>

    <main>
        <div class="topbar">
            <div>
                <h1>@yield('title', 'Admin')</h1>
                <div class="sub">@yield('subtitle', '')</div>
            </div>
            @if ($fake)
                <span class="badge fake spacer">● FAKE MODE</span>
                <span class="badge">{{ strtoupper($loc) }}</span>
            @else
                <span class="badge spacer">Live · {{ strtoupper($loc) }}</span>
            @endif
        </div>

        <div class="content">
            @yield('content')
        </div>
    </main>
</body>
</html>
