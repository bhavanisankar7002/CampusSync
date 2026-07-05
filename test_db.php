<?php
include 'config.php';
$res = $conn->query("DESCRIBE event_teams");
if ($res) {
    while($row = $res->fetch_assoc()) { print_r($row); }
} else {
    echo $conn->error;
}
