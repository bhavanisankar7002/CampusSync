<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = (int) $_SESSION['user_id'];
$success = false;
$error = '';

// Get user info to pre-fill form
$stmt = $conn->prepare("SELECT name, regd_no, department, year_studying FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_name = $user_info['name'] ?? '';
$regd_no = $user_info['regd_no'] ?? '';
$department = $user_info['department'] ?? '';
$year_of_study = $user_info['year_studying'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['view_only'])) {
    if (!csrf_verify()) {
        $error = "Invalid CSRF token.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $team_members = trim($_POST['team_members'] ?? '');
        $abstract = trim($_POST['abstract'] ?? '');
        $problem_statement = trim($_POST['problem_statement'] ?? '');
        $proposed_solution = trim($_POST['proposed_solution'] ?? '');
        $objectives = trim($_POST['objectives'] ?? '');
        $expected_outcome = trim($_POST['expected_outcome'] ?? '');
        $technologies_used = trim($_POST['technologies_used'] ?? '');
        $future_scope = trim($_POST['future_scope'] ?? '');
        $estimated_completion = trim($_POST['estimated_completion'] ?? '');
        $github_url = trim($_POST['github_url'] ?? '');
        $demo_video_url = trim($_POST['demo_video_url'] ?? '');

        // File uploads
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $research_paper_path = null;
        if (!empty($_FILES['research_paper']['name'])) {
            $research_paper_path = $upload_dir . time() . '_' . basename($_FILES['research_paper']['name']);
            move_uploaded_file($_FILES['research_paper']['tmp_name'], $research_paper_path);
        }

        $images_path = null;
        if (!empty($_FILES['images']['name'])) {
            $images_path = $upload_dir . time() . '_' . basename($_FILES['images']['name']);
            move_uploaded_file($_FILES['images']['tmp_name'], $images_path);
        }

        $architecture_diagram_path = null;
        if (!empty($_FILES['architecture_diagram']['name'])) {
            $architecture_diagram_path = $upload_dir . time() . '_' . basename($_FILES['architecture_diagram']['name']);
            move_uploaded_file($_FILES['architecture_diagram']['tmp_name'], $architecture_diagram_path);
        }

        $flowchart_path = null;
        if (!empty($_FILES['flowchart']['name'])) {
            $flowchart_path = $upload_dir . time() . '_' . basename($_FILES['flowchart']['name']);
            move_uploaded_file($_FILES['flowchart']['tmp_name'], $flowchart_path);
        }

        if (empty($title) || empty($abstract)) {
            $error = "Title and Abstract are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO project_submissions (student_id, title, domain, category, student_name, regd_no, department, year_of_study, team_members, abstract, problem_statement, proposed_solution, objectives, expected_outcome, technologies_used, future_scope, estimated_completion, research_paper_path, images_path, architecture_diagram_path, flowchart_path, github_url, demo_video_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssisssssssssssssss", $student_id, $title, $domain, $category, $student_name, $regd_no, $department, $year_of_study, $team_members, $abstract, $problem_statement, $proposed_solution, $objectives, $expected_outcome, $technologies_used, $future_scope, $estimated_completion, $research_paper_path, $images_path, $architecture_diagram_path, $flowchart_path, $github_url, $demo_video_url);
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Check for existing submission to switch to tracking mode
$has_submission = false;
$sub = null;
$stmt = $conn->prepare("SELECT * FROM project_submissions WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $has_submission = true;
    $sub = $res->fetch_assoc();
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Submission & Evaluation • CampusSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .logo-font { font-family: 'Space Grotesk', sans-serif; }
        .compact-input {
            width: 100%;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #F8FAFC;
            font-size: 0.875rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }
        .compact-input:focus {
            outline: none;
            border-color: #38BDF8;
            background-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }
        .compact-input:disabled, .compact-input[readonly] {
            background-color: rgba(255, 255, 255, 0.02);
            color: #94A3B8;
            cursor: not-allowed;
            border-color: rgba(255, 255, 255, 0.05);
        }
        .cs-card {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .upload-zone {
            border: 2px dashed rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
        }
        .upload-zone:hover {
            border-color: #38BDF8;
            background: rgba(56, 189, 248, 0.05);
        }
        /* Vertical Timeline */
        .timeline-item {
            position: relative;
            padding-left: 1.5rem;
            padding-bottom: 1.5rem;
            border-left: 2px solid rgba(255,255,255,0.1);
        }
        .timeline-item:last-child { border-left-color: transparent; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.375rem; /* half of width 0.75rem minus border */
            top: 0;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 50%;
            background: #1E293B;
            border: 2px solid rgba(255,255,255,0.2);
        }
        .timeline-item.completed::before {
            background: #10B981;
            border-color: #10B981;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);
        }
        .timeline-item.active::before {
            background: #F59E0B;
            border-color: #F59E0B;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
            animation: pulse-ring 2s infinite;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-[#F8FAFC]">

    <?php $active_page = 'project-submission'; include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-6 py-12 w-full">
        <div class="mb-8">
            <span class="text-xs font-bold tracking-widest text-[#38BDF8] uppercase">Academic Portal</span>
            <h1 class="logo-font text-4xl lg:text-5xl font-semibold tracking-tight mt-2 text-[#F8FAFC]">Project Submission & Faculty Evaluation</h1>
            <p class="text-[#94A3B8] mt-3 text-lg">Submit your project proposal with research documents and track faculty evaluation in one place.</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-400 text-red-300 rounded-2xl p-4 mb-6 flex items-center gap-2 text-sm">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-emerald-500/10 border border-emerald-400 text-emerald-300 rounded-2xl p-4 mb-6 flex items-center gap-2 text-sm">
                <i class="fa-solid fa-circle-check shrink-0"></i> Your proposal has been submitted successfully!
            </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Form / Submitted Data -->
            <div class="lg:col-span-8 space-y-6">
                
                <form method="POST" enctype="multipart/form-data" id="submissionForm">
                    <?= csrf_field() ?>
                    <?php if ($has_submission): ?>
                        <input type="hidden" name="view_only" value="1">
                    <?php endif; ?>

                    <div class="cs-card p-8">
                        <h2 class="text-xl font-semibold mb-6 border-b border-white/10 pb-4 flex items-center gap-2"><i class="fa-solid fa-book-open text-cyan-400"></i> Project Details</h2>
                        
                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Project Title <span class="text-red-400">*</span></label>
                                <input type="text" name="title" class="compact-input" required value="<?= htmlspecialchars($sub['title'] ?? '') ?>" <?= $has_submission ? 'readonly' : '' ?>>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Project Domain <span class="text-red-400">*</span></label>
                                <select name="domain" class="compact-input" required <?= $has_submission ? 'disabled' : '' ?>>
                                    <option value="" disabled selected>Select Domain</option>
                                    <option value="AI/ML" <?= ($sub['domain']??'') == 'AI/ML' ? 'selected' : '' ?>>AI/ML</option>
                                    <option value="Web Development" <?= ($sub['domain']??'') == 'Web Development' ? 'selected' : '' ?>>Web Development</option>
                                    <option value="IoT" <?= ($sub['domain']??'') == 'IoT' ? 'selected' : '' ?>>IoT</option>
                                    <option value="Cybersecurity" <?= ($sub['domain']??'') == 'Cybersecurity' ? 'selected' : '' ?>>Cybersecurity</option>
                                    <option value="Other" <?= ($sub['domain']??'') == 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Project Category <span class="text-red-400">*</span></label>
                            <input type="text" name="category" class="compact-input" required placeholder="e.g. Major Project, Capstone, Hackathon" value="<?= htmlspecialchars($sub['category'] ?? '') ?>" <?= $has_submission ? 'readonly' : '' ?>>
                        </div>

                        <!-- Readonly Student Details -->
                        <div class="bg-white/5 rounded-xl p-4 mb-6 border border-white/5">
                            <h3 class="text-sm font-semibold mb-3 text-cyan-100"><i class="fa-solid fa-graduation-cap"></i> Student Profile Data</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div><span class="block text-slate-400 text-xs mb-1">Name</span><?= htmlspecialchars($student_name) ?></div>
                                <div><span class="block text-slate-400 text-xs mb-1">Regd No</span><?= htmlspecialchars($regd_no) ?></div>
                                <div><span class="block text-slate-400 text-xs mb-1">Dept</span><?= htmlspecialchars($department) ?></div>
                                <div><span class="block text-slate-400 text-xs mb-1">Year</span><?= htmlspecialchars($year_of_study) ?></div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Team Members (Optional)</label>
                            <input type="text" name="team_members" class="compact-input" placeholder="Comma separated names" value="<?= htmlspecialchars($sub['team_members'] ?? '') ?>" <?= $has_submission ? 'readonly' : '' ?>>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Project Abstract <span class="text-red-400">*</span></label>
                            <textarea name="abstract" rows="4" class="compact-input" required <?= $has_submission ? 'readonly' : '' ?>><?= htmlspecialchars($sub['abstract'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Problem Statement <span class="text-red-400">*</span></label>
                            <textarea name="problem_statement" rows="3" class="compact-input" required <?= $has_submission ? 'readonly' : '' ?>><?= htmlspecialchars($sub['problem_statement'] ?? '') ?></textarea>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Proposed Solution <span class="text-red-400">*</span></label>
                                <textarea name="proposed_solution" rows="3" class="compact-input" required <?= $has_submission ? 'readonly' : '' ?>><?= htmlspecialchars($sub['proposed_solution'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Objectives <span class="text-red-400">*</span></label>
                                <textarea name="objectives" rows="3" class="compact-input" required <?= $has_submission ? 'readonly' : '' ?>><?= htmlspecialchars($sub['objectives'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Expected Outcome <span class="text-red-400">*</span></label>
                            <textarea name="expected_outcome" rows="3" class="compact-input" required <?= $has_submission ? 'readonly' : '' ?>><?= htmlspecialchars($sub['expected_outcome'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Technologies Used <span class="text-red-400">*</span></label>
                            <input type="text" name="technologies_used" class="compact-input" placeholder="e.g. React, Node.js, TensorFlow" required value="<?= htmlspecialchars($sub['technologies_used'] ?? '') ?>" <?= $has_submission ? 'readonly' : '' ?>>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Future Scope <span class="text-red-400">*</span></label>
                                <textarea name="future_scope" rows="2" class="compact-input" required <?= $has_submission ? 'readonly' : '' ?>><?= htmlspecialchars($sub['future_scope'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1">Estimated Completion Date <span class="text-red-400">*</span></label>
                                <input type="date" name="estimated_completion" class="compact-input" required value="<?= htmlspecialchars($sub['estimated_completion'] ?? '') ?>" <?= $has_submission ? 'readonly' : '' ?>>
                            </div>
                        </div>
                    </div> <!-- End Details Card -->

                    <div class="cs-card p-8 mt-6">
                        <h2 class="text-xl font-semibold mb-6 border-b border-white/10 pb-4 flex items-center gap-2"><i class="fa-solid fa-file-arrow-up text-purple-400"></i> Research & Documents Upload</h2>
                        
                        <?php if (!$has_submission): ?>
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Research Paper -->
                            <div class="upload-zone rounded-xl p-6 text-center cursor-pointer relative" onclick="document.getElementById('research_paper').click()">
                                <i class="fa-solid fa-file-pdf text-3xl text-rose-400 mb-3"></i>
                                <h4 class="text-sm font-semibold mb-1">Research Paper (PDF)</h4>
                                <p class="text-xs text-slate-400 mb-4">Max 5MB</p>
                                <span class="px-4 py-1.5 bg-white/10 rounded-full text-xs hover:bg-white/20 transition">Browse Files</span>
                                <input type="file" name="research_paper" id="research_paper" class="hidden" accept=".pdf">
                                <div id="research_paper_name" class="text-xs text-cyan-300 mt-3 truncate"></div>
                            </div>
                            
                            <!-- Images -->
                            <div class="upload-zone rounded-xl p-6 text-center cursor-pointer relative" onclick="document.getElementById('images').click()">
                                <i class="fa-solid fa-image text-3xl text-emerald-400 mb-3"></i>
                                <h4 class="text-sm font-semibold mb-1">Project Images</h4>
                                <p class="text-xs text-slate-400 mb-4">Max 5MB (JPG/PNG)</p>
                                <span class="px-4 py-1.5 bg-white/10 rounded-full text-xs hover:bg-white/20 transition">Browse Files</span>
                                <input type="file" name="images" id="images" class="hidden" accept="image/*">
                                <div id="images_name" class="text-xs text-cyan-300 mt-3 truncate"></div>
                            </div>

                            <!-- Architecture Diagram -->
                            <div class="upload-zone rounded-xl p-6 text-center cursor-pointer relative" onclick="document.getElementById('architecture_diagram').click()">
                                <i class="fa-solid fa-diagram-project text-3xl text-blue-400 mb-3"></i>
                                <h4 class="text-sm font-semibold mb-1">Architecture Diagram</h4>
                                <p class="text-xs text-slate-400 mb-4">IMG/PDF</p>
                                <span class="px-4 py-1.5 bg-white/10 rounded-full text-xs hover:bg-white/20 transition">Browse Files</span>
                                <input type="file" name="architecture_diagram" id="architecture_diagram" class="hidden" accept="image/*,.pdf">
                                <div id="architecture_diagram_name" class="text-xs text-cyan-300 mt-3 truncate"></div>
                            </div>

                            <!-- Flowchart -->
                            <div class="upload-zone rounded-xl p-6 text-center cursor-pointer relative" onclick="document.getElementById('flowchart').click()">
                                <i class="fa-solid fa-sitemap text-3xl text-orange-400 mb-3"></i>
                                <h4 class="text-sm font-semibold mb-1">Flowchart</h4>
                                <p class="text-xs text-slate-400 mb-4">IMG/PDF</p>
                                <span class="px-4 py-1.5 bg-white/10 rounded-full text-xs hover:bg-white/20 transition">Browse Files</span>
                                <input type="file" name="flowchart" id="flowchart" class="hidden" accept="image/*,.pdf">
                                <div id="flowchart_name" class="text-xs text-cyan-300 mt-3 truncate"></div>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1"><i class="fa-brands fa-github"></i> GitHub Repository Link</label>
                                <input type="url" name="github_url" class="compact-input" placeholder="https://github.com/...">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#CBD5E1] mb-1.5 pl-1"><i class="fa-solid fa-video"></i> Demo Video Link</label>
                                <input type="url" name="demo_video_url" class="compact-input" placeholder="YouTube, Drive, etc.">
                            </div>
                        </div>
                        <?php else: ?>
                            <!-- Readonly view of uploaded files -->
                            <div class="grid md:grid-cols-2 gap-4">
                                <?php if ($sub['research_paper_path']): ?>
                                    <a href="<?= htmlspecialchars($sub['research_paper_path']) ?>" target="_blank" class="flex items-center gap-3 bg-white/5 p-4 rounded-xl hover:bg-white/10 transition border border-white/5 group">
                                        <i class="fa-solid fa-file-pdf text-2xl text-rose-400 group-hover:scale-110 transition"></i>
                                        <span class="text-sm">Research Paper</span>
                                    </a>
                                <?php endif; ?>
                                <?php if ($sub['architecture_diagram_path']): ?>
                                    <a href="<?= htmlspecialchars($sub['architecture_diagram_path']) ?>" target="_blank" class="flex items-center gap-3 bg-white/5 p-4 rounded-xl hover:bg-white/10 transition border border-white/5 group">
                                        <i class="fa-solid fa-diagram-project text-2xl text-blue-400 group-hover:scale-110 transition"></i>
                                        <span class="text-sm">Architecture Diagram</span>
                                    </a>
                                <?php endif; ?>
                                <?php if ($sub['flowchart_path']): ?>
                                    <a href="<?= htmlspecialchars($sub['flowchart_path']) ?>" target="_blank" class="flex items-center gap-3 bg-white/5 p-4 rounded-xl hover:bg-white/10 transition border border-white/5 group">
                                        <i class="fa-solid fa-sitemap text-2xl text-orange-400 group-hover:scale-110 transition"></i>
                                        <span class="text-sm">Flowchart</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="mt-6 grid md:grid-cols-2 gap-4">
                                <div class="bg-white/5 p-3 rounded-lg border border-white/5">
                                    <span class="text-xs text-slate-400 block mb-1"><i class="fa-brands fa-github"></i> GitHub:</span> 
                                    <?= $sub['github_url'] ? '<a href="'.htmlspecialchars($sub['github_url']).'" class="text-cyan-400 underline text-sm" target="_blank">View Repository</a>' : '<span class="text-sm text-slate-500">Not provided</span>' ?>
                                </div>
                                <div class="bg-white/5 p-3 rounded-lg border border-white/5">
                                    <span class="text-xs text-slate-400 block mb-1"><i class="fa-solid fa-video"></i> Video:</span> 
                                    <?= $sub['demo_video_url'] ? '<a href="'.htmlspecialchars($sub['demo_video_url']).'" class="text-cyan-400 underline text-sm" target="_blank">Watch Demo</a>' : '<span class="text-sm text-slate-500">Not provided</span>' ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div> <!-- End Upload Card -->

                </form>
            </div>

            <!-- Right Column: Status & Timeline -->
            <div class="lg:col-span-4 relative">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Submission Status Card -->
                    <div class="cs-card p-6">
                        <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-widest mb-4">Submission Status</h3>
                        
                        <?php if (!$has_submission): ?>
                            <div class="bg-slate-800/50 border border-slate-700 p-4 rounded-xl text-center mb-6">
                                <span class="text-slate-400 text-sm">Not Submitted Yet</span>
                            </div>
                        <?php else: ?>
                            <?php
                                $status_colors = [
                                    'pending' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                                    'under_review' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                                    'revision_required' => 'bg-rose-500/20 text-rose-400 border-rose-500/30',
                                    'approved' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
                                ];
                                $st = $sub['status'];
                                $st_color = $status_colors[$st] ?? 'bg-white/10 text-white';
                                $st_text = ucwords(str_replace('_', ' ', $st));
                            ?>
                            <div class="<?= $st_color ?> border p-4 rounded-xl flex items-center justify-between mb-4">
                                <span class="text-sm font-semibold"><i class="fa-solid fa-circle-dot mr-2 text-xs"></i> <?= $st_text ?></span>
                                <span class="text-xs opacity-75"><?= date('M d, Y', strtotime($sub['created_at'])) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Timeline -->
                        <div class="mt-8 ml-2">
                            <?php 
                                $is_pending = $has_submission && $sub['status'] === 'pending';
                                $is_review = $has_submission && $sub['status'] === 'under_review';
                                $is_approved = $has_submission && $sub['status'] === 'approved';
                            ?>
                            <div class="timeline-item <?= $has_submission ? 'completed' : 'active' ?>">
                                <h4 class="text-sm font-semibold <?= $has_submission ? 'text-white' : 'text-slate-400' ?>">Proposal Submitted</h4>
                                <p class="text-xs text-slate-500 mt-1"><?= $has_submission ? 'Completed' : 'Pending submission' ?></p>
                            </div>
                            <div class="timeline-item <?= $has_submission && !$is_pending ? 'completed' : ($is_pending ? 'active' : '') ?>">
                                <h4 class="text-sm font-semibold <?= ($has_submission && !$is_pending) ? 'text-white' : 'text-slate-400' ?>">Faculty Assigned</h4>
                                <p class="text-xs text-slate-500 mt-1"><?= $has_submission && $sub['faculty_id'] ? 'Faculty member has taken up review' : 'Waiting for faculty assignment' ?></p>
                            </div>
                            <div class="timeline-item <?= $is_review ? 'active' : ($is_approved ? 'completed' : '') ?>">
                                <h4 class="text-sm font-semibold <?= $is_review || $is_approved ? 'text-white' : 'text-slate-400' ?>">Under Review</h4>
                                <p class="text-xs text-slate-500 mt-1">Faculty is reviewing your documents</p>
                            </div>
                            <div class="timeline-item <?= $is_approved ? 'completed' : '' ?>">
                                <h4 class="text-sm font-semibold <?= $is_approved ? 'text-emerald-400' : 'text-slate-400' ?>">Final Approval</h4>
                                <p class="text-xs text-slate-500 mt-1"><?= $is_approved ? 'Project approved successfully' : 'Pending approval' ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Faculty Feedback Card -->
                    <div class="cs-card p-6 border-t-[3px] border-t-cyan-500">
                        <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="fa-solid fa-comment-dots text-cyan-400"></i> Faculty Feedback</h3>
                        
                        <?php if (!$has_submission): ?>
                            <p class="text-sm text-slate-400 text-center py-4 italic">"Submit your proposal first. Faculty evaluation and feedback will appear here."</p>
                        <?php elseif ($sub['status'] === 'pending' || $sub['status'] === 'under_review'): ?>
                            <div class="text-center py-6">
                                <i class="fa-solid fa-clock-rotate-left text-3xl text-slate-500 mb-3 animate-pulse"></i>
                                <p class="text-sm text-slate-400 italic">"Your proposal has been submitted successfully. Faculty evaluation will appear here after review."</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php if ($sub['marks_obtained']): ?>
                                    <div class="flex justify-between items-center bg-cyan-900/30 p-3 rounded-lg border border-cyan-800/50">
                                        <span class="text-xs font-semibold text-cyan-300">Marks Obtained</span>
                                        <span class="text-lg font-bold text-white"><?= htmlspecialchars($sub['marks_obtained']) ?>/100</span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="text-xs font-semibold text-emerald-400 mb-1">Strengths</h4>
                                    <p class="text-sm text-slate-300 bg-white/5 p-3 rounded-lg border border-white/5"><?= htmlspecialchars($sub['faculty_strengths'] ?? 'None provided') ?></p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-semibold text-amber-400 mb-1">Suggestions / Revisions</h4>
                                    <p class="text-sm text-slate-300 bg-white/5 p-3 rounded-lg border border-white/5"><?= htmlspecialchars($sub['faculty_suggestions'] ?? 'None provided') ?></p>
                                </div>
                                <?php if ($sub['faculty_remarks']): ?>
                                <div>
                                    <h4 class="text-xs font-semibold text-slate-400 mb-1">Overall Remarks</h4>
                                    <p class="text-sm text-slate-300"><?= htmlspecialchars($sub['faculty_remarks']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$has_submission): ?>
                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-3">
                        <button type="submit" form="submissionForm" class="bg-cyan-500 hover:bg-cyan-400 text-slate-900 font-bold py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 transition shadow-[0_0_15px_rgba(34,211,238,0.2)]">
                            <i class="fa-solid fa-paper-plane"></i> Submit Proposal
                        </button>
                        <button type="button" class="bg-white/10 hover:bg-white/20 border border-white/10 text-white font-medium py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition">
                            <i class="fa-solid fa-floppy-disk"></i> Save Draft
                        </button>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- JS for File Upload Previews -->
    <script>
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const nameDisplay = document.getElementById(this.id + '_name');
                if (this.files && this.files.length > 0) {
                    nameDisplay.textContent = this.files[0].name;
                } else {
                    nameDisplay.textContent = '';
                }
            });
        });
    </script>
</body>
</html>
