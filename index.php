<?php
ob_start(); 
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// !!! СЮДА ВСТАВЬТЕ ВАШ URL ИЗ GOOGLE APPS SCRIPT !!!
$api = "https://script.google.com/macros/s/AKfycbxt9RdUegrIhotBPNQRs6_Nkb3Hy0NF2IJdpL3XyYZXtPbptFhYtUWfAse3Z10VhHCC/exec";

function getData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/json'
    ]);
    $res = curl_exec($ch); 
    curl_close($ch);
    return json_decode($res, true) ?: [];
}

// ПРИЕМ СКРИНШОТА ОТ C# КЛИЕНТА (Сюда бот шлет картинку)
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
        if (time() - $screen['time'] <= 45) { 
            header('Content-Type: image/jpeg');
            header('Content-Disposition: attachment; filename="screenshot_'.$dev_id.'.jpg"');
            echo base64_decode($screen['data']); exit;
        }
    }
    echo "Скриншот устарел или еще не получен от бота."; exit;
}

$translateAction = [
    "take_screenshot" => "📸 Скриншот экрана", 
    "shutdown" => "💻 Выключение ПК", 
    "stop_client" => "❌ Остановка клиента", 
    "delete" => "🗑 Удаление из системы"
];

// ФОНОВЫЙ AJAX ДЛЯ ОБНОВЛЕНИЯ ИНТЕРФЕЙСА (РАБОТАЕТ БЕЗ ПЕРЕЗАГРУЗОК)
if (isset($_GET['ajax_update'])) {
    $devices = getData($api);
    $logs = getData($api . "?get_logs=1");
    
    $dev_status = [];
    foreach ($devices as $d) {
        $dev_id = $d["device_id"]; 
        
        // Надежный парсинг даты Google Таблицы в формат PHP
        $time_raw = $d["time"] ?? "";
        $last = 0;
        if (!empty($time_raw)) {
            if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})\s(.*)/', $time_raw, $matches)) {
                $time_raw = $matches[3] . "-" . $matches[2] . "-" . $matches[1] . " " . $matches[4];
            }
            $last = strtotime($time_raw);
        }
        
        $age_heartbeat = ($last > 0) ? (time() - $last) : 999;
        
        // Проверка активности строго за последние 35 секунд
        $status = ($age_heartbeat <= 35 && $last > 0) ? "online" : "offline";
        
        if ($last === 0 || $age_heartbeat > 86400) {
            $time_str = "давно";
        } elseif ($age_heartbeat < 60) {
            $time_str = $age_heartbeat . " сек. назад";
        } else {
            $time_str = floor($age_heartbeat/60) . " мин. назад";
        }
        
        $has_screenshot = false; $seconds_left = 0;
        if (isset($_SESSION['screenshot_'.$dev_id])) {
            $s_age = time() - $_SESSION['screenshot_'.$dev_id]['time'];
            if ($s_age <= 40) { $has_screenshot = true; $seconds_left = 40 - $s_age; }
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

// ЕДИНЫЙ НАДЕЖНЫЙ ЖЕЛЕЗНЫЙ МЕТОД ОТПРАВКИ ВСЕХ КОМАНД (ВКЛЮЧАЯ СКРИНШОТ)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? ""; 
    $device_id = trim($_POST["device_id"] ?? "");

    if (!empty($action) && !empty($device_id)) {
        $postData = ["action" => $action, "device_id" => $device_id];
        
        $ch = curl_init($api); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData)); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_exec($ch); 
        curl_close($ch);
    }
    // После отправки формы перенаправляем пользователя на чистый URL, убирая зависания
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit;
}

$devices = getData($api);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Панель управления ПК</title>
<style>
body{ margin:0; font-family:system-ui, sans-serif; background:#090d16; color:#e2e8f0; padding-bottom:50px;}
.header{ padding:18px 24px; background:#0f172a; border-bottom:1px solid #1e293b; font-weight:bold; font-size:19px;}
.container{ padding:24px; display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:20px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; }
.name{ font-weight:700; font-size:17px; margin-bottom:6px; color:#f8fafc;}
.status{ font-size:11px; padding:4px 10px; border-radius:999px; display:inline-block; font-weight:bold; text-transform:uppercase;}
.online{ background:#064e3b; color:#34d399; } .offline{ background:#374151; color:#9ca3af; }
.row{ margin-top:10px; font-size:13px; color:#94a3b8;}
button, .btn-link{ width:100%; margin-top:8px; padding:10px; border-radius:8px; border:none; cursor:pointer; font-weight:600; display:block; text-align:center; box-sizing:border-box; text-decoration:none; font-size:13px;}
.blue { background:#4f46e5; color:white; } .blue:hover { background:#4338ca; }
.green { background:#059669; color:white; }
.orange { background:#d97706; color:white; } .red { background:#dc2626; color:white; }
.gray-danger { background:#374151; color:#f3f4f6; border: 1px solid #4b5563; }
.log-section{ margin:24px; background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; overflow-x:auto;}
table{ width:100%; border-collapse:collapse; font-size:13px; margin-top:14px; text-align:left;}
th, td{ padding:12px; border-bottom:1px solid #1f2937; }
th { color:#64748b; font-weight:600; text-transform: uppercase; font-size:11px;}
.badge-log { padding:3px 8px; border-radius:6px; font-size:11px; font-weight:bold;}
.status-waiting { background:#1e3a8a; color:#93c5fd; } .status-success { background:#064e3b; color:#a7f3d0; }
.status-error { background:#7f1d1d; color:#fca5a5; }
</style>
</head>
<body>

<div class="header">🖥 Центральная Панель Управления</div>

<div class="container">
    <?php foreach($devices as $d): $dev_id = $d["device_id"]; ?>
    <div class="card" data-id="<?=htmlspecialchars($dev_id)?>">
        <div class="name"><?=htmlspecialchars($d["name"] ?? "Неизвестный ПК")?></div>
        <div class="status-badge status offline">Синхронизация...</div>

        <div class="row"><b>IP-адрес:</b> <?=htmlspecialchars($d["ip"] ?? "не определен")?></div>
        <div class="row"><b>ID Железа:</b> <span style="font-size:11px; font-family:monospace;"><?=htmlspecialchars($dev_id)?></span></div>
        <div class="row"><b>Активность:</b> <span class="heartbeat-time">проверка...</span></div>

        <form method="POST" style="margin-bottom: 0;">
            <input type="hidden" name="action" value="take_screenshot">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="blue">📸 Запросить скриншот</button>
        </form>

        <div class="screenshot-container"></div>

        <hr style="border-color:#1f2937; margin:15px 0;">

        <form method="POST">
            <input type="hidden" name="action" value="stop_client">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="orange" onclick="return confirm('Остановить программу на ПК?')">Закрыть клиента</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="shutdown">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="red" onclick="return confirm('Выключить ПК?')">Выключить ПК</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="gray-danger" onclick="return confirm('Удалить из панели?')">Удалить устройство</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<div class="log-section">
    <div class="name" style="font-size:16px; color:#f1f5f9;">📋 Отчет о событиях</div>
    <table>
        <thead>
            <tr>
                <th>ID Задачи</th>
                <th>ID Устройства</th>
                <th>Действие</th>
                <th>Текущий статус</th>
                <th>Лог выполнения</th>
            </tr>
        </thead>
        <tbody id="log-table-body">
            <tr><td colspan="5" style="text-align:center; color:#64748b;">Синхронизация логов...</td></tr>
        </tbody>
    </table>
</div>

<script>
function updateDashboard() {
    fetch('?ajax_update=1')
        .then(response => response.json())
        .then(data => {
            for (let dev_id in data.devices) {
                let card = document.querySelector(`.card[data-id="${dev_id}"]`);
                if (!card) continue;

                // Обновляем плашку ОНЛАЙН / ОФФЛАЙН (До 35 секунд)
                let badge = card.querySelector('.status-badge');
                badge.textContent = data.devices[dev_id].status === 'online' ? 'В сети' : 'Не в сети';
                badge.className = `status-badge status ${data.devices[dev_id].status}`;

                // Исправлено: Пишем время последней активности ("Х сек. назад")
                card.querySelector('.heartbeat-time').textContent = data.devices[dev_id].time_text;

                // Показываем зеленую кнопку скачивания, когда C# загрузит скриншот в память сессии
                let screenBox = card.querySelector('.screenshot-container');
                if (data.devices[dev_id].has_screenshot) {
                    screenBox.innerHTML = `<a href="?download_screen=${encodeURIComponent(dev_id)}" class="btn-link green" style="margin-top:10px; padding:10px; border-radius:8px; color:white; display:block; text-align:center; font-weight:bold; background:#059669; text-decoration:none;">📥 Скачать скриншот (Осталось ${data.devices[dev_id].screenshot_left}с.)</a>`;
                } else {
                    screenBox.innerHTML = `<button style="width:100%; margin-top:8px; padding:10px; background:#1f2937; color:#4b5563; border:none; border-radius:8px; cursor:not-allowed;" disabled>Скриншота в памяти нет</button>`;
                }
            }
            document.getElementById('log-table-body').innerHTML = data.logs_html;
        })
        .catch(err => console.log("Синхронизация..."));
}

// Фоновое обновление времени онлайна и кнопок скачивания каждые 3 секунды
setInterval(updateDashboard, 3000);
updateDashboard();
</script>
</body>
</html>
