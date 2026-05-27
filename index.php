<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// !!! СЮДА ВСТАВЬТЕ ВАШ URL ИЗ GOOGLE APPS SCRIPT !!!
$api = "https://script.google.com/macros/s/AKfycbxt9RdUegrIhotBPNQRs6_Nkb3Hy0NF2IJdpL3XyYZXtPbptFhYtUWfAse3Z10VhHCC/exec";

function getData($url) {
    $ch = curl_init($url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch); curl_close($ch);
    return json_decode($res, true) ?: [];
}

// =========================================================
// ПРИЕМ СКРИНШОТА ОТ C# КЛИЕНТА
// =========================================================
if (isset($_POST['screenshot_device_id']) && isset($_FILES['screenshot_file'])) {
    $dev_id = $_POST['screenshot_device_id'];
    $file_path = $_FILES['screenshot_file']['tmp_name'];
    
    $img_data = file_get_contents($file_path);
    $_SESSION['screenshot_'.$dev_id] = [
        'time' => time(),
        'data' => base64_encode($img_data)
    ];
    echo "SERVER_SAVED_SCREENSHOT";
    exit;
}

// =========================================================
// СКАЧИВАНИЕ СКРИНШОТА
// =========================================================
if (isset($_GET['download_screen'])) {
    $dev_id = $_GET['download_screen'];
    if (isset($_SESSION['screenshot_'.$dev_id])) {
        $screen = $_SESSION['screenshot_'.$dev_id];
        if (time() - $screen['time'] <= 35) { 
            header('Content-Type: image/jpeg');
            header('Content-Disposition: attachment; filename="screenshot_'.$dev_id.'.jpg"');
            echo base64_decode($screen['data']);
            exit;
        }
    }
    echo "Скриншот устарел.";
    exit;
}

// =========================================================
// ФОНОВЫЙ AJAX ЗАПРОС (Браузер запрашивает этот кусок каждые 3 сек)
// =========================================================
if (isset($_GET['ajax_update'])) {
    $devices = getData($api);
    $response = [];

    foreach ($devices as $d) {
        $dev_id = $d["device_id"];
        $last = strtotime($d["time"] ?? "");
        $age_heartbeat = time() - $last;
        $status = ($age_heartbeat < 90) ? "online" : "offline";
        
        // Считаем время последнего онлайна текстом
        if ($age_heartbeat < 60) $time_str = $age_heartbeat . " sec ago";
        else $time_str = floor($age_heartbeat/60) . " min ago";

        // Проверяем скриншот в памяти
        $has_screenshot = false;
        $seconds_left = 0;
        if (isset($_SESSION['screenshot_'.$dev_id])) {
            $screenshot_age = time() - $_SESSION['screenshot_'.$dev_id]['time'];
            if ($screenshot_age <= 30) {
                $has_screenshot = true;
                $seconds_left = 30 - $screenshot_age;
            }
        }

        $response[$dev_id] = [
            'status' => $status,
            'time_text' => $time_str,
            'has_screenshot' => $has_screenshot,
            'screenshot_left' => $seconds_left
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Отправка команд в Google Script
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $device_id = trim($_POST["device_id"] ?? "");

    if (in_array($action, ["set_name", "shutdown", "stop_client", "delete", "take_screenshot"])) {
        $postData = ["action" => $action, "device_id" => $device_id];
        if ($action === "set_name") $postData["name"] = $_POST["name"] ?? "";
        
        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_exec($ch); curl_close($ch);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$devices = getData($api);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PC Dashboard</title>
<style>
body{ margin:0; font-family:system-ui; background:#0a0f1a; color:#e5e7eb; }
.header{ padding:16px 20px; background:#0f172a; border-bottom:1px solid #1f2937; position:sticky; top:0; font-weight:bold;}
.container{ padding:20px; display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:16px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:15px; }
.name{ font-weight:700; font-size:16px; margin-bottom:4px;}
.status{ font-size:11px; padding:3px 8px; border-radius:999px; display:inline-block; font-weight:bold;}
.online{ background:#14532d; color:#4ade80; } .offline{ background:#1f2937; color:#9ca3af; }
.row{ margin-top:8px; font-size:13px; }
input{ width:100%; margin-top:8px; padding:8px; border-radius:8px; border:1px solid #334155; background:#0b1220; color:white; box-sizing:border-box; }
button, .btn-link{ width:100%; margin-top:6px; padding:8px; border-radius:8px; border:none; cursor:pointer; font-weight:600; display:block; text-align:center; box-sizing:border-box; text-decoration:none;}
.blue{ background:#2563eb; color:white; } .red{ background:#dc2626; color:white; } .orange{ background:#f59e0b; color:black; }
.green{ background:#10b981; color:white; }
</style>
</head>
<body>

<div class="header">PC Control Panel (Автообновление включено)</div>

<div class="container">
    <?php foreach($devices as $d): ?>
    <?php
        $dev_id = $d["device_id"];
        $last = strtotime($d["time"] ?? "");
        $diff = time() - $last;
        $status = ($diff < 90) ? "online" : "offline";
        
        $has_screenshot = false;
        if (isset($_SESSION['screenshot_'.$dev_id])) {
            $age = time() - $_SESSION['screenshot_'.$dev_id]['time'];
            if ($age <= 30) { $has_screenshot = true; $seconds_left = 30 - $age; }
        }
    ?>
    <div class="card" data-id="<?=htmlspecialchars($dev_id)?>">
        <div class="name"><?=htmlspecialchars($d["name"] ?? "Unknown PC")?></div>
        <div class="status-badge status <?=$status?>"><?=$status?></div>

        <div class="row"><b>ID:</b> <?=htmlspecialchars($dev_id)?></div>
        <div class="row"><b>Last Heartbeat:</b> <span class="heartbeat-time">Загрузка...</span></div>

        <form method="POST">
            <input type="hidden" name="action" value="take_screenshot">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="blue">📸 Запросить скриншот</button>
        </form>

        <div class="screenshot-container">
            <?php if ($has_screenshot): ?>
                <a href="?download_screen=<?=urlencode($dev_id)?>" class="btn-link green">📥 Скачать скриншот (осталось <?=$seconds_left?>с)</a>
            <?php else: ?>
                <button class="btn-link" style="background:#1f2937; color:#4b5563; cursor:not-allowed;" disabled>Скриншота в памяти нет</button>
            <?php endif; ?>
        </div>

        <hr style="border-color:#1f2937; margin:15px 0;">

        <form method="POST">
            <input type="hidden" name="action" value="stop_client">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="orange">Stop Client</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="shutdown">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="orange">Shutdown PC</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="red">Delete Completely</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<script>
function updateDashboard() {
    fetch('?ajax_update=1')
        .then(response => response.json())
        .then(data => {
            for (let dev_id in data) {
                let card = document.querySelector(`.card[data-id="${dev_id}"]`);
                if (!card) continue;

                // 1. Обновляем плашку статуса (Online/Offline)
                let badge = card.querySelector('.status-badge');
                badge.textContent = data[dev_id].status;
                badge.className = `status-badge status ${data[dev_id].status}`;

                // 2. Обновляем текст времени последнего сигнала
                card.querySelector('.heartbeat-time').textContent = data[dev_id].time_text;

                // 3. Обновляем кнопку скриншота без перезагрузки всей страницы
                let screenBox = card.querySelector('.screenshot-container');
                if (data[dev_id].has_screenshot) {
                    screenBox.innerHTML = `<a href="?download_screen=${encodeURIComponent(dev_id)}" class="btn-link green">📥 Скачать скриншот (осталось ${data[dev_id].screenshot_left}с)</a>`;
                } else {
                    screenBox.innerHTML = `<button class="btn-link" style="background:#1f2937; color:#4b5563; cursor:not-allowed;" disabled>Скриншота в памяти нет</button>`;
                }
            }
        })
        .catch(err => Console.log("Ошибка обновления данных:", err));
}

// Запускать цикл опроса каждые 3000 миллисекунд (3 секунды)
setInterval(updateDashboard, 3000);
// Первый запуск сразу при открытии
updateDashboard();
</script>

</body>
</html>
