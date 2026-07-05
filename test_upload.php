<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode($_FILES);
    exit;
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="post_media[]" multiple>
    <button type="submit">Upload</button>
</form>
