<?php
// profile.php — redirect to the actual profile page
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: profileuser.php");
} else {
    header("Location: login.php");
}
exit;
