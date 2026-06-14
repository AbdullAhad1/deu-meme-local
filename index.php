<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require 'config.php';
require 'upload_helpers.php';

$post_error = "";

// Handle new post
if (isset($_POST['create_post'])) {
    $message = trim($_POST['message']);
    $image_file = null;
    $thumb_file = null;

    // Check if a GIF was selected from Giphy
    $gif_url = isset($_POST['gif_url']) ? trim($_POST['gif_url']) : '';
    if (strlen($gif_url) > 0) {
        $image_file = 'gif:' . $gif_url;
    }

    if ($image_file == null && isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $result = save_upload($_FILES['image'], 'img_');
        if (!$result['ok']) {
            $post_error = $result['error'];
        } else {
            $image_file = $result['file'];
            $thumb_name = 'thumb_' . $result['file'];
            $thumb_path = 'uploads/' . $thumb_name;
            if (create_thumbnail($result['path'], $thumb_path, 300)) {
                $thumb_file = $thumb_name;
            }
        }
    }

    if (strlen($post_error) == 0) {
        if (strlen($message) == 0 && $image_file == null) {
            $post_error = "Write something or attach an image.";
        } else {
            $uid = $_SESSION['user_id'];
            $stmt = db_prepare("INSERT INTO post (user_id, message, image_file, thumbnail_file) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'isss', $uid, $message, $image_file, $thumb_file);
            mysqli_stmt_execute($stmt);
            header("Location: index.php");
            exit;
        }
    }
}

// Handle like
if (isset($_POST['like_post'])) {
    $post_id = (int)$_POST['post_id'];
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
    header("Location: index.php");
    exit;
}

// Load posts
$result = db_query("SELECT post.*, user.name AS author_name, user.avatar AS author_avatar FROM post JOIN user ON post.user_id = user.id ORDER BY post.created_at DESC");
$posts = array();
while ($row = mysqli_fetch_assoc($result)) {
    $posts[] = $row;
}

// Load recent users for sidebar
$result = db_query("SELECT * FROM user ORDER BY created_at DESC LIMIT 5");
$recent_users = array();
while ($row = mysqli_fetch_assoc($result)) {
    $recent_users[] = $row;
}

// Get current user post count
$uid = $_SESSION['user_id'];
$stmt = db_prepare("SELECT COUNT(*) AS c FROM post WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$my_post_count = $row['c'];

// Get current user avatar
$stmt = db_prepare("SELECT avatar FROM user WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$my_avatar = $row ? $row['avatar'] : '';

// Get current user follower count
$stmt = db_prepare("SELECT COUNT(*) AS c FROM follows WHERE following_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$my_followers = $row['c'];

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
    <title>DEU Memeverse - Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="dark-theme">

<!-- Top Navbar -->
<nav class="top-nav">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a class="brand" href="index.php">
            <img src="images/deu-logo.svg" alt="DEU" width="34" height="34">
            <span>DEU <em>MEMEVERSE</em></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="admin.php?logout=1" class="top-nav-link">
                <i class="bi bi-shield-lock-fill"></i> <span>Admin</span>
            </a>
            <a href="profile.php?id=<?= $_SESSION['user_id'] ?>" class="top-nav-link">
                <?php if (strlen($my_avatar) > 0) { ?>
                    <img src="uploads/<?= htmlspecialchars($my_avatar, ENT_QUOTES, 'UTF-8') ?>" class="nav-avatar-img">
                <?php } else { ?>
                    <div class="nav-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                <?php } ?>
                <span><?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <a href="logout.php" class="top-nav-btn"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>

<div class="main-layout">
    <!-- Left Sidebar -->
    <aside class="sidebar-left">
        <div class="sidebar-profile">
            <?php if (strlen($my_avatar) > 0) { ?>
                <img src="uploads/<?= htmlspecialchars($my_avatar, ENT_QUOTES, 'UTF-8') ?>" class="sidebar-avatar-img">
            <?php } else { ?>
                <div class="sidebar-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
            <?php } ?>
            <h6 class="sidebar-name"><?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?></h6>
            <span class="sidebar-id"><?= htmlspecialchars($_SESSION['student_id'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="sidebar-badge">DEU Student</span>
        </div>

        <div class="sidebar-stats">
            <div class="stat-item">
                <div class="stat-num"><?= $my_post_count ?></div>
                <div class="stat-label">Posts</div>
            </div>
            <div class="stat-item">
                <div class="stat-num"><?= $my_followers ?></div>
                <div class="stat-label">Followers</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="sidebar-link active"><i class="bi bi-house-fill"></i> Home</a>
            <a href="profile.php?id=<?= $_SESSION['user_id'] ?>" class="sidebar-link"><i class="bi bi-person-fill"></i> My Profile</a>
            <span class="sidebar-link" onclick="openQrCode()" style="cursor:pointer;"><i class="bi bi-qr-code"></i> Invite Friends</span>
            <a href="admin.php?logout=1" class="sidebar-link"><i class="bi bi-shield-lock-fill"></i> Admin</a>
            <a href="logout.php" class="sidebar-link"><i class="bi bi-box-arrow-left"></i> Log Out</a>
        </nav>
    </aside>

    <!-- Center Feed -->
    <main class="feed-center">

        <!-- Create Post -->
        <div class="feed-card create-post-card" style="position:relative;z-index:100;">
            <div class="d-flex align-items-center gap-3 mb-3">
                <?php if (strlen($my_avatar) > 0) { ?>
                    <img src="uploads/<?= htmlspecialchars($my_avatar, ENT_QUOTES, 'UTF-8') ?>" class="feed-avatar-img">
                <?php } else { ?>
                    <div class="feed-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                <?php } ?>
                <span class="create-placeholder">What's cooking, DEU?</span>
            </div>

            <?php if (strlen($post_error) > 0) { ?>
                <div class="alert alert-danger py-2 mb-3" style="border-radius:10px;"><?= htmlspecialchars($post_error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php } ?>

            <form method="post" enctype="multipart/form-data" id="postForm">
                <textarea name="message" class="feed-textarea" rows="2" placeholder="Share a meme, a thought, or a rant..."></textarea>
                <input type="hidden" name="gif_url" id="gifUrlInput" value="">

                <!-- GIF Preview -->
                <div id="gifPreview" style="display:none;margin-bottom:12px;position:relative;">
                    <img id="gifPreviewImg" src="" style="width:100%;border-radius:12px;max-height:200px;object-fit:cover;">
                    <button type="button" onclick="removeGif()" style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.7);color:#fff;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:14px;">✕</button>
                </div>

                <div class="create-actions">
                    <div class="create-options">
                        <label class="create-option">
                            <i class="bi bi-image-fill"></i> Photo
                            <input type="file" name="image" accept="image/jpeg,image/png,image/gif" hidden>
                        </label>
                        <div style="position:relative;display:inline-block;">
                            <span class="create-option" onclick="toggleGifPicker()" style="cursor:pointer;">
                                <i class="bi bi-filetype-gif"></i> GIF
                            </span>
                            <!-- GIF Picker Dropdown -->
                            <div id="gifDropdown" style="display:none;position:absolute;top:100%;left:0;margin-top:8px;width:340px;background:var(--bg-card-solid);border:1px solid var(--border);border-radius:16px;padding:14px;box-shadow:0 10px 40px rgba(0,0,0,0.5);z-index:999;">
                                <input type="text" id="gifSearch" placeholder="Search GIFs..." oninput="searchGifs()" style="width:100%;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:10px;padding:8px 12px;color:var(--text);font-size:13px;margin-bottom:10px;">
                                <div id="gifResults" style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px;max-height:250px;overflow-y:auto;"></div>
                                <p style="color:var(--text-muted);font-size:10px;text-align:center;margin-top:8px;margin-bottom:0;">Powered by GIPHY</p>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="create_post" class="post-btn">Post</button>
                </div>
            </form>


        </div>

        <!-- Posts -->
        <?php if (count($posts) == 0) { ?>
            <div class="feed-card text-center" style="color:rgba(255,255,255,0.5);">
                <i class="bi bi-emoji-smile" style="font-size:48px;"></i>
                <p class="mt-2 mb-0">No posts yet. Be the first to post!</p>
            </div>
        <?php } ?>

        <?php foreach ($posts as $p) { ?>
            <div class="feed-card">
                <div class="d-flex align-items-center mb-3">
                    <a href="profile.php?id=<?= $p['user_id'] ?>" class="text-decoration-none me-3">
                        <?php if (strlen($p['author_avatar']) > 0) { ?>
                            <img src="uploads/<?= htmlspecialchars($p['author_avatar'], ENT_QUOTES, 'UTF-8') ?>" class="feed-avatar-img">
                        <?php } else { ?>
                            <div class="feed-avatar"><?= strtoupper(substr($p['author_name'], 0, 1)) ?></div>
                        <?php } ?>
                    </a>
                    <div>
                        <a href="profile.php?id=<?= $p['user_id'] ?>" class="post-author">
                            <?= htmlspecialchars($p['author_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <div class="post-time"><?= time_ago($p['created_at']) ?></div>
                    </div>
                </div>

                <?php if (strlen($p['message']) > 0) { ?>
                    <p class="post-text"><?= htmlspecialchars($p['message'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php } ?>

                <?php if (strlen($p['image_file']) > 0) { ?>
                    <div class="post-image-wrap">
                        <?php if (substr($p['image_file'], 0, 4) == 'gif:') { ?>
                            <img src="<?= htmlspecialchars(substr($p['image_file'], 4), ENT_QUOTES, 'UTF-8') ?>" class="post-image" alt="gif">
                        <?php } else { ?>
                            <img src="uploads/<?= htmlspecialchars($p['image_file'], ENT_QUOTES, 'UTF-8') ?>" class="post-image" alt="meme">
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="post-actions">
                    <form method="post" class="d-inline">
                        <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                        <button type="submit" name="like_post" class="like-btn <?= user_liked($conn, $p['id'], $_SESSION['user_id']) ? 'liked' : '' ?>">
                            <i class="bi <?= user_liked($conn, $p['id'], $_SESSION['user_id']) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                            <span><?= get_like_count($conn, $p['id']) ?></span>
                        </button>
                    </form>
                </div>
            </div>
        <?php } ?>

    </main>

    <!-- Right Sidebar -->
    <aside class="sidebar-right">
        <div class="sidebar-card">
            <div class="sidebar-card-header">
                <span>🎓 DEU Students</span>
            </div>
            <?php foreach ($recent_users as $u) { ?>
                <a href="profile.php?id=<?= $u['id'] ?>" class="student-item">
                    <?php if (strlen($u['avatar']) > 0) { ?>
                        <img src="uploads/<?= htmlspecialchars($u['avatar'], ENT_QUOTES, 'UTF-8') ?>" class="student-avatar-img">
                    <?php } else { ?>
                        <div class="student-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                    <?php } ?>
                    <div>
                        <div class="student-name"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="student-id"><?= htmlspecialchars($u['student_id'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </a>
            <?php } ?>
        </div>
    </aside>
</div>

<script>
var gifApiKey = 'GlVGYHkr3WSBnllca54iNt0yFbjz7L65';
var searchTimeout = null;
var gifOpen = false;

function toggleGifPicker() {
    var dropdown = document.getElementById('gifDropdown');
    gifOpen = !gifOpen;
    if (gifOpen) {
        dropdown.style.display = 'block';
        document.getElementById('gifSearch').value = '';
        loadTrendingGifs();
        document.getElementById('gifSearch').focus();
    } else {
        dropdown.style.display = 'none';
    }
}

function closeGifPicker() {
    document.getElementById('gifDropdown').style.display = 'none';
    gifOpen = false;
}

function loadTrendingGifs() {
    fetch('https://api.giphy.com/v1/gifs/trending?api_key=' + gifApiKey + '&limit=12&rating=pg')
        .then(function(r) { return r.json(); })
        .then(function(data) { renderGifs(data.data); });
}

function searchGifs() {
    clearTimeout(searchTimeout);
    var query = document.getElementById('gifSearch').value.trim();
    if (query.length == 0) {
        loadTrendingGifs();
        return;
    }
    searchTimeout = setTimeout(function() {
        fetch('https://api.giphy.com/v1/gifs/search?api_key=' + gifApiKey + '&q=' + encodeURIComponent(query) + '&limit=12&rating=pg')
            .then(function(r) { return r.json(); })
            .then(function(data) { renderGifs(data.data); });
    }, 400);
}

function renderGifs(gifs) {
    var container = document.getElementById('gifResults');
    container.innerHTML = '';
    for (var i = 0; i < gifs.length; i++) {
        var gif = gifs[i];
        var url = gif.images.fixed_height_small.url;
        var img = document.createElement('img');
        img.src = url;
        img.style.cssText = 'width:100%;border-radius:8px;cursor:pointer;height:90px;object-fit:cover;';
        img.setAttribute('data-url', gif.images.original.url);
        img.onclick = function() { selectGif(this.getAttribute('data-url')); };
        container.appendChild(img);
    }
}

function selectGif(url) {
    document.getElementById('gifUrlInput').value = url;
    document.getElementById('gifPreviewImg').src = url;
    document.getElementById('gifPreview').style.display = 'block';
    closeGifPicker();
}

function removeGif() {
    document.getElementById('gifUrlInput').value = '';
    document.getElementById('gifPreviewImg').src = '';
    document.getElementById('gifPreview').style.display = 'none';
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('gifDropdown');
    if (gifOpen && !dropdown.contains(e.target) && !e.target.closest('.create-option')) {
        closeGifPicker();
    }
});
</script>
<!-- QR Code Popup -->
<div id="qrOverlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);align-items:center;justify-content:center;" onclick="closeQrCode(event)">
    <div style="background:var(--bg-card-solid);border:1px solid var(--border);border-radius:24px;padding:32px;text-align:center;max-width:360px;width:90%;">
        <h3 style="color:var(--text);font-weight:800;margin-bottom:6px;">📱 Scan to Join</h3>
        <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px;">Share this QR code with your classmates</p>
        <div style="background:#fff;border-radius:16px;padding:16px;display:inline-block;margin-bottom:16px;">
            <img id="qrImage" src="" alt="QR Code" style="width:200px;height:200px;">
        </div>
        <p style="color:var(--text-muted);font-size:11px;margin-bottom:8px;">Or share this link:</p>
        <div style="background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:10px;padding:8px 14px;font-size:12px;color:var(--text);word-break:break-all;" id="qrLink"></div>
        <button onclick="closeQrCode()" style="margin-top:16px;background:var(--gradient);color:#fff;border:none;padding:8px 24px;border-radius:50rem;font-weight:600;font-size:13px;cursor:pointer;">Close</button>
    </div>
</div>

<script>
function getLocalUrl() {
    var host = window.location.hostname;
    var port = window.location.port ? ':' + window.location.port : '';
    return 'http://' + host + port + '/auth.php';
}

function openQrCode() {
    var url = getLocalUrl();
    var qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(url);
    document.getElementById('qrImage').src = qrApi;
    document.getElementById('qrLink').textContent = url;
    document.getElementById('qrOverlay').style.display = 'flex';
}

function closeQrCode(e) {
    if (!e || e.target === document.getElementById('qrOverlay')) {
        document.getElementById('qrOverlay').style.display = 'none';
    }
}
</script>

<nav class="mobile-nav">
    <a href="index.php"><i class="bi bi-house-fill"></i> Home</a>
    <a href="users.php"><i class="bi bi-people-fill"></i> Users</a>
    <a href="profile.php?id=<?= $_SESSION['user_id'] ?>"><i class="bi bi-person-fill"></i> Profile</a>
    <a href="logout.php"><i class="bi bi-box-arrow-left"></i> Log Out</a>
</nav>

</body>
</html>
