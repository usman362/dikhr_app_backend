{{--
  Shared layout for the public legal pages (Privacy Policy, Terms of
  Service). Apple + Google review require these to be reachable from
  publicly-hosted URLs, and we link to them from the in-app paywall —
  so the styling has to look professional but stay framework-agnostic
  (no Laravel/Filament hints in the rendered HTML).

  Used as `<x-legal.layout title="..." :effective-date="$date">...</x-legal.layout>`.
  Props become the local $title / $effectiveDate variables below.
--}}
@props(['title', 'effectiveDate'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>{{ $title }} · Community Dhikr</title>
    <meta name="description" content="{{ $title }} for the Community Dhikr mobile app.">
    <style>
        :root {
            --primary: #0D6B3F;
            --primary-light: #2E8B57;
            --bg: #F7F5F0;
            --card-bg: #FFFFFF;
            --text: #1A1A1A;
            --text-soft: #4B5563;
            --hint: #6B7280;
            --border: #E5E7EB;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
                         'Helvetica Neue', Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Header ─────────────────────────────────────────────── */
        header {
            background: var(--primary);
            color: #fff;
            padding: 24px 24px 32px;
        }
        .header-inner {
            max-width: 760px;
            margin: 0 auto;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 14px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            margin-bottom: 16px;
        }
        .brand-mark {
            width: 28px; height: 28px;
            border-radius: 8px;
            background: rgba(255,255,255,0.18);
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 800;
            color: #fff;
        }
        h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.4px;
            margin-bottom: 6px;
        }
        .effective {
            color: rgba(255,255,255,0.78);
            font-size: 14px;
        }

        /* ── Content card ───────────────────────────────────────── */
        main {
            max-width: 760px;
            margin: -16px auto 48px;
            padding: 0 16px;
        }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 8px 24px -12px rgba(0,0,0,0.1);
        }
        @media (max-width: 540px) {
            .card { padding: 24px 20px; }
            h1 { font-size: 26px; }
        }

        h2 {
            font-size: 19px;
            font-weight: 700;
            color: var(--primary);
            margin: 28px 0 10px;
        }
        h2:first-child { margin-top: 0; }

        p {
            margin: 0 0 12px;
            color: var(--text-soft);
            font-size: 15px;
        }
        ul {
            margin: 0 0 16px 22px;
            color: var(--text-soft);
            font-size: 15px;
        }
        li { margin-bottom: 4px; }

        a {
            color: var(--primary);
            text-decoration: underline;
        }
        a:hover { color: var(--primary-light); }

        /* ── Footer ─────────────────────────────────────────────── */
        footer {
            text-align: center;
            color: var(--hint);
            font-size: 12px;
            padding: 20px 16px 40px;
        }
        footer a {
            color: var(--hint);
            margin: 0 8px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-inner">
            <a href="/" class="brand">
                <span class="brand-mark">CD</span>
                Community Dhikr
            </a>
            <h1>{{ $title }}</h1>
            <div class="effective">Effective date: {{ $effectiveDate }}</div>
        </div>
    </header>

    <main>
        <div class="card">
            {{ $slot }}
        </div>
    </main>

    <footer>
        <a href="/privacy-policy">Privacy Policy</a>·
        <a href="/terms-of-service">Terms of Service</a>·
        <a href="mailto:Info@emhai.dk">Contact</a>
        <div style="margin-top:8px;">© {{ date('Y') }} Community Dhikr</div>
    </footer>
</body>
</html>
