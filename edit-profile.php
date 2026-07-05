<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$success = '';
$error = '';
$level_options = [20, 40, 60, 80, 100];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    $regd_no = trim($_POST['regd_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_no = trim($_POST['phone_no'] ?? '');
    $github_url = trim($_POST['github_url'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $portfolio_url = trim($_POST['portfolio_url'] ?? '');
    $year_studying = trim($_POST['year_studying'] ?? '');
    $skill_names = $_POST['skill_name'] ?? [];
    $skill_levels = $_POST['skill_level'] ?? [];
    $working_skill_name = trim($_POST['working_skill_name'] ?? '');
    $working_skill_level = (int) ($_POST['working_skill_level'] ?? 0);
    $projects_done = trim($_POST['projects_done'] ?? '');
    $achievements_from_college = trim($_POST['achievements_from_college'] ?? '');
    $skills_payload = [];
    $primary_skill = '';
    $primary_skill_level = 0;

    foreach ($skill_names as $index => $skill_name_raw) {
        $skill_name = trim((string) $skill_name_raw);
        $skill_level = (int) ($skill_levels[$index] ?? 0);

        if ($skill_name === '') {
            continue;
        }

        if (!in_array($skill_level, $level_options, true)) {
            $error = 'Choose valid experience levels for every skill.';
            break;
        }

        $skills_payload[] = [
            'name' => $skill_name,
            'level' => $skill_level
        ];
    }

    if (!empty($skills_payload)) {
        $primary_skill = $skills_payload[0]['name'];
        $primary_skill_level = (int) $skills_payload[0]['level'];
    }

    $skills_json = json_encode($skills_payload, JSON_UNESCAPED_UNICODE);

    if ($name === '' || $email === '') {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($github_url !== '' && !filter_var($github_url, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid GitHub link.';
    } elseif ($linkedin_url !== '' && !filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid LinkedIn link.';
    } elseif ($portfolio_url !== '' && !filter_var($portfolio_url, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid Portfolio link.';
    } elseif ($year_studying !== '' && (!ctype_digit($year_studying) || (int) $year_studying < 1 || (int) $year_studying > 8)) {
        $error = 'Year studying should be a number between 1 and 8.';
    } elseif (empty($skills_payload)) {
        $error = 'Add at least one skill with experience.';
    } elseif (!in_array($working_skill_level, $level_options, true)) {
        $error = 'Choose a valid current working skill level.';
    } else {
        $photo_sql = '';
        $photo_value = null;

        if (!empty($_FILES['profile_photo']['name'])) {
            // ✅ 2MB file size limit
            if ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) {
                $error = 'Profile photo must be under 2MB.';
            } elseif (!isset($_FILES['profile_photo']['error']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Profile photo upload failed. Please try again.';
            } else {
                $allowed_types = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif'
                ];
                $mime_type = mime_content_type($_FILES['profile_photo']['tmp_name']);

                if (!isset($allowed_types[$mime_type])) {
                    $error = 'Only JPG, PNG, WEBP, and GIF images are allowed.';
                } else {
                    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile-photos';

                    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
                        $error = 'Could not create the upload folder.';
                    } else {
                        $filename = 'user-' . $user_id . '-' . time() . '.' . $allowed_types[$mime_type];
                        $absolute_target = $upload_dir . DIRECTORY_SEPARATOR . $filename;
                        $relative_target = 'uploads/profile-photos/' . $filename;

                        if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $absolute_target)) {
                            $error = 'Could not save the uploaded profile photo.';
                        } else {
                            $photo_sql = ', profile_photo = ?';
                            $photo_value = $relative_target;
                        }
                    }
                }
            }
        }

        if ($error === '') {
            $sql = "UPDATE users SET name = ?, email = ?, regd_no = ?, phone_no = ?, github_url = ?, linkedin_url = ?, portfolio_url = ?, year_studying = ?, skills = ?, primary_skill = ?, primary_skill_level = ?, working_skill_name = ?, working_skill_level = ?, projects_done = ?, achievements_from_college = ?" . $photo_sql . " WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($photo_sql !== '') {
                $stmt->bind_param(
                    "ssssssssssisisssi",
                    $name,
                    $email,
                    $regd_no,
                    $phone_no,
                    $github_url,
                    $linkedin_url,
                    $portfolio_url,
                    $year_studying,
                    $skills_json,
                    $primary_skill,
                    $primary_skill_level,
                    $working_skill_name,
                    $working_skill_level,
                    $projects_done,
                    $achievements_from_college,
                    $photo_value,
                    $user_id
                );
            } else {
                $stmt->bind_param(
                    "ssssssssssisissi",
                    $name,
                    $email,
                    $regd_no,
                    $phone_no,
                    $github_url,
                    $linkedin_url,
                    $portfolio_url,
                    $year_studying,
                    $skills_json,
                    $primary_skill,
                    $primary_skill_level,
                    $working_skill_name,
                    $working_skill_level,
                    $projects_done,
                    $achievements_from_college,
                    $user_id
                );
            }

            if ($stmt->execute()) {
                $_SESSION['name'] = $name;
                $stmt->close();
                header("Location: profileuser.php?saved=1");
                exit;
            } else {
                error_log('Dashboard profile save error: ' . $conn->error);
                $error = 'Could not save the profile. Please try again.';
                $stmt->close();
            }
        }
    }
    } // end csrf_verify for save_profile
}

$user_stmt = $conn->prepare("SELECT name, email, skills, profile_photo, regd_no, phone_no, github_url, linkedin_url, portfolio_url, year_studying, primary_skill, primary_skill_level, working_skill_name, working_skill_level, projects_done, achievements_from_college FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

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

$working_level = (int) ($user['working_skill_level'] ?? 40);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile • CampusSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #1E293B; border-radius: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</head>
<body class="min-h-screen flex flex-col text-[#F8FAFC] bg-[#0B1220]">
    <?php $active_page = 'dashboard'; include 'navbar.php'; ?>
    
    <main class="flex-1 w-full max-w-4xl mx-auto px-6 py-10">
        <div class="mb-8">
            <a href="dashboard.php" class="text-sm text-[#94A3B8] hover:text-[#38BDF8] transition"><i class="fa-solid fa-arrow-left mr-2"></i>Back to Dashboard</a>
            <h1 class="text-3xl font-bold mt-4">Edit Profile</h1>
            <p class="text-[#94A3B8] mt-1">Complete your profile to unlock all features.</p>
        </div>

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

        <div class="cs-card">
            <form id="profile-builder" method="post" enctype="multipart/form-data" class="flex-1 flex flex-col overflow-hidden">
                            <input type="hidden" name="save_profile" value="1">
                            <?= csrf_field() ?>

                            <div class="flex-1 overflow-y-auto custom-scroll p-6 space-y-8">
                                
                                <!-- Core Info -->
                                <div>
                                    <h4 class="text-[10px] font-bold text-[#94A3B8] uppercase tracking-wider mb-4 border-b border-white/5 pb-2">Basic Information</h4>
                                    <div class="grid grid-cols-2 gap-5">
                                        <div class="col-span-2 flex items-center gap-4 cs-card-secondary p-4">
                                            <input type="file" name="profile_photo" class="text-xs text-[#CBD5E1] w-full" accept="image/*">
                                            <span class="text-[10px] text-[#94A3B8] whitespace-nowrap">Max 2MB (JPG, PNG)</span>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">Full Name</label>
                                            <input name="name" type="text" value="<?= htmlspecialchars($user['name'] ?? $_SESSION['name']) ?>" class="compact-input" required>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">Email</label>
                                            <input name="email" type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="compact-input" required>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">Registration No</label>
                                            <input name="regd_no" type="text" value="<?= htmlspecialchars($user['regd_no'] ?? '') ?>" class="compact-input">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">Phone</label>
                                            <input name="phone_no" type="text" value="<?= htmlspecialchars($user['phone_no'] ?? '') ?>" class="compact-input">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">Year Studying</label>
                                            <input name="year_studying" type="number" min="1" max="8" value="<?= htmlspecialchars($user['year_studying'] ?? '') ?>" class="compact-input">
                                        </div>
                                    </div>
                                </div>

                                <!-- Links -->
                                <div>
                                    <h4 class="text-[10px] font-bold text-[#94A3B8] uppercase tracking-wider mb-4 border-b border-white/5 pb-2">Social Links</h4>
                                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-5">
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">GitHub URL</label>
                                            <input name="github_url" type="url" value="<?= htmlspecialchars($user['github_url'] ?? '') ?>" class="compact-input" placeholder="https://github.com/...">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">LinkedIn URL</label>
                                            <input name="linkedin_url" type="url" value="<?= htmlspecialchars($user['linkedin_url'] ?? '') ?>" class="compact-input" placeholder="https://linkedin.com/in/...">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">Portfolio URL</label>
                                            <input name="portfolio_url" type="url" value="<?= htmlspecialchars($user['portfolio_url'] ?? '') ?>" class="compact-input" placeholder="https://yourportfolio.com">
                                        </div>
                                    </div>
                                </div>

                                <!-- Skills -->
                                <div>
                                    <div class="flex justify-between items-center mb-4 border-b border-white/5 pb-2">
                                        <h4 class="text-[10px] font-bold text-[#94A3B8] uppercase tracking-wider">Technical Skills</h4>
                                        <button type="button" id="addSkillBtn" class="text-[11px] font-semibold text-[#38BDF8] hover:text-[#3B82F6] transition"><i class="fa-solid fa-plus mr-1"></i> Add Skill</button>
                                    </div>
                                    
                                    <div class="cs-card-secondary p-5 space-y-5">
                                        <div class="grid grid-cols-2 gap-5 pb-5 border-b border-white/5">
                                            <div>
                                                <label class="block text-[11px] font-medium text-[#38BDF8] mb-1.5">Current Focus Skill</label>
                                                <input name="working_skill_name" type="text" value="<?= htmlspecialchars($user['working_skill_name'] ?? '') ?>" class="compact-input border-[#38BDF8]/30">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">Focus Level (%)</label>
                                                <select name="working_skill_level" class="compact-input">
                                                    <?php foreach ($level_options as $opt): ?>
                                                        <option value="<?= $opt ?>" <?= $working_level === $opt ? 'selected' : '' ?>><?= $opt ?>%</option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div id="skillsList" class="flex flex-col gap-3">
                                            <?php foreach ($skills_list as $index => $skill_item): ?>
                                                <div class="grid grid-cols-[1fr_120px_auto] items-center gap-3 skill-row">
                                                    <input name="skill_name[]" type="text" value="<?= htmlspecialchars($skill_item['name']) ?>" class="compact-input" placeholder="e.g. React">
                                                    <select name="skill_level[]" class="compact-input">
                                                        <?php foreach ($level_options as $opt): ?>
                                                            <option value="<?= $opt ?>" <?= (int)$skill_item['level'] === $opt ? 'selected' : '' ?>><?= $opt ?>%</option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="remove-skill text-[#94A3B8] hover:text-[#EF4444] px-2 transition"><i class="fa-solid fa-xmark text-sm"></i></button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Portfolio -->
                                <div>
                                    <h4 class="text-[10px] font-bold text-[#94A3B8] uppercase tracking-wider mb-4 border-b border-white/5 pb-2">Portfolio & Experience</h4>
                                    <div class="space-y-5">
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">Completed Projects</label>
                                            <textarea name="projects_done" rows="3" class="compact-input text-xs leading-relaxed"><?= htmlspecialchars($user['projects_done'] ?? '') ?></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5">College Achievements</label>
                                            <textarea name="achievements_from_college" rows="3" class="compact-input text-xs leading-relaxed"><?= htmlspecialchars($user['achievements_from_college'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                            
                            <div class="p-5 border-t border-white/5 bg-[#0B1220] flex justify-end rounded-b-2xl">
                                <button type="submit" class="btn-primary">Save Profile Changes</button>
                            </div>
                        </form>
        </div>
    </main>
    
    <script>
        const skillsList = document.getElementById('skillsList');
        const addSkillBtn = document.getElementById('addSkillBtn');
        const levelOptions = <?php echo json_encode($level_options); ?>;

        function refreshSkillEvents() {
            const rows = skillsList.querySelectorAll('.skill-row');
            rows.forEach((row) => {
                const removeBtn = row.querySelector('.remove-skill');
                removeBtn.classList.toggle('hidden', rows.length === 1);
                
                // Clone and replace to prevent duplicate listeners
                const newBtn = removeBtn.cloneNode(true);
                removeBtn.parentNode.replaceChild(newBtn, removeBtn);
                
                newBtn.addEventListener('click', () => {
                    row.remove();
                    refreshSkillEvents();
                });
            });
        }

        if (addSkillBtn && skillsList) {
            addSkillBtn.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'grid grid-cols-[1fr_120px_auto] items-center gap-3 skill-row';

                const optionsHtml = levelOptions.map(opt => 
                    `<option value="${opt}" ${opt === 60 ? 'selected' : ''}>${opt}%</option>`
                ).join('');

                row.innerHTML = `
                    <input name="skill_name[]" type="text" placeholder="e.g. React" class="compact-input">
                    <select name="skill_level[]" class="compact-input">
                        ${optionsHtml}
                    </select>
                    <button type="button" class="remove-skill text-[#94A3B8] hover:text-[#EF4444] px-2 transition"><i class="fa-solid fa-xmark text-sm"></i></button>
                `;

                skillsList.appendChild(row);
                refreshSkillEvents();
                
                // scroll to bottom of skills list
                row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
            refreshSkillEvents();
        }
    </script>
    
    <?php include 'footer.php'; ?>
</body>
</html>
