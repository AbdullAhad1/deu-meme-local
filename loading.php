<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEU Memeverse - Loading</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: url('images/bg.png') center center / cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow: hidden;
        }

        .loading-card {
            background: rgba(15, 23, 60, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px 36px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
        }

        .loading-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fff;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(41, 98, 255, 0.3);
            animation: bounce 1.5s ease-in-out infinite;
        }
        .loading-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .loading-title {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 6px;
        }
        .loading-title span {
            background: linear-gradient(90deg, #64b5f6, #e040fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .loading-sub {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
        }
        .dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: dotPulse 1.2s ease-in-out infinite;
        }
        .dots span:nth-child(1) { background: #e040fb; animation-delay: 0s; }
        .dots span:nth-child(2) { background: #2962ff; animation-delay: 0.2s; }
        .dots span:nth-child(3) { background: #64b5f6; animation-delay: 0.4s; }

        .checklist {
            text-align: left;
            margin-bottom: 28px;
        }
        .checklist-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            color: rgba(255, 255, 255, 0.85);
            font-size: 14px;
            opacity: 0;
            transform: translateX(-10px);
            transition: opacity 0.4s, transform 0.4s;
        }
        .checklist-item.show {
            opacity: 1;
            transform: translateX(0);
        }
        .checklist-item .icon { margin-right: 10px; font-size: 16px; }
        .checklist-item .check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            transition: all 0.3s;
        }
        .checklist-item.done .check {
            background: #4caf50;
            border-color: #4caf50;
            color: #fff;
        }

        .progress-bar-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50rem;
            height: 10px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #2962ff, #e040fb);
            border-radius: 50rem;
            transition: width 0.3s ease;
        }

        .percent {
            font-size: 36px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 2px;
        }
        .percent-label {
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        @keyframes dotPulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.4); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="loading-card">
    <div class="loading-logo">
        <img src="images/deu-logo.svg" alt="DEU">
    </div>

    <div class="loading-title">DEU <span>MEMEVERSE</span></div>
    <div class="loading-sub">Initializing campus chaos...</div>

    <div class="dots">
        <span></span><span></span><span></span>
    </div>

    <div class="checklist">
        <div class="checklist-item" id="step1">
            <div><span class="icon">👀</span> Finding students online</div>
            <div class="check">✓</div>
        </div>
        <div class="checklist-item" id="step2">
            <div><span class="icon">☕</span> Loading caffeine level</div>
            <div class="check">✓</div>
        </div>
        <div class="checklist-item" id="step3">
            <div><span class="icon">🔥</span> Scanning trending memes</div>
            <div class="check">✓</div>
        </div>
        <div class="checklist-item" id="step4">
            <div><span class="icon">📚</span> Detecting assignment stress</div>
            <div class="check">✓</div>
        </div>
    </div>

    <div class="progress-bar-container">
        <div class="progress-fill" id="progressFill"></div>
    </div>

    <div class="percent" id="percentText">0%</div>
    <div class="percent-label">Complete</div>
</div>

<script>
    var steps = ['step1', 'step2', 'step3', 'step4'];
    var progress = 0;
    var target = 100;
    var progressFill = document.getElementById('progressFill');
    var percentText = document.getElementById('percentText');

    // Show steps one by one
    steps.forEach(function(id, i) {
        setTimeout(function() {
            var el = document.getElementById(id);
            el.classList.add('show');
            setTimeout(function() {
                el.classList.add('done');
            }, 300);
        }, i * 400);
    });

    // Animate progress bar
    var interval = setInterval(function() {
        progress += 2;
        if (progress > target) progress = target;
        progressFill.style.width = progress + '%';
        percentText.textContent = progress + '%';
        if (progress >= target) {
            clearInterval(interval);
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 300);
        }
    }, 30);
</script>
</body>
</html>
