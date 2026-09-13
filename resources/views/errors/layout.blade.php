<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4338ca">
    <title>@yield('title', 'Kesalahan') — Holding Portal</title>
    <style>
        :root {
            color-scheme: light;
            --surface: #f7f7fc;
            --surface-card: #ffffff;
            --surface-muted: #f1f1f9;
            --text: #191830;
            --text-muted: #474663;
            --border: rgba(25, 24, 48, 0.12);
            --indigo: #4338ca;
            --indigo-deep: #2f2a7a;
            --indigo-soft: rgba(67, 56, 202, 0.1);
            --teal: #0f766e;
            --amber: #b45309;
            --shadow: 0 24px 60px rgba(47, 42, 122, 0.14);
        }

        [data-theme='dark'] {
            color-scheme: dark;
            --surface: #131120;
            --surface-card: #191728;
            --surface-muted: #1f1d30;
            --text: #e7e6f3;
            --text-muted: #b4b2ca;
            --border: rgba(231, 230, 243, 0.14);
            --indigo: #8b7ff5;
            --indigo-deep: #6d5ff0;
            --indigo-soft: rgba(139, 127, 245, 0.16);
            --teal: #2dd4bf;
            --amber: #fbbf24;
            --shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: radial-gradient(circle at 80% 10%, var(--indigo-soft), transparent 45%), var(--surface);
            color: var(--text);
            font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.6;
        }

        .error-shell {
            width: min(100%, 43rem);
            padding: 40px;
            border: 1px solid var(--border);
            border-radius: 24px;
            background: var(--surface-card);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 36px;
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--indigo-soft);
            color: var(--indigo);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .brand-mark {
            width: 12px;
            height: 12px;
            border-radius: 4px;
            background: linear-gradient(135deg, var(--indigo), var(--teal));
        }

        .icon-frame {
            display: grid;
            place-items: center;
            width: 92px;
            height: 92px;
            margin: 0 auto 28px;
            border-radius: 28px;
            background: linear-gradient(135deg, var(--indigo-deep), var(--indigo));
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);
        }

        .icon-frame svg { width: 52px; height: 52px; color: white; }
        .badge { display: inline-block; margin-bottom: 18px; padding: 7px 14px; border-radius: 999px; background: rgba(180, 83, 9, 0.12); color: var(--amber); font-size: 0.78rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(1.7rem, 5vw, 2.5rem); font-weight: 700; letter-spacing: -0.03em; }
        .description { max-width: 38rem; margin: 16px auto 0; color: var(--text-muted); font-size: 1rem; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 32px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 3.5rem; padding: 0 28px; border: 0; border-radius: 14px; background: linear-gradient(90deg, var(--indigo-deep), var(--indigo)); color: white; font: inherit; font-weight: 650; text-decoration: none; cursor: pointer; transition: transform 150ms ease, filter 150ms ease; }
        .button:hover { filter: brightness(1.08); transform: translateY(-1px); }
        .button-secondary { background: transparent; border: 1px solid var(--border); color: var(--indigo); }
        .support { margin: 20px 0 0; font-size: 0.9rem; color: var(--text-muted); }

        @media (max-width: 540px) {
            .error-shell { padding: 30px 22px; }
            .button { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="error-shell">
        <span class="brand"><span class="brand-mark" aria-hidden="true"></span> Holding Portal</span>
        <div class="icon-frame" aria-hidden="true">@yield('icon')</div>
        <span class="badge">HTTP @yield('code')</span>
        <h1>@yield('message')</h1>
        <p class="description">@yield('description')</p>
        <div class="actions">@yield('actions')</div>
        <p class="support">@yield('support', 'Gunakan tombol di atas untuk kembali ke area portal yang tersedia.')</p>
    </main>
    <script>
        (function () {
            try {
                var mode = localStorage.getItem('holding-theme') || 'system';
                var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            } catch (error) {}
        })();
    </script>
</body>
</html>
