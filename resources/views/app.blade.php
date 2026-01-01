<!DOCTYPE html>
<html lang="en" @class(['dark' => ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csp-nonce" content="{{ app('csp-nonce') }}">
    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script nonce="{{ app('csp-nonce') }}">
        (function () {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
            if (typeof window !== 'undefined') { window.Apex = { chart: { nonce: "{{ app('csp-nonce') }}" } }; }
        })();
    </script>


    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">

    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased scrollbar">
    @inertia


    {{-- Analytics - only for guests in production --}}
    @if(auth()->guest() && app()->environment('production'))
        @php
            $ga4Id = \App\Models\Setting::get('advanced_analytics_ga4_id');
            $plausibleDomain = \App\Models\Setting::get('advanced_analytics_plausible_domain');
            $clarityId = \App\Models\Setting::get('advanced_analytics_clarity_id');
        @endphp

        {{-- Google Analytics 4 --}}
        @if($ga4Id)
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
            <script nonce="{{ app('csp-nonce') }}">
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ $ga4Id }}');
            </script>
        @endif

        {{-- Plausible Analytics --}}
        @if($plausibleDomain)
            <script defer data-domain="{{ $plausibleDomain }}" src="https://plausible.io/js/script.js"></script>
        @endif

        {{-- Microsoft Clarity --}}
        @if($clarityId)
            <script nonce="{{ app('csp-nonce') }}">
                (function(c,l,a,r,i,t,y){
                    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
                })(window, document, "clarity", "script", "{{ $clarityId }}");
            </script>
        @endif
    @endif
</body>

</html>