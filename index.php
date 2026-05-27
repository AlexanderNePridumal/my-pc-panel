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
$translateAction = [
    "take_screenshot" => "📸 Скриншот экрана", 
    "shutdown" => "💻 Выключение ПК", 
    "stop_client" => "❌ Остановка клиента", 
    "delete" => "🗑 Удаление из системы"
];

// ФОНОВЫЙ AJAX ЗАПРОС ОБНОВЛЕНИЯ ДАННЫХ И ЛОГОВ
if (isset($_GET['ajax_update'])) {
    $devices = getData($api);
    $logs = getData($api . "?get_logs=1");
    
    $dev_status = [];
    foreach ($devices as $d) {
        $dev_id = $d["device_id"]; 
        $last = strtotime($d["time"] ?? ""); 
        $age_heartbeat = time() - $last;
        
        // ФИКС АКТИВНОСТИ: Если был в сети последние 35 секунд — ОНЛАЙН
        $status = ($age_heartbeat <= 35) ? "online" : "offline";
        
        if ($age_heartbeat < 60) {
            $time_str = $age_heartbeat . " сек. назад";
        } else {
            $time_str = floor($age_heartbeat/60) . " мин. назад";
        }
        
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

// ИСПРАВЛЕННЫЙ БЛОК ОТПРАВКИ КОМАНД В GOOGLE SCRIPT ИЗ PHP
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? ""; 
    $device_id = trim($_POST["device_id"] ?? "");

    if (!empty($action) && !empty($device_id)) {
        $postData = ["action" => $action, "device_id" => $device_id];
        if ($action === "set_name") {
            $postData["name"] = $_POST["name"] ?? "";
        }
        
        $ch = curl_init($api); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData)); 
        curl_exec($ch); 
        curl_close($ch);
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
body{ margin:0; font-family:system-ui, -apple-system, sans-serif; background:#090d16; color:#e2e8f0; padding-bottom:50px;}
.header{ padding:18px 24px; background:#0f172a; border-bottom:1px solid #1e293b; font-weight:bold; font-size:19px; letter-spacing: 0.5px;}
.container{ padding:24px; display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:20px; }
.card{ background:#111827; border:1px solid #1f2937; border-radius:14px; padding:20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); }
.name{ font-weight:700; font-size:17px; margin-bottom:6px; color:#f8fafc;}
.status{ font-size:11px; padding:4px 10px; border-radius:999px; display:inline-block; font-weight:bold; text-transform:uppercase; letter-spacing: 0.5px;}
.
