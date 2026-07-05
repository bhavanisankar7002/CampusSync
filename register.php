<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
// Preserve inputs on error
$old = ['name' => '', 'email' => '', 'role' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $role     = trim($_POST['role']     ?? '');
        $old      = compact('name', 'email', 'role');

        // Basic validation
        if ($name === '' || $email === '' || $password === '' || $role === '') {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (!in_array($role, ['student', 'faculty'], true)) {
            $error = 'Invalid role selected.';
        } else {
            // ✅ Check if email already exists (prepared statement)
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();
            $already_exists = $check->num_rows > 0;
            $check->close();

            if ($already_exists) {
                $error = 'An account with that email already exists. Please log in.';
            } else {
                // ✅ Prepared statement — no SQL injection
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt   = $conn->prepare(
                    "INSERT INTO users (name, email, password, role, college)
                     VALUES (?, ?, ?, ?, 'Lendi College')"
                );
                $stmt->bind_param("ssss", $name, $email, $hashed, $role);

                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    $stmt->close();
                    // ✅ Regenerate session ID on login
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['name']    = $name;
                    $_SESSION['role']    = $role;
                    header("Location: index.php");
                    exit;
                } else {
                    // ✅ Log real error, show generic message
                    error_log("Register DB error: " . $conn->error);
                    $error = 'Registration failed. Please try again.';
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register • CampusSync</title>
    <meta name="description" content="Join CampusSync — your college collaboration platform. Free for all students and faculty.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .logo-font { font-family: 'Space Grotesk', sans-serif; }
        select.compact-input option { background: #0f172a; color: #fff; }
        .pw-strength { height: 4px; border-radius: 99px; transition: width .3s, background .3s; }
    </style>
</head>
<body class="min-h-screen flex flex-col text-[#F8FAFC]">
    <?php include 'navbar.php'; ?>

    <div class="flex-1 flex items-center justify-center px-4 py-16">
        <div class="cs-card w-full max-w-md p-10">

            <div class="flex justify-center mb-8">
                <a href="index.php" class="flex items-center gap-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-[#38BDF8] to-[#3B82F6] rounded-xl flex items-center justify-center text-xl">🔗</div>
                    <span class="logo-font text-2xl font-bold tracking-tighter">CampusSync</span>
                </a>
            </div>

            <h1 class="text-3xl font-bold mb-2 text-center text-[#F8FAFC]">Create your account</h1>
            <p class="text-center text-[#94A3B8] mb-8 text-sm">Join your college platform in seconds</p>

            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-400 text-red-300 rounded-2xl p-4 mb-6 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" id="regForm" novalidate>
                <?= csrf_field() ?>

                <div class="mb-5">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="reg-name">Full Name</label>
                    <input type="text" name="name" id="reg-name"
                           value="<?= htmlspecialchars($old['name']) ?>"
                           placeholder="Your full name"
                           class="compact-input" required autocomplete="name">
                </div>

                <div class="mb-5">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="reg-email">Email address</label>
                    <input type="email" name="email" id="reg-email"
                           value="<?= htmlspecialchars($old['email']) ?>"
                           placeholder="you@college.edu"
                           class="compact-input" required autocomplete="email">
                </div>

                <div class="mb-5">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="reg-password">Password
                        <span class="text-[#94A3B8] font-normal opacity-70">(min 6 chars)</span>
                    </label>
                    <input type="password" name="password" id="reg-password"
                           placeholder="Create a strong password"
                           class="compact-input" required minlength="6" autocomplete="new-password">
                    <div class="mt-2 px-2">
                        <div class="bg-white/10 rounded-full h-1 overflow-hidden">
                            <div id="pwStrengthBar" class="pw-strength bg-[#94A3B8]" style="width:0%"></div>
                        </div>
                        <p id="pwStrengthLabel" class="text-xs text-[#94A3B8] mt-1"></p>
                    </div>
                </div>

                <div class="relative mb-8">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="reg-role">I am a…</label>
                    <select name="role" id="reg-role" class="compact-input" required>
                        <option value="" <?= $old['role']==='' ? 'selected' : '' ?>>Select your role</option>
                        <option value="student"  <?= $old['role']==='student'  ? 'selected' : '' ?>>Student</option>
                        <option value="faculty"  <?= $old['role']==='faculty'  ? 'selected' : '' ?>>Faculty Member</option>
                    </select>
                </div>

                <button type="submit" id="regBtn"
                        class="cs-btn-primary w-full py-3 rounded-xl flex items-center justify-center gap-2">
                    <span id="regBtnText">Create Account</span>
                    <i class="fa-solid fa-arrow-right" id="regBtnIcon"></i>
                </button>
            </form>

            <p class="text-center mt-8 text-[13px] text-[#94A3B8]">
                Already have an account?
                <a href="login.php" class="text-[#38BDF8] hover:text-[#3B82F6] font-semibold transition">Sign in</a>
            </p>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // ── Password strength indicator ───────────────────────────────────
        const pwInput = document.getElementById('reg-password');
        const pwBar   = document.getElementById('pwStrengthBar');
        const pwLabel = document.getElementById('pwStrengthLabel');

        pwInput.addEventListener('input', function () {
            const pw = this.value;
            let score = 0;
            if (pw.length >= 6)  score++;
            if (pw.length >= 10) score++;
            if (/[A-Z]/.test(pw)) score++;
            if (/[0-9]/.test(pw)) score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;

            const levels = [
                { w: '0%',   bg: '#475569', label: '' },
                { w: '25%',  bg: '#ef4444', label: 'Weak' },
                { w: '50%',  bg: '#f59e0b', label: 'Fair' },
                { w: '75%',  bg: '#06b6d4', label: 'Good' },
                { w: '100%', bg: '#10b981', label: 'Strong' },
            ];
            const lvl = levels[Math.min(score, 4)];
            pwBar.style.width      = lvl.w;
            pwBar.style.background = lvl.bg;
            pwLabel.textContent    = lvl.label;
            pwLabel.style.color    = lvl.bg;
        });

        // ── Submit loading state ──────────────────────────────────────────
        document.getElementById('regForm').addEventListener('submit', function () {
            if (!this.checkValidity()) return;
            const btn  = document.getElementById('regBtn');
            const text = document.getElementById('regBtnText');
            const icon = document.getElementById('regBtnIcon');
            btn.disabled     = true;
            btn.classList.add('opacity-75');
            text.textContent = 'Creating account…';
            icon.className   = 'fa-solid fa-spinner fa-spin';
        });
    </script>
</body>
</html>