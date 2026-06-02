{{--
  Public homepage for emhai.dk.

  Visitors landing on the bare host previously got redirected to the
  admin login — useless for end users and Apple/Google reviewers who
  click through from the App Store / Play Store listings. This page
  replaces that redirect with a proper marketing landing page:

    • Branded hero matching the in-app Community Dhikr / UmmahDhikr look
    • Feature highlights pulled straight from the in-app paywall copy
    • Store badges that send visitors to the Play Store listing
    • Footer links to the legal pages the app's paywall already links to
      (/privacy-policy, /terms-of-service) and a discreet /admin link
      for the operator

  Framework-agnostic intentionally — no Laravel/Filament hints in the
  rendered HTML so the public surface stays clean.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>UmmahDhikr · Community Dhikr</title>
    <meta name="description" content="UmmahDhikr — count your daily dhikr, join live global Ummah campaigns, and remember Allah with the community.">
    <meta property="og:title" content="UmmahDhikr · Community Dhikr">
    <meta property="og:description" content="Count your daily dhikr, join live global Ummah campaigns, and remember Allah with the community.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://emhai.dk/">
    <style>
        :root {
            --primary: #0D6B3F;
            --primary-light: #2E8B57;
            --primary-dark: #065F46;
            --gold: #D4A437;
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

        /* ── Hero ──────────────────────────────────────────────────── */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 80px 24px 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(212,164,55,0.08), transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(46,139,87,0.15), transparent 40%);
            pointer-events: none;
        }
        .hero-inner {
            max-width: 720px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        .badge {
            display: inline-block;
            padding: 6px 16px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 24px;
        }
        .hero h1 {
            font-size: clamp(36px, 6vw, 56px);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.1;
            margin-bottom: 16px;
        }
        .hero p.lede {
            font-size: clamp(16px, 2vw, 19px);
            opacity: 0.92;
            max-width: 540px;
            margin: 0 auto 36px;
        }
        .bismillah {
            font-size: 26px;
            opacity: 0.85;
            margin-bottom: 28px;
            line-height: 1.8;
        }

        /* ── Store buttons ─────────────────────────────────────────── */
        .store-row {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .store-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 22px;
            background: #fff;
            color: var(--text);
            text-decoration: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 14px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 4px 14px rgba(0,0,0,0.18);
        }
        .store-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(0,0,0,0.24);
        }
        .store-btn svg { flex-shrink: 0; }
        .store-btn small {
            display: block;
            font-size: 10px;
            font-weight: 500;
            color: var(--hint);
            line-height: 1.2;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .store-btn strong { font-size: 15px; }

        /* ── Features ──────────────────────────────────────────────── */
        section.features {
            padding: 80px 24px;
            max-width: 1080px;
            margin: 0 auto;
        }
        .section-title {
            text-align: center;
            font-size: clamp(26px, 4vw, 34px);
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.01em;
        }
        .section-sub {
            text-align: center;
            color: var(--text-soft);
            max-width: 540px;
            margin: 0 auto 48px;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 22px;
        }
        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 28px 24px;
            transition: transform 0.15s ease, border-color 0.15s ease;
        }
        .feature-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-light);
        }
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: rgba(13, 107, 63, 0.1);
            color: var(--primary);
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .feature-card h3 {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .feature-card p {
            font-size: 14px;
            color: var(--text-soft);
            line-height: 1.55;
        }

        /* ── Trial banner ──────────────────────────────────────────── */
        .trial {
            background: var(--card-bg);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 56px 24px;
            text-align: center;
        }
        .trial-inner {
            max-width: 620px;
            margin: 0 auto;
        }
        .trial h2 {
            font-size: clamp(22px, 3.5vw, 28px);
            font-weight: 800;
            margin-bottom: 10px;
        }
        .trial p {
            color: var(--text-soft);
            margin-bottom: 24px;
        }
        .trial-pill {
            display: inline-block;
            background: rgba(212,164,55,0.15);
            color: #8a6a1f;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        /* ── Footer ────────────────────────────────────────────────── */
        footer {
            padding: 40px 24px;
            text-align: center;
            color: var(--hint);
            font-size: 13px;
            background: var(--bg);
            border-top: 1px solid var(--border);
        }
        footer .links {
            margin-bottom: 14px;
        }
        footer a {
            color: var(--primary);
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
        }
        footer a:hover { text-decoration: underline; }
        footer .copy { font-size: 12px; opacity: 0.7; }
    </style>
</head>
<body>
    <header class="hero">
        <div class="hero-inner">
            <span class="badge">Community Dhikr</span>
            <h1>Count Every Dhikr.<br>Together as Ummah.</h1>
            <div class="bismillah">
                {{-- Bismillah ar-Rahman ar-Raheem --}}
                بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ
            </div>
            <p class="lede">
                A beautiful counter for your daily dhikr, live global Ummah
                campaigns, and private groups to remember Allah together.
            </p>
            <div class="store-row">
                <a class="store-btn" href="https://play.google.com/store/apps/details?id=com.emhai.ummahdhikr" target="_blank" rel="noopener">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 20.5V3.5C3 2.91 3.34 2.39 3.84 2.15L13.69 12L3.84 21.85C3.34 21.6 3 21.09 3 20.5Z" fill="#34A853"/>
                        <path d="M16.81 15.12L6.05 21.34L14.54 12.85L16.81 15.12Z" fill="#FBBC04"/>
                        <path d="M20.16 10.81C20.5 11.08 20.75 11.5 20.75 12C20.75 12.5 20.53 12.9 20.18 13.18L17.89 14.5L15.39 12L17.89 9.5L20.16 10.81Z" fill="#EA4335"/>
                        <path d="M6.05 2.66L16.81 8.88L14.54 11.15L6.05 2.66Z" fill="#4285F4"/>
                    </svg>
                    <span>
                        <small>Get it on</small>
                        <strong>Google Play</strong>
                    </span>
                </a>
                <a class="store-btn" href="#" style="opacity:0.55; cursor:default;" aria-disabled="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="#1A1A1A" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12 7.25C11.85 5 13.69 3.15 15.79 3c.27 2.34-2.05 4.26-3.79 4.25z"/>
                    </svg>
                    <span>
                        <small>Coming soon</small>
                        <strong>App Store</strong>
                    </span>
                </a>
            </div>
        </div>
    </header>

    <section class="features" id="features">
        <h2 class="section-title">Built for Daily Remembrance</h2>
        <p class="section-sub">
            Everything you need to keep your dhikr going, alone or with the
            wider Muslim community.
        </p>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
                </div>
                <h3>Unlimited Dhikr Counter</h3>
                <p>Tap-friendly tasbeeh counter with multiple dhikrs, target presets and milestone celebrations.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <h3>Live Global Campaigns</h3>
                <p>Join the wider Ummah in collective dhikr drives — real-time totals updated as believers contribute worldwide.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Private Groups</h3>
                <p>Create a circle for your family, friends or masjid — count together, track progress and motivate each other.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <h3>Personal Stats &amp; Streaks</h3>
                <p>See your daily totals, ongoing streak and full history — a gentle nudge to keep your tongue moist with remembrance.</p>
            </div>
        </div>
    </section>

    <section class="trial">
        <div class="trial-inner">
            <span class="trial-pill">7 Days Free</span>
            <h2>Start your free trial today</h2>
            <p>Try every feature for a week. Cancel anytime from your subscription settings.</p>
            <a class="store-btn" href="https://play.google.com/store/apps/details?id=com.emhai.ummahdhikr" target="_blank" rel="noopener" style="background: var(--primary); color: #fff; box-shadow: 0 8px 22px rgba(13,107,63,0.35);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 20.5V3.5C3 2.91 3.34 2.39 3.84 2.15L13.69 12L3.84 21.85C3.34 21.6 3 21.09 3 20.5Z" fill="#34A853"/>
                    <path d="M16.81 15.12L6.05 21.34L14.54 12.85L16.81 15.12Z" fill="#FBBC04"/>
                    <path d="M20.16 10.81C20.5 11.08 20.75 11.5 20.75 12C20.75 12.5 20.53 12.9 20.18 13.18L17.89 14.5L15.39 12L17.89 9.5L20.16 10.81Z" fill="#EA4335"/>
                    <path d="M6.05 2.66L16.81 8.88L14.54 11.15L6.05 2.66Z" fill="#4285F4"/>
                </svg>
                <span style="color: #fff;">
                    <small style="color: rgba(255,255,255,0.75);">Download on</small>
                    <strong>Google Play</strong>
                </span>
            </a>
        </div>
    </section>

    <footer>
        <div class="links">
            <a href="/privacy-policy">Privacy Policy</a>
            <a href="/terms-of-service">Terms of Service</a>
            <a href="mailto:info@emhai.dk">Contact</a>
            <a href="/admin/login">Admin</a>
        </div>
        <div class="copy">&copy; {{ date('Y') }} Community Dhikr. All rights reserved.</div>
    </footer>
</body>
</html>
