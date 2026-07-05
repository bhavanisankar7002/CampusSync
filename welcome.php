<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to CampusSync</title>
    <meta name="description" content="CampusSync — Connect, Collaborate, and Create with your campus community.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            background: #070e1c;
            color: #f8fafc;
            font-family: 'Outfit', system-ui, sans-serif;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Ambient background glow ── */
        .bg-glow {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }
        .bg-glow::before {
            content: '';
            position: absolute;
            top: -20%;
            left: 50%;
            transform: translateX(-50%);
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(79,172,254,0.10) 0%, transparent 70%);
            animation: breathe 6s ease-in-out infinite;
        }
        .bg-glow::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: 50%;
            transform: translateX(-50%);
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(47,111,237,0.07) 0%, transparent 70%);
            animation: breathe 8s ease-in-out infinite reverse;
        }
        @keyframes breathe {
            0%, 100% { transform: translateX(-50%) scale(1); opacity: 1; }
            50%        { transform: translateX(-50%) scale(1.15); opacity: 0.7; }
        }

        /* ── Particle dots ── */
        .particles { position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(79,172,254,0.25);
            animation: float linear infinite;
        }
        @keyframes float {
            0%   { transform: translateY(110vh) scale(0); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }

        /* ── Main stage ── */
        .stage {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
            padding: 24px;
        }

        /* ── Logo SVG ── */
        .logo-wrap {
            opacity: 0;
            animation: fadeUp .6s ease-out .1s forwards;
        }

        /* ── Wordmark ── */
        .wordmark {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(2.8rem, 7vw, 5rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
            margin-top: 28px;
            opacity: 0;
            animation: fadeUp .6s ease-out .9s forwards;
        }
        .wordmark span { color: #4facfe; }

        /* ── Tagline ── */
        .tagline-wrap {
            margin-top: 20px;
            position: relative;
            opacity: 0;
            animation: fadeUp .5s ease-out 1.3s forwards;
        }
        .tagline {
            font-size: clamp(0.9rem, 2.2vw, 1.15rem);
            font-weight: 400;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #8a94a6;
        }
        /* Animated underline */
        .tagline-line {
            display: block;
            height: 2px;
            border-radius: 99px;
            background: linear-gradient(90deg, transparent, #4facfe 40%, #2f6fed 60%, transparent);
            width: 0;
            margin: 10px auto 0;
            animation: drawLine 1s ease-out 1.8s forwards;
        }
        @keyframes drawLine {
            0%   { width: 0; opacity: 0; }
            20%  { opacity: 1; }
            100% { width: 100%; opacity: 1; }
        }

        /* ── Separator ── */
        .divider {
            width: 1px;
            height: 36px;
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.15), transparent);
            margin: 32px 0 0;
            opacity: 0;
            animation: fade .5s ease-out 2.2s forwards;
        }

        /* ── CTA Buttons ── */
        .cta-wrap {
            display: flex;
            gap: 14px;
            margin-top: 28px;
            opacity: 0;
            animation: fadeUp .5s ease-out 2.4s forwards;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #4facfe, #2f6fed);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            padding: 14px 32px;
            border-radius: 99px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
            box-shadow: 0 0 24px rgba(79,172,254,0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 36px rgba(79,172,254,0.55);
        }
        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.05);
            color: #cbd5e1;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            padding: 14px 32px;
            border-radius: 99px;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.12);
            cursor: pointer;
            transition: transform .2s ease, background .2s ease, border-color .2s ease;
        }
        .btn-ghost:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.25);
            color: #fff;
        }

        /* ── Footer hint ── */
        .hint {
            margin-top: 28px;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 0.05em;
            opacity: 0;
            animation: fade .5s ease-out 2.8s forwards;
        }

        /* ── Keyframes ── */
        @keyframes fadeUp {
            0%   { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        @keyframes fade {
            0%   { opacity: 0; }
            100% { opacity: 1; }
        }

        /* ── Icon SVG animations ── */
        .cs-box {
            transform-origin: 110px 110px;
            opacity: 0;
            animation: boxPop .55s cubic-bezier(.34,1.56,.64,1) .15s forwards;
        }
        .cs-line {
            stroke-dasharray: 70;
            stroke-dashoffset: 70;
            opacity: 0;
            animation: lineDraw .45s ease-out forwards;
        }
        .cs-l1 { animation-delay: .65s; }
        .cs-l2 { animation-delay: .78s; }
        .cs-node {
            opacity: 0;
            animation: nodePop .35s ease-out forwards;
        }
        .cs-n1 { animation-delay: .55s; transform-origin: 90px 130px; }
        .cs-n2 { animation-delay: .68s; transform-origin: 130px 100px; }
        .cs-n3 { animation-delay: .80s; transform-origin: 130px 160px; }

        @keyframes boxPop {
            0%   { opacity: 0; transform: scale(.3) rotate(-8deg); }
            100% { opacity: 1; transform: scale(1) rotate(0deg); }
        }
        @keyframes lineDraw {
            0%   { opacity: 1; stroke-dashoffset: 70; }
            100% { opacity: 1; stroke-dashoffset: 0; }
        }
        @keyframes nodePop {
            0%   { opacity: 0; transform: scale(0); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* ── Responsive ── */
        @media (max-width: 480px) {
            .cta-wrap { flex-direction: column; width: 100%; }
            .btn-primary, .btn-ghost { justify-content: center; }
        }
    </style>
</head>
<body>

    <!-- Ambient glow -->
    <div class="bg-glow"></div>

    <!-- Floating particles -->
    <div class="particles" id="particles"></div>

    <!-- Main content -->
    <div class="stage">

        <!-- Animated icon -->
        <div class="logo-wrap">
            <svg width="120" height="120" viewBox="0 0 220 220" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="iconGradWelcome" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#4facfe"/>
                        <stop offset="100%" stop-color="#2f6fed"/>
                    </linearGradient>
                </defs>
                <rect class="cs-box" x="10" y="10" width="200" height="200" rx="48"
                      fill="url(#iconGradWelcome)"
                      style="filter:drop-shadow(0 0 30px rgba(79,172,254,0.5))"/>
                <line class="cs-line cs-l1" x1="90" y1="130" x2="145" y2="90"
                      stroke="#fff" stroke-width="7" stroke-linecap="round"/>
                <line class="cs-line cs-l2" x1="90" y1="130" x2="145" y2="165"
                      stroke="#fff" stroke-width="7" stroke-linecap="round"/>
                <circle class="cs-node cs-n1" cx="90"  cy="130" r="13" fill="#fff"/>
                <circle class="cs-node cs-n2" cx="145" cy="90"  r="13" fill="#fff"/>
                <circle class="cs-node cs-n3" cx="145" cy="165" r="13" fill="#fff"/>
            </svg>
        </div>

        <!-- Wordmark -->
        <h1 class="wordmark">Campus<span>Sync</span></h1>

        <!-- Tagline with animated underline -->
        <div class="tagline-wrap">
            <p class="tagline">Connect &nbsp;·&nbsp; Collaborate &nbsp;·&nbsp; Create</p>
            <span class="tagline-line"></span>
        </div>

        <!-- Vertical divider -->
        <div class="divider"></div>

        <!-- CTA buttons -->
        <div class="cta-wrap">
            <a href="register.php" class="btn-primary">
                <i class="fa-solid fa-user-plus"></i> Get Started
            </a>
            <a href="login.php" class="btn-ghost">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
            </a>
        </div>

        <!-- Hint -->
        <p class="hint">Official platform for your college community</p>

    </div>

    <script>
        // Generate floating particles
        const container = document.getElementById('particles');
        const count = 28;
        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const size = Math.random() * 4 + 2;
            p.style.cssText = `
                width: ${size}px;
                height: ${size}px;
                left: ${Math.random() * 100}%;
                animation-duration: ${Math.random() * 18 + 10}s;
                animation-delay: ${Math.random() * 10}s;
                opacity: ${Math.random() * 0.4 + 0.1};
            `;
            container.appendChild(p);
        }
    </script>
</body>
</html>
