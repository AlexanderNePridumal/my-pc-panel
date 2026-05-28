<?php
ob_start(); 
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);


$api = "https://script.google.com/macros/s/AKfycbxRsRgjvfGlyEsZZYo2U9qDTMnx_JXY9WIaEPgdy8He6eJt3zRf6iLOOAqmC9S63T03/exec";

function getData($url) {
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 6); 
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    $res = curl_exec($ch); 
    curl_close($ch);
    return json_decode($res, true) ?: [];
}

// ПРИЕМ СКРИНШОТА ОТ БОТА
if (isset($_POST['screenshot_device_id']) && isset($_FILES['screenshot_file'])) {
    $dev_id = $_POST['screenshot_device_id'];
    $file_path = $_FILES['screenshot_file']['tmp_name'];
    $_SESSION['screenshot_'.$dev_id] = [ 'time' => time(), 'data' => base64_encode(file_get_contents($file_path)) ];
    echo "SERVER_SAVED_SCREENSHOT"; exit;
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

// ЕДИНЫЙ ВСЕЯДНЫЙ ОБРАБОТЧИК ДЛЯ ВСЕХ КНОПОК
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST["action"]) ? trim($_POST["action"]) : ""; 
    $device_id = isset($_POST["device_id"]) ? trim($_POST["device_id"]) : "";

    // Шлем ЛЮБУЮ команду, которая пришла из формы, без лишних фильтров
    if (!empty($action) && !empty($device_id)) {
        $ch = curl_init($api); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "action" => $action,
            "device_id" => $device_id
        ])); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch); 
        curl_close($ch);
    }
    
    // Перенаправляем обратно, чтобы не было дублей при обновлении
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit;
}

// Загружаем данные из таблицы для отрисовки страницы
$devices = getData($api);
$logs = getData($api . "?get_logs=1");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Панель управления ПК</title>
<style>
body{ margin:0; font-family:system-ui, sans-serif; background:#090d16; color:#e2e8f0; padding-bottom:50px;}
.header{ padding:18px 24px; background:#0f172a; border-bottom:1px solid #1e293b; font-weight:bold; font-size:19px; display:flex; justify-content:space-between; align-items:center;}
.sync-indicator{ font-size:12px; color:#64748b; display:flex; align-items:center;}
.dot{ width:8px; height:8px; background:#34d399; border-radius:50%; margin-right:8px; animation: pulse 1.5s infinite; }
@keyframes pulse { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }
.container{ padding:24px; display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:20px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; transition: border-color 0.3s; }
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
.log-section{ margin:24px; background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; overflow-x:auto;}
table{ width:100%; border-collapse:collapse; font-size:13px; margin-top:14px; text-align:left;}
th, td{ padding:12px; border-bottom:1px solid #1f2937; }
th { color:#64748b; font-weight:600; text-transform: uppercase; font-size:11px;}
.badge-log { padding:3px 8px; border-radius:6px; font-size:11px; font-weight:bold;}
.status-waiting { background:#1e3a8a; color:#93c5fd; } .status-success { background:#064e3b; color:#a7f3d0; } .status-error { background:#7f1d1d; color:#fca5a5; }
.process-box { background:#030712; padding:10px; border-radius:8px; border:1px solid #1f2937; font-family:monospace; font-size:11px; color:#10b981; max-height:80px; overflow-y:auto; margin-top:8px; display:none; white-space: pre-wrap; }
</style>
</head>
<body>

<div class="header">
    <span>🖥 Центральная Панель Управления</span>
    <div class="sync-indicator"><span class="dot"></span>Автообновление (15с)</div>
</div>

<div class="container">
    <?php 
    foreach($devices as $d): 
        $dev_id = $d["device_id"] ?? ""; 
        if (empty($dev_id)) continue;

        // Расчет онлайна (35 секунд)
        $time_raw = $d["time"] ?? ""; $last = 0;
        if (!empty($time_raw)) {
            if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})\s(.*)/', $time_raw, $matches)) {
                $time_raw = $matches[3] . "-" . $matches[2] . "-" . $matches[1] . " " . $matches[4];
            }
            $last = strtotime($time_raw);
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

        <form method="POST" action="index.php" style="margin-bottom: 6px;">
            <input type="hidden" name="action" value="take_screenshot">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button type="submit" class="blue" onclick="return confirm('Отправить команду на снимок экрана?')">📸 Запросить скриншот</button>
        </form>

        <div class="screenshot-container">
            <?php 
            if (isset($_SESSION['screenshot_'.$dev_id])): 
                $s_age = time() - $_SESSION['screenshot_'.$dev_id]['time'];
                if ($s_age <= 60): 
            ?>
                <a href="?download_screen=<?=urlencode($dev_id)?>" class="btn-link green">📥 Скачать скриншот (Осталось <?=60 - $s_age?>с.)</a>
            <?php else: ?>
                <button style="background:#1f2937; color:#4b5563; cursor:not-allowed;" disabled>Снимок в памяти устарел</button>
            <?php endif; else: ?>
                <button style="background:#1f2937; color:#4b5563; cursor:not-allowed;" disabled>Скриншота в памяти нет</button>
            <?php endif; ?>
        </div>

        <hr style="border-color:#1f2937; margin:15px 0;">

        <form method="POST" action="index.php" style="margin-bottom:6px;">
            <input type="hidden" name="action" value="stop_client">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button type="submit" class="orange" onclick="return confirm('Выключить клиент программу?')">❌ Закрыть клиента</button>
        </form>

        <form method="POST" action="index.php" style="margin-bottom:6px;">
            <input type="hidden" name="action" value="shutdown">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button type="submit" class="red" onclick="return confirm('Выключить компьютер дистанционно?')">💻 Выключить ПК</button>
        </form>

        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($dev_id)?>">
            <button type="submit" class="gray-danger" onclick="return confirm('Удалить компьютер из базы данных панели?')">🗑 Удалить из базы</button>
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
            if(empty($logs)): 
                echo "<tr><td colspan='5' style='text-align:center;'>История пуста</td></tr>";
            else:
                $translateAction = ["take_screenshot" => "📸 Скриншот", "shutdown" => "💻 Выключение", "stop_client" => "❌ Остановка", "delete" => "🗑 Удаление"];
                foreach ($logs as $l):
                    if (!isset($l["id"]) || empty($l["device_id"])) continue;
                    $act = $translateAction[$l["action"] ?? ""] ?? ($l["action"] ?? "");
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
// Функция открытия/закрытия консоли процессов
function toggleConsole(id) {
    let box = document.getElementById(id);
    if(box.style.display === "block") {
        box.style.display = "none";
    } else {
        box.style.display = "block";
    }
}

// Железное автообновление страницы (F5) каждые 15 секунд
setInterval(function() {
    location.reload();
}, 15000);
</script>

</body>
</html>
