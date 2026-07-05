<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    $logged_in       = false;
    $current_user_id = 0;
} else {
    $logged_in       = true;
    $current_user_id = (int) $_SESSION['user_id'];
}

$apply_success         = false;
$apply_success_message = '';
$apply_error           = '';
$delete_message        = '';

// ── DELETE IDEA ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_idea'])) {
    if (!$logged_in)     { header("Location: login.php"); exit; }
    if (!csrf_verify())  { $apply_error = 'Invalid request. Please try again.'; }
    else {
        $idea_id = (int) ($_POST['idea_id'] ?? 0);
        // Only the owner may delete
        $del = $conn->prepare("DELETE FROM ideas WHERE id = ? AND posted_by = ?");
        $del->bind_param("ii", $idea_id, $current_user_id);
        $del->execute();
        if ($del->affected_rows > 0) {
            $delete_message = 'Your idea has been deleted.';
        } else {
            $apply_error = 'Could not delete that idea.';
        }
        $del->close();
    }
}

// ── UPDATE IDEA STATUS ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!$logged_in)    { header("Location: login.php"); exit; }
    if (!csrf_verify()) { $apply_error = 'Invalid request.'; }
    else {
        $idea_id    = (int) ($_POST['idea_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';
        if (in_array($new_status, ['open','in_progress','completed'], true)) {
            $upd = $conn->prepare("UPDATE ideas SET status = ? WHERE id = ? AND posted_by = ?");
            $upd->bind_param("sii", $new_status, $idea_id, $current_user_id);
            $upd->execute();
            $upd->close();
        }
    }
}

// ── APPLY TO IDEA ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    if (!$logged_in) { header("Location: login.php"); exit; }
    if (!csrf_verify()) {
        $apply_error = 'Invalid request. Please try again.';
    } else {
        $idea_id  = (int)  ($_POST['idea_id']  ?? 0);
        $name     = trim($_POST['name']     ?? '');
        $regd_no  = trim($_POST['regd_no']  ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        $role     = trim($_POST['role']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $prev_work = trim($_POST['prev_work'] ?? '');

        if ($idea_id <= 0 || $name==='' || $regd_no==='' || $phone==='' || $role==='' || $email==='') {
            $apply_error = 'Please fill all required application details.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $apply_error = 'Please enter a valid email address.';
        } else {
            $idea_stmt = $conn->prepare(
                "SELECT i.id, i.title, i.posted_by, u.name AS posted_by_name
                 FROM ideas i LEFT JOIN users u ON i.posted_by = u.id
                 WHERE i.id = ? LIMIT 1"
            );
            $idea_stmt->bind_param("i", $idea_id);
            $idea_stmt->execute();
            $idea = $idea_stmt->get_result()->fetch_assoc();
            $idea_stmt->close();

            if (!$idea) {
                $apply_error = 'This idea is no longer available.';
            } elseif ((int) $idea['posted_by'] === $current_user_id) {
                $apply_error = 'You cannot apply to your own idea.';
            } else {
                $existing_stmt = $conn->prepare(
                    "SELECT status FROM idea_applications
                     WHERE idea_id=? AND applicant_id=?
                     ORDER BY created_at DESC, id DESC LIMIT 1"
                );
                $existing_stmt->bind_param("ii", $idea_id, $current_user_id);
                $existing_stmt->execute();
                $existing = $existing_stmt->get_result()->fetch_assoc();
                $existing_stmt->close();

                if ($existing) {
                    $apply_success = true;
                    $apply_success_message = match($existing['status']) {
                        'pending'  => 'You have already applied. Waiting for joining.',
                        'accepted' => 'Your request has already been accepted!',
                        default    => 'Your previous request was rejected by the idea owner.',
                    };
                } else {
                    $message = "Name: $name | Regd: $regd_no | Phone: $phone | Email: $email | Role: $role | Prev: $prev_work";
                    $apply_stmt = $conn->prepare(
                        "INSERT INTO idea_applications (idea_id, applicant_id, message, status)
                         VALUES (?, ?, ?, 'pending')"
                    );
                    $apply_stmt->bind_param("iis", $idea_id, $current_user_id, $message);
                    if ($apply_stmt->execute()) {
                        $apply_success         = true;
                        $owner_name            = $idea['posted_by_name'] ?: 'the idea owner';
                        $apply_success_message = "Request sent to $owner_name. Waiting for joining.";
                    } else {
                        error_log("Apply error: " . $conn->error);
                        $apply_error = 'Could not submit your request. Please try again.';
                    }
                    $apply_stmt->close();
                }
            }
        }
    }
}

// ── FETCH IDEAS ───────────────────────────────────────────────────────────
$ideas = [];
if ($logged_in) {
    $ideas_stmt = $conn->prepare("
        SELECT i.*, u.name AS posted_by_name, u.phone_no AS owner_phone,
               ia.status AS my_application_status
        FROM ideas i
        LEFT JOIN users u ON i.posted_by = u.id
        LEFT JOIN (
            SELECT idea_id, applicant_id, MAX(id) AS latest_id
            FROM idea_applications WHERE applicant_id=?
            GROUP BY idea_id, applicant_id
        ) my_app ON my_app.idea_id = i.id
        LEFT JOIN idea_applications ia ON ia.id = my_app.latest_id
        ORDER BY i.created_at DESC
    ");
    $ideas_stmt->bind_param("i", $current_user_id);
    $ideas_stmt->execute();
    $result = $ideas_stmt->get_result();
    while ($row = $result->fetch_assoc()) $ideas[] = $row;
    $ideas_stmt->close();
} else {
    $result = $conn->query("
        SELECT i.*, u.name AS posted_by_name, u.phone_no AS owner_phone,
               NULL AS my_application_status
        FROM ideas i LEFT JOIN users u ON i.posted_by = u.id
        ORDER BY i.created_at DESC
    ");
    if ($result) while ($row = $result->fetch_assoc()) $ideas[] = $row;
}

// Collect all unique skills for filter pills
$all_skills = [];
foreach ($ideas as $idea) {
    foreach (explode(',', $idea['skills_needed'] ?? '') as $s) {
        $s = trim($s);
        if ($s) $all_skills[$s] = true;
    }
}
ksort($all_skills);
$all_skills = array_keys($all_skills);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Ideas • CampusSync</title>
    <meta name="description" content="Browse all open project ideas on CampusSync and find one that matches your skills.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .logo-font { font-family: 'Space Grotesk', sans-serif; }
        .skill-filter-pill {
            cursor: pointer;
            transition: background .15s, color .15s;
            user-select: none;
        }
        .skill-filter-pill.active {
            background: rgba(56,189,248,.15);
            border-color: rgba(56,189,248,.4);
            color: #38BDF8;
        }
        .status-badge { font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 99px; }
        .status-open        { background: rgba(16,185,129,.15); color: #34d399; border: 1px solid rgba(52,211,153,.3); }
        .status-in_progress { background: rgba(245,158,11,.15); color: #fbbf24; border: 1px solid rgba(251,191,36,.3); }
        .status-completed   { background: rgba(99,102,241,.15); color: #a5b4fc; border: 1px solid rgba(165,180,252,.3); }
    </style>
</head>
<body class="min-h-screen flex flex-col text-[#F8FAFC]">

    <?php $active_page = 'ideas'; include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-6 py-12">

        <!-- Header -->
        <div class="flex flex-wrap justify-between items-end mb-10 gap-4">
            <div>
                <span class="text-xs font-bold tracking-widest text-[#38BDF8] uppercase">Opportunities</span>
                <h1 class="logo-font text-4xl lg:text-5xl font-semibold tracking-tight mt-1 text-[#F8FAFC]">Browse Ideas</h1>
                <p class="text-[#94A3B8] mt-2 text-base lg:text-lg">Find projects that match your skills</p>
            </div>
            <a href="post-idea.php" class="cs-btn-primary flex items-center gap-x-2">
                <i class="fa-solid fa-plus"></i> Post New Idea
            </a>
        </div>

        <!-- Flash messages -->
        <?php if ($apply_success): ?>
            <div class="bg-emerald-500/20 border border-emerald-400 text-emerald-300 rounded-3xl p-5 mb-8 flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-xl shrink-0"></i>
                <?= htmlspecialchars($apply_success_message ?: 'Request sent. Waiting for joining.') ?>
            </div>
        <?php endif; ?>
        <?php if ($delete_message): ?>
            <div class="bg-slate-700/50 border border-slate-500 text-slate-200 rounded-3xl p-5 mb-8 flex items-center gap-3">
                <i class="fa-solid fa-trash-can shrink-0"></i>
                <?= htmlspecialchars($delete_message) ?>
            </div>
        <?php endif; ?>
        <?php if ($apply_error): ?>
            <div class="bg-red-500/20 border border-red-400 text-red-300 rounded-3xl p-5 mb-8 flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <?= htmlspecialchars($apply_error) ?>
            </div>
        <?php endif; ?>

        <!-- Search + Skill Filters -->
        <div class="mb-8 space-y-4">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-[#94A3B8]"></i>
                <input type="text" id="idea-search"
                       placeholder="Search by title or skill…"
                       class="w-full bg-[#0F172A] border border-white/10 rounded-2xl pl-12 pr-6 py-4 text-[#F8FAFC] text-sm focus:outline-none focus:border-[#38BDF8] transition shadow-inner"
                       autocomplete="off">
            </div>
            <?php if ($all_skills): ?>
            <div class="flex flex-wrap gap-2" id="skill-filters">
                <button class="skill-filter-pill text-xs border border-white/10 text-[#94A3B8] px-4 py-2 rounded-full active" data-skill="__all__">
                    All Skills
                </button>
                <?php foreach ($all_skills as $sk): ?>
                <button class="skill-filter-pill text-xs border border-white/10 text-[#94A3B8] px-4 py-2 rounded-full hover:bg-white/5" data-skill="<?= htmlspecialchars($sk) ?>">
                    <?= htmlspecialchars($sk) ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <p class="text-sm text-[#94A3B8]" id="result-count">
                <?= count($ideas) ?> idea<?= count($ideas) !== 1 ? 's' : '' ?> available
            </p>
        </div>

        <!-- Ideas Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" id="ideas-grid">
            <?php if (!empty($ideas)): ?>
                <?php foreach ($ideas as $i => $idea): ?>
                    <?php
                        $is_owner          = $logged_in && (int)$idea['posted_by'] === $current_user_id;
                        $app_status        = $idea['my_application_status'] ?? '';
                        $skill_list        = array_map('trim', explode(',', $idea['skills_needed'] ?? ''));
                        $status            = $idea['status'] ?? 'open';
                        $status_label      = ['open'=>'Open','in_progress'=>'In Progress','completed'=>'Completed'][$status] ?? 'Open';
                    ?>
                    <div class="idea-card cs-card p-6 flex flex-col"
                         data-title="<?= htmlspecialchars(strtolower($idea['title'])) ?>"
                         data-skills="<?= htmlspecialchars(strtolower($idea['skills_needed'] ?? '')) ?>"
                         data-index="<?= $i ?>">

                        <div class="flex justify-between items-start mb-4">
                            <span class="status-badge status-<?= $status ?>"><?= $status_label ?></span>
                            <span class="text-xs text-[#94A3B8]"><?= date('M d', strtotime($idea['created_at'])) ?></span>
                        </div>

                        <h3 class="text-lg font-semibold mb-2 leading-snug text-[#F8FAFC]"><?= htmlspecialchars($idea['title']) ?></h3>
                        <p class="text-[#CBD5E1] text-sm line-clamp-3 mb-4 flex-1"><?= htmlspecialchars($idea['description']) ?></p>

                        <div class="mb-5">
                            <p class="text-[10px] text-[#94A3B8] uppercase tracking-wider font-bold mb-2">Skills Needed</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($skill_list as $sk): ?>
                                    <span class="text-xs bg-[#0F172A] border border-white/5 text-[#CBD5E1] px-3 py-1 rounded-md"><?= htmlspecialchars($sk) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-auto pt-4 border-t border-white/5 flex items-center justify-between gap-3">
                            <p class="text-xs text-[#94A3B8]">
                                by <span class="text-[#F8FAFC] font-medium"><?= htmlspecialchars($idea['posted_by_name'] ?? 'Anonymous') ?></span>
                            </p>

                            <?php if ($is_owner): ?>
                                <div class="flex items-center gap-2">
                                    <!-- Status update dropdown -->
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="idea_id" value="<?= (int)$idea['id'] ?>">
                                        <select name="new_status" onchange="this.form.submit()"
                                                class="compact-input text-xs cursor-pointer px-3 py-1.5 w-auto inline-block">
                                            <option value="open"        <?= $status==='open'        ? 'selected':'' ?>>Open</option>
                                            <option value="in_progress" <?= $status==='in_progress' ? 'selected':'' ?>>In Progress</option>
                                            <option value="completed"   <?= $status==='completed'   ? 'selected':'' ?>>Completed</option>
                                        </select>
                                    </form>
                                    <!-- Delete button -->
                                    <form method="POST" onsubmit="return confirm('Delete this idea? This cannot be undone.')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delete_idea" value="1">
                                        <input type="hidden" name="idea_id" value="<?= (int)$idea['id'] ?>">
                                        <button type="submit" title="Delete idea"
                                                class="text-slate-500 hover:text-red-400 transition p-1.5 rounded-xl hover:bg-red-400/10">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                </div>

                            <?php elseif ($app_status === 'pending'): ?>
                                <span class="bg-amber-400/15 border border-amber-300/40 px-4 py-2 rounded-3xl text-xs font-semibold text-amber-200">
                                    Waiting for joining
                                </span>

                            <?php elseif ($app_status === 'accepted'): ?>
                                <div class="flex items-center gap-2">
                                    <span class="bg-emerald-400/15 border border-emerald-300/40 px-4 py-2 rounded-3xl text-xs font-semibold text-emerald-200">Joined</span>
                                    <?php if (!empty($idea['owner_phone'])): ?>
                                        <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/','',$idea['owner_phone'])) ?>"
                                           target="_blank" class="bg-green-500 hover:bg-green-600 px-3 py-2 rounded-2xl text-xs font-semibold transition flex items-center gap-1 text-white">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($app_status === 'rejected'): ?>
                                <span class="bg-rose-400/15 border border-rose-300/40 px-4 py-2 rounded-3xl text-xs font-semibold text-rose-200">Rejected</span>

                            <?php else: ?>
                                <button type="button"
                                        onclick="openApplyModal(<?= (int)$idea['id'] ?>, <?= htmlspecialchars(json_encode($idea['title'], JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)"
                                        class="cs-btn-primary text-xs px-5 py-2">
                                    Quick Apply
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Empty state (hidden by default, shown by JS) -->
            <div id="no-results" class="col-span-3 hidden text-center py-24 text-[#94A3B8]">
                <div class="text-6xl mb-4">🔍</div>
                <p class="text-2xl font-semibold text-[#F8FAFC] mb-2">No ideas found</p>
                <p class="mb-6">Try a different search term or skill filter.</p>
                <button onclick="resetFilters()" class="cs-btn-primary px-8 py-3">
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Original empty state (no ideas at all) -->
        <?php if (empty($ideas)): ?>
            <div class="text-center py-24 text-slate-400">
                <div class="text-7xl mb-6">💡</div>
                <p class="text-2xl font-semibold text-white mb-2">No ideas posted yet</p>
                <p class="mb-8">Be the first to post an idea and find a team!</p>
                <a href="post-idea.php" class="bg-cyan-500 hover:bg-cyan-600 px-10 py-4 rounded-3xl font-semibold text-lg text-white transition">
                    Post Your First Idea
                </a>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <div id="pagination" class="flex justify-center items-center gap-3 mt-12 flex-wrap hidden"></div>
    </div>

    <!-- Apply Modal -->
    <div id="applyModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm items-center justify-center z-50 p-4">
        <div class="bg-slate-900 border border-white/10 rounded-3xl w-full max-w-lg shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-8 pt-8 pb-4 shrink-0">
                <div>
                    <h2 class="text-2xl font-semibold" id="modalIdeaTitle"></h2>
                    <p class="text-slate-400 text-sm mt-1">Fill your details to apply</p>
                </div>
                <button onclick="closeApplyModal()" class="text-slate-400 hover:text-white text-2xl leading-none">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" id="applyForm" class="overflow-y-auto px-8 pb-8 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="apply" value="1">
                <input type="hidden" name="idea_id" id="modalIdeaId">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-300 mb-2">Full Name *</label>
                        <input type="text" name="name" required
                               value="<?= $logged_in ? htmlspecialchars($_SESSION['name']) : '' ?>"
                               class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-3 text-white text-sm focus:outline-none focus:border-cyan-400">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300 mb-2">Registration No *</label>
                        <input type="text" name="regd_no" required
                               class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-3 text-white text-sm focus:outline-none focus:border-cyan-400">
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-2">Phone Number *</label>
                    <input type="text" name="phone" required
                           class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-3 text-white text-sm focus:outline-none focus:border-cyan-400">
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-2">Role you want to take *</label>
                    <input type="text" name="role" required placeholder="e.g. Developer, Designer, Tester"
                           class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-3 text-white text-sm focus:outline-none focus:border-cyan-400">
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-2">Email *</label>
                    <input type="email" name="email" required
                           value="<?= $logged_in && isset($user_email) ? htmlspecialchars($user_email) : '' ?>"
                           class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-3 text-white text-sm focus:outline-none focus:border-cyan-400">
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-2">Previous Works <span class="text-slate-500">(optional)</span></label>
                    <textarea name="prev_work" rows="3"
                              class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-3 text-white text-sm focus:outline-none focus:border-cyan-400 resize-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeApplyModal()"
                            class="flex-1 py-3 border border-white/20 rounded-3xl text-sm font-medium hover:bg-white/10 transition">Cancel</button>
                    <button type="submit" id="applyBtn"
                            class="flex-1 bg-cyan-500 hover:bg-cyan-400 py-3 rounded-3xl text-sm font-semibold transition flex items-center justify-center gap-2">
                        <span id="applyBtnText">Submit Application</span>
                        <i id="applyBtnIcon" class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // ── Apply modal ───────────────────────────────────────────────────
        function openApplyModal(ideaId, title) {
            document.getElementById('modalIdeaId').value    = ideaId;
            document.getElementById('modalIdeaTitle').textContent = title;
            const m = document.getElementById('applyModal');
            m.classList.remove('hidden');
            m.style.display = 'flex';
        }
        function closeApplyModal() {
            const m = document.getElementById('applyModal');
            m.classList.add('hidden');
            m.style.display = 'none';
        }
        document.getElementById('applyModal').addEventListener('click', e => {
            if (e.target.id === 'applyModal') closeApplyModal();
        });
        document.getElementById('applyForm').addEventListener('submit', function () {
            if (!this.checkValidity()) return;
            const btn  = document.getElementById('applyBtn');
            const text = document.getElementById('applyBtnText');
            const icon = document.getElementById('applyBtnIcon');
            btn.disabled     = true;
            text.textContent = 'Submitting…';
            icon.className   = 'fa-solid fa-spinner fa-spin';
        });

        // ── Search + filter + pagination ──────────────────────────────────
        const PER_PAGE   = 12;
        let currentPage  = 1;
        let activeSkill  = '__all__';
        let searchTerm   = '';

        const cards      = Array.from(document.querySelectorAll('.idea-card'));
        const grid       = document.getElementById('ideas-grid');
        const noResults  = document.getElementById('no-results');
        const pagination = document.getElementById('pagination');
        const countEl    = document.getElementById('result-count');

        function getFiltered() {
            return cards.filter(card => {
                const title  = card.dataset.title  || '';
                const skills = card.dataset.skills || '';
                const matchSearch = !searchTerm || title.includes(searchTerm) || skills.includes(searchTerm);
                const matchSkill  = activeSkill === '__all__' || skills.includes(activeSkill.toLowerCase());
                return matchSearch && matchSkill;
            });
        }

        function render() {
            const filtered = getFiltered();
            const total    = filtered.length;
            const pages    = Math.max(1, Math.ceil(total / PER_PAGE));
            if (currentPage > pages) currentPage = 1;
            const start = (currentPage - 1) * PER_PAGE;
            const end   = start + PER_PAGE;

            cards.forEach(c => c.classList.add('hidden'));
            filtered.slice(start, end).forEach(c => c.classList.remove('hidden'));

            noResults.classList.toggle('hidden', total > 0);
            countEl.textContent = `${total} idea${total !== 1 ? 's' : ''} ${searchTerm || activeSkill !== '__all__' ? 'found' : 'available'}`;

            // Pagination buttons
            pagination.innerHTML = '';
            if (pages > 1) {
                pagination.classList.remove('hidden');
                const mkBtn = (label, page, active) => {
                    const b = document.createElement('button');
                    b.textContent = label;
                    b.className   = `px-4 py-2 rounded-2xl text-sm font-semibold transition ${
                        active
                        ? 'bg-cyan-500 text-slate-950 shadow-lg shadow-cyan-500/30'
                        : 'bg-white/10 text-slate-300 hover:bg-white/20'
                    }`;
                    b.disabled = active;
                    b.addEventListener('click', () => { currentPage = page; render(); window.scrollTo({top:0,behavior:'smooth'}); });
                    return b;
                };
                if (currentPage > 1)  pagination.appendChild(mkBtn('← Prev', currentPage - 1, false));
                for (let p = 1; p <= pages; p++) pagination.appendChild(mkBtn(p, p, p === currentPage));
                if (currentPage < pages) pagination.appendChild(mkBtn('Next →', currentPage + 1, false));
            } else {
                pagination.classList.add('hidden');
            }
        }

        document.getElementById('idea-search').addEventListener('input', function () {
            searchTerm  = this.value.trim().toLowerCase();
            currentPage = 1;
            render();
        });

        document.querySelectorAll('.skill-filter-pill').forEach(pill => {
            pill.addEventListener('click', function () {
                document.querySelectorAll('.skill-filter-pill').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                activeSkill = this.dataset.skill;
                currentPage = 1;
                render();
            });
        });

        function resetFilters() {
            document.getElementById('idea-search').value = '';
            searchTerm  = '';
            activeSkill = '__all__';
            currentPage = 1;
            document.querySelectorAll('.skill-filter-pill').forEach(p => p.classList.remove('active'));
            document.querySelector('[data-skill="__all__"]')?.classList.add('active');
            render();
        }

        // Initial render
        render();
    </script>
</body>
</html>
