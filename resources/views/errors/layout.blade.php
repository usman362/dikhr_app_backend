{{--
  Single neutral error layout used by every error code (404/403/500).
  Deliberately framework-agnostic — no Laravel mentions, no version
  numbers, no Blade comments leaking to the rendered HTML. The visual
  language matches the Community Dhikr mobile app so the admin panel
  visitor sees one consistent product.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'Error' }} · Community Dhikr</title>
    <style>
        :root {
            --primary: #0D6B3F;
            --primary-light: #2E8B57;
            --bg: #F7F5F0;
            --text: #1A1A1A;
            --hint: #6B7280;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            max-width: 480px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(13,107,63,0.1);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
        }
        h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 12px;
            line-height: 1.2;
        }
        p {
            font-size: 15px;
            color: var(--hint);
            line-height: 1.5;
            margin-bottom: 24px;
        }
        a.btn {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            transition: background 0.15s ease;
        }
        a.btn:hover { background: var(--primary-light); }
        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: var(--hint);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">{{ $code ?? '???' }}</div>
        <h1>{{ $title ?? 'Something went wrong' }}</h1>
        <p>{{ $message ?? 'The page you were looking for could not be loaded.' }}</p>
        <a href="/" class="btn">Go Home</a>
        <div class="footer">Community Dhikr</div>
    </div>
</body>
</html>
