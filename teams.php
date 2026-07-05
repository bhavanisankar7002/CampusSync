<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$enroll_success = false;
$enroll_error = "";

// ENROLL IN A TEAM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    $team_id = (int) $_POST['team_id'];
    $student_name = trim($_POST['student_name']);
    $year = (int) $_POST['year_studying'];
    $regd_no = trim($_POST['regd_no']);
    $phone = trim($_POST['phone_no']);

    $chk = $conn->prepare("SELECT id FROM event_team_enrollments WHERE team_id = ? AND user_id = ?");
    $chk->bind_param("ii", $team_id, $user_id);
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
        $stmt->bind_param("iisiss", $team_id, $user_id, $student_name, $year, $regd_no, $phone);
        $enroll_success = $stmt->execute();
        if (!$enroll_success)
            $enroll_error = "Enrollment failed. Please try again.";
        $stmt->close();
    }
    $chk->close();
}

$sql = "SELECT event_teams.*, users.name AS lead_name_from_user,
        (SELECT COUNT(*) FROM event_team_enrollments e WHERE e.team_id = event_teams.id AND e.user_id = $user_id) AS is_enrolled
        FROM event_teams
        LEFT JOIN users ON event_teams.posted_by = users.id
        ORDER BY event_teams.created_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams • CampusSync</title>
    <meta name="description" content="Find and join campus teams for hackathons, projects, and events on CampusSync.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .logo-font { font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>

<body class="min-h-screen flex flex-col text-[#F8FAFC]">

    <?php $active_page = 'teams'; include 'navbar.php'; ?>

    <!-- Heading -->
    <section class="max-w-7xl mx-auto px-6 py-10">
        <div class="text-center mb-10">
            <span class="text-xs font-bold tracking-widest text-[#38BDF8] uppercase">Find Your Crew</span>
            <h1 class="logo-font text-4xl lg:text-5xl font-semibold tracking-tight mt-2 text-[#F8FAFC]">Teams</h1>
            <p class="text-[#94A3B8] mt-2 text-lg">Explore team requests and apply to join events</p>
            <div class="mt-6">
                <a href="post-team.php" class="cs-btn-primary inline-flex items-center gap-2 px-6 py-2.5"><i class="fa-solid fa-plus"></i> Post Team Request</a>
            </div>
        </div>

        <?php if ($enroll_success): ?>
            <div
                class="mt-6 mb-8 bg-emerald-500/20 border border-emerald-400 text-emerald-300 rounded-3xl p-5 flex items-center gap-x-3">
                <i class="fa-solid fa-circle-check text-2xl"></i>
                <div>
                    <p class="font-semibold">Enrolled successfully!</p>
                    <p class="text-sm">You can now connect with the team lead.</p>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($enroll_error): ?>
            <div class="mt-6 mb-8 bg-red-500/20 border border-red-400 text-red-300 rounded-3xl p-5">
                <i class="fa-solid fa-circle-exclamation mr-2"></i><?= htmlspecialchars($enroll_error) ?>
            </div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="cs-card p-6 flex flex-col justify-between">

                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-[#F8FAFC]">
                                <?= htmlspecialchars($row['event_name']) ?>
                            </h3>
                            <p class="text-sm text-[#94A3B8] mt-1">
                                <i class="fa-solid fa-calendar-days mr-2 text-[#64748B]"></i>
                                <?= htmlspecialchars($row['event_dates']) ?>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm mb-5">
                            <div>
                                <p class="text-[10px] text-[#94A3B8] uppercase tracking-wider font-bold mb-1">Lead Name</p>
                                <p class="text-[#F8FAFC] font-medium"><?= htmlspecialchars($row['lead_name']) ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[#94A3B8] uppercase tracking-wider font-bold mb-1">Phone No</p>
                                <p class="text-[#F8FAFC] font-medium"><?= htmlspecialchars($row['lead_phone']) ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[#94A3B8] uppercase tracking-wider font-bold mb-1">Studying Year</p>
                                <p class="text-[#F8FAFC] font-medium"><?= htmlspecialchars($row['lead_year']) ?></p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-[10px] text-[#94A3B8] uppercase tracking-wider font-bold mb-1.5">Skills Needed</p>
                                <div class="flex flex-wrap gap-2">
                                   <?php 
                                        $team_skills = array_filter(array_map('trim', explode(',', $row['skills_needed'] ?? '')));
                                        if(!empty($team_skills)):
                                            foreach ($team_skills as $sk): 
                                   ?>
                                        <span class="text-[11px] font-medium bg-[#0F172A] border border-white/5 text-[#CBD5E1] px-2.5 py-1 rounded-md"><?= htmlspecialchars($sk) ?></span>
                                   <?php 
                                            endforeach; 
                                        else: 
                                   ?>
                                        <span class="text-[#94A3B8] text-sm">N/A</span>
                                   <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5 flex-1">
                            <p class="text-[#94A3B8] text-sm leading-relaxed">
                                <?= nl2br(htmlspecialchars($row['description'] ?? 'No description provided.')) ?>
                            </p>
                        </div>

                        <?php if ($row['posted_by'] == $user_id): ?>
                            <?php
                            $team_id_for_members = (int) $row['id'];
                            $members_res = $conn->query("SELECT student_name, phone_no, regd_no, year_studying FROM event_team_enrollments WHERE team_id = $team_id_for_members");
                            $members_html = '';
                            if ($members_res && $members_res->num_rows > 0) {
                                while ($m = $members_res->fetch_assoc()) {
                                    $m_name = htmlspecialchars($m['student_name']);
                                    $m_wa = htmlspecialchars(preg_replace('/[^0-9]/', '', $m['phone_no']));
                                    $m_regd = htmlspecialchars($m['regd_no']);
                                    $m_year = htmlspecialchars($m['year_studying']);

                                    $members_html .= '<div class="flex items-center justify-between cs-card-secondary p-4 mb-3"><div><p class="font-semibold text-[#F8FAFC]">' . $m_name . '</p><p class="text-xs text-[#94A3B8] mt-1">Reg: ' . $m_regd . ' &bull; Year: ' . $m_year . '</p></div><a href="https://wa.me/' . $m_wa . '" target="_blank" class="bg-green-500 hover:bg-green-600 text-white w-10 h-10 flex items-center justify-center rounded-lg transition" title="Connect on WhatsApp"><i class="fa-brands fa-whatsapp text-xl"></i></a></div>';
                                }
                            } else {
                                $members_html = '<p class="text-[#94A3B8] text-center py-6">No members have joined yet.</p>';
                            }
                            ?>
                            <button
                                onclick="openMembersModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['event_name'], ENT_QUOTES) ?>')"
                                class="w-full flex items-center justify-center gap-2 cs-btn-primary py-3 mt-4">
                                <i class="fa-solid fa-users"></i> View Members
                            </button>
                            <div id="members_data_<?= $row['id'] ?>" class="hidden">
                                <?= $members_html ?>
                            </div>
                        <?php elseif (isset($row['is_enrolled']) && $row['is_enrolled'] > 0): ?>
                            <div class="flex items-center gap-2 mt-4">
                                <span
                                    class="flex-1 block text-center bg-emerald-500/20 border border-emerald-400 text-emerald-300 font-semibold px-4 py-3 rounded-xl">
                                    Joined
                                </span>
                                <?php if (!empty($row['lead_phone'])): ?>
                                    <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $row['lead_phone'])) ?>"
                                        target="_blank" title="Connect on WhatsApp"
                                        class="bg-green-500 hover:bg-green-600 text-white px-5 py-3 rounded-xl transition flex items-center justify-center">
                                        <i class="fa-brands fa-whatsapp text-xl"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <button
                                onclick="openEnrollModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['event_name'], ENT_QUOTES) ?>')"
                                class="w-full block text-center cs-btn-primary py-3 mt-4">
                                Apply Now
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="cs-card p-10 text-center">
                <p class="text-[#94A3B8] text-lg">No team requests found.</p>
            </div>
        <?php endif; ?>
    </section>

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
                        value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>"
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
                    <input type="text" name="regd_no" required placeholder="e.g. 22BCE1234" class="compact-input">
                </div>

                <div class="mb-6">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1">
                        Phone Number <span class="text-red-400">*</span>
                    </label>
                    <input type="tel" name="phone_no" required placeholder="e.g. 9876543210" class="compact-input">
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

    <!-- MODAL 4 - VIEW MEMBERS -->
    <div id="membersModal"
        class="hidden fixed inset-0 bg-[#0B1220]/80 backdrop-blur-md items-center justify-center z-50 p-4">
        <div
            class="bg-[#0F172A] border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 bg-[#111827] border-b border-white/5 shrink-0 rounded-t-2xl">
                <div>
                    <h2 class="text-xl font-bold text-[#F8FAFC]">Joined Members</h2>
                    <p id="membersEventName" class="text-[#38BDF8] text-sm mt-1"></p>
                </div>
                <button onclick="closeMembersModal()" class="text-[#94A3B8] hover:text-[#EF4444] transition p-2">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div id="membersListContainer" class="overflow-y-auto p-6">
                <!-- Members list will be injected here -->
            </div>
        </div>
    </div>

    <script>
        function openEnrollModal(teamId, eventName) {
            document.getElementById('enrollTeamId').value = teamId;
            document.getElementById('enrollEventName').textContent = eventName;
            const el = document.getElementById('enrollModal');
            el.classList.remove('hidden');
            el.style.display = 'flex';
        }
        function closeEnrollModal() {
            const el = document.getElementById('enrollModal');
            el.classList.add('hidden');
            el.style.display = 'none';
        }
        document.getElementById('enrollModal').addEventListener('click', e => {
            if (e.target.id === 'enrollModal') closeEnrollModal();
        });

        function openMembersModal(teamId, eventName) {
            document.getElementById('membersEventName').textContent = eventName;
            document.getElementById('membersListContainer').innerHTML = document.getElementById('members_data_' + teamId).innerHTML;
            const el = document.getElementById('membersModal');
            el.classList.remove('hidden');
            el.style.display = 'flex';
        }
        function closeMembersModal() {
            const el = document.getElementById('membersModal');
            el.classList.add('hidden');
            el.style.display = 'none';
        }
        document.getElementById('membersModal').addEventListener('click', e => {
            if (e.target.id === 'membersModal') closeMembersModal();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeEnrollModal();
                closeMembersModal();
            }
        });
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>