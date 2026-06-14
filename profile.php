<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require 'config.php';
require 'upload_helpers.php';

$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

$stmt = db_prepare("SELECT * FROM user WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $profile_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($res);

if (!$profile) {
    echo "User not found.";
    exit;
}

// Handle follow/unfollow
if (isset($_POST['toggle_follow']) && $_SESSION['user_id'] != $profile_id) {
    $uid = $_SESSION['user_id'];
    $stmt = db_prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $uid, $profile_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($res)) {
        $stmt = db_prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $uid, $profile_id);
        mysqli_stmt_execute($stmt);
    } else {
        $stmt = db_prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ii', $uid, $profile_id);
        mysqli_stmt_execute($stmt);
    }
    header("Location: profile.php?id=" . $profile_id);
    exit;
}

// Handle bio update
if (isset($_POST['update_bio']) && $_SESSION['user_id'] == $profile_id) {
    $bio = trim($_POST['bio']);
    $stmt = db_prepare("UPDATE user SET bio = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $bio, $profile_id);
    mysqli_stmt_execute($stmt);
    header("Location: profile.php?id=" . $profile_id);
    exit;
}

// Handle avatar upload
if (isset($_POST['upload_avatar']) && (int)$_SESSION['user_id'] == $profile_id) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $result = save_upload($_FILES['avatar'], 'avatar_' . $profile_id . '_');
        if ($result['ok']) {
            $new_name = $result['file'];
            $thumb_name = 'thumb_' . $new_name;
            $thumb_path = 'uploads/' . $thumb_name;
            create_thumbnail($result['path'], $thumb_path, 150);

            $stmt = db_prepare("UPDATE user SET avatar = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $new_name, $profile_id);
            mysqli_stmt_execute($stmt);
        }
    }
    header("Location: profile.php?id=" . $profile_id);
    exit;
}

// Stats
$stmt = db_prepare("SELECT COUNT(*) AS c FROM post WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $profile_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$post_count = $row['c'];

$stmt = db_prepare("SELECT COUNT(*) AS c FROM follows WHERE following_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $profile_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$followers = $row['c'];

$stmt = db_prepare("SELECT COUNT(*) AS c FROM follows WHERE follower_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $profile_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$following = $row['c'];

$is_following = false;
if ($_SESSION['user_id'] != $profile_id) {
    $uid = $_SESSION['user_id'];
    $stmt = db_prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $uid, $profile_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $is_following = mysqli_fetch_assoc($res) ? true : false;
}

// User posts
$stmt = db_prepare("SELECT post.*, user.name AS author_name FROM post JOIN user ON post.user_id = user.id WHERE post.user_id = ? ORDER BY post.created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $profile_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$posts = array();
while ($row = mysqli_fetch_assoc($res)) {
    $posts[] = $row;
}

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return $diff . "s ago";
    if ($diff < 3600) return floor($diff / 60) . "m ago";
    if ($diff < 86400) return floor($diff / 3600) . "h ago";
    if ($diff < 2592000) return floor($diff / 86400) . "d ago";
    return date("M d", strtotime($datetime));
}

function get_like_count($conn, $post_id) {
    $stmt = db_prepare("SELECT COUNT(*) AS c FROM likes WHERE post_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $post_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    return $row['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8') ?> - DEU Memeverse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="dark-theme">

<!-- Navbar -->
<nav class="top-nav">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a class="brand" href="index.php">
            <img src="images/deu-logo.svg" alt="DEU" width="34" height="34">
            <span>DEU <em>MEMEVERSE</em></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="profile.php?id=<?= $_SESSION['user_id'] ?>" class="top-nav-link">
                <div class="nav-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                <span><?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <a href="logout.php" class="top-nav-btn"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-7">

            <!-- Profile Header -->
            <div class="post-card text-center mb-4">
                <?php if (strlen($profile['avatar']) > 0) { ?>
                    <img src="uploads/<?= htmlspecialchars($profile['avatar'], ENT_QUOTES, 'UTF-8') ?>" class="profile-pic mx-auto mb-3" alt="avatar">
                <?php } else { ?>
                    <div class="avatar-lg mx-auto mb-3"><?= strtoupper(substr($profile['name'], 0, 1)) ?></div>
                <?php } ?>

                <?php if ($_SESSION['user_id'] == $profile_id) { ?>
                    <form method="post" enctype="multipart/form-data" action="profile.php?id=<?= $profile_id ?>" class="mb-3">
                        <label class="btn btn-sm btn-outline-light rounded-pill" style="cursor:pointer;">
                            <i class="bi bi-camera-fill"></i> Change Photo
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif" hidden onchange="this.form.submit()">
                        </label>
                        <input type="hidden" name="upload_avatar" value="1">
                    </form>
                <?php } ?>

                <h4 class="fw-bold mb-1"><?= htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                <p class="text-muted small mb-3">Student ID: <?= htmlspecialchars($profile['student_id'], ENT_QUOTES, 'UTF-8') ?></p>

                <p class="mb-3">
                    <?php if (strlen($profile['bio']) > 0) { ?>
                        <?= htmlspecialchars($profile['bio'], ENT_QUOTES, 'UTF-8') ?>
                    <?php } else { ?>
                        <span class="text-muted">No bio yet.</span>
                    <?php } ?>
                </p>

                <div class="d-flex justify-content-center gap-4 mb-3">
                    <div class="text-center">
                        <div class="fw-bold"><?= $post_count ?></div>
                        <small class="text-muted">Posts</small>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold"><?= $followers ?></div>
                        <small class="text-muted">Followers</small>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold"><?= $following ?></div>
                        <small class="text-muted">Following</small>
                    </div>
                </div>

                <?php if ($_SESSION['user_id'] != $profile_id) { ?>
                    <form method="post" class="d-inline">
                        <button type="submit" name="toggle_follow" class="btn btn-sm <?= $is_following ? 'btn-outline-secondary' : 'btn-primary' ?> rounded-pill px-4">
                            <?= $is_following ? 'Unfollow' : 'Follow' ?>
                        </button>
                    </form>
                <?php } else { ?>
                    <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="document.getElementById('editBio').style.display = document.getElementById('editBio').style.display === 'block' ? 'none' : 'block'">
                        Edit Bio
                    </button>
                    <div id="editBio" style="display:none;margin-top:16px;">
                        <form method="post">
                            <textarea name="bio" class="form-control mb-2" rows="2" placeholder="Write something about yourself..."><?= htmlspecialchars($profile['bio'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            <button type="submit" name="update_bio" class="btn btn-success btn-sm rounded-pill px-3">Save</button>
                        </form>
                    </div>
                <?php } ?>
            </div>

            <!-- User Posts -->
            <h6 class="text-muted mb-3">Posts by <?= htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8') ?></h6>

            <?php if (count($posts) == 0) { ?>
                <div class="post-card text-center text-muted">No posts yet.</div>
            <?php } ?>

            <?php foreach ($posts as $p) { ?>
                <div class="post-card mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm me-3"><?= strtoupper(substr($p['author_name'], 0, 1)) ?></div>
                        <div>
                            <span class="fw-bold"><?= htmlspecialchars($p['author_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <div class="text-muted small"><?= time_ago($p['created_at']) ?></div>
                        </div>
                    </div>

                    <?php if (strlen($p['message']) > 0) { ?>
                        <p class="mb-3" style="white-space:pre-wrap;"><?= htmlspecialchars($p['message'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php } ?>

                    <?php if (strlen($p['image_file']) > 0) { ?>
                        <div class="mb-3">
                            <?php if (substr($p['image_file'], 0, 4) == 'gif:') { ?>
                                <img src="<?= htmlspecialchars(substr($p['image_file'], 4), ENT_QUOTES, 'UTF-8') ?>" class="post-img" alt="gif" style="width:100%;border-radius:14px;">
                            <?php } else { ?>
                                <img src="uploads/<?= htmlspecialchars($p['image_file'], ENT_QUOTES, 'UTF-8') ?>" class="post-img" alt="post" style="width:100%;border-radius:14px;">
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <div class="d-flex align-items-center gap-3 post-actions">
                        <span class="action-btn"><i class="bi bi-heart"></i> <?= get_like_count($conn, $p['id']) ?></span>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

<nav class="mobile-nav">
    <a href="index.php"><i class="bi bi-house-fill"></i> Home</a>
    <a href="users.php"><i class="bi bi-people-fill"></i> Users</a>
    <a href="profile.php?id=<?= $_SESSION['user_id'] ?>"><i class="bi bi-person-fill"></i> Profile</a>
    <a href="logout.php"><i class="bi bi-box-arrow-left"></i> Log Out</a>
</nav>

</body>
</html>
