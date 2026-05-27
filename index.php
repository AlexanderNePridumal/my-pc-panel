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

// ПРИЕМ СКРИНШОТА ОТ C# КЛИЕНТА
if (isset($_POST['screenshot_device_id']) && isset($_FILES['screenshot_file'])) {
    $dev_id = $_POST['screenshot_device_id'];
    $file_path = $_FILES['screenshot_file']['tmp_name'];
    $_SESSION['screenshot_'.$dev_id] = [ 'time' => time(), 'data' => base64_encode(file_get_contents($file_path)) ];
    echo "SERVER_SAVED_SCREENSHOT";
    exit;
}

// СКАЧИВАНИЕ СКРИНШОТА
if (isset($_GET['download_screen'])) {
    $dev_id = $_GET['download_screen'];
    if (isset($_SESSION['screenshot_'.$dev_id])) {
        $screen = $_SESSION['screenshot_'.$dev_id];
        if (time() - $screen['time'] <= 35) { 
            header('Content-Type: image/jpeg');
            header('Content-Disposition: attachment; filename="screenshot_'.$dev_id.'.jpg"');
            echo base64_decode($screen['data']); exit;
        }
    }
    echo "Скриншот устарел."; exit;
}

// СЛОВАРЬ ПЕРЕВОДА КОМАНД ДЛЯ ТАБЛИЦЫ ЛОГОВ
$translateAction = ["take_screenshot" => "📸 Скриншот экрана", "shutdown" => "💻 Выключение ПК", "stop_client" => "❌ Остановка клиента", "delete" => "🗑 Удаление из системы"];

// ФОНОВЫЙ AJAX ЗАПРОС ОБНОВЛЕНИЯ ДАННЫХ И ЛОГОВ
if (isset($_GET['ajax_update'])) {
    $devices = getData($api);
    $logs = getData($api . "?get_logs=1");
    
    $dev_status = [];
    foreach ($devices as $d) {
        $dev_id = $d["device_id"]; $last = strtotime($d["time"] ?? ""); $age = time() - $last;
        $status = ($age < 90) ? "online" : "offline";
        $time_str = ($age < 60) ? $age . " сек. назад" : floor($age/60) . " мин. назад";
        
        $has_screenshot = false; $seconds_left = 0;
        if (isset($_SESSION['screenshot_'.$dev_id])) {
            $s_age = time() - $_SESSION['screenshot_'.$dev_id]['time'];
            if ($s_age <= 30) { $has_screenshot = true; $seconds_left = 30 - $s_age; }
        }
        $dev_status[$dev_id] = [ 'status' => $status, 'time_text' => $time_str, 'has_screenshot' => $has_screenshot, 'screenshot_left' => $seconds_left ];
    }

    $log_html = "";
    foreach ($logs as $l) {
        $act = $translateAction[$l["action"]] ?? $l["action"];
        $statusText = "⏳ В очереди"; $statusClass = "status-waiting";
        if ($l["status"] === "1") { $statusText = "✅ Выполнено"; $statusClass = "status-success"; }
        if ($l["status"] === "Ошибка") { $statusText = "🛑 Сбой"; $statusClass = "status-error"; }
        
        $log_html .= "<tr>
            <td>".htmlspecialchars($l["id"])."</td>
            <td>".htmlspecialchars($l["device_id"])."</td>
            <td>".htmlspecialchars($act)."</td>
            <td><span class='badge-log {$statusClass}'>{$statusText}</span></td>
            <td>".htmlspecialchars($l["log"])."</td>
        </tr>";
    }

    header('Content-Type: application/json');
    echo json_encode(['devices' => $dev_status, 'logs_html' => $log_html]);
    exit;
}

// Отправка команд в Google Script
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? ""; $device_id = trim($_POST["device_id"] ?? "");
    if (in_array($action, ["set_name", "shutdown", "stop_client", "delete", "take_screenshot"])) {
        $postData = ["action" => $action, "device_id" => $device_id];
        if ($action === "set_name") $postData["name"] = $_POST["name"] ?? "";
        $ch = curl_init($api); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData)); curl_exec($ch); curl_close($ch);
    }
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

$devices = getData($api);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Панель управления ПК</title>
<style>
body{ margin:0; font-family:system-ui; background:#0a0f1a; color:#e5e7eb; padding-bottom:50px;}
.header{ padding:16px 20px; background:#0f172a; border-bottom:1px solid #1f2937; font-weight:bold; font-size:18px;}
.container{ padding:20px; display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:16px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:15px; }
.name{ font-weight:700; font-size:16px; margin-bottom:4px;}
.status{ font-size:11px; padding:3px 8px; border-radius:999px; display:inline-block; font-weight:bold; text-transform:uppercase;}
.online{ background:#14532d; color:#4ade80; } .offline{ background:#1f2937; color:#9ca3af; }
.row{ margin-top:8px; font-size:13px; }
input{ width:100%; margin-top:8px; padding:8px; border-radius:8px; border:1px solid #334155; background:#0b1220; color:white; box-sizing:border-box; }
button, .btn-link{ width:100%; margin-top:6px; padding:8px; border-radius:8px; border:none; cursor:pointer; font-weight:600; display:block; text-align:center; box-sizing:border-box; text-decoration:none; font-size:13px;}
.blue{ background:#2563eb; color:white; } .red{ background:#dc2626; color:white; } .orange{ background:#f59e0b; color:black; } .green{ background:#10b981; color:white; }
.log-section{ margin:20px; background:#111827; border:1px solid #1f2937; border-radius:14px; padding:15px; overflow-x:auto;}
table{ width:100%; border-collapse:collapse; font-size:13px; margin-top:10px; text-align:left;}
th, td{ padding:10px; border-bottom:1px solid #1f2937; }
th { color:#94a3b8; font-weight:600; }
.badge-log { padding:2px 6px; border-radius:4px; font-size:11px; font-weight:bold;}
.status-waiting { background:#3b82f622; color:#60a5fa; }
.status-success { background:#10b98122; color:#34d399; }
.status-error { background:#ef444422; color:#f87171; }
</style>
</head>
<body>

<div class="header">🖥 Центральная Панель Управления</div>

<div class="container">
    <?php foreach($devices as $d): $dev_id = $d["device_id"]; ?>
    <div class="card" data-id="<?=htmlspecialchars($dev_id)?>">
        <div class="name"><?=htmlspecialchars($d["name"] ?? "Неизвестный ПК")?></div>
        <div class="status-badge status offline">Загрузка...</div>

        <div class="row"><b>IP-адрес:</b> <?=htmlspecialchars($d["ip"] ?? "не определен")?></div>
        <div class="row"><b>ID Железа:</b> <span style="font-size:11px; color:#94a3b8;"><?=htmlspecialchars($dev_id)?></span></div>
        <div class="row"><b>Активность:</b> <span class="heartbeat-time">проверка...</span></div>

        <form method="POST" style="margin-top:12px;">
            <input type="hidden" name="action" value="take_screenshot">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="blue">📸 Запросить скриншот</button>
        </form>

        <div class="screenshot-container">
            <button class="btn-link" style="background:#1f2937; color:#4b5563; cursor:not-allowed;" disabled>Скриншота в памяти нет</button>
        </div>

        <hr style="border-color:#1f2937; margin:15px 0;">

        <form method="POST">
            <input type="hidden" name="action" value="stop_client">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="orange" onclick="return confirm('Остановить программу-агент на удаленном ПК?')">Закрыть клиента</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="shutdown">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="orange" onclick="return confirm('Вы действительно хотите ВЫКЛЮЧИТЬ компьютер?')">Выключить ПК</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="red" onclick="return confirm('Удалить устройство из базы данных панели?')">Удалить устройство</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<div class="log-section">
    <div class="name" style="font-size:18px;">📋 Отчет о событиях и этапах выполнения</div>
    <table>
        <thead>
            <tr>
                <th>ID Задачи</th>
                <th>ID Устройства</th>
                <th>Действие / Команда</th>
                <th>Текущий статус</th>
                <th>Этап выполнения / Лог ошибки</th>
            </tr>
        </thead>
        <tbody id="log-table-body">
            <tr><td colspan="5" style="text-align:center; color:#64748b;">Синхронизация логов событий...</td></tr>
        </tbody>
    </table>
</div>

<script>
function updateDashboard() {
    fetch('?ajax_update=1')
        .then(response => response.json())
        .then(data => {
            // 1. Обновление карточек ПК
            for (let dev_id in data.devices) {
                let card = document.querySelector(`.card[data-id="${dev_id}"]`);
                if (!card) continue;

                let badge = card.querySelector('.status-badge');
                badge.textContent = data.devices[dev_id].status === 'online' ? 'В сети' : 'Не в сети';
                badge.className = `status-badge status ${data.devices[dev_id].status}`;

                card.querySelector('.heartbeat-time').textContent = data.devices[dev_id].time_text;

                let screenBox = card.querySelector('.screenshot-container');
                if (data.devices[dev_id].has_screenshot) {
                    screenBox.innerHTML = `<a href="?download_screen=${encodeURIComponent(dev_id)}" class="btn-link green">📥 Скачать скриншот (Доступен ${data.devices[dev_id].screenshot_left}с.)</a>`;
                } else {
                    screenBox.innerHTML = `<button class="btn-link" style="background:#1f2937; color:#4b5563; cursor:not-allowed;" disabled>Скриншота в памяти нет</button>`;
                }
            }
            // 2. Автоматическое обновление таблицы отчетов
            document.getElementById('log-table-body').innerHTML = data.logs_html;
        })
        .catch(err => console.log("Ошибка обновления:", err));
}

setInterval(updateDashboard, 3000);
updateDashboard();
</script>

</body>
</html>
