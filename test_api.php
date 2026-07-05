<?php
// Mock session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['name'] = 'Test User';

// We need to call feed_actions.php as if we were a browser.
// The easiest way is just to write a curl script.
$url = 'http://localhost/campussync/components/feed_actions.php';

$cfile1 = new CURLFile('c:/xampp/htdocs/campussync/assets/default_avatar.png', 'image/png', 'default_avatar.png');

$post = array(
    'action' => 'create_post',
    'content' => 'Test multiple post from script',
    'post_media[]' => $cfile1,
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// send cookies for session? We don't have the session cookie, so feed_actions will return Unauthorized.
// I will just include feed_actions directly to debug, or fake the session.
