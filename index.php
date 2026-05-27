<?php
ob_start();
session_start();

// 1. НАСТРОЙКИ
$api = "https://script.google.com/macros/s/AKfycbxt9RdUegrIhotBPNQRs6_Nkb3Hy0NF2IJdpL3XyYZXtPbptFhYtUWfAse3Z10VhHCC/exec"; // ВСТАВЬ СВОЙ URL

// 2. ОБРАБОТКА POST-ЗАПРОСОВ (КНОПКИ)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $device_id = trim($_POST["device_id"] ?? "");
    if (!empty($action) && !empty($device_id)) {
        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["action" => $action, "device_id" => $device_id]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_exec($ch);
        curl_close($ch);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 3. ФУНКЦИЯ ДЛЯ GET-ЗАПРОСОВ К ТАБЛИЦЕ
function fetchApi($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?: [];
}

$devices = fetchApi($api);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления</title>
    <style>
        body { font-family: sans-serif; background: #0f172a; color: white; padding: 20px; }
        .card { background: #1e293b; padding: 15px; border-radius: 10px; margin-bottom: 10px; display: inline-block; width: 300px; vertical-align: top; }
        button { padding: 10px; width: 100%; margin-top: 5px; cursor: pointer; border: none; border-radius: 5px; }
        .btn-blue { background: #3b82f6; color: white; }
        .btn-red { background: #ef4444; color: white; }
    </style>
</head>
<body>

<h1>🖥 Мониторинг ПК</h1>

<div id="container">
    <?php foreach($devices as $d): ?>
    <div class="card">
        <h3><?=htmlspecialchars($d['name'])?></h3>
        <p>ID: <?=htmlspecialchars($d['device_id'])?></p>
        <p>Последняя активность: <span class="time"><?=$d['time']?></span></p>

        <form method="POST" action="">
            <input type="hidden" name="action" value="take_screenshot">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($d['device_id'])?>">
            <button type="submit" class="btn-blue" onclick="return confirm('Запросить скриншот?')">📸 Запросить скриншот</button>
        </form>

        <form method="POST" action="">
            <input type="hidden" name="action" value="shutdown">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($d['device_id'])?>">
            <button type="submit" class="btn-red" onclick="return confirm('Выключить ПК?')">💻 Выключить ПК</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<script>
    // АВТООБНОВЛЕНИЕ СТРАНИЦЫ (F5)
    // Страница будет перезагружаться каждые 30 секунд, чтобы обновлять список ПК
    setInterval(function() {
        location.reload();
    }, 30000); 
</script>

</body>
</html>
