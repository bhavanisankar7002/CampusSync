-- ============================================================
-- CampusSync — Full Database Setup (v2)
-- Run this on a fresh database. Safe to re-run (IF NOT EXISTS).
-- ============================================================

CREATE DATABASE IF NOT EXISTS campussync;
USE campussync;

-- ── 1. USERS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id                        INT AUTO_INCREMENT PRIMARY KEY,
    name                      VARCHAR(255)  NOT NULL,
    email                     VARCHAR(255)  UNIQUE NOT NULL,
    password                  VARCHAR(255)  NOT NULL,
    role                      ENUM('student','faculty') NOT NULL,
    college                   VARCHAR(100)  NOT NULL DEFAULT 'Your College',
    department                VARCHAR(100),
    year                      INT,
    bio                       TEXT,
    skills                    TEXT,
    profile_photo             VARCHAR(255),
    regd_no                   VARCHAR(100),
    phone_no                  VARCHAR(30),
    github_url                VARCHAR(255),
    linkedin_url              VARCHAR(255),
    portfolio_url             VARCHAR(255),          -- used in dashboard.php
    year_studying             INT,
    primary_skill             VARCHAR(255),
    primary_skill_level       INT  DEFAULT 0,
    working_skill_name        VARCHAR(255),
    working_skill_level       INT  DEFAULT 0,
    projects_done             TEXT,
    achievements_from_college TEXT,
    created_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. IDEAS / PROJECTS ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS ideas (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255)  NOT NULL,
    description    TEXT          NOT NULL,
    posted_by      INT           NOT NULL,
    skills_needed  VARCHAR(500),
    status         ENUM('open','in_progress','completed') DEFAULT 'open',
    faculty_status ENUM('pending','approved','suggestions','revision','rejected') DEFAULT 'pending',  -- used by faculty.php
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. IDEA APPLICATIONS ────────────────────────────────────
CREATE TABLE IF NOT EXISTS idea_applications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    idea_id      INT  NOT NULL,
    applicant_id INT  NOT NULL,
    message      TEXT,
    status       ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_idea_applicant (idea_id, applicant_id),
    FOREIGN KEY (idea_id)      REFERENCES ideas(id)  ON DELETE CASCADE,
    FOREIGN KEY (applicant_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. TEAMS (internal collaboration teams) ─────────────────
CREATE TABLE IF NOT EXISTS teams (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT,
    created_by  INT  NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS team_members (
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (team_id, user_id),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. EVENT TEAMS (teams.php / post-team.php) ──────────────
-- Students post team requests for hackathons / events
CREATE TABLE IF NOT EXISTS event_teams (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 6. EVENT TEAM ENROLLMENTS ───────────────────────────────
-- Students who enroll / apply to join an event_team
CREATE TABLE IF NOT EXISTS event_team_enrollments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    team_id       INT          NOT NULL,
    user_id       INT          NOT NULL,
    student_name  VARCHAR(255) NOT NULL,
    year_studying INT,
    regd_no       VARCHAR(100),
    phone_no      VARCHAR(30),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_team_user (team_id, user_id),
    FOREIGN KEY (team_id)  REFERENCES event_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 7. HACKATHONS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hackathons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT         NOT NULL,
    event_date  VARCHAR(255) NOT NULL,
    posted_by   INT          NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 8. HACKATHON TEAMS ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS hackathon_teams (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    hackathon_id  INT          NOT NULL,
    team_name     VARCHAR(255) NOT NULL,
    leader_name   VARCHAR(255) NOT NULL,
    leader_phone  VARCHAR(50)  NOT NULL,
    members       TEXT,
    registered_by INT          NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hackathon_id)  REFERENCES hackathons(id) ON DELETE CASCADE,
    FOREIGN KEY (registered_by) REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 9. FACULTY FEEDBACK ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS feedback (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    idea_id    INT            NOT NULL,
    faculty_id INT            NOT NULL,
    comment    TEXT           NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id)    REFERENCES ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 10. FACULTY EVALUATIONS ─────────────────────────────────
-- Full rubric-based evaluation used in faculty.php
CREATE TABLE IF NOT EXISTS evaluations (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    idea_id              INT            NOT NULL,
    faculty_id           INT            NOT NULL,
    innovation_score     TINYINT UNSIGNED DEFAULT 0,
    technical_score      TINYINT UNSIGNED DEFAULT 0,
    documentation_score  TINYINT UNSIGNED DEFAULT 0,
    presentation_score   TINYINT UNSIGNED DEFAULT 0,
    implementation_score TINYINT UNSIGNED DEFAULT 0,
    scalability_score    TINYINT UNSIGNED DEFAULT 0,
    collaboration_score  TINYINT UNSIGNED DEFAULT 0,
    problem_solving_score TINYINT UNSIGNED DEFAULT 0,
    overall_score        DECIMAL(5,2)   DEFAULT 0,
    comments             TEXT,
    decision             ENUM('approved','suggestions','revision','rejected') DEFAULT 'suggestions',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id)    REFERENCES ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 11. PROJECT SUBMISSIONS ─────────────────────────────────
-- Used in project-submission.php
CREATE TABLE IF NOT EXISTS project_submissions (
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    student_id               INT           NOT NULL,
    title                    VARCHAR(255)  NOT NULL,
    domain                   VARCHAR(100),
    category                 VARCHAR(100),
    student_name             VARCHAR(255),
    regd_no                  VARCHAR(100),
    department               VARCHAR(100),
    year_of_study            INT,
    team_members             TEXT,
    abstract                 TEXT          NOT NULL,
    problem_statement        TEXT,
    proposed_solution        TEXT,
    objectives               TEXT,
    expected_outcome         TEXT,
    technologies_used        TEXT,
    future_scope             TEXT,
    estimated_completion     DATE,
    research_paper_path      VARCHAR(500),
    images_path              VARCHAR(500),
    architecture_diagram_path VARCHAR(500),
    flowchart_path           VARCHAR(500),
    github_url               VARCHAR(255),
    demo_video_url           VARCHAR(255),
    -- Faculty evaluation fields
    faculty_id               INT           DEFAULT NULL,
    status                   ENUM('pending','under_review','revision_required','approved') DEFAULT 'pending',
    marks_obtained           INT           DEFAULT NULL,
    faculty_strengths        TEXT,
    faculty_suggestions      TEXT,
    faculty_remarks          TEXT,
    created_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── TEST ACCOUNTS ────────────────────────────────────────────
-- Password for both: "password" (bcrypt hash)
INSERT INTO users (
    name, email, password, role, college, department, year, skills, regd_no, phone_no,
    github_url, linkedin_url, year_studying,
    primary_skill, primary_skill_level, working_skill_name, working_skill_level,
    projects_done, achievements_from_college
) VALUES
(
    'Rahul Sharma', 'student@college.edu',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'student', 'IIT Delhi', 'CSE', 3, 'Python,React,ML',
    '2023CSE142', '+91 9876543210',
    'https://github.com/rahulsharma', 'https://linkedin.com/in/rahulsharma', 3,
    'React', 80, 'Node.js', 60,
    'Built a smart attendance dashboard and a campus events tracker.',
    'Won 2nd place in college hackathon and served as coding club coordinator.'
),
(
    'Prof. Priya Rao', 'faculty@college.edu',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'faculty', 'IIT Delhi', 'AI & DS', NULL, 'Machine Learning,Research',
    'FAC-AIDS-007', '+91 9988776655',
    'https://github.com/priyarao', 'https://linkedin.com/in/priyarao', NULL,
    'Machine Learning', 100, 'Research Mentoring', 80,
    'Guided multiple capstone projects in AI for social good.',
    'Received best faculty mentor award and led inter-college innovation reviews.'
)
ON DUPLICATE KEY UPDATE name = VALUES(name);
