<?php
session_start();
include 'config.php';

$logged_in = isset($_SESSION['user_id']);
$current_user_id = $logged_in ? (int) $_SESSION['user_id'] : null;

// Ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS hackathons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    event_date VARCHAR(255) NOT NULL,
    posted_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS hackathon_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hackathon_id INT NOT NULL,
    team_name VARCHAR(255) NOT NULL,
    leader_name VARCHAR(255) NOT NULL,
    leader_phone VARCHAR(255) NOT NULL,
    members TEXT,
    registered_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$post_success = false;
$post_error = "";
$register_success = false;
$register_error = "";

// POST HACKATHON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_hackathon') {
    if (!$logged_in) { header("Location: login.php"); exit; }
    if (!csrf_verify()) { $post_error = 'Invalid request. Please try again.'; } else {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = trim($_POST['event_date']);

    if ($title === '' || $description === '' || $event_date === '') {
        $post_error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO hackathons (title, description, event_date, posted_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $description, $event_date, $current_user_id);
        $post_success = $stmt->execute();
        if (!$post_success)
            $post_error = "Something went wrong. Please try again.";
        $stmt->close();
    }
    } // end csrf check
}

// REGISTER TEAM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_team') {
    if (!$logged_in) { header("Location: login.php"); exit; }
    if (!csrf_verify()) { $register_error = 'Invalid request. Please try again.'; } else {

    $hackathon_id = (int) $_POST['hackathon_id'];
    $team_name = trim($_POST['team_name']);
    $leader_name = trim($_POST['leader_name']);
    $leader_phone = trim($_POST['leader_phone']);
    $members = trim($_POST['members']);

    $chk = $conn->prepare("SELECT id FROM hackathon_teams WHERE hackathon_id = ? AND registered_by = ?");
    $chk->bind_param("ii", $hackathon_id, $current_user_id);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows > 0) {
        $register_error = "You have already registered a team for this hackathon.";
    } elseif ($team_name === '' || $leader_name === '' || $leader_phone === '') {
        $register_error = "Team Name, Leader Name, and Phone are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO hackathon_teams (hackathon_id, team_name, leader_name, leader_phone, members, registered_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $hackathon_id, $team_name, $leader_name, $leader_phone, $members, $current_user_id);
        $register_success = $stmt->execute();
        if (!$register_success)
            $register_error = "Registration failed. Please try again.";
        $stmt->close();
    }
    $chk->close();
    } // end csrf check
}

$enroll_success = false;
$enroll_error = "";

// ENROLL IN A TEAM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    if (!$logged_in) {
        header("Location: login.php");
        exit;
    }

    $team_id = (int) $_POST['team_id'];
    $student_name = trim($_POST['student_name']);
    $year = (int) $_POST['year_studying'];
    $regd_no = trim($_POST['regd_no']);
    $phone = trim($_POST['phone_no']);

    $chk = $conn->prepare("SELECT id FROM event_team_enrollments WHERE team_id = ? AND user_id = ?");
    $chk->bind_param("ii", $team_id, $current_user_id);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows > 0) {
        $enroll_error = "You have already enrolled in this team.";
    } elseif ($student_name === '' || $regd_no === '' || $phone === '') {
        $enroll_error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO event_team_enrollments (team_id, user_id, student_name, year_studying, regd_no, phone_no)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisiss", $team_id, $current_user_id, $student_name, $year, $regd_no, $phone);
        $enroll_success = $stmt->execute();
        if (!$enroll_success)
            $enroll_error = "Enrollment failed. Please try again.";
        $stmt->close();
    }
    $chk->close();
}

$hackathons_result = $conn->query("
    SELECT h.*, 
           (SELECT COUNT(*) FROM hackathon_teams t WHERE t.hackathon_id = h.id) AS teams_count
    FROM hackathons h 
    ORDER BY h.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hackathons • CampusSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .logo-font { font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>

<body class="min-h-screen flex flex-col text-[#F8FAFC]">

    <?php $active_page = 'hackathons'; include 'navbar.php'; ?>

    <!-- HERO STRIP -->
    <div class="border-b border-white/5 py-12">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="text-xs font-bold tracking-widest text-[#38BDF8] uppercase">Compete & Build</span>
                <h1 class="text-4xl lg:text-5xl font-semibold tracking-tight logo-font mt-1 text-[#F8FAFC]">Hackathons</h1>
                <p class="text-[#94A3B8] mt-2 text-base lg:text-lg">Discover upcoming hackathons and register your team to showcase
                    your skills.</p>
            </div>
            <?php if ($logged_in): ?>
                <button onclick="openPostModal()"
                    class="shrink-0 cs-btn-primary px-8 py-4 flex items-center gap-x-3">
                    <i class="fa-solid fa-plus"></i> Host Hackathon
                </button>
            <?php else: ?>
                <a href="login.php"
                    class="shrink-0 cs-btn-primary px-8 py-4 flex items-center gap-x-3">
                    <i class="fa-solid fa-right-to-bracket"></i> Login to Host
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    <div class="max-w-7xl mx-auto px-6">
        <?php if ($post_success): ?>
            <div
                class="mt-6 bg-emerald-500/20 border border-emerald-400 text-emerald-300 rounded-3xl p-5 flex items-center gap-x-3">
                <i class="fa-solid fa-circle-check text-2xl"></i>
                <div>
                    <p class="font-semibold">Hackathon posted!</p>
                    <p class="text-sm">Teams can now register for your hackathon.</p>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($post_error): ?>
            <div class="mt-6 bg-red-500/20 border border-red-400 text-red-300 rounded-3xl p-5">
                <i class="fa-solid fa-circle-exclamation mr-2"></i><?= htmlspecialchars($post_error) ?>
            </div>
        <?php endif; ?>
        <?php if ($register_success): ?>
            <div
                class="mt-6 bg-emerald-500/20 border border-emerald-400 text-emerald-300 rounded-3xl p-5 flex items-center gap-x-3">
                <i class="fa-solid fa-circle-check text-2xl"></i>
                <div>
                    <p class="font-semibold">Team registered successfully!</p>
                    <p class="text-sm">Get ready to hack and build amazing things.</p>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($register_error): ?>
            <div class="mt-6 bg-red-500/20 border border-red-400 text-red-300 rounded-3xl p-5">
                <i class="fa-solid fa-circle-exclamation mr-2"></i><?= htmlspecialchars($register_error) ?>
            </div>
        <?php endif; ?>
        <?php if ($enroll_success): ?>
            <div
                class="mt-6 bg-emerald-500/20 border border-emerald-400 text-emerald-300 rounded-3xl p-5 flex items-center gap-x-3">
                <i class="fa-solid fa-circle-check text-2xl"></i>
                <div>
                    <p class="font-semibold">Enrolled successfully!</p>
                    <p class="text-sm">The team lead will be in touch with you.</p>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($enroll_error): ?>
            <div class="mt-6 bg-red-500/20 border border-red-400 text-red-300 rounded-3xl p-5">
                <i class="fa-solid fa-circle-exclamation mr-2"></i><?= htmlspecialchars($enroll_error) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- HACKATHONS LIST -->
    <div class="max-w-7xl mx-auto px-6 py-10">
        <?php if ($hackathons_result && $hackathons_result->num_rows > 0): ?>
            <p class="text-[#94A3B8] text-sm mb-8">
                <span class="text-[#F8FAFC] font-semibold"><?= $hackathons_result->num_rows ?></span> upcoming
                hackathon<?= $hackathons_result->num_rows !== 1 ? 's' : '' ?>
            </p>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while ($hackathon = $hackathons_result->fetch_assoc()): ?>
                    <div class="cs-card p-6 flex flex-col justify-between">

                        <div class="flex justify-between items-start mb-5">
                            <span
                                class="inline-flex items-center gap-x-2 bg-[#0F172A] border border-white/5 text-[#38BDF8] text-[10px] font-bold px-3 py-1 rounded-md tracking-wider uppercase">
                                <i class="fa-solid fa-code"></i> HACKATHON
                            </span>
                            <span class="text-xs font-bold text-[#94A3B8]">
                                <?= $hackathon['teams_count'] ?> Team<?= $hackathon['teams_count'] !== 1 ? 's' : '' ?>
                                Registered
                            </span>
                        </div>

                        <h3 class="text-xl font-semibold leading-snug mb-3 text-[#F8FAFC]"><?= htmlspecialchars($hackathon['title']) ?></h3>
                        <p class="text-[#CBD5E1] text-sm line-clamp-3 mb-5"><?= htmlspecialchars($hackathon['description']) ?>
                        </p>

                        <div class="flex items-center gap-x-2 text-sm text-[#94A3B8] mb-6">
                            <i class="fa-solid fa-calendar-days text-[#64748B]"></i>
                            <span><?= htmlspecialchars($hackathon['event_date']) ?></span>
                        </div>

                        <div class="mt-auto">
                            <?php if (!$logged_in): ?>
                                <a href="login.php"
                                    class="w-full block text-center cs-btn-primary py-3 font-semibold">
                                    Login to Register
                                </a>
                            <?php else: ?>
                                <button
                                    onclick="openRegisterModal(<?= $hackathon['id'] ?>, '<?= htmlspecialchars($hackathon['title'], ENT_QUOTES) ?>')"
                                    class="w-full bg-purple-500 hover:bg-purple-600 py-3.5 rounded-3xl font-semibold transition mb-3">
                                    <i class="fa-solid fa-user-group mr-2"></i>Register Team
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php
                        $h_title = $conn->real_escape_string($hackathon['title']);
                        $req_teams = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM event_team_enrollments e WHERE e.team_id = t.id) AS enrolled_count FROM event_teams t WHERE t.event_name = '$h_title'");
                        if ($req_teams && $req_teams->num_rows > 0):
                            ?>
                            <div class="mt-3 pt-4 border-t border-white/10">
                                <p class="text-[11px] text-purple-300 font-bold mb-3 tracking-wider">JOIN EXISTING TEAMS</p>
                                <div class="flex flex-col gap-2">
                                    <?php while ($rt = $req_teams->fetch_assoc()):
                                        $spots_left = max(0, $rt['slots'] - $rt['enrolled_count']);
                                        ?>
                                        <?php if ($spots_left > 0): ?>
                                            <div
                                                class="flex items-center justify-between bg-white/5 border border-white/10 p-3 rounded-2xl">
                                                <div class="flex items-center gap-x-3">
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center font-bold text-xs text-white">
                                                        <?= strtoupper(substr($rt['lead_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-slate-200">
                                                            <?= htmlspecialchars($rt['lead_name']) ?>'s Team
                                                        </div>
                                                        <div class="text-xs text-slate-400"><?= $spots_left ?> spots left</div>
                                                    </div>
                                                </div>
                                                <?php if ($logged_in && $rt['posted_by'] == $current_user_id): ?>
                                                    <span class="text-xs text-slate-500 font-medium px-3">Your Team</span>
                                                <?php else: ?>
                                                    <button
                                                        onclick="openEnrollModal(<?= $rt['id'] ?>, '<?= htmlspecialchars($rt['event_name'], ENT_QUOTES) ?>')"
                                                        class="text-xs bg-purple-500 hover:bg-purple-600 px-4 py-2 rounded-xl font-semibold transition text-white">
                                                        Apply
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="cs-card text-center py-24 text-[#94A3B8]">
                <div class="text-6xl mb-6">🚀</div>
                <p class="text-2xl font-semibold text-[#F8FAFC] mb-2">No hackathons hosted yet</p>
                <p class="mb-8">Be the first to host a hackathon and invite teams!</p>
                <?php if ($logged_in): ?>
                    <button onclick="openPostModal()" class="cs-btn-primary px-10 py-4">
                        Host Hackathon
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL 1 - HOST HACKATHON -->
    <div id="postModal" class="hidden fixed inset-0 bg-[#0B1220]/80 backdrop-blur-md items-center justify-center z-50 p-4">
        <div
            class="bg-[#0F172A] border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 bg-[#111827] border-b border-white/5 shrink-0 rounded-t-2xl">
                <div>
                    <h2 class="text-xl font-bold text-[#F8FAFC]">Host Hackathon</h2>
                    <p class="text-[#94A3B8] text-sm mt-1">Post your hackathon to invite teams</p>
                </div>
                <button onclick="closePostModal()" class="text-[#94A3B8] hover:text-[#EF4444] transition p-2">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <form method="POST" class="overflow-y-auto px-6 py-6">
                <input type="hidden" name="action" value="post_hackathon">

                <div class="mb-4">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Title <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="e.g. Campus Connect Hackathon 2026"
                        class="compact-input">
                </div>

                <div class="mb-4">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Description <span class="text-red-400">*</span>
                    </label>
                    <textarea name="description" required rows="4" placeholder="What is this hackathon about?"
                        class="compact-input"></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Event Date <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="event_date" required placeholder="e.g. October 15-16, 2026"
                        class="compact-input">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                    <button type="button" onclick="closePostModal()"
                        class="px-5 py-2 rounded-xl font-semibold text-[#94A3B8] hover:text-white hover:bg-white/10 transition">Cancel</button>
                    <button type="submit" class="cs-btn-primary">
                        Post Hackathon
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2 - REGISTER TEAM -->
    <div id="registerModal"
        class="hidden fixed inset-0 bg-[#0B1220]/80 backdrop-blur-md items-center justify-center z-50 p-4">
        <div
            class="bg-[#0F172A] border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 bg-[#111827] border-b border-white/5 shrink-0 rounded-t-2xl">
                <div>
                    <h2 class="text-xl font-bold text-[#F8FAFC]">Register Team</h2>
                    <p id="registerHackathonName" class="text-[#38BDF8] text-sm mt-1"></p>
                </div>
                <button onclick="closeRegisterModal()" class="text-[#94A3B8] hover:text-[#EF4444] transition p-2">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <form method="POST" class="overflow-y-auto px-6 py-6">
                <input type="hidden" name="action" value="register_team">
                <input type="hidden" name="hackathon_id" id="registerHackathonId">

                <div class="mb-4">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Team Name <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="team_name" required placeholder="e.g. Byte Builders"
                        class="compact-input">
                </div>

                <div class="mb-4">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Leader Name <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="leader_name" required
                        value="<?= $logged_in ? htmlspecialchars($_SESSION['name']) : '' ?>"
                        class="compact-input">
                </div>

                <div class="mb-4">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Leader Phone <span class="text-red-400">*</span>
                    </label>
                    <input type="tel" name="leader_phone" required placeholder="e.g. 9876543210"
                        class="compact-input">
                </div>

                <div class="mb-6">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Team Members (Optional)
                    </label>
                    <textarea name="members" rows="3" placeholder="Comma separated member names"
                        class="compact-input"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                    <button type="button" onclick="closeRegisterModal()"
                        class="px-5 py-2 rounded-xl font-semibold text-[#94A3B8] hover:text-white hover:bg-white/10 transition">Cancel</button>
                    <button type="submit" class="cs-btn-primary">
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3 - ENROLL IN A TEAM -->
    <div id="enrollModal"
        class="hidden fixed inset-0 bg-[#0B1220]/80 backdrop-blur-md items-center justify-center z-50 p-4">
        <div
            class="bg-[#0F172A] border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 bg-[#111827] border-b border-white/5 shrink-0 rounded-t-2xl">
                <div>
                    <h2 class="text-xl font-bold text-[#F8FAFC]">Apply to Team</h2>
                    <p id="enrollEventName" class="text-[#38BDF8] text-sm mt-1"></p>
                </div>
                <button onclick="closeEnrollModal()" class="text-[#94A3B8] hover:text-[#EF4444] transition p-2">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <form method="POST" class="overflow-y-auto px-6 py-6">
                <input type="hidden" name="action" value="enroll">
                <input type="hidden" name="team_id" id="enrollTeamId">

                <div class="mb-4">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Full Name <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="student_name" required
                        value="<?= $logged_in ? htmlspecialchars($_SESSION['name']) : '' ?>"
                        class="compact-input">
                </div>

                <div class="mb-4">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Year Studying <span class="text-red-400">*</span>
                    </label>
                    <select name="year_studying" required class="compact-input cursor-pointer">
                        <option value="">Select your year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Registration Number <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="regd_no" required placeholder="e.g. 22BCE1234"
                        class="compact-input">
                </div>

                <div class="mb-6">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Phone Number <span class="text-red-400">*</span>
                    </label>
                    <input type="tel" name="phone_no" required placeholder="e.g. 9876543210"
                        class="compact-input">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-white/5">
                    <button type="button" onclick="closeEnrollModal()"
                        class="px-5 py-2 rounded-xl font-semibold text-[#94A3B8] hover:text-white hover:bg-white/10 transition">Cancel</button>
                    <button type="submit" class="cs-btn-primary">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal(id) {
            const el = document.getElementById(id);
            el.classList.remove('hidden');
            el.style.display = 'flex';
        }
        function hideModal(id) {
            const el = document.getElementById(id);
            el.classList.add('hidden');
            el.style.display = 'none';
        }

        function openPostModal() { showModal('postModal'); }
        function closePostModal() { hideModal('postModal'); }

        function openRegisterModal(id, title) {
            document.getElementById('registerHackathonId').value = id;
            document.getElementById('registerHackathonName').textContent = title;
            showModal('registerModal');
        }
        function closeRegisterModal() { hideModal('registerModal'); }

        function openEnrollModal(teamId, eventName) {
            document.getElementById('enrollTeamId').value = teamId;
            document.getElementById('enrollEventName').textContent = eventName;
            showModal('enrollModal');
        }
        function closeEnrollModal() { hideModal('enrollModal'); }

        ['postModal', 'registerModal', 'enrollModal'].forEach(id => {
            document.getElementById(id).addEventListener('click', e => {
                if (e.target.id === id) hideModal(id);
            });
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') { closePostModal(); closeRegisterModal(); closeEnrollModal(); }
        });
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>