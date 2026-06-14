<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require 'config.php';

// Get all users with their post count
$result = db_query("SELECT user.*, (SELECT COUNT(*) FROM post WHERE post.user_id = user.id) AS post_count FROM user ORDER BY user.created_at DESC");
$users = array();
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users - DEU Memeverse</title>
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
            <a href="index.php" class="top-nav-link">
                <i class="bi bi-house-fill"></i> <span>Home</span>
            </a>
            <a href="logout.php" class="top-nav-btn"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</nav>

<div class="container mt-4" style="position:relative; z-index:1;">
    <div class="row justify-content-center">
        <div class="col-md-10">

            <div class="post-card mb-4">
                <h4 class="fw-bold mb-1"><i class="bi bi-people-fill"></i> All Registered Users</h4>
                <p style="color:var(--text-muted);" class="mb-0"><?= count($users) ?> students on DEU Memeverse</p>
            </div>

            <div class="post-card">
                <div class="table-responsive">
                    <table class="table table-dark table-borderless align-middle mb-0" style="background:transparent;">
                        <thead>
                            <tr style="color:var(--text-muted); font-size:12px; text-transform:uppercase; letter-spacing:1px;">
                                <th>#</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Student ID</th>
                                <th>Bio</th>
                                <th>Posts</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($users as $u) { ?>
                                <tr>
                                    <td style="color:var(--text-muted);"><?= $i++ ?></td>
                                    <td>
                                        <?php if (strlen($u['avatar']) > 0) { ?>
                                            <img src="uploads/<?= htmlspecialchars($u['avatar'], ENT_QUOTES, 'UTF-8') ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--accent);">
                                        <?php } else { ?>
                                            <div style="width:40px;height:40px;border-radius:50%;background:var(--gradient);color:#fff;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;">
                                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="profile.php?id=<?= $u['id'] ?>" style="color:var(--text);text-decoration:none;font-weight:600;">
                                            <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td style="color:var(--text-muted);"><?= htmlspecialchars($u['student_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="color:var(--text-muted);font-size:13px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= strlen($u['bio']) > 0 ? htmlspecialchars($u['bio'], ENT_QUOTES, 'UTF-8') : '—' ?>
                                    </td>
                                    <td><span style="background:var(--accent);color:#fff;padding:3px 10px;border-radius:50rem;font-size:12px;font-weight:600;"><?= $u['post_count'] ?></span></td>
                                    <td style="color:var(--text-muted);font-size:12px;"><?= date("M d, Y", strtotime($u['created_at'])) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
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
