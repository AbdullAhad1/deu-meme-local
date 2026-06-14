<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require 'config.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = db_prepare("SELECT post.*, user.name AS author_name FROM post JOIN user ON post.user_id = user.id WHERE post.id = ?");
mysqli_stmt_bind_param($stmt, 'i', $post_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($res);

if (!$post) {
    echo "Post not found.";
    exit;
}


// Handle like
if (isset($_POST['like_post'])) {
    $uid = $_SESSION['user_id'];
    $stmt = db_prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $post_id, $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($res)) {
        $stmt = db_prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $post_id, $uid);
        mysqli_stmt_execute($stmt);
    } else {
        $stmt = db_prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ii', $post_id, $uid);
        mysqli_stmt_execute($stmt);
    }
    header("Location: post.php?id=" . $post_id);
    exit;
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

function user_liked($conn, $post_id, $user_id) {
    $stmt = db_prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $post_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($res) ? true : false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post - DEU Memeverse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <img src="images/deu-logo.svg" alt="DEU" width="32" height="32" class="me-2" style="border-radius:50%;">
            DEU <span style="background:linear-gradient(90deg,#2962ff,#6c47ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Memeverse</span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="profile.php?id=<?= $_SESSION['user_id'] ?>" class="nav-link-custom">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill">Log Out</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-7">

            <a href="index.php" class="text-decoration-none text-muted mb-3 d-inline-block">
                <i class="bi bi-arrow-left"></i> Back to Feed
            </a>

            <!-- Post -->
            <div class="post-card mb-4">
                <div class="d-flex align-items-center mb-3">
                    <a href="profile.php?id=<?= $post['user_id'] ?>" class="text-decoration-none">
                        <div class="avatar-sm me-3"><?= strtoupper(substr($post['author_name'], 0, 1)) ?></div>
                    </a>
                    <div>
                        <a href="profile.php?id=<?= $post['user_id'] ?>" class="fw-bold text-dark text-decoration-none">
                            <?= htmlspecialchars($post['author_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <div class="text-muted small"><?= time_ago($post['created_at']) ?></div>
                    </div>
                </div>

                <?php if (strlen($post['message']) > 0) { ?>
                    <p class="mb-3" style="white-space:pre-wrap;"><?= htmlspecialchars($post['message'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php } ?>

                <?php if (strlen($post['image_file']) > 0) { ?>
                    <div class="mb-3">
                        <?php if (substr($post['image_file'], 0, 4) == 'gif:') { ?>
                            <img src="<?= htmlspecialchars(substr($post['image_file'], 4), ENT_QUOTES, 'UTF-8') ?>" class="post-img" alt="gif" style="width:100%;border-radius:14px;">
                        <?php } else { ?>
                            <img src="uploads/<?= htmlspecialchars($post['image_file'], ENT_QUOTES, 'UTF-8') ?>" class="post-img" alt="post" style="width:100%;border-radius:14px;">
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="d-flex align-items-center gap-3 post-actions">
                    <form method="post" class="d-inline">
                        <button type="submit" name="like_post" class="action-btn <?= user_liked($conn, $post_id, $_SESSION['user_id']) ? 'liked' : '' ?>">
                            <i class="bi <?= user_liked($conn, $post_id, $_SESSION['user_id']) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                            <?= get_like_count($conn, $post_id) ?>
                        </button>
                    </form>
                </div>
            </div>

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
