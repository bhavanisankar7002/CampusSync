<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = false;
$error   = '';
// Preserve inputs on error
$old = [
    'event_name' => '', 'event_dates' => '', 'lead_name' => '', 
    'lead_phone' => '', 'lead_year' => '', 'slots' => '4', 
    'skills_needed' => '', 'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $event_name    = trim($_POST['event_name']    ?? '');
        $event_dates   = trim($_POST['event_dates']   ?? '');
        $lead_name     = trim($_POST['lead_name']     ?? '');
        $lead_phone    = trim($_POST['lead_phone']    ?? '');
        $lead_year     = (int) ($_POST['lead_year']   ?? 0);
        $slots         = (int) ($_POST['slots']       ?? 4);
        $skills_needed = trim($_POST['skills_needed'] ?? '');
        $description   = trim($_POST['description']   ?? '');
        
        $posted_by   = (int) $_SESSION['user_id'];
        
        $old = compact('event_name', 'event_dates', 'lead_name', 'lead_phone', 'lead_year', 'slots', 'skills_needed', 'description');

        if ($event_name === '' || $event_dates === '' || $lead_name === '' || $lead_phone === '' || $skills_needed === '' || $description === '' || $lead_year <= 0) {
            $error = 'All fields are required.';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO event_teams (event_name, event_dates, lead_name, lead_phone, lead_year, slots, posted_by, description, skills_needed)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssssiiiss", $event_name, $event_dates, $lead_name, $lead_phone, $lead_year, $slots, $posted_by, $description, $skills_needed);
            
            if ($stmt->execute()) {
                $success = true;
                $old = ['event_name' => '', 'event_dates' => '', 'lead_name' => '', 'lead_phone' => '', 'lead_year' => '', 'slots' => '4', 'skills_needed' => '', 'description' => ''];
            } else {
                error_log("Post team DB error: " . $conn->error);
                $error = 'Something went wrong. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Team Request • CampusSync</title>
    <meta name="description" content="Post a team request on CampusSync and find skilled students to join your team.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .logo-font { font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex flex-col text-[#F8FAFC]">

    <?php $active_page = 'teams'; include 'navbar.php'; ?>

    <div class="max-w-5xl mx-auto px-6 py-12 w-full">
        <div class="grid md:grid-cols-1 gap-12 items-start max-w-3xl mx-auto">

            <div>
                <div class="mb-8 text-center">
                    <span class="text-xs font-bold tracking-widest text-[#38BDF8] uppercase">Form Your Crew</span>
                    <h1 class="logo-font text-4xl lg:text-5xl font-semibold tracking-tight mt-2 text-[#F8FAFC]">Post a Team Request</h1>
                    <p class="text-[#94A3B8] mt-3 text-lg">Looking for teammates for a hackathon or project? Post it here!</p>
                </div>

                <?php if ($success): ?>
                    <div class="bg-emerald-500/10 border border-emerald-400 text-emerald-300 rounded-3xl p-8 text-center mb-8">
                        <i class="fa-solid fa-users text-5xl mb-4 block"></i>
                        <h3 class="text-2xl font-semibold">Team Request Posted!</h3>
                        <p class="mt-2 text-slate-300">Your team request is now visible to all students.</p>
                        <div class="flex justify-center gap-4 mt-6">
                            <a href="teams.php" class="inline-flex items-center bg-white text-slate-900 px-6 py-3 rounded-3xl font-semibold gap-x-2 hover:bg-cyan-100 transition">
                                View Teams <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <a href="post-team.php" class="inline-flex items-center border border-white/30 px-6 py-3 rounded-3xl font-semibold gap-x-2 hover:bg-white/10 transition">
                                Post Another
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-500/10 border border-red-400 text-red-300 rounded-2xl p-4 mb-6 flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="cs-card p-8">
                    <?= csrf_field() ?>

                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="event_name">
                                Event / Project Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="event_name" id="event_name"
                                   value="<?= htmlspecialchars($old['event_name']) ?>"
                                   class="compact-input"
                                   placeholder="e.g. Smart India Hackathon 2026"
                                   maxlength="255" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="event_dates">
                                Event Dates / Timeline <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="event_dates" id="event_dates"
                                   value="<?= htmlspecialchars($old['event_dates']) ?>"
                                   class="compact-input"
                                   placeholder="e.g. Oct 15 - Oct 17"
                                   maxlength="150" required>
                        </div>
                    </div>
                    
                    <hr class="border-white/5 my-6">
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="lead_name">
                                Team Lead Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="lead_name" id="lead_name"
                                   value="<?= htmlspecialchars($old['lead_name'] ?: $_SESSION['name']) ?>"
                                   class="compact-input" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="lead_phone">
                                Lead Phone / WhatsApp <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="lead_phone" id="lead_phone"
                                   value="<?= htmlspecialchars($old['lead_phone']) ?>"
                                   class="compact-input" required>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="lead_year">
                                Lead Studying Year <span class="text-red-400">*</span>
                            </label>
                            <select name="lead_year" id="lead_year" class="compact-input" required>
                                <option value="" disabled <?= !$old['lead_year'] ? 'selected' : '' ?>>Select Year...</option>
                                <option value="1" <?= $old['lead_year'] == 1 ? 'selected' : '' ?>>1st Year</option>
                                <option value="2" <?= $old['lead_year'] == 2 ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3" <?= $old['lead_year'] == 3 ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4" <?= $old['lead_year'] == 4 ? 'selected' : '' ?>>4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="slots">
                                Members Needed (Slots) <span class="text-red-400">*</span>
                            </label>
                            <input type="number" name="slots" id="slots"
                                   value="<?= htmlspecialchars($old['slots']) ?>"
                                   class="compact-input"
                                   min="1" max="10" required>
                        </div>
                    </div>
                    
                    <hr class="border-white/5 my-6">

                    <div class="mb-6">
                        <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="skills_needed">
                            Skills Needed <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="skills_needed" id="skills_needed"
                               value="<?= htmlspecialchars($old['skills_needed']) ?>"
                               class="compact-input"
                               placeholder="e.g. Frontend, Next.js, Figma, Python"
                               required>
                        <p class="text-xs text-[#94A3B8] mt-2 pl-1">Separate skills with commas</p>
                    </div>

                    <div class="mb-8">
                        <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="description">
                            Team Description / Requirements <span class="text-red-400">*</span>
                        </label>
                        <textarea name="description" id="description" rows="5"
                                  class="compact-input"
                                  placeholder="Describe the project goals and what you expect from applicants."
                                  required><?= htmlspecialchars($old['description']) ?></textarea>
                    </div>

                    <button type="submit" class="cs-btn-primary w-full py-4 rounded-xl flex items-center justify-center gap-x-2">
                        <i class="fa-solid fa-users-viewfinder"></i> Post Team Request
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
