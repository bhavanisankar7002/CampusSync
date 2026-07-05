<?php
date_default_timezone_set('Asia/Kolkata');
// config.php — Database connection + shared helpers (NO session_start here)


$host = "localhost";
$dbname = "campussync";
$username = "root";
$password = "";

$conn = new mysqli("localhost", "root", "", "campussync");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ── CSRF Protection ───────────────────────────────────────────────────────
/**
 * Returns the CSRF token for the current session, creating it if needed.
 */
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) return '';
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Outputs a hidden <input> with the CSRF token — echo inside every <form>.
 */
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="'
         . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Returns true if the POST request has a valid CSRF token. Call at the top
 * of every POST handler.
 */
function csrf_verify(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $submitted = (string) ($_POST['_csrf'] ?? '');
    $stored    = csrf_token();
    if ($stored === '' || $submitted === '') return false;
    return hash_equals($stored, $submitted);
}

// ── Schema helpers ────────────────────────────────────────────────────────
function column_exists(mysqli $conn, string $database, string $table, string $column): bool {
    $stmt = $conn->prepare(
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1"
    );
    $stmt->bind_param("sss", $database, $table, $column);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function table_exists(mysqli $conn, string $database, string $table): bool {
    $stmt = $conn->prepare(
        "SELECT 1 FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1"
    );
    $stmt->bind_param("ss", $database, $table);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function ensure_user_profile_schema(mysqli $conn, string $database): void {
    $cols = [
        'profile_photo'             => "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL",
        'regd_no'                   => "ALTER TABLE users ADD COLUMN regd_no VARCHAR(100) NULL",
        'phone_no'                  => "ALTER TABLE users ADD COLUMN phone_no VARCHAR(30) NULL",
        'github_url'                => "ALTER TABLE users ADD COLUMN github_url VARCHAR(255) NULL",
        'linkedin_url'              => "ALTER TABLE users ADD COLUMN linkedin_url VARCHAR(255) NULL",
        'portfolio_url'             => "ALTER TABLE users ADD COLUMN portfolio_url VARCHAR(255) NULL",
        'year_studying'             => "ALTER TABLE users ADD COLUMN year_studying INT NULL",
        'primary_skill'             => "ALTER TABLE users ADD COLUMN primary_skill VARCHAR(255) NULL",
        'primary_skill_level'       => "ALTER TABLE users ADD COLUMN primary_skill_level INT NOT NULL DEFAULT 0",
        'working_skill_name'        => "ALTER TABLE users ADD COLUMN working_skill_name VARCHAR(255) NULL",
        'working_skill_level'       => "ALTER TABLE users ADD COLUMN working_skill_level INT NOT NULL DEFAULT 0",
        'projects_done'             => "ALTER TABLE users ADD COLUMN projects_done TEXT NULL",
        'achievements_from_college' => "ALTER TABLE users ADD COLUMN achievements_from_college TEXT NULL",
    ];
    foreach ($cols as $col => $sql) {
        if (!column_exists($conn, $database, 'users', $col)) $conn->query($sql);
    }
    $st = $conn->prepare(
        "SELECT DATA_TYPE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=? AND TABLE_NAME='users' AND COLUMN_NAME='skills' LIMIT 1"
    );
    $st->bind_param("s", $database);
    $st->execute();
    $type = $st->get_result()->fetch_assoc()['DATA_TYPE'] ?? '';
    $st->close();
    if ($type !== 'text') $conn->query("ALTER TABLE users MODIFY COLUMN skills TEXT NULL");
}

function ensure_idea_applications_schema(mysqli $conn, string $database): void {
    if (!table_exists($conn, $database, 'idea_applications')) {
        $conn->query("
            CREATE TABLE idea_applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idea_id INT NOT NULL,
                applicant_id INT NOT NULL,
                message TEXT NULL,
                status ENUM('pending','accepted','rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_idea_applicant (idea_id, applicant_id),
                FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
                FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        return;
    }
    $cols = [
        'message'    => "ALTER TABLE idea_applications ADD COLUMN message TEXT NULL",
        'status'     => "ALTER TABLE idea_applications ADD COLUMN status ENUM('pending','accepted','rejected') DEFAULT 'pending'",
        'created_at' => "ALTER TABLE idea_applications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    ];
    foreach ($cols as $col => $sql) {
        if (!column_exists($conn, $database, 'idea_applications', $col)) $conn->query($sql);
    }
}

function ensure_feedback_schema(mysqli $conn, string $database): void {
    if (!table_exists($conn, $database, 'feedback')) {
        $conn->query("
            CREATE TABLE feedback (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idea_id INT NOT NULL,
                faculty_id INT NOT NULL,
                comment TEXT NOT NULL,
                rating TINYINT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
                FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        return;
    }
    // Add rating column if missing (upgrade existing installs)
    if (!column_exists($conn, $database, 'feedback', 'rating')) {
        $conn->query("ALTER TABLE feedback ADD COLUMN rating TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER comment");
    }
}

function ensure_ideas_faculty_status(mysqli $conn, string $database): void {
    if (!column_exists($conn, $database, 'ideas', 'faculty_status')) {
        $conn->query("ALTER TABLE ideas ADD COLUMN faculty_status ENUM('pending','approved','suggestions','revision','rejected') NOT NULL DEFAULT 'pending' AFTER status");
    }
}

function ensure_event_teams_schema(mysqli $conn, string $database): void {
    if (!table_exists($conn, $database, 'event_teams')) {
        $conn->query("
            CREATE TABLE event_teams (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                event_name   VARCHAR(255) NOT NULL,
                event_dates  VARCHAR(150),
                lead_name    VARCHAR(255) NOT NULL,
                lead_phone   VARCHAR(50)  NOT NULL,
                lead_year    INT          NOT NULL,
                slots        INT          NOT NULL DEFAULT 4,
                skills_needed VARCHAR(500),
                description  TEXT,
                posted_by    INT          NOT NULL,
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
    if (!table_exists($conn, $database, 'event_team_enrollments')) {
        $conn->query("
            CREATE TABLE event_team_enrollments (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                team_id       INT          NOT NULL,
                user_id       INT          NOT NULL,
                student_name  VARCHAR(255) NOT NULL,
                year_studying INT,
                regd_no       VARCHAR(100),
                phone_no      VARCHAR(30),
                created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_team_user (team_id, user_id),
                FOREIGN KEY (team_id) REFERENCES event_teams(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id)       ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}

function ensure_evaluations_schema(mysqli $conn, string $database): void {
    if (!table_exists($conn, $database, 'evaluations')) {
        $conn->query("
            CREATE TABLE evaluations (
                id                    INT AUTO_INCREMENT PRIMARY KEY,
                idea_id               INT            NOT NULL,
                faculty_id            INT            NOT NULL,
                innovation_score      TINYINT UNSIGNED DEFAULT 0,
                technical_score       TINYINT UNSIGNED DEFAULT 0,
                documentation_score   TINYINT UNSIGNED DEFAULT 0,
                presentation_score    TINYINT UNSIGNED DEFAULT 0,
                implementation_score  TINYINT UNSIGNED DEFAULT 0,
                scalability_score     TINYINT UNSIGNED DEFAULT 0,
                collaboration_score   TINYINT UNSIGNED DEFAULT 0,
                problem_solving_score TINYINT UNSIGNED DEFAULT 0,
                overall_score         DECIMAL(5,2)   DEFAULT 0,
                comments              TEXT,
                decision              ENUM('approved','suggestions','revision','rejected') DEFAULT 'suggestions',
                created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (idea_id)    REFERENCES ideas(id) ON DELETE CASCADE,
                FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}

function ensure_project_submissions_schema(mysqli $conn, string $database): void {
    if (!table_exists($conn, $database, 'project_submissions')) {
        $conn->query("
            CREATE TABLE project_submissions (
                id                         INT AUTO_INCREMENT PRIMARY KEY,
                student_id                 INT           NOT NULL,
                title                      VARCHAR(255)  NOT NULL,
                domain                     VARCHAR(100),
                category                   VARCHAR(100),
                student_name               VARCHAR(255),
                regd_no                    VARCHAR(100),
                department                 VARCHAR(100),
                year_of_study              INT,
                team_members               TEXT,
                abstract                   TEXT          NOT NULL,
                problem_statement          TEXT,
                proposed_solution          TEXT,
                objectives                 TEXT,
                expected_outcome           TEXT,
                technologies_used          TEXT,
                future_scope               TEXT,
                estimated_completion       DATE,
                research_paper_path        VARCHAR(500),
                images_path                VARCHAR(500),
                architecture_diagram_path  VARCHAR(500),
                flowchart_path             VARCHAR(500),
                github_url                 VARCHAR(255),
                demo_video_url             VARCHAR(255),
                faculty_id                 INT           DEFAULT NULL,
                status                     ENUM('pending','under_review','revision_required','approved') DEFAULT 'pending',
                marks_obtained             INT           DEFAULT NULL,
                faculty_strengths          TEXT,
                faculty_suggestions        TEXT,
                faculty_remarks            TEXT,
                created_at                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}

// ── Run schema checks ONCE per session (major performance improvement) ────
function _run_all_schema_checks(mysqli $conn, string $db): void {
    ensure_user_profile_schema($conn, $db);
    ensure_idea_applications_schema($conn, $db);
    ensure_feedback_schema($conn, $db);
    ensure_ideas_faculty_status($conn, $db);
    ensure_event_teams_schema($conn, $db);
    ensure_evaluations_schema($conn, $db);
    ensure_project_submissions_schema($conn, $db);
}

if (session_status() === PHP_SESSION_ACTIVE) {
    if (empty($_SESSION['schema_v4'])) {
        _run_all_schema_checks($conn, $db);
        $_SESSION['schema_v4'] = true;
    }
} else {
    // Session not started yet (e.g. called before session_start on login page)
    static $ran = false;
    if (!$ran) {
        _run_all_schema_checks($conn, $db);
        $ran = true;
    }
}
