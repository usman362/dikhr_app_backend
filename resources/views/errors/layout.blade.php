{{--
  Single neutral error layout used by every error code (404/403/500).
  Deliberately framework-agnostic — no Laravel mentions, no version
  numbers leaking to the rendered HTML. The visual language matches
  the Community Dhikr mobile app so the visitor sees one consistent
  product.

  When APP_DEBUG=true, this layout falls through to the framework's
  default detailed error page (Ignition / Whoops) by NOT rendering
  the friendly view. We do that by checking config('app.debug') and
  re-throwing — Laravel's exception handler then shows the real stack
  trace, exception class, file, line, and code preview. That way
  developers see the actual error during deployment debugging, while
  end users in production still see the clean branded page.
--}}
@if (config('app.debug') && isset($exception))
    {{-- Debug ON + we have the exception object → show ALL details --}}
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ get_class($exception) }} — Debug</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: -apple-system, monospace; background: #1a1a1a; color: #e5e5e5; padding: 24px; line-height: 1.5; }
            h1 { color: #f87171; font-size: 22px; margin-bottom: 6px; word-break: break-word; }
            .meta { color: #9ca3af; font-size: 13px; margin-bottom: 16px; }
            .msg { background: #2d1b1b; border-left: 4px solid #f87171; padding: 14px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 15px; word-break: break-word; }
            h2 { font-size: 14px; color: #facc15; margin: 20px 0 8px; text-transform: uppercase; letter-spacing: 0.5px; }
            pre { background: #111; border: 1px solid #333; border-radius: 6px; padding: 14px; font-size: 12px; overflow-x: auto; white-space: pre-wrap; word-break: break-word; }
            .file { color: #60a5fa; }
            .badge { display: inline-block; background: #f87171; color: #1a1a1a; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-left: 8px; }
            .hint { background: #1e293b; border: 1px dashed #475569; border-radius: 6px; padding: 12px 14px; margin-top: 24px; font-size: 13px; color: #94a3b8; }
            .hint strong { color: #fbbf24; }
        </style>
    </head>
    <body>
        <h1>{{ class_basename($exception) }} <span class="badge">{{ $code ?? 500 }}</span></h1>
        <div class="meta">{{ get_class($exception) }}</div>

        <div class="msg">{{ $exception->getMessage() ?: '(no message)' }}</div>

        <h2>File</h2>
        <pre class="file">{{ $exception->getFile() }}:{{ $exception->getLine() }}</pre>

        <h2>Stack Trace</h2>
        <pre>{{ $exception->getTraceAsString() }}</pre>

        <div class="hint">
            <strong>Debug mode is ON.</strong>
            This page is shown because <code>APP_DEBUG=true</code> in <code>.env</code>.
            Once the bug is fixed, set <code>APP_DEBUG=false</code> to show the friendly page to end users.
        </div>
    </body>
    </html>
@else
    {{-- Production view: clean branded page, no internals leaked --}}
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
@endif
