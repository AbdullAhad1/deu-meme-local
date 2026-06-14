<?php
session_start();

require 'config.php';

// Admin credentials (hardcoded)
$admin_username = 'admin';
$admin_password = 'deu2024';

$error = "";
$is_admin = false;

// Handle admin login
if (isset($_POST['admin_login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username == $admin_username && $password == $admin_password) {
        $_SESSION['is_admin'] = true;
        $is_admin = true;
    } else {
        $error = "Wrong admin credentials";
    }
}

// Check if already authenticated this session
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true) {
    $is_admin = true;
}

// Handle admin logout
if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    header("Location: admin.php");
    exit;
}

// If coming from index page (fresh visit), require login again
if (!$is_admin && !isset($_POST['admin_login'])) {
    unset($_SESSION['is_admin']);
}

// Handle delete post
if ($is_admin && isset($_POST['delete_post'])) {
    $post_id = (int)$_POST['post_id'];
    // Delete likes for this post first
    $stmt = db_prepare("DELETE FROM likes WHERE post_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $post_id);
    mysqli_stmt_execute($stmt);
    // Delete the post
    $stmt = db_prepare("DELETE FROM post WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $post_id);
    mysqli_stmt_execute($stmt);
    header("Location: admin.php");
    exit;
}

// Handle delete user
if ($is_admin && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    // Delete user's likes
    $stmt = db_prepare("DELETE FROM likes WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    // Delete user's posts' likes
    $stmt = db_prepare("DELETE FROM likes WHERE post_id IN (SELECT id FROM post WHERE user_id = ?)");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    // Delete user's posts
    $stmt = db_prepare("DELETE FROM post WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    // Delete follows
    $stmt = db_prepare("DELETE FROM follows WHERE follower_id = ? OR following_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    // Delete user
    $stmt = db_prepare("DELETE FROM user WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    header("Location: admin.php");
    exit;
}

// Load data if admin
$posts = array();
$users = array();
if ($is_admin) {
    $result = db_query("SELECT post.*, user.name AS author_name FROM post JOIN user ON post.user_id = user.id ORDER BY post.created_at DESC");
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }

    $result = db_query("SELECT * FROM user ORDER BY created_at DESC");
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - DEU Memeverse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .admin-login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 32px;
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(12px);
        }
        .admin-input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--text);
            font-size: 14px;
        }
        .admin-input:focus {
            outline: none;
            border-color: var(--accent);
        }
        .admin-input::placeholder { color: var(--text-muted); }
        .admin-btn {
            width: 100%;
            background: linear-gradient(90deg, #e53935, #d81b60);
            color: #fff;
            border: none;
            border-radius: 50rem;
            padding: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
        }
        .admin-btn:hover { opacity: 0.9; }
        .delete-btn {
            background: rgba(229, 57, 53, 0.15);
            border: 1px solid rgba(229, 57, 53, 0.3);
            color: #e53935;
            border-radius: 8px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .delete-btn:hover { background: rgba(229, 57, 53, 0.25); }
        .admin-table { color: var(--text); }
        .admin-table th { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border); }
        .admin-table td { border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; font-size: 14px; }
        .tab-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 8px 20px;
            border-radius: 50rem;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .tab-btn.active, .tab-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
    </style>
</head>
<body class="dark-theme">

<?php if (!$is_admin) { ?>
<!-- Admin Login -->
<div class="admin-login-wrap" style="position:relative;z-index:1;">
    <div class="admin-login-card text-center">
        <div style="font-size:48px;margin-bottom:16px;">🔒</div>
        <h3 style="color:var(--text);font-weight:800;margin-bottom:6px;">Admin Panel</h3>
        <p style="color:var(--text-muted);font-size:13px;margin-bottom:24px;">DEU Memeverse - Authorized access only</p>

        <?php if (strlen($error) > 0) { ?>
            <div class="alert alert-danger py-2 mb-3" style="border-radius:10px;font-size:13px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php } ?>

        <form method="post">
            <input type="text" name="username" class="admin-input mb-3" placeholder="Admin Username">
            <input type="password" name="password" class="admin-input mb-4" placeholder="Admin Password">
            <button type="submit" name="admin_login" class="admin-btn">
                <i class="bi bi-shield-lock-fill"></i> Login as Admin
            </button>
        </form>

        <a href="index.php" style="color:var(--text-muted);font-size:12px;display:block;margin-top:16px;text-decoration:none;">← Back to Memeverse</a>
    </div>
</div>

<?php } else { ?>
<!-- Admin Dashboard -->
<nav class="top-nav">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a class="brand" href="admin.php">
            <span>🔒 Admin Panel</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="top-nav-link"><i class="bi bi-house"></i> Site</a>
            <a href="admin.php?logout=1" class="top-nav-btn" title="Logout Admin"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>

<div class="container mt-4" style="position:relative;z-index:1;">

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="post-card text-center">
                <div style="font-size:32px;font-weight:800;color:var(--accent);"><?= count($users) ?></div>
                <div style="color:var(--text-muted);font-size:13px;">Total Users</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="post-card text-center">
                <div style="font-size:32px;font-weight:800;color:var(--pink);"><?= count($posts) ?></div>
                <div style="color:var(--text-muted);font-size:13px;">Total Posts</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="post-card text-center">
                <div style="font-size:32px;font-weight:800;color:#4caf50;">Active</div>
                <div style="color:var(--text-muted);font-size:13px;">System Status</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="d-flex gap-2 mb-4">
        <a href="admin.php#posts" class="tab-btn active"><i class="bi bi-file-post"></i> Posts (<?= count($posts) ?>)</a>
        <a href="admin.php#users" class="tab-btn"><i class="bi bi-people"></i> Users (<?= count($users) ?>)</a>
    </div>

    <!-- All Posts -->
    <div class="post-card mb-4" id="posts">
        <h5 style="color:var(--text);font-weight:700;margin-bottom:16px;"><i class="bi bi-file-post-fill"></i> All Posts</h5>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Author</th>
                        <th>Message</th>
                        <th>Image</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p) { ?>
                        <tr>
                            <td style="color:var(--text-muted);"><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['author_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-muted);">
                                <?= strlen($p['message']) > 0 ? htmlspecialchars($p['message'], ENT_QUOTES, 'UTF-8') : '—' ?>
                            </td>
                            <td>
                                <?php if (strlen($p['image_file']) > 0) { ?>
                                    <img src="uploads/<?= htmlspecialchars($p['image_file'], ENT_QUOTES, 'UTF-8') ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover;">
                                <?php } else { ?>
                                    <span style="color:var(--text-muted);">—</span>
                                <?php } ?>
                            </td>
                            <td style="color:var(--text-muted);font-size:12px;"><?= date("M d, H:i", strtotime($p['created_at'])) ?></td>
                            <td>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this post?')">
                                    <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="delete_post" class="delete-btn">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (count($posts) == 0) { ?>
                        <tr><td colspan="6" style="color:var(--text-muted);text-align:center;">No posts yet.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- All Users -->
    <div class="post-card mb-4" id="users">
        <h5 style="color:var(--text);font-weight:700;margin-bottom:16px;"><i class="bi bi-people-fill"></i> All Users</h5>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u) { ?>
                        <tr>
                            <td style="color:var(--text-muted);"><?= $u['id'] ?></td>
                            <td>
                                <?php if (strlen($u['avatar']) > 0) { ?>
                                    <img src="uploads/<?= htmlspecialchars($u['avatar'], ENT_QUOTES, 'UTF-8') ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                <?php } else { ?>
                                    <div style="width:32px;height:32px;border-radius:50%;background:var(--gradient);color:#fff;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                <?php } ?>
                            </td>
                            <td><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="color:var(--text-muted);"><?= htmlspecialchars($u['student_id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="color:var(--text-muted);font-size:12px;"><?= date("M d, Y", strtotime($u['created_at'])) ?></td>
                            <td>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this user and all their posts?')">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" name="delete_user" class="delete-btn">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php } ?>

<nav class="mobile-nav">
    <a href="index.php"><i class="bi bi-house-fill"></i> Home</a>
    <a href="users.php"><i class="bi bi-people-fill"></i> Users</a>
    <a href="profile.php?id=<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>"><i class="bi bi-person-fill"></i> Profile</a>
    <a href="logout.php"><i class="bi bi-box-arrow-left"></i> Log Out</a>
</nav>

</body>
</html>
