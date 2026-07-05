<?php
session_start();
include 'config.php';

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    // If not faculty, redirect to dashboard or login
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$faculty_name = $_SESSION['name'];
$faculty_photo = isset($_SESSION['profile_photo']) ? $_SESSION['profile_photo'] : '';

// Process Evaluation Submission Securely
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error_msg = 'Invalid request. Please try again.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'evaluate') {
        $idea_id = (int) $_POST['idea_id'];
        $innovation = (int) $_POST['innovation'];
        $technical = (int) $_POST['technical'];
        $documentation = (int) $_POST['documentation'];
        $presentation = (int) $_POST['presentation'];
        $implementation = (int) $_POST['implementation'];
        $scalability = (int) $_POST['scalability'];
        $collaboration = (int) $_POST['collaboration'];
        $problem_solving = (int) $_POST['problem_solving'];
        $comments = trim($_POST['comments']);
        $decision = $_POST['decision']; // 'approved', 'suggestions', 'revision', 'rejected'
        
        $valid_decisions = ['approved', 'suggestions', 'revision', 'rejected'];
        
        if (in_array($decision, $valid_decisions) && $idea_id > 0) {
            // Calculate overall score (max 80, convert to max 10 or 100 as needed. Let's do max 10)
            $total_score = $innovation + $technical + $documentation + $presentation + $implementation + $scalability + $collaboration + $problem_solving;
            $overall_score = ($total_score / 40) * 10; // since 8 fields * max 5 = 40.

            // Insert into evaluations using Prepared Statements
            $stmt = $conn->prepare("INSERT INTO evaluations (idea_id, faculty_id, innovation_score, technical_score, documentation_score, presentation_score, implementation_score, scalability_score, collaboration_score, problem_solving_score, overall_score, comments, decision) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiiiiiiiiss", $idea_id, $user_id, $innovation, $technical, $documentation, $presentation, $implementation, $scalability, $collaboration, $problem_solving, $overall_score, $comments, $decision);
            
            if ($stmt->execute()) {
                // Update faculty_status in ideas table
                $update_stmt = $conn->prepare("UPDATE ideas SET faculty_status = ? WHERE id = ?");
                $status_map = [
                    'approved' => 'approved',
                    'suggestions' => 'suggestions',
                    'revision' => 'revision',
                    'rejected' => 'rejected'
                ];
                $update_stmt->bind_param("si", $status_map[$decision], $idea_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                $success_msg = 'Evaluation submitted successfully.';
            } else {
                $error_msg = 'Database error. Could not save evaluation.';
            }
            $stmt->close();
        } else {
            $error_msg = 'Invalid input parameters.';
        }
    }
}

// Fetch Statistics
$stats = [
    'pending' => 0,
    'approved' => 0,
    'revision' => 0,
    'reviewed' => 0
];

$res = $conn->query("SELECT faculty_status, COUNT(*) as count FROM ideas GROUP BY faculty_status");
while ($row = $res->fetch_assoc()) {
    if ($row['faculty_status'] === 'pending') $stats['pending'] = $row['count'];
    if ($row['faculty_status'] === 'approved') $stats['approved'] = $row['count'];
    if ($row['faculty_status'] === 'revision') $stats['revision'] = $row['count'];
}

$rev_stmt = $conn->prepare("SELECT COUNT(*) as count FROM evaluations WHERE faculty_id = ?");
$rev_stmt->bind_param("i", $user_id);
$rev_stmt->execute();
$stats['reviewed'] = $rev_stmt->get_result()->fetch_assoc()['count'];
$rev_stmt->close();

// Fetch Projects/Ideas
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? 'all';
$filter_dept = $_GET['dept'] ?? 'all';

$query = "SELECT i.*, u.name as student_name, u.department 
          FROM ideas i 
          JOIN users u ON i.posted_by = u.id 
          WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $query .= " AND (i.title LIKE ? OR u.name LIKE ?)";
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $types .= "ss";
}
if ($filter_status !== 'all') {
    $query .= " AND i.faculty_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if ($filter_dept !== 'all') {
    $query .= " AND u.department = ?";
    $params[] = $filter_dept;
    $types .= "s";
}

$query .= " ORDER BY i.created_at DESC";

$stmt = $conn->prepare($query);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$projects = $stmt->get_result();

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Evaluation Dashboard • CampusSync</title>
    
    <!-- Required for navbar and overall global styling consistency as per existing layout -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS strictly handling all the new dashboard components as requested -->
    <style>
        :root {
            --bg-page: #0B1220;
            --bg-card: #111827;
            --bg-card-secondary: #1E293B;
            --border-color: rgba(255, 255, 255, 0.1);
            --text-primary: #F8FAFC;
            --text-secondary: #94A3B8;
            --accent: #38BDF8;
            --green: #34d399;
            --blue: #60a5fa;
            --orange: #fb923c;
            --red: #f87171;
            --font-family: 'Space Grotesk', system-ui, sans-serif;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            background-color: var(--bg-page);
            color: var(--text-primary);
            font-family: var(--font-family);
            margin: 0;
            padding: 0;
        }

        /* Layout Grid */
        .dashboard-container {
            max-w-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        @media(min-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 3fr 1fr;
            }
        }

        /* Generic Card */
        .fac-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: var(--transition);
        }
        .fac-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
        }

        /* Welcome Section */
        .welcome-hero {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .welcome-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--bg-card-secondary);
            border: 2px solid var(--border-color);
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--accent);
            font-weight: bold;
        }
        .welcome-text h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: 600;
        }
        .welcome-text p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* Stats Row */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            background: var(--bg-card-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .stat-icon.pending { color: var(--accent); background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); }
        .stat-icon.approved { color: var(--green); background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); }
        .stat-icon.revision { color: var(--orange); background: rgba(251, 146, 60, 0.1); border: 1px solid rgba(251, 146, 60, 0.2); }
        .stat-icon.reviewed { color: var(--blue); background: rgba(96, 165, 250, 0.1); border: 1px solid rgba(96, 165, 250, 0.2); }
        
        .stat-info h3 { margin: 0; font-size: 1.5rem; font-weight: bold; }
        .stat-info p { margin: 0; color: var(--text-secondary); font-size: 0.875rem; }

        /* Search & Filter */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            background: var(--bg-card);
            padding: 1rem;
            border-radius: 1rem;
            border: 1px solid var(--border-color);
        }
        .fac-input {
            background: var(--bg-page);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            outline: none;
            transition: var(--transition);
            flex: 1;
            min-width: 150px;
        }
        .fac-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
        }
        .fac-btn {
            background: var(--accent);
            color: #0F172A;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .fac-btn:hover { background: #7dd3fc; transform: scale(1.02); }

        /* Project List */
        .project-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .project-card {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .project-title { margin: 0 0 0.25rem 0; font-size: 1.25rem; font-weight: 600; }
        .project-meta { font-size: 0.875rem; color: var(--text-secondary); display: flex; gap: 1rem; flex-wrap: wrap; }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid transparent;
        }
        .badge-pending { color: var(--accent); background: rgba(56, 189, 248, 0.1); border-color: rgba(56, 189, 248, 0.2); }
        .badge-approved { color: var(--green); background: rgba(52, 211, 153, 0.1); border-color: rgba(52, 211, 153, 0.2); }
        .badge-suggestions { color: var(--blue); background: rgba(96, 165, 250, 0.1); border-color: rgba(96, 165, 250, 0.2); }
        .badge-revision { color: var(--orange); background: rgba(251, 146, 60, 0.1); border-color: rgba(251, 146, 60, 0.2); }
        .badge-rejected { color: var(--red); background: rgba(248, 113, 113, 0.1); border-color: rgba(248, 113, 113, 0.2); }

        .tags-container {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .tag {
            background: var(--bg-card-secondary);
            color: var(--text-secondary);
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            border: 1px solid var(--border-color);
        }

        .project-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.5rem;
            border-top: 1px solid var(--border-color);
            padding-top: 1rem;
        }
        .action-btn {
            background: var(--bg-card-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .action-btn:hover { background: rgba(255, 255, 255, 0.1); }
        .action-evaluate { background: var(--accent); color: #0F172A; border-color: transparent; font-weight: 600; }
        .action-evaluate:hover { background: #7dd3fc; }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(11, 18, 32, 0.8);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            transform: scale(0.95);
            transition: transform 0.3s ease;
            display: grid;
            grid-template-columns: 1fr;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        @media(min-width: 768px) {
            .modal-content { grid-template-columns: 1fr 1fr; }
        }
        .modal-overlay.active .modal-content { transform: scale(1); }
        
        .modal-header {
            grid-column: 1 / -1;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: var(--bg-card);
            z-index: 10;
            border-radius: 1rem 1rem 0 0;
        }
        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
        }
        .modal-close:hover { color: var(--text-primary); }
        
        .modal-left, .modal-right { padding: 1.5rem; }
        .modal-left { border-right: 1px solid var(--border-color); }
        
        /* Modal Typography */
        .m-title { font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem; }
        .m-label { font-size: 0.75rem; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 0.05em; margin-bottom: 0.25rem; display: block; }
        .m-text { margin-bottom: 1.5rem; line-height: 1.6; }
        
        .dummy-file {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: var(--bg-page);
            border: 1px dashed var(--border-color);
            border-radius: 0.5rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        /* Star Rating */
        .rating-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .rating-name { font-size: 0.875rem; }
        .stars {
            display: flex;
            gap: 0.25rem;
            flex-direction: row-reverse;
        }
        .stars input { display: none; }
        .stars label {
            color: var(--text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label {
            color: var(--accent);
        }

        /* Evaluation Form */
        .eval-textarea {
            width: 100%;
            background: var(--bg-page);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            color: var(--text-primary);
            font-family: var(--font-family);
            min-height: 100px;
            margin-bottom: 1.5rem;
            resize: vertical;
            box-sizing: border-box;
        }
        .eval-textarea:focus { border-color: var(--accent); outline: none; }
        
        .decision-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .btn-decision {
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-decision.approve { background: rgba(52, 211, 153, 0.1); color: var(--green); border-color: rgba(52, 211, 153, 0.2); }
        .btn-decision.approve:hover { background: var(--green); color: #0F172A; }
        
        .btn-decision.suggest { background: rgba(96, 165, 250, 0.1); color: var(--blue); border-color: rgba(96, 165, 250, 0.2); }
        .btn-decision.suggest:hover { background: var(--blue); color: #0F172A; }
        
        .btn-decision.revise { background: rgba(251, 146, 60, 0.1); color: var(--orange); border-color: rgba(251, 146, 60, 0.2); }
        .btn-decision.revise:hover { background: var(--orange); color: #0F172A; }
        
        .btn-decision.reject { background: rgba(248, 113, 113, 0.1); color: var(--red); border-color: rgba(248, 113, 113, 0.2); }
        .btn-decision.reject:hover { background: var(--red); color: #0F172A; }

        /* Right Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 1.5rem; }
        .feed-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        .feed-item:last-child { border-bottom: none; padding-bottom: 0; }
        .feed-icon { width: 32px; height: 32px; border-radius: 50%; background: var(--bg-card-secondary); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: var(--text-secondary); }
        .feed-content p { margin: 0; font-size: 0.875rem; }
        .feed-time { font-size: 0.75rem; color: var(--text-secondary); }

        /* CSS Charts */
        .chart-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }
        .chart-label { width: 80px; font-size: 0.75rem; color: var(--text-secondary); text-align: right; }
        .chart-bar-bg { flex: 1; height: 8px; background: var(--bg-page); border-radius: 4px; overflow: hidden; }
        .chart-bar-fill { height: 100%; background: var(--accent); border-radius: 4px; }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        .alert-success { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: var(--green); }
        .alert-error { background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.2); color: var(--red); }
    </style>
</head>
<body>
    
    <?php $active_page = 'dashboard'; include 'navbar.php'; ?>

    <div class="dashboard-container">
        
        <!-- Main Content -->
        <div class="main-content">
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error_msg); ?></div>
            <?php endif; ?>

            <!-- Welcome Section -->
            <div class="welcome-hero fac-card">
                <div class="welcome-photo">
                    <?php if ($faculty_photo): ?>
                        <img src="<?php echo e($faculty_photo); ?>" alt="Profile" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($faculty_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="welcome-text">
                    <h1>Good Morning, <?php echo e($faculty_name); ?></h1>
                    <p>You have <strong><?php echo $stats['pending']; ?></strong> pending reviews today. Let's evaluate some innovations.</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="stats-grid">
                <div class="fac-card stat-card">
                    <div class="stat-icon pending"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending Reviews</p>
                    </div>
                </div>
                <div class="fac-card stat-card">
                    <div class="stat-icon approved"><i class="fa-solid fa-check-double"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['approved']; ?></h3>
                        <p>Approved Projects</p>
                    </div>
                </div>
                <div class="fac-card stat-card">
                    <div class="stat-icon revision"><i class="fa-solid fa-pen-nib"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['revision']; ?></h3>
                        <p>Needs Revision</p>
                    </div>
                </div>
                <div class="fac-card stat-card">
                    <div class="stat-icon reviewed"><i class="fa-solid fa-chart-pie"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['reviewed']; ?></h3>
                        <p>Total Evaluated</p>
                    </div>
                </div>
            </div>

            <!-- Search & Filter -->
            <form class="filter-bar" method="GET" action="faculty.php">
                <input type="text" name="search" class="fac-input" placeholder="Search projects or students..." value="<?php echo e($search); ?>">
                
                <select name="status" class="fac-input">
                    <option value="all" <?php if($filter_status==='all') echo 'selected'; ?>>All Statuses</option>
                    <option value="pending" <?php if($filter_status==='pending') echo 'selected'; ?>>Pending</option>
                    <option value="approved" <?php if($filter_status==='approved') echo 'selected'; ?>>Approved</option>
                    <option value="suggestions" <?php if($filter_status==='suggestions') echo 'selected'; ?>>Suggestions</option>
                    <option value="revision" <?php if($filter_status==='revision') echo 'selected'; ?>>Revision</option>
                    <option value="rejected" <?php if($filter_status==='rejected') echo 'selected'; ?>>Rejected</option>
                </select>
                
                <select name="dept" class="fac-input">
                    <option value="all" <?php if($filter_dept==='all') echo 'selected'; ?>>All Departments</option>
                    <option value="Computer Science" <?php if($filter_dept==='Computer Science') echo 'selected'; ?>>Computer Science</option>
                    <option value="Information Technology" <?php if($filter_dept==='Information Technology') echo 'selected'; ?>>Information Technology</option>
                    <option value="Electronics" <?php if($filter_dept==='Electronics') echo 'selected'; ?>>Electronics</option>
                </select>
                
                <button type="submit" class="fac-btn"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
            </form>

            <!-- Project List -->
            <div class="project-list">
                <?php if ($projects->num_rows === 0): ?>
                    <div class="fac-card text-center text-[#94A3B8] py-12">
                        <i class="fa-solid fa-inbox text-4xl mb-3"></i>
                        <p>No projects found matching your criteria.</p>
                    </div>
                <?php endif; ?>

                <?php while ($row = $projects->fetch_assoc()): ?>
                    <?php 
                        $status_class = 'badge-pending';
                        if ($row['faculty_status'] === 'approved') $status_class = 'badge-approved';
                        if ($row['faculty_status'] === 'suggestions') $status_class = 'badge-suggestions';
                        if ($row['faculty_status'] === 'revision') $status_class = 'badge-revision';
                        if ($row['faculty_status'] === 'rejected') $status_class = 'badge-rejected';
                        
                        $skills = explode(',', $row['skills_needed'] ?? '');
                    ?>
                    <div class="fac-card project-card">
                        <div class="project-header">
                            <div>
                                <h2 class="project-title"><?php echo e($row['title']); ?></h2>
                                <div class="project-meta">
                                    <span><i class="fa-regular fa-user"></i> <?php echo e($row['student_name']); ?></span>
                                    <span><i class="fa-solid fa-building-columns"></i> <?php echo e($row['department'] ?: 'N/A'); ?></span>
                                    <span><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo e($row['faculty_status']); ?></span>
                        </div>
                        
                        <div class="tags-container">
                            <?php foreach($skills as $skill): $skill = trim($skill); if($skill !== ''): ?>
                                <span class="tag"><?php echo e($skill); ?></span>
                            <?php endif; endforeach; ?>
                        </div>
                        
                        <div class="project-actions">
                            <button type="button" class="action-btn" onclick='openModal(<?php echo json_encode([
                                "id" => $row["id"],
                                "title" => e($row["title"]),
                                "description" => e($row["description"]),
                                "student" => e($row["student_name"]),
                                "department" => e($row["department"]),
                                "skills" => e($row["skills_needed"])
                            ]); ?>)'>
                                <i class="fa-regular fa-eye"></i> View Details
                            </button>
                            <?php if ($row['faculty_status'] === 'pending' || $row['faculty_status'] === 'revision'): ?>
                                <button type="button" class="action-btn action-evaluate" onclick='openModal(<?php echo json_encode([
                                    "id" => $row["id"],
                                    "title" => e($row["title"]),
                                    "description" => e($row["description"]),
                                    "student" => e($row["student_name"]),
                                    "department" => e($row["department"]),
                                    "skills" => e($row["skills_needed"])
                                ]); ?>)'>
                                    <i class="fa-solid fa-clipboard-check"></i> Evaluate
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
        </div>
        
        <!-- Right Sidebar -->
        <div class="sidebar">
            <div class="fac-card">
                <h3 style="margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem; margin-bottom:1rem; font-size:1.1rem;">Quick Actions</h3>
                <a href="dashboard.php" class="action-btn" style="width:100%; justify-content:center; margin-bottom:0.5rem;"><i class="fa-solid fa-house"></i> Return Home</a>
                <a href="ideas.php" class="action-btn" style="width:100%; justify-content:center;"><i class="fa-solid fa-lightbulb"></i> All Ideas Hub</a>
            </div>

            <div class="fac-card">
                <h3 style="margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem; margin-bottom:1rem; font-size:1.1rem;">Faculty Analytics</h3>
                
                <span class="m-label">Dept Review Distribution</span>
                <div class="chart-row">
                    <div class="chart-label">CS</div>
                    <div class="chart-bar-bg"><div class="chart-bar-fill" style="width: 75%;"></div></div>
                </div>
                <div class="chart-row">
                    <div class="chart-label">IT</div>
                    <div class="chart-bar-bg"><div class="chart-bar-fill" style="width: 45%; background: var(--green);"></div></div>
                </div>
                <div class="chart-row" style="margin-bottom:1.5rem;">
                    <div class="chart-label">ECE</div>
                    <div class="chart-bar-bg"><div class="chart-bar-fill" style="width: 25%; background: var(--orange);"></div></div>
                </div>
                
                <span class="m-label">Weekly Progress</span>
                <div class="chart-row">
                    <div class="chart-label">Target</div>
                    <div class="chart-bar-bg"><div class="chart-bar-fill" style="width: 60%; background: var(--blue);"></div></div>
                </div>
            </div>

            <div class="fac-card">
                <h3 style="margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem; margin-bottom:0; font-size:1.1rem;">Recent Activity</h3>
                <div class="feed-item">
                    <div class="feed-icon"><i class="fa-solid fa-check"></i></div>
                    <div class="feed-content">
                        <p>Approved <strong>IoT Waste Management</strong></p>
                        <span class="feed-time">2 hours ago</span>
                    </div>
                </div>
                <div class="feed-item">
                    <div class="feed-icon"><i class="fa-solid fa-message"></i></div>
                    <div class="feed-content">
                        <p>Requested revision for <strong>AI Chatbot</strong></p>
                        <span class="feed-time">5 hours ago</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Evaluation Modal -->
    <div class="modal-overlay" id="evalModal">
        <form method="POST" action="faculty.php" class="modal-content">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="evaluate">
            <input type="hidden" name="idea_id" id="modal_idea_id">
            
            <div class="modal-header">
                <h2 style="margin:0; font-size:1.25rem;">Project Evaluation</h2>
                <button type="button" class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <!-- Left: Details -->
            <div class="modal-left">
                <h3 class="m-title" id="modal_title">Project Title</h3>
                
                <span class="m-label">Student & Department</span>
                <p class="m-text" id="modal_student">Student Name / Dept</p>
                
                <span class="m-label">Problem Statement & Description</span>
                <p class="m-text" id="modal_desc">Description goes here</p>
                
                <span class="m-label">Required Skills & Tech</span>
                <p class="m-text" id="modal_skills">Skills</p>
                
                <span class="m-label">Submitted Assets</span>
                <div class="dummy-file"><i class="fa-solid fa-file-pdf"></i> project_proposal_v1.pdf</div>
                <div class="dummy-file"><i class="fa-brands fa-github"></i> Repository / Source Code linked</div>
                <div class="dummy-file"><i class="fa-solid fa-image"></i> 3 Prototype Images attached</div>
            </div>
            
            <!-- Right: Evaluation Form -->
            <div class="modal-right">
                <span class="m-label" style="margin-bottom:1rem;">Evaluation Metrics (1-5 Stars)</span>
                
                <?php 
                $metrics = [
                    'innovation' => 'Innovation & Originality',
                    'technical' => 'Technical Complexity',
                    'documentation' => 'Documentation Quality',
                    'presentation' => 'Presentation / UI',
                    'implementation' => 'Implementation Feasibility',
                    'scalability' => 'Scalability & Impact',
                    'collaboration' => 'Team Collaboration',
                    'problem_solving' => 'Problem Solving Approach'
                ];
                foreach ($metrics as $key => $label):
                ?>
                <div class="rating-row">
                    <span class="rating-name"><?php echo $label; ?></span>
                    <div class="stars">
                        <input type="radio" id="star5_<?php echo $key; ?>" name="<?php echo $key; ?>" value="5" required />
                        <label for="star5_<?php echo $key; ?>"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star4_<?php echo $key; ?>" name="<?php echo $key; ?>" value="4" />
                        <label for="star4_<?php echo $key; ?>"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star3_<?php echo $key; ?>" name="<?php echo $key; ?>" value="3" />
                        <label for="star3_<?php echo $key; ?>"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star2_<?php echo $key; ?>" name="<?php echo $key; ?>" value="2" />
                        <label for="star2_<?php echo $key; ?>"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star1_<?php echo $key; ?>" name="<?php echo $key; ?>" value="1" />
                        <label for="star1_<?php echo $key; ?>"><i class="fa-solid fa-star"></i></label>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <span class="m-label" style="margin-top:1.5rem;">Constructive Feedback</span>
                <textarea name="comments" class="eval-textarea" placeholder="Provide constructive feedback, suggestions for improvement, or rationale for your decision..." required></textarea>
                
                <span class="m-label">Final Decision</span>
                <div class="decision-grid">
                    <button type="submit" name="decision" value="approved" class="btn-decision approve">
                        <i class="fa-solid fa-check"></i> Approve
                    </button>
                    <button type="submit" name="decision" value="suggestions" class="btn-decision suggest">
                        <i class="fa-regular fa-lightbulb"></i> W/ Suggestions
                    </button>
                    <button type="submit" name="decision" value="revision" class="btn-decision revise">
                        <i class="fa-solid fa-rotate-left"></i> Needs Revision
                    </button>
                    <button type="submit" name="decision" value="rejected" class="btn-decision reject">
                        <i class="fa-solid fa-xmark"></i> Reject
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function openModal(data) {
            document.getElementById('modal_idea_id').value = data.id;
            document.getElementById('modal_title').textContent = data.title;
            document.getElementById('modal_student').textContent = data.student + (data.department ? ' / ' + data.department : '');
            document.getElementById('modal_desc').textContent = data.description;
            document.getElementById('modal_skills').textContent = data.skills;
            
            // Reset form
            const form = document.querySelector('.modal-content');
            form.reset();
            
            document.getElementById('evalModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.getElementById('evalModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        // Close on overlay click
        document.getElementById('evalModal').addEventListener('click', function(e) {
            if(e.target === this) closeModal();
        });
    </script>
</body>
</html>
