<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle ?? 'impactNav') ?> — impactNav</title>

  <!-- Plus Jakarta Sans: Türkçe karakter desteği (ş ğ ü ö ç ı) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
          },
          colors: {
            surface: {
              950: '#05040c',
              900: '#08071a',
              800: '#0f0d1f',
              750: '#131128',
              700: '#1a1730',
              600: '#221e3a',
              500: '#2d2850',
            },
            violet: {
              50:  '#f5f3ff',
              100: '#ede9fe',
              200: '#ddd6fe',
              300: '#c4b5fd',
              400: '#a78bfa',
              500: '#8b5cf6',
              600: '#7c3aed',
              700: '#6d28d9',
              800: '#5b21b6',
              900: '#4c1d95',
              950: '#2e1065',
            },
          },
          backgroundImage: {
            'glow-violet': 'radial-gradient(ellipse 60% 50% at 50% 0%, rgba(139,92,246,0.15) 0%, transparent 70%)',
            'hero-overlay': 'linear-gradient(135deg, rgba(5,4,12,0.92) 0%, rgba(8,7,26,0.85) 50%, rgba(15,13,31,0.9) 100%)',
          },
          animation: {
            'fade-in': 'fadeIn 0.4s ease forwards',
            'slide-up': 'slideUp 0.5s ease forwards',
            'pulse-slow': 'pulse 3s ease-in-out infinite',
          },
          keyframes: {
            fadeIn:  { from: { opacity: '0' }, to: { opacity: '1' } },
            slideUp: { from: { opacity: '0', transform: 'translateY(16px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
          },
          boxShadow: {
            'glow-sm': '0 0 20px rgba(139,92,246,0.2)',
            'glow':    '0 0 40px rgba(139,92,246,0.3)',
            'glow-lg': '0 0 80px rgba(139,92,246,0.35)',
            'card':    '0 4px 24px rgba(0,0,0,0.4)',
          },
        },
      },
    };
  </script>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

  <style>
    * { box-sizing: border-box; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #08071a; }
    ::selection { background: rgba(139,92,246,0.35); color: #f5f3ff; }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: #05040c; }
    ::-webkit-scrollbar-thumb { background: rgba(139,92,246,0.4); border-radius: 99px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(139,92,246,0.7); }
    .glass { background: rgba(15,13,31,0.7); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
    .gradient-text { background: linear-gradient(135deg, #a78bfa 0%, #22d3ee 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .gradient-border { position: relative; }
    .gradient-border::before { content: ''; position: absolute; inset: 0; border-radius: inherit; padding: 1px; background: linear-gradient(135deg, rgba(139,92,246,0.6), rgba(34,211,238,0.3)); -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
    input:-webkit-autofill, input:-webkit-autofill:hover, input:-webkit-autofill:focus { -webkit-box-shadow: 0 0 0 1000px #131128 inset; -webkit-text-fill-color: #f1f0ff; caret-color: #f1f0ff; }
    .chat-typing span { width: 7px; height: 7px; background: #8b5cf6; border-radius: 50%; display: inline-block; animation: chatBounce 1.2s infinite; }
    .chat-typing span:nth-child(2) { animation-delay: .2s; }
    .chat-typing span:nth-child(3) { animation-delay: .4s; }
    @keyframes chatBounce { 0%,60%,100% { transform: translateY(0); } 30% { transform: translateY(-7px); } }
  </style>
</head>
<body class="bg-surface-900 text-slate-100 antialiased">
