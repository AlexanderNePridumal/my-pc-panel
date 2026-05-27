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
    curl_setopt($ch, CURLOPT_TIMEOUT, 6); // Жесткий таймаут, чтобы страница не висела
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/json'
    ]);
    $res = curl_exec($ch); 
    curl_close($ch);
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
        if (time() - $screen['time'] <= 60) { 
            header('Content-Type: image/jpeg');
            header('Content-Disposition: attachment; filename="screenshot_'.$dev_id.'.jpg"');
            echo base64_decode($screen['data']); exit;
        }
    }
    echo "Скриншот устарел или еще не получен."; exit;
}

// AJAX ОБНОВЛЕНИЕ 1: ТОЛЬКО СТАТУСЫ УСТРОЙСТВ (Быстрый запрос)
if (isset($_GET['get_devices_ajax'])) {
    $devices = getData($api);
    $dev_status = [];
    foreach ($devices as $d) {
        $dev_id = $d["device_id"]; 
        $time_raw = $d["time"] ?? "";
        $last = 0;
        if (!empty($time_raw)) {
            if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})\s(.*)/', $time_raw, $matches)) {
                $time_raw = $matches[3] . "-" . $matches[2] . "-" . $matches[1] . " " . $matches[4];
            }
            $last = strtotime($time_raw);
        }
        
        $age = ($last > 0) ? (time() - $last) : 999;
        $status = ($age <= 35 && $last > 0) ? "online" : "offline";
        
        if ($last === 0 || $age > 86400) { $time_str = "давно"; }
        // Если бот прислал пинг только что, выводим красивое "в сети"
        elseif ($age < 5) { $time_str = "только что"; } 
        elseif ($age < 60) { $time_str = $age . " сек. назад"; } 
        else { $time_str = floor($age/60) . " мин. назад"; }
        
        $has_screenshot = false; $seconds_left = 0;
        if (isset($_SESSION['screenshot_'.$dev_id])) {
            $s_age = time() - $_SESSION['screenshot_'.$dev_id]['time'];
            if ($s_age <= 60) { $has_screenshot = true; $seconds_left = 60 - $s_age; }
        }
        $dev_status[$dev_id] = [ 'status' => $status, 'time_text' => $time_str, 'has_screenshot' => $has_screenshot, 'screenshot_left' => $seconds_left ];
    }
    header('Content-Type: application/json');
    echo json_encode($dev_status); exit;
}

// AJAX ОБНОВЛЕНИЕ 2: ТОЛЬКО ЛОГИ ТАБЛИЦЫ (Редкий запрос)
// AJAX ОБНОВЛЕНИЕ 2: ТОЛЬКО ЛОГИ ТАБЛИЦЫ (Редкий запрос)
if (isset($_GET['get_logs_ajax'])) {
    $logs = getData($api . "?get_logs=1");
    $translateAction = ["take_screenshot" => "📸 Скриншот", "shutdown" => "💻 Выключение", "stop_client" => "❌ Остановка", "delete" => "🗑 Удаление"];
    $log_html = "";
    
    if(empty($logs)) {
        $log_html = "<tr><td colspan='5' style='text-align:center; color:#64748b;'>Нет ответов от таблицы...</td></tr>";
    } else {
        foreach ($logs as $l) {
            // ФИКС: Проверяем, что строка таблицы не пустая и содержит нужные ключи
            if (!isset($l["id"]) || empty($l["device_id"])) {
                continue; // Пропускаем пустые или кривые строки в Google Таблице
            }

            $action_key = $l["action"] ?? "";
            $act = $translateAction[$action_key] ?? $action_key;
            
            $status = $l["status"] ?? "";
            $statusText = $status === "1" ? "✅ Выполнено" : ($status === "Ошибка" ? "🛑 Сбой" : "⏳ В очереди");
            $statusClass = $status === "1" ? "status-success" : ($status === "Ошибка" ? "status-error" : "status-waiting");
            
            $id = htmlspecialchars($l["id"]);
            $dev_id = htmlspecialchars($l["device_id"]);
            $log_msg = htmlspecialchars($l["log"] ?? "Нет данных");

            $log_html .= "<tr>
                <td>{$id}</td>
                <td>{$dev_id}</td>
                <td>{$act}</td>
                <td><span class='badge-log {$statusClass}'>{$statusText}</span></td>
                <td>{$log_msg}</td>
            </tr>";
        }
    }
    
    // Если после фильтрации все строки оказались пустыми
    if (empty($log_html)) {
        $log_html = "<tr><td colspan='5' style='text-align:center; color:#64748b;'>История событий пуста</td></tr>";
    }
    
    echo $log_html; 
    exit;
}

// ОБРАБОТКА КОМАНД ИЗ ФОРМ
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? ""; $device_id = trim($_POST["device_id"] ?? "");
    if (!empty($action) && !empty($device_id)) {
        $ch = curl_init($api); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["action" => $action, "device_id" => $device_id])); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_exec($ch); curl_close($ch);
    }
    header("Location: " . $_SERVER['PHP_SELF']); exit;
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
.header{ padding:18px 24px; background:#0f172a; border-bottom:1px solid #1e293b; font-weight:bold; font-size:19px; display:flex; justify-content:space-between; align-items:center;}
.sync-indicator{ font-size:12px; color:#64748b; display:flex; align-items:center; font-weight:normal;}
.dot{ width:8px; height:8px; background:#34d399; border-radius:50%; margin-right:8px; display:inline-block; animation: pulse 1.5s infinite; }
@keyframes pulse { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }
.container{ padding:24px; display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:20px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; transition: border-color 0.3s; }
.card.online-card { border-color: #064e3b; }
.name{ font-weight:700; font-size:17px; margin-bottom:6px; color:#f8fafc;}
.status{ font-size:11px; padding:4px 10px; border-radius:999px; display:inline-block; font-weight:bold; text-transform:uppercase;}
.online{ background:#064e3b; color:#34d399; } .offline{ background:#374151; color:#9ca3af; }
.row{ margin-top:10px; font-size:13px; color:#94a3b8;}
button, .btn-link{ width:100%; margin-top:8px; padding:10px; border-radius:8px; border:none; cursor:pointer; font-weight:600; display:block; text-align:center; box-sizing:border-box; text-decoration:none; font-size:13px; transition: 0.2s;}
.blue { background:#4f46e5; color:white; } .blue:hover{ background:#4338ca; }
.green { background:#059669; color:white; }
.orange { background:#d97706; color:white; } .red { background:#dc2626; color:white; }
.gray-danger { background:#374151; color:#f3f4f6; border: 1px solid #4b5563; } .gray-danger:hover{ background:#991b1b; color:white;}
.log-section{ margin:24px; background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; overflow-x:auto;}
table{ width:100%; border-collapse:collapse; font-size:13px; margin-top:14px; text-align:left;}
th, td{ padding:12px; border-bottom:1px solid #1f2937; }
th { color:#64748b; font-weight:600; text-transform: uppercase; font-size:11px;}
.badge-log { padding:3px 8px; border-radius:6px; font-size:11px; font-weight:bold;}
.status-waiting { background:#1e3a8a; color:#93c5fd; } .status-success { background:#064e3b; color:#a7f3d0; } .status-error { background:#7f1d1d; color:#fca5a5; }
</style>
</head>
<body>

<div class="header">
    <span>🖥 Центральная Панель Управления</span>
    <div class="sync-indicator"><span class="dot"></span>Постоянный мониторинг</div>
</div>

<div class="container">
    <?php foreach($devices as $d): $dev_id = $d["device_id"]; ?>
    <div class="card" data-id="<?=htmlspecialchars($dev_id)?>">
        <div class="name"><?=htmlspecialchars($d["name"] ?? "Неизвестный ПК")?></div>
        <div class="status-badge status offline">Подключение...</div>

        <div class="row"><b>IP:</b> <?=htmlspecialchars($d["ip"] ?? "—")?></div>
        <div class="row"><b>ID:</b> <span style="font-size:11px; font-family:monospace;"><?=htmlspecialchars($dev_id)?></span></div>
        <div class="row"><b>Активность:</b> <span class="heartbeat-time">синхронизация...</span></div>

        <form method="POST" style="margin-bottom: 0;">
            <input type="hidden" name="action" value="take_screenshot">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="blue">📸 Запросить скриншот</button>
        </form>
        <div class="screenshot-container"></div>

        <hr style="border-color:#1f2937; margin:15px 0;">

        <form method="POST" style="margin-bottom:6px;">
            <input type="hidden" name="action" value="stop_client">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="orange" onclick="return confirm('Выключить клиент?')">Закрыть клиента</button>
        </form>

        <form method="POST" style="margin-bottom:6px;">
            <input type="hidden" name="action" value="shutdown">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="red" onclick="return confirm('Выключить ПК?')">Выключить ПК</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button class="gray-danger" onclick="return confirm('Удалить?')">Удалить из базы</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<div class="log-section">
    <div class="name" style="font-size:16px; color:#f1f5f9;">📋 Отчет о событиях (Живая лента)</div>
    <table>
        <thead>
            <tr>
                <th>ID Задачи</th><th>ID Устройства</th><th>Действие</th><th>Статус</th><th>Лог выполнения</th>
            </tr>
        </thead>
        <tbody id="log-table-body">
            <tr><td colspan="5" style="text-align:center; color:#64748b;">Загрузка истории...</td></tr>
        </tbody>
    </table>
</div>

<script>
// 1. БЫСТРОЕ ОБНОВЛЕНИЕ КАРТОЧЕК И ОНЛАЙНА (Каждые 2.5 секунды)
function updateDevices() {
    fetch('?get_devices_ajax=1')
        .then(res => res.json())
        .then(data => {
            for (let dev_id in data) {
                let card = document.querySelector(`.card[data-id="${dev_id}"]`);
                if (!card) continue;

                let isOnline = data[dev_id].status === 'online';
                
                // Меняем плашку сети
                let badge = card.querySelector('.status-badge');
                badge.textContent = isOnline ? 'В сети' : 'Не в сети';
                badge.className = `status-badge status ${data[dev_id].status}`;

                // Подсвечиваем саму карточку зеленой рамкой, если ПК онлайн
                if(isOnline) { card.classList.add('online-card'); } else { card.classList.remove('online-card'); }

                // Текст времени активности
                card.querySelector('.heartbeat-time').textContent = data[dev_id].time_text;

                // Кнопка скриншота
                let screenBox = card.querySelector('.screenshot-container');
                if (data[dev_id].has_screenshot) {
                    screenBox.innerHTML = `<a href="?download_screen=${encodeURIComponent(dev_id)}" class="btn-link green" style="margin-top:10px; padding:10px; border-radius:8px; color:white; display:block; text-align:center; font-weight:bold; background:#059669; text-decoration:none;">📥 Скачать скриншот (${data[dev_id].screenshot_left}с.)</a>`;
                } else {
                    screenBox.innerHTML = `<button style="width:100%; margin-top:8px; padding:10px; background:#1f2937; color:#4b5563; border:none; border-radius:8px; cursor:not-allowed;" disabled>Скриншота в памяти нет</button>`;
                }
            }
        }).catch(e => console.log("Пропуск шага сети ПК..."));
}

// 2. ОБНОВЛЕНИЕ ИСТОРИИ СОБЫТИЙ (Раз в 8 секунд, чтобы убрать тормоза Google)
function updateLogs() {
    fetch('?get_logs_ajax=1')
        .then(res => res.text())
        .then(html => {
            document.getElementById('log-table-body').innerHTML = html;
        }).catch(e => console.log("Пропуск шага таблицы логов..."));
}

// Запускаем независимые таймеры
setInterval(updateDevices, 2500); 
setInterval(updateLogs, 8000);

// Первичный моментальный вызов при открытии
updateDevices();
updateLogs();
</script>
</body>
</html>
