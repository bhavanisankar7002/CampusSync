<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$success = '';
$error = '';
$level_options = [20, 40, 60, 80, 100];

function application_status_label(string $status): string
{
    if ($status === 'accepted') {
        return 'Joined';
    }

    if ($status === 'rejected') {
        return 'Rejected';
    }

    return 'Waiting for joining';
}

function application_status_classes(string $status): string
{
    if ($status === 'accepted') {
        return 'border-emerald-300/40 bg-emerald-400/15 text-emerald-100';
    }

    if ($status === 'rejected') {
        return 'border-rose-300/40 bg-rose-400/15 text-rose-100';
    }

    return 'border-amber-300/40 bg-amber-400/15 text-amber-100';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_action'])) {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
    $application_id = (int) ($_POST['application_id'] ?? 0);
    $application_action = $_POST['application_action'] ?? '';
    $new_status = $application_action === 'accept' ? 'accepted' : ($application_action === 'reject' ? 'rejected' : '');

    if ($application_id <= 0 || $new_status === '') {
        $error = 'Choose a valid request action.';
    } else {
        $request_stmt = $conn->prepare("
            UPDATE idea_applications ia
            INNER JOIN ideas i ON ia.idea_id = i.id
            SET ia.status = ?
            WHERE ia.id = ? AND i.posted_by = ?
        ");
        $request_stmt->bind_param("sii", $new_status, $application_id, $user_id);
        $request_stmt->execute();

        if ($request_stmt->affected_rows > 0) {
            $success = $new_status === 'accepted'
                ? 'Join request accepted.'
                : 'Join request rejected.';
        } else {
            $error = 'Could not update that request.';
        }

        $request_stmt->close();
    }
    } // end csrf_verify
}



$user_stmt = $conn->prepare("SELECT name, email, skills, profile_photo, regd_no, phone_no, github_url, linkedin_url, portfolio_url, year_studying, primary_skill, primary_skill_level, working_skill_name, working_skill_level, projects_done, achievements_from_college FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$ideas_count = 0;
$teams_count = 0;
$feedback_count = 0;

if ($user_role === 'student') {
    if (table_exists($conn, $db, 'ideas')) {
        $ideas_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM ideas WHERE posted_by = ?");
        $ideas_stmt->bind_param("i", $user_id);
        $ideas_stmt->execute();
        $ideas_count = (int) ($ideas_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $ideas_stmt->close();
    }

    if (table_exists($conn, $db, 'team_members')) {
        $teams_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM team_members WHERE user_id = ?");
        $teams_stmt->bind_param("i", $user_id);
        $teams_stmt->execute();
        $teams_count = (int) ($teams_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $teams_stmt->close();
    }
} else {
    if (table_exists($conn, $db, 'feedback')) {
        $feedback_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM feedback WHERE faculty_id = ?");
        $feedback_stmt->bind_param("i", $user_id);
        $feedback_stmt->execute();
        $feedback_count = (int) ($feedback_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $feedback_stmt->close();
    }
}

$incoming_applications = [];
$sent_applications = [];

if (table_exists($conn, $db, 'idea_applications') && table_exists($conn, $db, 'ideas')) {
    $incoming_stmt = $conn->prepare("
        SELECT
            ia.id,
            ia.message,
            ia.status,
            ia.created_at,
            i.title AS idea_title,
            u.name AS applicant_name,
            u.email AS applicant_email,
            u.regd_no AS applicant_regd_no,
            u.phone_no AS applicant_phone
        FROM idea_applications ia
        INNER JOIN ideas i ON ia.idea_id = i.id
        INNER JOIN users u ON ia.applicant_id = u.id
        WHERE i.posted_by = ?
        ORDER BY FIELD(ia.status, 'pending', 'accepted', 'rejected'), ia.created_at DESC
        LIMIT 20
    ");
    $incoming_stmt->bind_param("i", $user_id);
    $incoming_stmt->execute();
    $incoming_result = $incoming_stmt->get_result();
    while ($application = $incoming_result->fetch_assoc()) {
        $incoming_applications[] = $application;
    }
    $incoming_stmt->close();

    $sent_stmt = $conn->prepare("
        SELECT
            ia.id,
            ia.status,
            ia.created_at,
            i.title AS idea_title,
            u.name AS owner_name,
            u.phone_no AS owner_phone
        FROM idea_applications ia
        INNER JOIN ideas i ON ia.idea_id = i.id
        LEFT JOIN users u ON i.posted_by = u.id
        WHERE ia.applicant_id = ?
        ORDER BY ia.created_at DESC
        LIMIT 20
    ");
    $sent_stmt->bind_param("i", $user_id);
    $sent_stmt->execute();
    $sent_result = $sent_stmt->get_result();
    while ($application = $sent_result->fetch_assoc()) {
        $sent_applications[] = $application;
    }
    $sent_stmt->close();
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
    }
}
if (empty($skills_list) && !empty($user['primary_skill'])) {
    $skills_list[] = [
        'name' => (string) $user['primary_skill'],
        'level' => (int) ($user['primary_skill_level'] ?? 60)
    ];
}
if (empty($skills_list)) {
    $skills_list[] = [
        'name' => '',
        'level' => 60
    ];
}

$profile_completed = !empty($user['regd_no']) && !empty($user['phone_no']) && !empty($user['github_url']) && !empty($user['linkedin_url']) && !empty($skills_list[0]['name']) && !empty($user['projects_done']);
$photo_path = !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : '';
$display_name = htmlspecialchars($_SESSION['name']);
$primary_skill = htmlspecialchars($skills_list[0]['name'] ?: 'Add your profile to highlight your strengths');
$year_studying = htmlspecialchars($user['year_studying'] ?? 'Not set yet');
$primary_level = (int) ($skills_list[0]['level'] ?? 60);
$working_level = (int) ($user['working_skill_level'] ?? 40);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard • CampusSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Outfit', system-ui, sans-serif; }
        body { background: #0B1220; color: #F8FAFC; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        
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
        
        .cs-card-glass {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.25s ease;
        }
        .cs-card-glass:hover {
            border-color: rgba(255, 255, 255, 0.15);
            background: rgba(17, 24, 39, 0.85);
        }

        .cs-card-secondary {
            background: #1E293B;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            transition: all 0.25s ease;
        }
        .cs-card-secondary:hover {
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .compact-input {
            background: #0F172A;
            border: 1px solid rgba(255,255,255,0.08);
            color: #F8FAFC;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.8125rem; /* 13px */
            width: 100%;
            outline: none;
            transition: all 0.25s ease;
        }
        .compact-input:focus { 
            border-color: #38BDF8; 
            background: #0F172A; 
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
        }
        .compact-input::placeholder { color: #94A3B8; }
        select.compact-input option { background: #0F172A; color: #F8FAFC; }

        .btn-primary {
            background: #3B82F6;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            transition: all 0.25s ease;
            cursor: pointer;
            text-align: center;
            border: none;
        }
        .btn-primary:hover { 
            background: #2563EB; 
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); 
            transform: translateY(-1px);
        }
        
        .action-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #CBD5E1;
            transition: all 0.25s ease;
        }
        .action-link:hover { background: #1E293B; color: #F8FAFC; }
        
        /* Custom scrollbar for small containers */
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #1E293B; border-radius: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <?php $active_page = 'dashboard'; include 'navbar.php'; ?>
    
    <main class="flex-1 w-full max-w-7xl mx-auto px-6 py-8 overflow-y-auto custom-scroll">
        
        <!-- Flash Messages -->
        <?php if ($success !== ''): ?>
            <div class="mb-6 rounded-xl border border-[#22C55E]/30 bg-[#22C55E]/10 text-[#22C55E] px-4 py-3 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="mb-6 rounded-xl border border-[#EF4444]/30 bg-[#EF4444]/10 text-[#EF4444] px-4 py-3 text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-12 gap-6">
            
            <!-- Left Sidebar (3/12) -->
            <div class="col-span-12 lg:col-span-3 flex flex-col gap-6">
                <!-- Profile Overview Widget -->
                <div class="cs-card-glass p-6 text-center flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-white/10 bg-white/5 mb-4 flex-shrink-0 shadow-lg shadow-black/20">
                        <?php if ($photo_path): ?>
                            <img src="<?= $photo_path ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-[#38BDF8]">
                                <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2 class="text-base font-semibold text-[#F8FAFC] truncate w-full mb-1"><?= htmlspecialchars($display_name) ?></h2>
                    <p class="text-xs text-[#94A3B8] mb-4 truncate w-full"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-[11px] font-medium <?= $profile_completed ? 'text-[#22C55E]' : 'text-[#F59E0B]' ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?= $profile_completed ? 'bg-[#22C55E]' : 'bg-[#F59E0B]' ?>"></span>
                        <?= $profile_completed ? 'Profile Complete' : 'Profile Incomplete' ?>
                    </span>
                </div>
                
                <!-- Quick Actions Widget -->
                <div class="cs-card p-5 border-l-4 border-l-[#3B82F6]">
                    <h3 class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider mb-3 px-2">Quick Actions</h3>
                    <div class="flex flex-col">
                        <a href="post-idea.php" class="action-link"><i class="fa-solid fa-plus w-4 text-center"></i> Post New Idea</a>
                        <a href="ideas.php" class="action-link"><i class="fa-solid fa-lightbulb w-4 text-center"></i> Browse Ideas</a>
                        <a href="teams.php" class="action-link"><i class="fa-solid fa-users w-4 text-center"></i> Find Teams</a>
                        <a href="profileuser.php" class="action-link"><i class="fa-solid fa-user w-4 text-center"></i> Public Profile</a>
                    </div>
                </div>
            </div>

            <!-- Main Content (9/12) -->
            <div class="col-span-12 lg:col-span-9 flex flex-col gap-6">
                
                <!-- Statistics Row -->
                    <div class="grid grid-cols-3 gap-6">
                    <?php if ($user_role === 'student'): ?>
                        <div class="cs-card p-5 flex flex-col justify-center">
                            <p class="text-xs text-[#94A3B8] font-medium mb-1">Ideas Posted</p>
                            <p class="text-2xl font-bold text-[#F8FAFC]"><?= $ideas_count ?></p>
                        </div>
                        <div class="cs-card p-5 flex flex-col justify-center">
                            <p class="text-xs text-[#94A3B8] font-medium mb-1">Active Teams</p>
                            <p class="text-2xl font-bold text-[#F8FAFC]"><?= $teams_count ?></p>
                        </div>
                        <div class="cs-card p-5 flex flex-col justify-center border-b-4 border-b-[#38BDF8]">
                            <p class="text-xs text-[#94A3B8] font-medium mb-1">Core Skill Focus</p>
                            <p class="text-sm font-semibold text-[#38BDF8] truncate"><?= $primary_skill ?></p>
                        </div>
                    <?php else: ?>
                        <div class="cs-card p-5 flex flex-col justify-center">
                            <p class="text-xs text-[#94A3B8] font-medium mb-1">Reviews Submitted</p>
                            <p class="text-2xl font-bold text-[#F8FAFC]"><?= $feedback_count ?></p>
                        </div>
                        <div class="cs-card p-5 col-span-2 flex flex-col justify-center">
                            <p class="text-xs text-[#94A3B8] font-medium mb-1">Faculty Hub</p>
                            <p class="text-sm text-[#CBD5E1]">Mentoring students and guiding technical architecture.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-12 gap-6 h-[calc(100vh-290px)] min-h-[450px]">
                    <!-- Recent Activity (5/12) -->
                    <div class="col-span-12 md:col-span-5 cs-card p-0 flex flex-col overflow-hidden">
                        <div class="p-5 border-b border-white/5 bg-[#111827] z-10 rounded-t-2xl">
                            <h3 class="text-[13px] font-semibold text-[#F8FAFC]">Activity & Requests</h3>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto custom-scroll p-5 space-y-6">
                            
                            <!-- Incoming Requests -->
                            <div>
                                <h4 class="text-[10px] font-bold text-[#94A3B8] uppercase tracking-wider mb-3">Incoming Applications</h4>
                                <?php if (empty($incoming_applications)): ?>
                                    <div class="text-xs text-[#94A3B8] p-3 cs-card-secondary">No incoming requests.</div>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($incoming_applications as $app): $st = $app['status']; ?>
                                            <div class="p-4 cs-card-secondary flex flex-col gap-3">
                                                <div class="flex justify-between items-start gap-2">
                                                    <div>
                                                        <p class="text-xs font-semibold text-[#F8FAFC]"><?= htmlspecialchars($app['applicant_name']) ?></p>
                                                        <p class="text-[11px] text-[#94A3B8] mt-0.5">applied to <span class="text-[#38BDF8]"><?= htmlspecialchars($app['idea_title']) ?></span></p>
                                                    </div>
                                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded uppercase <?= application_status_classes($st) ?>"><?= application_status_label($st) ?></span>
                                                </div>
                                                <p class="text-[12px] text-[#CBD5E1] bg-[#0F172A] p-2.5 rounded-md leading-relaxed border border-white/5 line-clamp-2"><?= htmlspecialchars($app['message']) ?></p>
                                                <?php if ($st === 'pending'): ?>
                                                    <div class="flex gap-2 mt-1">
                                                        <form method="post" class="flex-1">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                            <button type="submit" name="application_action" value="accept" class="w-full text-[11px] bg-[#22C55E]/10 text-[#22C55E] hover:bg-[#22C55E]/20 py-2 rounded-md font-semibold transition">Accept</button>
                                                        </form>
                                                        <form method="post" class="flex-1">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                            <button type="submit" name="application_action" value="reject" class="w-full text-[11px] bg-[#EF4444]/10 text-[#EF4444] hover:bg-[#EF4444]/20 py-2 rounded-md font-semibold transition">Reject</button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Outgoing Requests -->
                            <div>
                                <h4 class="text-[10px] font-bold text-[#94A3B8] uppercase tracking-wider mb-3">My Sent Applications</h4>
                                <?php if (empty($sent_applications)): ?>
                                    <div class="text-xs text-[#94A3B8] p-3 cs-card-secondary">You haven't sent any requests.</div>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($sent_applications as $app): $st = $app['status']; ?>
                                            <div class="p-3.5 cs-card-secondary flex justify-between items-center">
                                                <div>
                                                    <p class="text-xs font-semibold text-[#F8FAFC]"><?= htmlspecialchars($app['idea_title']) ?></p>
                                                    <p class="text-[10px] text-[#94A3B8] mt-0.5">Owner: <?= htmlspecialchars($app['owner_name']) ?></p>
                                                </div>
                                                <span class="text-[9px] font-bold px-2 py-0.5 rounded uppercase <?= application_status_classes($st) ?>"><?= application_status_label($st) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($user_role === 'student'): ?>
                        <!-- Project Submission Widget (4/12) -->
                        <div class="col-span-12 md:col-span-4 cs-card p-6 flex flex-col items-center justify-center text-center relative border-t-4 border-t-[#10B981]">
                            <div class="w-16 h-16 bg-[#10B981]/10 rounded-full flex items-center justify-center text-[#10B981] text-3xl mb-4">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#F8FAFC]">Project Submission</h3>
                            <p class="text-[#94A3B8] mt-2 text-sm max-w-sm mx-auto">Submit your major project proposal and research documents for faculty review.</p>
                            
                            <a href="project-submission.php" class="mt-8 px-5 py-2.5 rounded-xl bg-emerald-500/20 text-emerald-400 font-semibold hover:bg-emerald-500/30 transition flex items-center gap-2 border border-emerald-500/30">
                                Open Portal <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>

                        <!-- Profile Completion Widget (3/12) -->
                        <div class="col-span-12 md:col-span-3 cs-card p-6 flex flex-col items-center justify-center text-center relative border-t-4 border-t-[#3B82F6]">
                            <div class="w-12 h-12 bg-[#3B82F6]/10 rounded-full flex items-center justify-center text-[#3B82F6] text-2xl mb-3">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                            <h3 class="text-lg font-bold text-[#F8FAFC]">Profile</h3>
                            
                            <div class="w-full mt-4">
                                <div class="flex justify-between text-xs font-semibold mb-1">
                                    <span class="text-[#38BDF8]">Progress</span>
                                    <span class="text-[#F8FAFC]"><?= $profile_completed ? "100%" : "40%" ?></span>
                                </div>
                                <div class="w-full bg-[#1E293B] rounded-full h-1.5 mb-4 overflow-hidden">
                                    <div class="bg-gradient-to-r from-[#3B82F6] to-[#38BDF8] h-1.5 rounded-full" style="width: <?= $profile_completed ? "100%" : "40%" ?>"></div>
                                </div>
                            </div>
                            
                            <a href="edit-profile.php" class="btn-primary flex items-center gap-2 w-full justify-center">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Faculty Evaluation Access Widget (7/12) -->
                        <div class="col-span-12 md:col-span-7 cs-card p-6 flex flex-col items-center justify-center text-center relative border-t-4 border-t-[#38BDF8]">
                            <div class="w-16 h-16 bg-[#38BDF8]/10 rounded-full flex items-center justify-center text-[#38BDF8] text-3xl mb-4">
                                <i class="fa-solid fa-clipboard-check"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#F8FAFC]">Faculty Evaluation Portal</h3>
                            <p class="text-[#94A3B8] mt-2 text-sm max-w-md mx-auto">Review student innovations, evaluate project feasibility, and mentor the next generation of engineers.</p>
                            
                            <a href="faculty.php" class="mt-8 px-6 py-3 rounded-full bg-gradient-to-r from-[#3B82F6] to-[#38BDF8] text-white font-semibold hover:shadow-lg hover:shadow-[#38BDF8]/20 transition flex items-center gap-2">
                                Open Evaluation Dashboard <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>



            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>