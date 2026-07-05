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
$old = ['title' => '', 'description' => '', 'skills' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $skills      = trim($_POST['skills']       ?? '');
        $posted_by   = (int) $_SESSION['user_id'];
        $old         = compact('title', 'description', 'skills');

        if ($title === '' || $description === '' || $skills === '') {
            $error = 'All fields are required.';
        } else {
            // ✅ Prepared statement — no SQL injection
            $stmt = $conn->prepare(
                "INSERT INTO ideas (title, description, skills_needed, posted_by)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("sssi", $title, $description, $skills, $posted_by);
            if ($stmt->execute()) {
                $success = true;
                $old = ['title' => '', 'description' => '', 'skills' => '']; // clear on success
            } else {
                error_log("Post idea DB error: " . $conn->error);
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
    <title>Post New Idea • CampusSync</title>
    <meta name="description" content="Post a new idea on CampusSync and find skilled teammates to build it with you.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .logo-font { font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex flex-col text-[#F8FAFC]">

    <?php $active_page = 'post-idea'; include 'navbar.php'; ?>

    <div class="max-w-5xl mx-auto px-6 py-12">
        <div class="grid md:grid-cols-12 gap-12 items-start">

            <!-- Left: Form -->
            <div class="md:col-span-7">
                <div class="mb-8">
                    <span class="text-xs font-bold tracking-widest text-[#38BDF8] uppercase">Share Your Vision</span>
                    <h1 class="logo-font text-4xl lg:text-5xl font-semibold tracking-tight mt-2 text-[#F8FAFC]">Post Your Idea</h1>
                    <p class="text-[#94A3B8] mt-3 text-lg">Turn your idea into a real campus project</p>
                </div>

                <?php if ($success): ?>
                    <div class="bg-emerald-500/10 border border-emerald-400 text-emerald-300 rounded-3xl p-8 text-center mb-8">
                        <i class="fa-solid fa-rocket text-5xl mb-4 block"></i>
                        <h3 class="text-2xl font-semibold">Idea Posted Successfully!</h3>
                        <p class="mt-2 text-slate-300">Your idea is now visible to all students and faculty.</p>
                        <div class="flex justify-center gap-4 mt-6">
                            <a href="ideas.php" class="inline-flex items-center bg-white text-slate-900 px-6 py-3 rounded-3xl font-semibold gap-x-2 hover:bg-cyan-100 transition">
                                View All Ideas <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <a href="post-idea.php" class="inline-flex items-center border border-white/30 px-6 py-3 rounded-3xl font-semibold gap-x-2 hover:bg-white/10 transition">
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

                <form method="POST" id="ideaForm" class="cs-card p-8">
                    <?= csrf_field() ?>

                    <div class="mb-6">
                        <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="idea-title">
                            Idea Title <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="title" id="idea-title"
                               value="<?= htmlspecialchars($old['title']) ?>"
                               class="compact-input"
                               placeholder="e.g. Smart Waste Management IoT System"
                               maxlength="255" required>
                        <p class="text-xs text-[#94A3B8] mt-2 pl-1"><span id="titleCount">0</span>/255</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="idea-desc">
                            Description <span class="text-red-400">*</span>
                        </label>
                        <textarea name="description" id="idea-desc" rows="6"
                                  class="compact-input"
                                  placeholder="What problem are you solving? How will it work? What's your approach?"
                                  required><?= htmlspecialchars($old['description']) ?></textarea>
                    </div>

                    <div class="mb-8">
                        <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="idea-skills">
                            Skills / Tech Stack Needed <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="skills" id="idea-skills"
                               value="<?= htmlspecialchars($old['skills']) ?>"
                               class="compact-input"
                               placeholder="Python, IoT, React, Arduino, Machine Learning"
                               required>
                        <p class="text-xs text-[#94A3B8] mt-2 pl-1">Separate skills with commas</p>
                    </div>

                    <button type="submit" id="ideaBtn"
                            class="cs-btn-primary w-full py-4 rounded-xl flex items-center justify-center gap-x-2">
                        <i id="ideaBtnIcon" class="fa-solid fa-paper-plane"></i>
                        <span id="ideaBtnText">Post Idea to Campus</span>
                    </button>
                </form>
            </div>

            <!-- Right: Live Preview -->
            <div class="md:col-span-5 mt-10 md:mt-0">
                <div class="sticky top-24">

                    <!-- Live card preview -->
                    <div class="cs-card p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 text-[10px] font-bold bg-[#0F172A] border border-white/5 text-[#34d399] rounded-md tracking-wider">OPEN</span>
                            <span class="text-xs text-[#94A3B8]">just now</span>
                        </div>
                        <h3 id="preview-title" class="text-lg font-semibold mb-2 text-[#F8FAFC] italic">Your idea title will appear here…</h3>
                        <p id="preview-desc" class="text-[#CBD5E1] text-sm line-clamp-3 mb-4">Your description will appear here…</p>
                        <div>
                            <p class="text-[10px] text-[#94A3B8] uppercase tracking-wider font-bold mb-2">Skills Needed</p>
                            <div id="preview-skills" class="flex flex-wrap gap-2">
                                <span class="text-xs bg-[#0F172A] border border-white/5 text-[#CBD5E1] px-3 py-1 rounded-md">skills appear here</span>
                            </div>
                        </div>
                        <div class="mt-5 flex justify-between items-center pt-4 border-t border-white/5">
                            <p class="text-xs text-[#94A3B8]">
                                Posted by <span class="font-medium text-[#F8FAFC]"><?= htmlspecialchars($_SESSION['name']) ?></span>
                            </p>
                            <span class="cs-btn-primary text-xs px-4 py-2">Quick Apply</span>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="cs-card-secondary p-6">
                        <h4 class="text-sm font-bold mb-4 flex items-center gap-2 text-[#F8FAFC]">
                            <i class="fa-solid fa-circle-info text-[#38BDF8]"></i> Tips for a great idea post
                        </h4>
                        <ul class="space-y-3 text-xs text-[#CBD5E1]">
                            <li class="flex items-start gap-2"><i class="fa-solid fa-check mt-0.5 text-[#34d399]"></i> Faculty will see your idea immediately</li>
                            <li class="flex items-start gap-2"><i class="fa-solid fa-check mt-0.5 text-[#34d399]"></i> Be specific about the tech stack needed</li>
                            <li class="flex items-start gap-2"><i class="fa-solid fa-check mt-0.5 text-[#34d399]"></i> Students can apply with their skill profiles</li>
                            <li class="flex items-start gap-2"><i class="fa-solid fa-check mt-0.5 text-[#34d399]"></i> You can form a team right from the ideas feed</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // ── Live preview ──────────────────────────────────────────────────
        const titleInput  = document.getElementById('idea-title');
        const descInput   = document.getElementById('idea-desc');
        const skillsInput = document.getElementById('idea-skills');
        const preTitle    = document.getElementById('preview-title');
        const preDesc     = document.getElementById('preview-desc');
        const preSkills   = document.getElementById('preview-skills');
        const titleCount  = document.getElementById('titleCount');

        titleInput.addEventListener('input', function () {
            preTitle.textContent    = this.value || 'Your idea title will appear here…';
            preTitle.style.color    = this.value ? '#fff' : '';
            preTitle.style.fontStyle = this.value ? 'normal' : 'italic';
            titleCount.textContent   = this.value.length;
        });

        descInput.addEventListener('input', function () {
            preDesc.textContent    = this.value || 'Your description will appear here…';
            preDesc.style.color    = this.value ? '#cbd5e1' : '';
        });

        skillsInput.addEventListener('input', function () {
            const parts = this.value.split(',').map(s => s.trim()).filter(Boolean);
            preSkills.innerHTML = parts.length
                ? parts.map(s => `<span class="text-xs bg-[#0F172A] border border-white/5 text-[#CBD5E1] px-3 py-1 rounded-md">${s}</span>`).join('')
                : '<span class="text-xs bg-[#0F172A] border border-white/5 text-[#CBD5E1] px-3 py-1 rounded-md">skills appear here</span>';
        });

        // ── Submit loading state ──────────────────────────────────────────
        document.getElementById('ideaForm').addEventListener('submit', function () {
            if (!this.checkValidity()) return;
            const btn  = document.getElementById('ideaBtn');
            const text = document.getElementById('ideaBtnText');
            const icon = document.getElementById('ideaBtnIcon');
            btn.disabled     = true;
            btn.classList.add('opacity-75');
            text.textContent = 'Posting…';
            icon.className   = 'fa-solid fa-spinner fa-spin';
        });

        // Init preview from preserved form values (after error)
        if (titleInput.value)  titleInput.dispatchEvent(new Event('input'));
        if (descInput.value)   descInput.dispatchEvent(new Event('input'));
        if (skillsInput.value) skillsInput.dispatchEvent(new Event('input'));
    </script>
</body>
</html>