<?php
// footer.php — include at the bottom of every page before </body>
?>
<!-- ════════════════════════  CAMPUSSYNC FOOTER  ════════════════════════ -->
<footer class="cs-footer">
    <div class="cs-footer-inner">
        <div class="cs-footer-brand">
            <div class="cs-footer-logo">
                <div class="cs-logo-icon">🔗</div>
                <span class="cs-logo-text">CampusSync</span>
            </div>
            <p class="cs-footer-tagline">Your college's official collaboration platform.<br>Built for students, powered by faculty.</p>
            <p class="cs-footer-copy">for Lendi College Students • © 2026</p>
        </div>

        <div class="cs-footer-links">
            <div>
                <p class="cs-footer-heading">Platform</p>
                <a href="ideas.php"      class="cs-footer-link">Ideas Feed</a>
                <a href="teams.php"      class="cs-footer-link">Teams</a>
                <a href="teams.php" class="cs-footer-link">Hackathons</a>
                <a href="post-idea.php"  class="cs-footer-link">Post an Idea</a>
            </div>
            <div>
                <p class="cs-footer-heading">Account</p>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php"   class="cs-footer-link">Dashboard</a>
                    <a href="profileuser.php" class="cs-footer-link">My Profile</a>
                    <a href="logout.php"      class="cs-footer-link">Logout</a>
                <?php else: ?>
                    <a href="login.php"    class="cs-footer-link">Login</a>
                    <a href="register.php" class="cs-footer-link">Register</a>
                <?php endif; ?>
            </div>
            <div>
                <p class="cs-footer-heading">Connect</p>
                <div class="cs-footer-socials">
                    <a href="https://www.linkedin.com/school/lendi-institute-of-engineering-and-technology-liet/"
                       target="_blank" rel="noopener noreferrer"
                       aria-label="Lendi College on LinkedIn"
                       title="LinkedIn">
                        <i class="fa-brands fa-linkedin"></i>
                    </a>
                    <a href="https://www.instagram.com/lendienggcollege?igsh=MXR4eDBrZXZ0Y3d0cQ=="
                       target="_blank" rel="noopener noreferrer"
                       aria-label="Lendi College on Instagram"
                       title="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="https://lendi.edu.in/"
                       target="_blank" rel="noopener noreferrer"
                       class="cs-footer-website"
                       aria-label="Lendi College Official Website"
                       title="Official Website">
                        <i class="fa-solid fa-globe"></i>
                        <span>lendi.edu.in</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php if(isset($_SESSION['user_id'])): ?>
    <!-- FLOATING QUICK ACTIONS MENU -->
    <div class="fab-container">
        <div class="fab-menu">
            <a href="post-idea.php" class="bg-[#1E293B] hover:bg-[#3B82F6] hover:text-white border border-white/10 text-[#F8FAFC] text-sm font-medium py-2.5 px-4 rounded-xl shadow-lg transition flex items-center justify-end gap-3 whitespace-nowrap">
                Post New Idea <i class="fa-solid fa-plus bg-white/10 p-1.5 rounded-lg"></i>
            </a>
            <a href="post-team.php" class="bg-[#1E293B] hover:bg-[#3B82F6] hover:text-white border border-white/10 text-[#F8FAFC] text-sm font-medium py-2.5 px-4 rounded-xl shadow-lg transition flex items-center justify-end gap-3 whitespace-nowrap">
                Post Team Request <i class="fa-solid fa-users-viewfinder bg-white/10 p-1.5 rounded-lg"></i>
            </a>
            <a href="ideas.php" class="bg-[#1E293B] hover:bg-[#3B82F6] hover:text-white border border-white/10 text-[#F8FAFC] text-sm font-medium py-2.5 px-4 rounded-xl shadow-lg transition flex items-center justify-end gap-3 whitespace-nowrap">
                Browse Ideas <i class="fa-solid fa-magnifying-glass bg-white/10 p-1.5 rounded-lg"></i>
            </a>
            <a href="teams.php" class="bg-[#1E293B] hover:bg-[#3B82F6] hover:text-white border border-white/10 text-[#F8FAFC] text-sm font-medium py-2.5 px-4 rounded-xl shadow-lg transition flex items-center justify-end gap-3 whitespace-nowrap">
                Find Teams <i class="fa-solid fa-users bg-white/10 p-1.5 rounded-lg"></i>
            </a>
            <a href="dashboard.php" class="bg-[#1E293B] hover:bg-[#3B82F6] hover:text-white border border-white/10 text-[#F8FAFC] text-sm font-medium py-2.5 px-4 rounded-xl shadow-lg transition flex items-center justify-end gap-3 whitespace-nowrap">
                My Dashboard <i class="fa-solid fa-chart-line bg-white/10 p-1.5 rounded-lg"></i>
            </a>
            <a href="logout.php" class="bg-[#1E293B] hover:bg-red-500 hover:text-white border border-white/10 text-[#F8FAFC] text-sm font-medium py-2.5 px-4 rounded-xl shadow-lg transition flex items-center justify-end gap-3 whitespace-nowrap">
                Logout <i class="fa-solid fa-right-from-bracket bg-white/10 p-1.5 rounded-lg"></i>
            </a>
        </div>
        <button class="fab-btn bg-[#3B82F6] text-white w-14 h-14 rounded-full flex items-center justify-center text-xl shadow-[0_0_20px_rgba(59,130,246,0.4)] hover:shadow-[0_0_30px_rgba(59,130,246,0.6)] focus:outline-none">
            <i class="fa-solid fa-plus"></i>
        </button>
    </div>
<?php endif; ?>

<style>
/* ─── FOOTER ───────────────────────────────────────────────── */
.cs-footer {
    background: #020c18;
    border-top: 1px solid rgba(255,255,255,.07);
    padding: 52px 24px 32px;
    font-family: 'Outfit', system-ui, sans-serif;
    color: #94a3b8;
    margin-top: 60px;
}
.cs-footer-inner {
    max-width: 1280px;
    margin: 0 auto;
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    justify-content: space-between;
}
.cs-footer-brand { max-width: 260px; }
.cs-footer-logo  { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.cs-footer-logo .cs-logo-icon {
    width: 36px; height: 36px;
    background: linear-gradient(135deg,#22d3ee,#2563eb);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
.cs-footer-logo .cs-logo-text {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.45rem;
    font-weight: 700;
    letter-spacing: -0.04em;
    color: #fff;
}
.cs-footer-tagline { font-size: .875rem; line-height: 1.7; }
.cs-footer-copy    { font-size: .75rem; margin-top: 16px; opacity:.6; }
.cs-footer-links {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
}
.cs-footer-heading {
    color: #fff;
    font-weight: 600;
    font-size: .875rem;
    margin-bottom: 14px;
    letter-spacing: .02em;
}
.cs-footer-link {
    display: block;
    color: #94a3b8;
    font-size: .875rem;
    text-decoration: none;
    padding: 3px 0;
    transition: color .18s;
}
.cs-footer-link:hover { color: #22d3ee; }
.cs-footer-socials {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 6px;
}
.cs-footer-socials a {
    font-size: 1.25rem;
    color: #64748b;
    text-decoration: none;
    transition: color .18s, transform .18s;
    display: flex;
    align-items: center;
}
.cs-footer-socials a:hover { color: #22d3ee; transform: translateY(-2px); }
/* College website pill */
.cs-footer-website {
    display: inline-flex !important;
    align-items: center;
    gap: 7px;
    font-size: .8rem !important;
    font-weight: 600;
    color: #94a3b8 !important;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 99px;
    padding: 5px 13px 5px 10px;
    transition: background .18s, color .18s, border-color .18s, transform .18s !important;
    white-space: nowrap;
}
.cs-footer-website i { font-size: 1rem; }
.cs-footer-website:hover {
    background: rgba(34,211,238,.12) !important;
    border-color: rgba(34,211,238,.4) !important;
    color: #22d3ee !important;
    transform: translateY(-2px);
}
</style>
