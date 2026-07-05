<?php
session_start();
include 'config.php';
$active_page = '';

// Fetch 3 recent ideas for the preview section
$recent_ideas = [];
if (isset($conn) && function_exists('table_exists') && table_exists($conn, $db, 'ideas')) {
    $res = $conn->query("
        SELECT i.*, u.name AS posted_by_name 
        FROM ideas i 
        LEFT JOIN users u ON i.posted_by = u.id 
        ORDER BY i.created_at DESC LIMIT 3
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $recent_ideas[] = $row;
        }
    }
}

include 'navbar.php';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusSync • Connect • Collaborate • Create</title>
    <meta name="description" content="CampusSync – your college's official platform to post ideas, find teammates, and build together.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap');

        * { font-family: 'Outfit', system-ui, sans-serif; }
        .logo-font { font-family: 'Space Grotesk', sans-serif; }

        body { background: #07111f; color: #fff; overflow-x: hidden; }

        .hero-bg {
            background: linear-gradient(135deg, #0a1729 0%, #11223d 100%);
            position: relative;
        }
        
        .hero-bg::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 80% 20%, rgba(34,211,238,0.08) 0%, transparent 40%);
            pointer-events: none;
        }

        /* Feature Card Hover Effects */
        .feature-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: block;
        }
        .feature-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(180deg, rgba(255,255,255,0.03) 0%, transparent 100%);
            opacity: 0; transition: opacity 0.4s ease;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -10px rgba(34, 211, 238, 0.15);
            border-color: rgba(34, 211, 238, 0.4);
        }
        .feature-card:hover::before { opacity: 1; }
        .card-arrow { transition: transform 0.3s ease; }
        .feature-card:hover .card-arrow { transform: translateX(6px); }

        /* Floating Action Button (FAB) */
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

        /* Interactive Project Preview Card */
        .preview-card {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        /* Scroll Animations */
        .reveal {
            opacity: 0; transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }
        .reveal.active {
            opacity: 1; transform: translateY(0);
        }
        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }
    </style>
</head>
<body>

    <!-- HERO SECTION -->
    <section class="hero-bg text-white pt-24 pb-20 border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-12 gap-12 items-center">
                <div class="md:col-span-7 reveal active">
                    <div class="inline-flex items-center gap-x-2 bg-white/10 backdrop-blur-md text-white text-sm font-medium px-6 py-2 rounded-3xl mb-6 border border-white/10 hover:bg-white/15 transition cursor-default">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-400"></span>
                        </span>
                        Official Platform for Our College
                    </div>

                    <h1 class="logo-font text-6xl md:text-7xl font-semibold tracking-tighter leading-tight mb-6">
                        Post ideas.<br>Find teammates.<br>Build together.
                    </h1>

                    <p class="text-xl text-cyan-100/80 max-w-lg mb-10 leading-relaxed">
                        Build your personal identity in your college.
                        Students post ideas, apply with skills, form teams for hackathons,
                        and get real faculty feedback from your own professors.
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <a href="post-idea.php"
                            class="bg-cyan-500 text-slate-950 hover:bg-cyan-400 px-8 py-4 rounded-3xl text-lg font-semibold flex items-center gap-x-3 shadow-[0_0_20px_rgba(34,211,238,0.3)] hover:shadow-[0_0_30px_rgba(34,211,238,0.5)] transition-all">
                            <i class="fa-solid fa-lightbulb"></i>
                            Start Posting Ideas
                        </a>
                        <a href="ideas.php"
                            class="bg-white/5 border border-white/20 hover:bg-white/10 px-8 py-4 rounded-3xl text-lg font-semibold flex items-center gap-x-3 transition-all hover:border-white/40">
                            <i class="fa-solid fa-magnifying-glass text-cyan-400"></i>
                            Browse Open Ideas
                        </a>
                    </div>
                </div>

                <!-- Right Interactive Project Preview Card -->
                <div class="md:col-span-5 reveal active delay-200">
                    <a href="ideas.php" class="block group preview-card relative">
                        <!-- Glow effect behind card -->
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-cyan-400 to-blue-500 rounded-3xl blur opacity-30 group-hover:opacity-60 transition duration-500"></div>
                        
                        <div class="relative bg-slate-900 border border-white/10 rounded-3xl p-8 text-white shadow-2xl transition-transform group-hover:scale-[1.02]">
                            <div class="flex justify-between items-center mb-6">
                                <div class="px-4 py-1.5 bg-emerald-400/20 text-emerald-400 border border-emerald-400/30 text-xs font-bold rounded-full flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> ACTIVE PROJECT
                                </div>
                                <i class="fa-solid fa-arrow-up-right-from-square text-slate-500 group-hover:text-cyan-400 transition"></i>
                            </div>

                            <?php if (!empty($recent_ideas)): $hero_idea = $recent_ideas[0]; ?>
                            <h3 class="text-2xl font-semibold mb-3 group-hover:text-cyan-300 transition line-clamp-2"><?= htmlspecialchars($hero_idea['title']) ?></h3>
                            <p class="text-slate-400 text-sm mb-6 leading-relaxed line-clamp-3">
                                <?= htmlspecialchars($hero_idea['description']) ?>
                            </p>
                            
                            <div class="mb-6">
                                <p class="text-xs text-cyan-400 font-bold mb-2">SKILLS NEEDED</p>
                                <div class="flex flex-wrap gap-2">
                                    <?php
                                    $hero_skills = array_map('trim', explode(',', $hero_idea['skills_needed'] ?? ''));
                                    foreach (array_slice($hero_skills, 0, 3) as $skill):
                                        if ($skill):
                                    ?>
                                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full border border-white/5"><?= htmlspecialchars($skill) ?></span>
                                    <?php endif; endforeach; ?>
                                    <?php if(count($hero_skills) > 3): ?>
                                    <span class="text-xs bg-white/10 px-2 py-1 rounded-full border border-white/5">+<?= count($hero_skills) - 3 ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="border-t border-white/10 pt-4 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-sm font-bold uppercase"><?= htmlspecialchars(substr($hero_idea['posted_by_name'] ?? 'S', 0, 1)) ?></div>
                                    <span class="text-sm text-slate-300">Posted by <?= htmlspecialchars(explode(' ', $hero_idea['posted_by_name'] ?? 'Student')[0]) ?></span>
                                </div>
                                <span class="text-cyan-400 text-sm font-semibold group-hover:underline flex items-center gap-1">
                                    Apply Now <i class="fa-solid fa-arrow-right text-xs card-arrow"></i>
                                </span>
                            </div>
                            <?php else: ?>
                            <h3 class="text-2xl font-semibold mb-3 group-hover:text-cyan-300 transition">Smart Campus Navigation App</h3>
                            <p class="text-slate-400 text-sm mb-6 leading-relaxed">
                                Looking for developers to build an AR-based indoor navigation system for our college buildings.
                            </p>
                            
                            <div class="mb-6">
                                <p class="text-xs text-cyan-400 font-bold mb-2">SKILLS NEEDED</p>
                                <div class="flex flex-wrap gap-2">
                                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full border border-white/5">React Native</span>
                                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full border border-white/5">ARCore</span>
                                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full border border-white/5">UI/UX</span>
                                </div>
                            </div>
                            
                            <div class="border-t border-white/10 pt-4 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-sm font-bold">A</div>
                                    <span class="text-sm text-slate-300">Posted by Alex</span>
                                </div>
                                <span class="text-cyan-400 text-sm font-semibold group-hover:underline flex items-center gap-1">
                                    Apply Now <i class="fa-solid fa-arrow-right text-xs card-arrow"></i>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- QUICK NAVIGATION CARDS (Replacing Fake Statistics) -->
    <section class="bg-slate-950 border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6 py-6 flex flex-wrap justify-center gap-4">
            <a href="ideas.php" class="bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl px-6 py-3 flex items-center gap-3 text-sm font-medium transition text-slate-300 hover:text-white">
                <i class="fa-solid fa-lightbulb text-yellow-400"></i> Browse Ideas
            </a>
            <a href="teams.php" class="bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl px-6 py-3 flex items-center gap-3 text-sm font-medium transition text-slate-300 hover:text-white">
                <i class="fa-solid fa-users text-blue-400"></i> Find Teams
            </a>
            <a href="teams.php" class="bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl px-6 py-3 flex items-center gap-3 text-sm font-medium transition text-slate-300 hover:text-white">
                <i class="fa-solid fa-trophy text-rose-400"></i> Hackathons
            </a>
            <a href="dashboard.php" class="bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl px-6 py-3 flex items-center gap-3 text-sm font-medium transition text-slate-300 hover:text-white">
                <i class="fa-solid fa-chart-line text-emerald-400"></i> Dashboard
            </a>
        </div>
    </section>

    <!-- RECENTLY POSTED PROJECTS SECTION -->
    <section class="max-w-7xl mx-auto px-6 py-24">
        <div class="flex justify-between items-end mb-12 reveal">
            <div>
                <span class="text-xs font-bold tracking-widest text-emerald-400 uppercase">Live Feed</span>
                <h2 class="logo-font text-4xl font-semibold tracking-tighter mt-2 text-white">Recently Posted Ideas</h2>
            </div>
            <a href="ideas.php" class="text-cyan-400 hover:text-cyan-300 font-medium flex items-center gap-2 group transition">
                View All <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition"></i>
            </a>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <?php if (!empty($recent_ideas)): ?>
                <?php foreach ($recent_ideas as $index => $idea): ?>
                    <a href="ideas.php" class="feature-card bg-white/5 border border-white/10 rounded-3xl p-6 flex flex-col reveal delay-<?= ($index+1)*100 ?>">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-xs font-bold px-3 py-1 bg-white/10 rounded-full border border-white/10"><?= htmlspecialchars($idea['status'] ?? 'open') ?></span>
                            <span class="text-xs text-slate-500"><?= date('M d', strtotime($idea['created_at'])) ?></span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2 line-clamp-1"><?= htmlspecialchars($idea['title']) ?></h3>
                        <p class="text-slate-400 text-sm line-clamp-3 mb-4 flex-1"><?= htmlspecialchars($idea['description']) ?></p>
                        <div class="border-t border-white/10 pt-4 flex justify-between items-center text-sm">
                            <span class="text-slate-400">By <?= htmlspecialchars($idea['posted_by_name'] ?? 'Student') ?></span>
                            <span class="text-cyan-400 font-medium">Apply <i class="fa-solid fa-arrow-right text-xs ml-1 card-arrow"></i></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Placeholder Cards if DB is empty -->
                <?php for($i=1; $i<=3; $i++): ?>
                    <a href="post-idea.php" class="feature-card bg-white/5 border border-white/10 rounded-3xl p-6 flex flex-col reveal delay-<?= $i*100 ?>">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-xs font-bold px-3 py-1 bg-white/10 rounded-full border border-white/10 border-dashed text-slate-400">Waiting for ideas</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2 text-slate-300">Be the first to post</h3>
                        <p class="text-slate-500 text-sm mb-4 flex-1">There are no projects posted yet. Click here to share your idea with the campus.</p>
                        <div class="border-t border-white/10 pt-4 flex justify-end items-center text-sm">
                            <span class="text-cyan-400 font-medium">Post Idea <i class="fa-solid fa-plus text-xs ml-1 card-arrow"></i></span>
                        </div>
                    </a>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="bg-slate-950/50 border-t border-white/5 py-24">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16 reveal">
                <span class="px-4 py-1.5 bg-cyan-900/40 text-cyan-400 rounded-full text-xs font-bold tracking-widest border border-cyan-800/50 uppercase">Built for your college</span>
                <h2 class="logo-font text-5xl font-semibold tracking-tighter mt-4 text-white">Everything you need to turn ideas into reality</h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="post-idea.php" class="feature-card group bg-white/5 hover:bg-white/[0.07] border border-white/10 hover:border-white/20 rounded-3xl p-8 reveal delay-100 transition-all duration-300">
                    <div class="w-12 h-12 bg-orange-500/10 border border-orange-500/20 text-orange-400 rounded-2xl flex items-center justify-center text-xl mb-6 shadow-[0_0_20px_rgba(249,115,22,0.2)] group-hover:scale-110 group-hover:bg-orange-500/20 group-hover:shadow-[0_0_30px_rgba(249,115,22,0.4)] transition-all duration-300">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-white">1. Post Ideas</h3>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6 group-hover:text-slate-300 transition-colors">Students can easily post project ideas and instantly find skilled friends who match the exact tech stack needed.</p>
                    <span class="text-cyan-400 font-medium flex items-center text-sm">Post your first idea <i class="fa-solid fa-arrow-right ml-2 card-arrow group-hover:translate-x-1 transition-transform"></i></span>
                </a>

                <a href="ideas.php" class="feature-card group bg-white/5 hover:bg-white/[0.07] border border-white/10 hover:border-white/20 rounded-3xl p-8 reveal delay-200 transition-all duration-300">
                    <div class="w-12 h-12 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-2xl flex items-center justify-center text-xl mb-6 shadow-[0_0_20px_rgba(59,130,246,0.2)] group-hover:scale-110 group-hover:bg-blue-500/20 group-hover:shadow-[0_0_30px_rgba(59,130,246,0.4)] transition-all duration-300">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-white">2. Apply to Projects</h3>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6 group-hover:text-slate-300 transition-colors">Showcase your skills (just like LinkedIn) and apply to open ideas with one click. Get matched instantly.</p>
                    <span class="text-cyan-400 font-medium flex items-center text-sm">Browse opportunities <i class="fa-solid fa-arrow-right ml-2 card-arrow group-hover:translate-x-1 transition-transform"></i></span>
                </a>

                <a href="project-submission.php" class="feature-card group bg-white/5 hover:bg-white/[0.07] border border-white/10 hover:border-white/20 rounded-3xl p-8 reveal delay-300 transition-all duration-300">
                    <div class="w-12 h-12 bg-purple-500/10 border border-purple-500/20 text-purple-400 rounded-2xl flex items-center justify-center text-xl mb-6 shadow-[0_0_20px_rgba(168,85,247,0.2)] group-hover:scale-110 group-hover:bg-purple-500/20 group-hover:shadow-[0_0_30px_rgba(168,85,247,0.4)] transition-all duration-300">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-white">3. Faculty Evaluation</h3>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6 group-hover:text-slate-300 transition-colors">Faculty members can review, comment, rate and mentor every idea. Real academic guidance at your fingertips.</p>
                    <span class="text-cyan-400 font-medium flex items-center text-sm">See faculty dashboard <i class="fa-solid fa-arrow-right ml-2 card-arrow group-hover:translate-x-1 transition-transform"></i></span>
                </a>

                <a href="profileuser.php" class="feature-card group bg-white/5 hover:bg-white/[0.07] border border-white/10 hover:border-white/20 rounded-3xl p-8 reveal delay-100 lg:col-span-1 transition-all duration-300">
                    <div class="w-12 h-12 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center text-xl mb-6 shadow-[0_0_20px_rgba(16,185,129,0.2)] group-hover:scale-110 group-hover:bg-emerald-500/20 group-hover:shadow-[0_0_30px_rgba(16,185,129,0.4)] transition-all duration-300">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-white">4. College Profile</h3>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6 group-hover:text-slate-300 transition-colors">Build your identity &amp; profile. Skills, past projects, achievements — all in one college-verified place.</p>
                    <span class="text-cyan-400 font-medium flex items-center text-sm">Edit your profile <i class="fa-solid fa-arrow-right ml-2 card-arrow group-hover:translate-x-1 transition-transform"></i></span>
                </a>

                <a href="teams.php" class="feature-card group bg-white/5 hover:bg-white/[0.07] border border-white/10 hover:border-white/20 rounded-3xl p-8 reveal delay-200 col-span-1 md:col-span-2 lg:col-span-2 transition-all duration-300">
                    <div class="w-12 h-12 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-2xl flex items-center justify-center text-xl mb-6 shadow-[0_0_20px_rgba(244,63,94,0.2)] group-hover:scale-110 group-hover:bg-rose-500/20 group-hover:shadow-[0_0_30px_rgba(244,63,94,0.4)] transition-all duration-300">
                        <i class="fa-solid fa-people-group"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3 text-white">5. Find or Form Teams</h3>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6 group-hover:text-slate-300 transition-colors">Discover teammates by skills, create or join teams for hackathons, competitions, or semester projects. Smart recommendations included.</p>
                    <span class="text-cyan-400 font-medium flex items-center text-sm">Start building your team <i class="fa-solid fa-arrow-right ml-2 card-arrow group-hover:translate-x-1 transition-transform"></i></span>
                </a>
            </div>
        </div>
    </section>

    <!-- Campus Showcase Feed -->
    <section id="campus-showcase" class="py-16 bg-[#07111f] border-t border-white/5 relative z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12 reveal">
                <h2 class="text-3xl font-bold mb-4 logo-font"><span class="text-emerald-400">Campus</span> Showcase</h2>
                <p class="text-slate-400 max-w-2xl mx-auto">See what your peers are building, prototyping, and sharing right now.</p>
            </div>
            
            <?php include 'components/feed_widget.php'; ?>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <!-- Minimal Scroll Animation JS -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const reveals = document.querySelectorAll(".reveal");
            
            const revealOnScroll = () => {
                const windowHeight = window.innerHeight;
                const elementVisible = 100;
                
                reveals.forEach(reveal => {
                    const elementTop = reveal.getBoundingClientRect().top;
                    if (elementTop < windowHeight - elementVisible) {
                        reveal.classList.add("active");
                    }
                });
            };
            
            // Initial check
            setTimeout(revealOnScroll, 100);
            
            // Check on scroll
            window.addEventListener("scroll", revealOnScroll, { passive: true });
        });
    </script>
</body>
</html>