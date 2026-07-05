<?php
// navbar.php — include at the top of every page's <body>
// Requires: session_start() already called, $conn available.
// Optional: set $active_page = 'ideas' | 'teams' | 'hackathons' | 'dashboard' before including.
if (!isset($active_page)) $active_page = '';
$_nav_logged_in  = isset($_SESSION['user_id']);
$_nav_name       = $_nav_logged_in ? htmlspecialchars($_SESSION['name']) : '';
?>
<!-- ════════════════════════  CAMPUSSYNC NAVBAR  ════════════════════════ -->
<nav id="cs-navbar" class="cs-nav">
    <div class="cs-nav-inner">
        <!-- Logo -->
        <a href="index.php" class="cs-logo" aria-label="CampusSync home">
            <!-- Animated SVG Icon Mark -->
            <svg class="cs-logo-icon-svg" width="36" height="36" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="navIconGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#4facfe"/>
                        <stop offset="100%" stop-color="#2f6fed"/>
                    </linearGradient>
                    <style>
                        .nav-box{transform-origin:50px 50px;opacity:0;animation:navPopIn .5s cubic-bezier(.34,1.56,.64,1) .1s forwards;}
                        .nav-node{opacity:0;animation:navNodePop .35s ease-out forwards;}
                        .nav-n1{animation-delay:.55s;transform-origin:30px 62px;}
                        .nav-n2{animation-delay:.65s;transform-origin:70px 38px;}
                        .nav-n3{animation-delay:.75s;transform-origin:70px 74px;}
                        .nav-line{stroke-dasharray:55;stroke-dashoffset:55;opacity:0;animation:navDraw .4s ease-out forwards;}
                        .nav-l1{animation-delay:.7s;}
                        .nav-l2{animation-delay:.8s;}
                        @keyframes navPopIn{0%{opacity:0;transform:scale(.4);}100%{opacity:1;transform:scale(1);}}
                        @keyframes navNodePop{0%{opacity:0;transform:scale(0);}100%{opacity:1;transform:scale(1);}}
                        @keyframes navDraw{0%{opacity:1;stroke-dashoffset:55;}100%{opacity:1;stroke-dashoffset:0;}}
                    </style>
                </defs>
                <rect class="nav-box" x="5" y="5" width="90" height="90" rx="22" fill="url(#navIconGrad)"/>
                <line class="nav-line nav-l1" x1="30" y1="62" x2="70" y2="38" stroke="#fff" stroke-width="6" stroke-linecap="round"/>
                <line class="nav-line nav-l2" x1="30" y1="62" x2="70" y2="74" stroke="#fff" stroke-width="6" stroke-linecap="round"/>
                <circle class="nav-node nav-n1" cx="30" cy="62" r="9" fill="#fff"/>
                <circle class="nav-node nav-n2" cx="70" cy="38" r="9" fill="#fff"/>
                <circle class="nav-node nav-n3" cx="70" cy="74" r="9" fill="#fff"/>
            </svg>
            <span class="cs-logo-text">Campus<span style="color:#4facfe">Sync</span></span>
        </a>

        <!-- Desktop links -->
        <div class="cs-nav-links" id="cs-desktop-links">
            <a href="ideas.php"      class="cs-nav-link <?= $active_page==='ideas'      ? 'cs-nav-active':'' ?>"><i class="fa-solid fa-lightbulb"></i> Ideas</a>
            <a href="index.php#campus-showcase" class="cs-nav-link <?= $active_page==='explore' ? 'cs-nav-active':'' ?>"><i class="fa-solid fa-compass"></i> Explore</a>
            <a href="teams.php" class="cs-nav-link <?= $active_page==='hackathons' ? 'cs-nav-active':'' ?>"><i class="fa-solid fa-trophy"></i> Hackathons</a>
            <a href="post-idea.php"  class="cs-nav-link <?= $active_page==='post-idea'  ? 'cs-nav-active':'' ?>"><i class="fa-solid fa-briefcase"></i> Post Idea</a>
        </div>

        <!-- Auth section -->
        <div class="cs-nav-auth">
            <?php if ($_nav_logged_in): ?>
                <a href="profileuser.php" class="cs-nav-user">
                    <i class="fa-solid fa-circle-user"></i>
                    <span class="cs-nav-username"><?= $_nav_name ?></span>
                </a>
            <?php else: ?>
                <a href="login.php"    class="cs-btn-ghost">Login</a>
                <a href="register.php" class="cs-btn-primary">Join Free</a>
            <?php endif; ?>

            <!-- Hamburger -->
            <button class="cs-hamburger" id="cs-hamburger" onclick="csToggleMenu()" aria-label="Toggle menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>

    <!-- Mobile dropdown -->
    <div class="cs-mobile-menu hidden" id="cs-mobile-menu">
        <a href="ideas.php"      class="cs-mob-link <?= $active_page==='ideas'      ? 'cs-nav-active':'' ?>"><i class="fa-solid fa-lightbulb w-5"></i> Ideas</a>
        <a href="index.php#campus-showcase" class="cs-mob-link <?= $active_page==='explore' ? 'cs-nav-active':'' ?>"><i class="fa-solid fa-compass w-5"></i> Explore</a>
        <a href="teams.php" class="cs-mob-link <?= $active_page==='hackathons' ? 'cs-nav-active':'' ?>"><i class="fa-solid fa-trophy w-5"></i> Hackathons</a>
        <a href="post-idea.php"  class="cs-mob-link <?= $active_page==='post-idea'  ? 'cs-nav-active':'' ?>"><i class="fa-solid fa-briefcase w-5"></i> Post Idea</a>
        <?php if ($_nav_logged_in): ?>
            <a href="profileuser.php" class="cs-mob-link"><i class="fa-solid fa-circle-user w-5"></i> <?= $_nav_name ?></a>
        <?php else: ?>
            <a href="login.php"    class="cs-mob-link"><i class="fa-solid fa-arrow-right-to-bracket w-5"></i> Login</a>
            <a href="register.php" class="cs-mob-link cs-mob-join"><i class="fa-solid fa-user-plus w-5"></i> Join Free</a>
        <?php endif; ?>
    </div>
</nav>

<style>
/* ─── GLOBAL DESIGN SYSTEM ─────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

body {
    background: #0B1220;
    color: #F8FAFC;
    font-family: 'Outfit', system-ui, sans-serif;
    -webkit-font-smoothing: antialiased;
    margin: 0;
}
.cs-card {
    background: #111827;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: all 0.25s ease;
}
.cs-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
    border-color: rgba(255, 255, 255, 0.12);
}
.cs-card-secondary {
    background: #1E293B;
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
}
.cs-btn-primary {
    background: #3B82F6;
    color: #ffffff;
    font-size: .875rem;
    font-weight: 700;
    padding: 8px 20px;
    border-radius: 99px;
    text-decoration: none;
    transition: all 0.25s ease;
    border: none;
    display: inline-block;
}
.cs-btn-primary:hover { 
    background: #2563EB; 
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); 
    transform: translateY(-1px);
}
.compact-input {
    width: 100%;
    background: #0F172A;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 12px 16px;
    color: #F8FAFC;
    font-size: 0.9rem;
    outline: none;
    transition: all 0.2s ease;
}
.compact-input:focus {
    border-color: #3B82F6;
    background: #0F172A;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.compact-input::placeholder {
    color: #64748B;
}
.compact-input option {
    background: #0F172A;
    color: #F8FAFC;
}

/* ─── NAVBAR BASE ──────────────────────────────────────────── */
.cs-nav {
    position: sticky;
    top: 0;
    z-index: 999;
    background: rgba(15, 23, 42, 0.85); /* #0F172A */
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    font-family: 'Outfit', system-ui, sans-serif;
}
.cs-nav-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    height: 66px;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* ─── LOGO ─────────────────────────────────────────────────── */
.cs-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    flex-shrink: 0;
}
.cs-logo-icon-svg {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    filter: drop-shadow(0 0 8px rgba(79,172,254,0.4));
    transition: filter 0.3s ease;
}
.cs-logo:hover .cs-logo-icon-svg {
    filter: drop-shadow(0 0 14px rgba(79,172,254,0.7));
}
.cs-logo-text {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.55rem;
    font-weight: 700;
    letter-spacing: -0.04em;
    color: #fff;
    line-height: 1;
}

/* ─── DESKTOP LINKS ────────────────────────────────────────── */
.cs-nav-links {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-left: auto;
}
.cs-nav-link {
    display: flex;
    align-items: center;
    gap: 6px;
    color: rgba(255,255,255,.72);
    font-size: .875rem;
    font-weight: 500;
    padding: 7px 14px;
    border-radius: 99px;
    text-decoration: none;
    transition: background .18s, color .18s;
    white-space: nowrap;
}
.cs-nav-link:hover,
.cs-nav-link.cs-nav-active {
    background: rgba(34,211,238,.13);
    color: #22d3ee;
}

/* ─── AUTH ─────────────────────────────────────────────────── */
.cs-nav-auth {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 12px;
    flex-shrink: 0;
}
.cs-nav-user {
    display: flex;
    align-items: center;
    gap: 7px;
    color: rgba(255,255,255,.85);
    font-size: .85rem;
    font-weight: 600;
    padding: 7px 16px;
    border-radius: 99px;
    background: rgba(255,255,255,.08);
    text-decoration: none;
    transition: background .18s;
    border: 1px solid rgba(255,255,255,.1);
}
.cs-nav-user:hover,
.cs-nav-user.cs-nav-active { background: rgba(34,211,238,.15); color: #22d3ee; }
.cs-nav-username { display: none; }
@media (min-width: 768px) { .cs-nav-username { display: inline; } }
.cs-nav-logout {
    display: flex;
    align-items: center;
    gap: 5px;
    color: rgba(255,255,255,.6);
    font-size: .85rem;
    font-weight: 500;
    text-decoration: none;
    padding: 7px 12px;
    border-radius: 99px;
    transition: color .18s, background .18s;
}
.cs-nav-logout:hover { color: #f87171; background: rgba(248,113,113,.08); }
.cs-btn-ghost {
    color: rgba(255,255,255,.8);
    font-size: .875rem;
    font-weight: 500;
    padding: 7px 18px;
    border-radius: 99px;
    text-decoration: none;
    transition: background .18s;
}
.cs-btn-ghost:hover { background: rgba(255,255,255,.08); }
.cs-btn-primary {
    background: #06b6d4;
    color: #0f172a;
    font-size: .875rem;
    font-weight: 700;
    padding: 8px 20px;
    border-radius: 99px;
    text-decoration: none;
    transition: background .18s, box-shadow .18s;
    box-shadow: 0 0 14px rgba(6,182,212,.4);
}
.cs-btn-primary:hover { background: #22d3ee; box-shadow: 0 0 22px rgba(34,211,238,.5); }

/* ─── HAMBURGER ────────────────────────────────────────────── */
.cs-hamburger {
    display: none;
    background: none;
    border: none;
    color: rgba(255,255,255,.8);
    font-size: 1.35rem;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 8px;
    transition: background .18s;
}
.cs-hamburger:hover { background: rgba(255,255,255,.08); }
@media (max-width: 767px) {
    .cs-nav-links { display: none; }
    .cs-hamburger { display: flex; align-items: center; }
}

/* ─── MOBILE MENU ──────────────────────────────────────────── */
.cs-mobile-menu {
    border-top: 1px solid rgba(255,255,255,.08);
    padding: 12px 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.cs-mobile-menu.hidden { display: none; }
.cs-mob-link {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,.78);
    font-size: .9rem;
    font-weight: 500;
    padding: 10px 14px;
    border-radius: 12px;
    text-decoration: none;
    transition: background .18s, color .18s;
}
.cs-mob-link:hover,
.cs-mob-link.cs-nav-active { background: rgba(34,211,238,.12); color: #22d3ee; }
.cs-mob-logout { color: #f87171; }
.cs-mob-logout:hover { background: rgba(248,113,113,.1); color: #f87171; }
.cs-mob-join { background: rgba(6,182,212,.15); color: #22d3ee; font-weight: 700; }
.cs-mob-join:hover { background: rgba(6,182,212,.25); }

/* ─── FLOATING ACTION BUTTON ───────────────────────────────── */
.fab-container { position: fixed; bottom: 2rem; right: 2rem; z-index: 50; }
.fab-menu {
    position: absolute; bottom: 100%; right: 0; margin-bottom: 1rem;
    display: flex; flex-direction: column; gap: 0.75rem;
    opacity: 0; pointer-events: none; transform: translateY(15px) scale(0.95);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: bottom right;
}
.fab-container:hover .fab-menu, .fab-container:focus-within .fab-menu {
    opacity: 1; pointer-events: auto; transform: translateY(0) scale(1);
}
.fab-btn { transition: transform 0.3s ease, background-color 0.3s; }
.fab-container:hover .fab-btn { transform: rotate(45deg); background-color: #ef4444; }
</style>

<script>
function csToggleMenu(){
    const m = document.getElementById('cs-mobile-menu');
    m.classList.toggle('hidden');
}
// Close on outside click
document.addEventListener('click', function(e){
    const nav = document.getElementById('cs-navbar');
    if(!nav.contains(e.target)){
        document.getElementById('cs-mobile-menu').classList.add('hidden');
    }
});
</script>
