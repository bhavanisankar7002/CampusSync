<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error     = '';
$email_old = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } else {
        $email     = trim($_POST['email'] ?? '');
        $pass      = $_POST['password'] ?? '';
        $email_old = $email;

        // ✅ Prepared statement — no SQL injection possible
        $stmt = $conn->prepare("SELECT id, name, role, password FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($pass, $user['password'])) {
            // ✅ Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            // ✅ Small delay to slow brute-force attacks
            usleep(400000); // 0.4 s
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login • CampusSync</title>
    <meta name="description" content="Sign in to CampusSync — your college collaboration platform.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .logo-font { font-family: 'Space Grotesk', sans-serif; }
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

            <h1 class="text-3xl font-bold mb-2 text-center text-[#F8FAFC]">Welcome back</h1>
            <p class="text-center text-[#94A3B8] mb-8 text-sm">Sign in to your college platform</p>

            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-400 text-red-300 rounded-2xl p-4 mb-6 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" id="loginForm" novalidate>
                <?= csrf_field() ?>

                <div class="mb-5">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="login-email">Email address</label>
                    <input type="email" name="email" id="login-email"
                           value="<?= htmlspecialchars($email_old) ?>"
                           placeholder="you@college.edu"
                           class="compact-input" required autocomplete="email">
                </div>

                <div class="mb-6">
                    <label class="block text-[11px] font-medium text-[#CBD5E1] mb-1.5 pl-1" for="login-password">Password</label>
                    <input type="password" name="password" id="login-password"
                           placeholder="••••••••"
                           class="compact-input" required autocomplete="current-password">
                </div>

                <button type="submit" id="loginBtn"
                        class="cs-btn-primary w-full py-3 rounded-xl flex items-center justify-center gap-2">
                    <span id="loginBtnText">Sign In</span>
                    <i class="fa-solid fa-arrow-right" id="loginBtnIcon"></i>
                </button>
            </form>

            <p class="text-center mt-8 text-[13px] text-[#94A3B8]">
                Don't have an account?
                <a href="register.php" class="text-[#38BDF8] hover:text-[#3B82F6] font-semibold transition">Register free</a>
            </p>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn  = document.getElementById('loginBtn');
            const text = document.getElementById('loginBtnText');
            const icon = document.getElementById('loginBtnIcon');
            if (!this.checkValidity()) return;
            btn.disabled   = true;
            btn.classList.add('opacity-75');
            text.textContent = 'Signing in…';
            icon.className   = 'fa-solid fa-spinner fa-spin';
        });
    </script>
</body>
</html>