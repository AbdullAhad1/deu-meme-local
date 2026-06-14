<?php
session_start();

$error = "";

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $student_id = trim($_POST['student_id']);
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (strlen($name) == 0 || strlen($student_id) == 0 || strlen($password) == 0) {
        $error = "Please fill all fields";
    } else {
        require 'config.php';

        // Check if name already exists (case-insensitive)
        $stmt = db_prepare("SELECT * FROM user WHERE LOWER(name) = LOWER(?)");
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $existing_name = mysqli_fetch_assoc($res);

        // Check if student_id already exists
        $stmt = db_prepare("SELECT * FROM user WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt, 's', $student_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $existing_id = mysqli_fetch_assoc($res);

        if ($existing_name) {
            // Name exists - student_id and password must match
            $stored_hash = isset($existing_name['password']) ? $existing_name['password'] : '';

            if (strlen($stored_hash) == 0) {
                // Old account with no password: save the entered password now
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db_prepare("UPDATE user SET password = ? WHERE id = ?");
                $uid = $existing_name['id'];
                mysqli_stmt_bind_param($stmt, 'si', $hash, $uid);
                mysqli_stmt_execute($stmt);

                $_SESSION['user_id'] = $existing_name['id'];
                $_SESSION['name'] = $existing_name['name'];
                $_SESSION['student_id'] = $existing_name['student_id'];
                header("Location: loading.php");
                exit;
            }

            if ($existing_name['student_id'] == $student_id && password_verify($password, $stored_hash)) {
                $_SESSION['user_id'] = $existing_name['id'];
                $_SESSION['name'] = $existing_name['name'];
                $_SESSION['student_id'] = $existing_name['student_id'];
                header("Location: loading.php");
                exit;
            } else {
                $error = "Wrong Student ID or password for this name";
            }
        } else if ($existing_id) {
            // Student ID taken by someone else
            $error = "This Student ID is already registered with another name";
        } else {
            // Brand new user - register
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db_prepare("INSERT INTO user (student_id, name, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sss', $student_id, $name, $hash);
            mysqli_stmt_execute($stmt);
            $new_id = db_insert_id();

            $_SESSION['user_id'] = $new_id;
            $_SESSION['name'] = $name;
            $_SESSION['student_id'] = $student_id;
            header("Location: loading.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEU Board - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: url('images/bg.png') center center / cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
            position: relative;
            overflow-x: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        /* Decorative blobs */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            filter: blur(60px);
            pointer-events: none;
        }
        body::before { top: -150px; left: -150px; }
        body::after { bottom: -150px; right: -150px; }

        .meme-card {
            background: #ffffff;
            border-radius: 28px;
            padding: 40px 34px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
        }

        .meme-logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #ffffff;
            border: 3px solid #e3eaff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 4px 16px rgba(41, 98, 255, 0.2);
            overflow: hidden;
        }
        .meme-logo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .meme-uni {
            display: block;
            text-align: center;
            font-size: 11px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #7a8bb5;
            margin-bottom: 16px;
        }

        .meme-title {
            text-align: center;
            font-size: 34px;
            font-weight: 900;
            color: #0a1f44;
            letter-spacing: -1px;
            margin-bottom: 6px;
        }
        .meme-title span {
            background: linear-gradient(90deg, #2962ff, #6c47ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .meme-subtitle {
            text-align: center;
            color: #6b7c9e;
            font-size: 15px;
            margin-bottom: 20px;
        }
        .meme-subtitle strong { color: #2962ff; }

        .meme-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
            color: #2962ff;
        }
        .meme-divider .line {
            height: 1px;
            width: 70px;
            background: #e3eaff;
        }

        .meme-flow {
            text-align: center;
            color: #7a8bb5;
            font-size: 13px;
            font-style: italic;
            margin-bottom: 28px;
        }
        .meme-flow i { color: #2962ff; margin: 0 4px; }

        .meme-label {
            display: block;
            font-weight: 700;
            color: #0a1f44;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .meme-label i { color: #2962ff; margin-right: 6px; }

        .meme-input {
            position: relative;
            margin-bottom: 20px;
        }
        .meme-input > i {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            color: #9aa7c7;
            font-size: 18px;
        }
        .meme-input input {
            width: 100%;
            border: 1.5px solid #e3eaff;
            background: #f7f9ff;
            border-radius: 14px;
            padding: 14px 16px 14px 48px;
            font-size: 15px;
            color: #0a1f44;
            transition: all 0.2s ease;
        }
        .meme-input input::placeholder { color: #b0bdd4; }
        .meme-input input:focus {
            outline: none;
            border-color: #2962ff;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(41, 98, 255, 0.1);
        }

        .meme-btn {
            width: 100%;
            border: none;
            border-radius: 50rem;
            padding: 15px 20px;
            font-size: 17px;
            font-weight: 700;
            color: #ffffff;
            cursor: pointer;
            background: linear-gradient(90deg, #2962ff, #6c47ff);
            box-shadow: 0 10px 28px rgba(41, 98, 255, 0.35);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }
        .meme-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 32px rgba(41, 98, 255, 0.45);
        }
        .meme-btn:active {
            transform: translateY(0);
        }

        .meme-footer-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 28px 0 20px;
            color: #2962ff;
        }
        .meme-footer-divider .line {
            height: 1px;
            width: 70px;
            background: #e3eaff;
        }

        .meme-alive {
            background: #f4f7ff;
            border: 1.5px solid #e3eaff;
            border-radius: 16px;
            padding: 14px 18px;
        }

        .alert-danger {
            border-radius: 12px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="meme-card">
    <!-- Logo -->
    <div class="meme-logo">
        <img src="images/deu-logo.svg" alt="DEU Logo">
    </div>

    <span class="meme-uni">Dong-Eui University</span>

    <h1 class="meme-title">DEU <span>MEMEVERSE</span></h1>
    <p class="meme-subtitle">Dong-Eui <strong>students</strong> only</p>

    <div class="meme-divider">
        <span class="line"></span>
        <span class="line"></span>
    </div>



    <?php if (strlen($error) > 0) { ?>
        <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>

    <form method="post">
        <label class="meme-label">
            <i class="bi bi-person-circle"></i> Your Name
        </label>
        <div class="meme-input">
            <i class="bi bi-person"></i>
            <input type="text" name="name" placeholder="Enter your name">
        </div>

        <label class="meme-label">
            <i class="bi bi-mortarboard-fill"></i> Student ID
        </label>
        <div class="meme-input">
            <i class="bi bi-person-vcard"></i>
            <input type="text" name="student_id" placeholder="Enter your student ID">
        </div>

        <label class="meme-label">
            <i class="bi bi-key-fill"></i> Password
        </label>
        <div class="meme-input">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" placeholder="Create a password">
        </div>

        <button type="submit" name="submit" class="meme-btn">
            Join Memeverse
        </button>
    </form>


</div>

</body>
</html>
