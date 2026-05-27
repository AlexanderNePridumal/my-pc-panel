<?php
ob_start();
session_start();

// ВСТАВЬ СВОЙ URL GOOGLE SCRIPT НИЖЕ
$api = "https://script.google.com/macros/s/AKfycbxt9RdUegrIhotBPNQRs6_Nkb3Hy0NF2IJdpL3XyYZXtPbptFhYtUWfAse3Z10VhHCC/exec";

// УНИВЕРСАЛЬНЫЙ POST-ОБРАБОТЧИК ДЛЯ ВСЕХ КНОПОК
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
    <title>Управление ПК</title>
    <style>
        body { font-family: sans-serif; background: #0f172a; color: white; padding: 20px; }
        .container { display: flex; flex-wrap: wrap; gap: 15px; }
        .card { background: #1e293b; padding: 20px; border-radius: 12px; width: 280px; }
        button { width: 100%; margin: 5px 0; padding: 8px; border: none; border-radius: 6px; cursor: pointer; color: white; font-weight: bold; }
        .btn-blue { background: #3b82f6; }
        .btn-orange { background: #f59e0b; }
        .btn-red { background: #ef4444; }
        .btn-gray { background: #4b5563; }
    </style>
</head>
<body>

<h1>🖥 Мониторинг сети</h1>
<div class="container">
    <?php foreach($devices as $d): ?>
    <div class="card">
        <h3><?=htmlspecialchars($d['name'] ?? 'ПК')?></h3>
        <p>ID: <?=htmlspecialchars($d['device_id'])?></p>
        <p>Последний пинг: <?=htmlspecialchars($d['time'])?></p>
        
        <?php 
        $btns = [
            ['action' => 'take_screenshot', 'text' => '📸 Скриншот', 'class' => 'btn-blue'],
            ['action' => 'stop_client', 'text' => '❌ Остановить клиент', 'class' => 'btn-orange'],
            ['action' => 'shutdown', 'text' => '💻 Выключить ПК', 'class' => 'btn-red'],
            ['action' => 'delete', 'text' => '🗑 Удалить из базы', 'class' => 'btn-gray']
        ];
        
        foreach ($btns as $b): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?=$b['action']?>">
            <input type="hidden" name="device_id" value="<?=htmlspecialchars($d['device_id'])?>">
            <button type="submit" class="<?=$b['class']?>" onclick="return confirm('Подтвердить действие?')"><?=$b['text']?></button>
        </form>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>

<script>
    // Автообновление каждые 15 секунд
    setInterval(() => location.reload(), 15000);
</script>

</body>
</html>
