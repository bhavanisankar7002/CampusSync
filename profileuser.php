<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$level_options = [20, 40, 60, 80, 100];

$user_stmt = $conn->prepare("SELECT name, email, role, college, department, year, skills, profile_photo, regd_no, phone_no, github_url, linkedin_url, portfolio_url, year_studying, primary_skill, primary_skill_level, working_skill_name, working_skill_level, projects_done, achievements_from_college FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function field_value($value, string $fallback = 'Not added yet'): string
{
    $value = trim((string) $value);
    return $value !== '' ? $value : $fallback;
}

$skills_list = [];
if (!empty($user['skills'])) {
    $decoded_skills = json_decode($user['skills'], true);

    if (is_array($decoded_skills)) {
        foreach ($decoded_skills as $skill_item) {
            $skill_name = trim((string) ($skill_item['name'] ?? ''));
            $skill_level = (int) ($skill_item['level'] ?? 0);

            if ($skill_name !== '' && in_array($skill_level, $level_options, true)) {
                $skills_list[] = [
                    'name' => $skill_name,
                    'level' => $skill_level
                ];
            }
        }
    } else {
        foreach (explode(',', $user['skills']) as $skill_name) {
            $skill_name = trim($skill_name);
            if ($skill_name !== '') {
                $skills_list[] = [
                    'name' => $skill_name,
                    'level' => 60
                ];
            }
        }
    }
}

if (empty($skills_list) && !empty($user['primary_skill'])) {
    $skills_list[] = [
        'name' => (string) $user['primary_skill'],
        'level' => (int) ($user['primary_skill_level'] ?? 60)
    ];
}

$display_name = e($user['name'] ?? $_SESSION['name']);
$initial = strtoupper(substr((string) ($user['name'] ?? $_SESSION['name']), 0, 1));
$photo_path = !empty($user['profile_photo']) ? e($user['profile_photo']) : '';
$primary_skill = !empty($skills_list[0]['name']) ? $skills_list[0]['name'] : field_value($user['primary_skill'], 'Profile in progress');
$primary_level = !empty($skills_list[0]['level']) ? (int) $skills_list[0]['level'] : (int) ($user['primary_skill_level'] ?? 0);
$working_level = (int) ($user['working_skill_level'] ?? 0);
$saved = isset($_GET['saved']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $display_name; ?>'s CampusSync profile – skills, projects, and achievements.">
    <title><?php echo $display_name; ?> — CampusSync Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── Font overrides ── */
        body { font-family: 'Outfit', system-ui, sans-serif; }
        .font-display { font-family: 'Space Grotesk', sans-serif; }

        /* ── Gradient name text ── */
        .gradient-name {
            background: linear-gradient(135deg, #F8FAFC 0%, #38BDF8 55%, #818CF8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Entrance animations ── */
        .anim-fade-up {
            opacity: 0;
            transform: translateY(32px);
            animation: fadeUp 0.65s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }
        .delay-100 { animation-delay: 0.10s; }
        .delay-200 { animation-delay: 0.20s; }
        .delay-300 { animation-delay: 0.30s; }
        .delay-400 { animation-delay: 0.40s; }
        .delay-500 { animation-delay: 0.50s; }
        .delay-600 { animation-delay: 0.60s; }

        /* ── Avatar glow ring ── */
        .avatar-ring {
            position: relative;
        }
        .avatar-ring::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #38BDF8, #818CF8, #34d399, #38BDF8);
            animation: spinRing 3.5s linear infinite;
            z-index: -1;
        }
        .avatar-ring::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #38BDF8, #818CF8, #34d399, #38BDF8);
            filter: blur(10px);
            opacity: 0.5;
            animation: spinRing 3.5s linear infinite;
            z-index: -2;
        }
        @keyframes spinRing {
            to { transform: rotate(360deg); }
        }

        /* ── Skill bar animated shimmer ── */
        .skill-bar-track {
            position: relative;
            height: 10px;
            border-radius: 99px;
            background: rgba(255,255,255,0.06);
            overflow: hidden;
        }
        .skill-bar-fill {
            height: 100%;
            border-radius: 99px;
            width: 0%;
            transition: width 1.1s cubic-bezier(0.22, 1, 0.36, 1);
            position: relative;
            overflow: hidden;
        }
        .skill-bar-fill::after {
            content: '';
            position: absolute;
            top: 0; left: -60%;
            width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
            animation: shimmer 2.2s ease-in-out infinite;
        }
        @keyframes shimmer {
            0%   { left: -60%; }
            100% { left: 120%; }
        }
        .fill-blue   { background: linear-gradient(90deg, #3B82F6, #818CF8); }
        .fill-green  { background: linear-gradient(90deg, #10b981, #34d399); }

        /* ── Stat card accent border ── */
        .stat-card {
            background: #111827;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            transition: all 0.28s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, #38BDF8, #818CF8);
            opacity: 0;
            transition: opacity 0.28s ease;
        }
        .stat-card:hover::before { opacity: 1; }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(0,0,0,0.25), 0 0 0 1px rgba(56,189,248,0.15);
            border-color: rgba(56,189,248,0.2);
        }

        /* ── Section cards ── */
        .section-card {
            background: #111827;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            transition: all 0.28s ease;
        }
        .section-card:hover {
            border-color: rgba(255,255,255,0.13);
            box-shadow: 0 12px 24px rgba(0,0,0,0.2);
        }

        /* ── Social link buttons ── */
        .social-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 20px;
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255,255,255,0.10);
            color: #F8FAFC;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.22s ease;
            cursor: pointer;
        }
        .social-btn:hover {
            background: rgba(51, 65, 85, 0.9);
            border-color: rgba(56,189,248,0.3);
            box-shadow: 0 0 16px rgba(56,189,248,0.15);
            transform: translateY(-2px);
            color: #38BDF8;
        }
        .social-btn i { font-size: 1.05rem; color: #94A3B8; transition: color 0.22s; }
        .social-btn:hover i { color: #38BDF8; }

        /* ── Edit profile button ── */
        .btn-edit-profile {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3B82F6, #6366F1);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(99,102,241,0.35);
            letter-spacing: 0.01em;
        }
        .btn-edit-profile:hover {
            background: linear-gradient(135deg, #2563EB, #4F46E5);
            box-shadow: 0 6px 22px rgba(99,102,241,0.5);
            transform: translateY(-2px);
        }

        /* ── Role badge ── */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 14px;
            border-radius: 8px;
            border: 1px solid rgba(52,211,153,0.25);
            background: rgba(52,211,153,0.10);
            color: #34d399;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .role-badge-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #34d399;
            animation: pulseDot 2s ease-in-out infinite;
        }
        @keyframes pulseDot {
            0%,100% { box-shadow: 0 0 0 0 rgba(52,211,153,0.5); }
            50%      { box-shadow: 0 0 0 5px rgba(52,211,153,0); }
        }

        /* ── Background grid pattern ── */
        .bg-grid {
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* ── Section label ── */
        .section-label {
            font-size: 0.70rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #38BDF8;
        }

        /* ── Skill item card ── */
        .skill-item {
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.05);
            background: #0F172A;
            padding: 18px 20px;
            transition: border-color 0.22s, box-shadow 0.22s;
        }
        .skill-item:hover {
            border-color: rgba(56,189,248,0.2);
            box-shadow: 0 0 14px rgba(56,189,248,0.07);
        }

        /* ── Sub-stat boxes ── */
        .sub-stat {
            border-radius: 14px;
            background: #0F172A;
            padding: 18px 20px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: border-color 0.22s;
        }
        .sub-stat:hover { border-color: rgba(56,189,248,0.18); }

        /* ── Success toast ── */
        .toast-success {
            border-radius: 16px;
            border: 1px solid rgba(52,211,153,0.3);
            background: rgba(52,211,153,0.12);
            color: #a7f3d0;
            padding: 14px 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-[#F8FAFC] bg-grid">
    <?php $active_page = 'dashboard'; include 'navbar.php'; ?>

    <main class="max-w-6xl mx-auto px-5 py-12 w-full">

        <?php if ($saved): ?>
        <div class="toast-success mb-8 anim-fade-up">
            <i class="fa-solid fa-circle-check text-[#34d399] text-lg"></i>
            Profile updated successfully!
        </div>
        <?php endif; ?>

        <!-- ═══ HERO PROFILE CARD ═══ -->
        <section class="section-card p-7 md:p-10 flex flex-col md:flex-row gap-8 items-center md:items-start max-w-5xl mx-auto anim-fade-up delay-100">

            <!-- Avatar -->
            <div class="avatar-ring shrink-0 w-36 h-36 md:w-44 md:h-44 rounded-full" style="z-index:1;">
                <div class="w-full h-full rounded-full overflow-hidden bg-[#0F172A] border-2 border-[#0F172A]">
                    <?php if ($photo_path): ?>
                        <img src="<?php echo $photo_path; ?>" alt="Profile photo of <?php echo $display_name; ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center font-display font-bold text-[#38BDF8]"
                             style="font-size:clamp(2.5rem,10vw,3.5rem);">
                            <?php echo e($initial); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="flex-1 text-center md:text-left">
                <div class="role-badge mb-4">
                    <span class="role-badge-dot"></span>
                    <?php echo e(ucfirst((string) $user['role'])); ?> Profile
                </div>

                <h1 class="font-display gradient-name font-extrabold leading-tight"
                    style="font-size: clamp(2rem, 5vw, 3.25rem); letter-spacing: -0.03em;">
                    <?php echo $display_name; ?>
                </h1>

                <p class="mt-2 font-semibold text-[#38BDF8]" style="font-size: 1.1rem;">
                    <?php echo e($primary_skill); ?>
                </p>

                <p class="mt-1 text-[#94A3B8]" style="font-size: 0.95rem;">
                    <?php echo e(field_value($user['college'])); ?>
                    <?php if (!empty($user['department'])): ?>
                        <span class="text-[#334155] mx-1">·</span><?php echo e($user['department']); ?>
                    <?php endif; ?>
                </p>

                <!-- Social + Edit -->
                <div class="mt-7 flex flex-wrap justify-center md:justify-start gap-3">
                    <?php if (!empty($user['github_url'])): ?>
                        <a href="<?php echo e($user['github_url']); ?>" target="_blank" rel="noopener" class="social-btn">
                            <i class="fa-brands fa-github"></i> GitHub
                        </a>
                    <?php else: ?>
                        <button onclick="alert('No GitHub link added yet.');" class="social-btn">
                            <i class="fa-brands fa-github"></i> GitHub
                        </button>
                    <?php endif; ?>

                    <?php if (!empty($user['portfolio_url'])): ?>
                        <a href="<?php echo e($user['portfolio_url']); ?>" target="_blank" rel="noopener" class="social-btn">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Portfolio
                        </a>
                    <?php else: ?>
                        <button onclick="alert('No portfolio link added yet.');" class="social-btn">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Portfolio
                        </button>
                    <?php endif; ?>

                    <?php if (!empty($user['linkedin_url'])): ?>
                        <a href="<?php echo e($user['linkedin_url']); ?>" target="_blank" rel="noopener" class="social-btn">
                            <i class="fa-brands fa-linkedin"></i> LinkedIn
                        </a>
                    <?php else: ?>
                        <button onclick="alert('No LinkedIn link added yet.');" class="social-btn">
                            <i class="fa-brands fa-linkedin"></i> LinkedIn
                        </button>
                    <?php endif; ?>

                    <a href="edit-profile.php" class="btn-edit-profile">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                    </a>
                </div>
            </div>
        </section>

        <!-- ═══ STAT CARDS ROW ═══ -->
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-5 mt-7 anim-fade-up delay-200">

            <div class="stat-card p-6">
                <p class="text-[#64748B] font-medium" style="font-size:0.78rem; letter-spacing:0.06em; text-transform:uppercase;">Reg. No.</p>
                <p class="mt-2 text-[#F8FAFC] font-bold break-words" style="font-size:1.15rem;">
                    <?php echo e(field_value($user['regd_no'])); ?>
                </p>
            </div>

            <div class="stat-card p-6">
                <p class="text-[#64748B] font-medium" style="font-size:0.78rem; letter-spacing:0.06em; text-transform:uppercase;">Year</p>
                <p class="mt-2 text-[#F8FAFC] font-bold" style="font-size:1.15rem;">
                    <?php echo e(field_value($user['year_studying'])); ?>
                </p>
            </div>

            <div class="stat-card p-6">
                <p class="text-[#64748B] font-medium" style="font-size:0.78rem; letter-spacing:0.06em; text-transform:uppercase;">E-mail</p>
                <p class="mt-2 text-[#F8FAFC] font-semibold break-words" style="font-size:0.95rem;">
                    <?php echo e(field_value($user['email'])); ?>
                </p>
            </div>

            <div class="stat-card p-6">
                <p class="text-[#64748B] font-medium" style="font-size:0.78rem; letter-spacing:0.06em; text-transform:uppercase;">Phone</p>
                <p class="mt-2 text-[#F8FAFC] font-bold" style="font-size:1.15rem;">
                    <?php echo e(field_value($user['phone_no'])); ?>
                </p>
            </div>

        </section>

        <!-- ═══ SKILLS + WORKING ON ═══ -->
        <section class="grid lg:grid-cols-[1.2fr_0.8fr] gap-6 mt-6 anim-fade-up delay-300">

            <!-- Skills -->
            <div class="section-card p-8">
                <p class="section-label">Skills</p>
                <h2 class="font-display font-bold text-[#F8FAFC] mt-2" style="font-size:1.45rem;">Core Strengths</h2>

                <div class="mt-6 space-y-4" id="skills-list">
                    <?php if (!empty($skills_list)): ?>
                        <?php foreach ($skills_list as $idx => $skill_item): ?>
                        <div class="skill-item">
                            <div class="flex items-center justify-between gap-4 mb-3">
                                <p class="text-[#F8FAFC] font-semibold" style="font-size:0.975rem;">
                                    <?php echo e($skill_item['name']); ?>
                                </p>
                                <p class="font-bold text-[#38BDF8]" style="font-size:0.9rem;">
                                    <?php echo (int) $skill_item['level']; ?>%
                                </p>
                            </div>
                            <div class="skill-bar-track">
                                <div class="skill-bar-fill fill-blue"
                                     data-width="<?php echo (int) $skill_item['level']; ?>">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center py-10 gap-3 text-center">
                            <i class="fa-solid fa-code-branch text-3xl text-[#334155]"></i>
                            <p class="text-[#64748B]" style="font-size:0.9rem;">No skills added yet.</p>
                            <a href="edit-profile.php" class="text-[#38BDF8] hover:underline" style="font-size:0.85rem;">Add your first skill →</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Currently Working On -->
            <div class="section-card p-8 flex flex-col">
                <p class="section-label">Currently Working On</p>
                <h2 class="font-display font-bold text-[#F8FAFC] mt-2 leading-tight" style="font-size:1.35rem;">
                    <?php echo e(field_value($user['working_skill_name'], 'No active skill')); ?>
                </h2>

                <p class="text-[#64748B] mt-5" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.08em;">Current Level</p>
                <p class="font-display font-extrabold text-[#F8FAFC] mt-1" style="font-size:2.75rem; line-height:1;">
                    <?php echo $working_level; ?><span class="text-[#38BDF8]" style="font-size:1.5rem;">%</span>
                </p>

                <div class="skill-bar-track mt-3">
                    <div class="skill-bar-fill fill-green" data-width="<?php echo $working_level; ?>"></div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-6">
                    <div class="sub-stat">
                        <p class="text-[#64748B]" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.07em;">Primary Lvl</p>
                        <p class="font-display font-bold text-[#F8FAFC] mt-1" style="font-size:1.6rem;">
                            <?php echo $primary_level; ?><span class="text-[#38BDF8]" style="font-size:1rem;">%</span>
                        </p>
                    </div>
                    <div class="sub-stat">
                        <p class="text-[#64748B]" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.07em;">Role</p>
                        <p class="font-display font-bold text-[#F8FAFC] mt-1" style="font-size:1.1rem;">
                            <?php echo e(ucfirst((string) $user['role'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══ PROJECTS + ACHIEVEMENTS ═══ -->
        <section class="grid lg:grid-cols-2 gap-6 mt-6 pb-12 anim-fade-up delay-400">

            <div class="section-card p-8">
                <p class="section-label">Projects</p>
                <h2 class="font-display font-bold text-[#F8FAFC] mt-2" style="font-size:1.45rem;">Projects Done</h2>
                <div class="mt-5 border-t border-white/5 pt-5">
                    <p class="text-[#CBD5E1] leading-relaxed whitespace-pre-line" style="font-size:0.94rem; line-height:1.75;">
                        <?php echo nl2br(e(field_value($user['projects_done'], 'No projects added yet.'))); ?>
                    </p>
                </div>
            </div>

            <div class="section-card p-8">
                <p class="section-label">Achievements</p>
                <h2 class="font-display font-bold text-[#F8FAFC] mt-2" style="font-size:1.45rem;">College Milestones</h2>
                <div class="mt-5 border-t border-white/5 pt-5">
                    <p class="text-[#CBD5E1] leading-relaxed whitespace-pre-line" style="font-size:0.94rem; line-height:1.75;">
                        <?php echo nl2br(e(field_value($user['achievements_from_college'], 'No achievements added yet.'))); ?>
                    </p>
                </div>
            </div>

        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script>
    // Animate skill bars after page load
    document.addEventListener('DOMContentLoaded', () => {
        const bars = document.querySelectorAll('.skill-bar-fill');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const bar = entry.target;
                    const targetWidth = bar.dataset.width;
                    setTimeout(() => {
                        bar.style.width = targetWidth + '%';
                    }, 120);
                    observer.unobserve(bar);
                }
            });
        }, { threshold: 0.3 });

        bars.forEach(bar => observer.observe(bar));
    });
    </script>
</body>
</html>
