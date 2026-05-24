<?php
$imgDir = 'screenshots/';
$namesFile = 'names.json';
if (!is_dir($imgDir)) mkdir($imgDir);

// 1. Загрузка имен ПК из файла
$names = file_exists($namesFile) ? json_decode(file_get_contents($namesFile), true) : [];

// 2. Обработка действий (POST запросы)
if (isset($_POST['set_name'])) {
    $names[$_POST['pc_id']] = $_POST['new_name'];
    file_put_contents($namesFile, json_encode($names));
    header("Location: /"); exit;
}

if (isset($_POST['set_cmd'])) {
    $cmd = $_POST['set_cmd'];
    if ($cmd == 'delete') $cmd .= ' ' . ($_POST['proc_name'] ?? '');
    file_put_contents("cmd_" . $_POST['pc_id'] . ".txt", $cmd);
    header("Location: /"); exit;
}

// 3. Обработка API для бота
if (isset($_FILES['screen'])) {
    move_uploaded_file($_FILES['screen']['tmp_name'], $imgDir . $_FILES['screen']['name']);
    echo "ok"; exit;
}

if (isset($_POST['log'])) {
    $pc_id = $_POST['pc_id'];
    $new_data = "--- " . date("H:i:s") . " ---\n" . $_POST['log'] . "\n";
    $file = "history_$pc_id.txt";
    $history = file_exists($file) ? explode("---", file_get_contents($file)) : [];
    array_unshift($history, $new_data);
    file_put_contents($file, implode("---", array_slice($history, 0, 10)));
    echo "ok"; exit;
}

if (isset($_GET['get_cmd'])) {
    echo file_exists("cmd_" . $_GET['pc_id'] . ".txt") ? file_get_contents("cmd_" . $_GET['pc_id'] . ".txt") : 'none';
    exit;
}

if (isset($_GET['clear_cmd'])) {
    file_put_contents("cmd_" . $_GET['pc_id'] . ".txt", 'none');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Control Panel</title>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 20px; }
        .pc-card { background: #1e293b; padding: 20px; margin: 15px; border-radius: 12px; border: 1px solid #475569; width: 480px; display: inline-block; vertical-align: top; }
        pre { background: #000; padding: 12px; height: 350px; overflow-y: auto; color: #38bdf8; font-size: 13px; border-radius: 6px; }
        button { background: #6366f1; border: none; color: white; padding: 10px; border-radius: 6px; cursor: pointer; margin-top: 5px; }
        input { padding: 8px; border-radius: 4px; border: none; width: 120px; }
        #modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); justify-content:center; align-items:center; z-index:999; }
    </style>
</head>
<body>
    <h1>Панель управления</h1>
    
    <?php 
    $pcs = ['PC-01', 'PC-02']; // Список ID твоих ПК
    foreach($pcs as $pc) {
        $displayName = $names[$pc] ?? $pc;
        $history = file_exists("history_$pc.txt") ? file_get_contents("history_$pc.txt") : "Нет истории";
        echo "<div class='pc-card'>
                <h3>$displayName</h3>
                <form method='POST'>
                    <input type='hidden' name='pc_id' value='$pc'>
                    <input type='text' name='new_name' placeholder='Новое имя'>
                    <button name='set_name' value='1'>✏️ Переименовать</button>
                </form>
                <form method='POST' style='margin-top:10px;'>
                    <input type='hidden' name='pc_id' value='$pc'>
                    <button name='set_cmd' value='screen'>📸 Скрин</button>
                    <button name='set_cmd' value='app'>📋 Окна</button>
                    <input type='text' name='proc_name' placeholder='Имя процесса'>
                    <button name='set_cmd' value='delete'>❌ Kill</button>
                </form>
                <p>История действий:</p><pre>$history</pre>
              </div>";
    }
    ?>

    <h2>Галерея скриншотов</h2>
    <div id="modal" onclick="this.style.display='none'"><img id="modal-img" style="max-width:90%; max-height:90%"></div>
    <?php
    $files = glob("$imgDir*.jpg");
    rsort($files);
    foreach ($files as $file) {
        echo "<img src='$file' style='width:150px; cursor:pointer; margin:5px;' onclick=\"document.getElementById('modal-img').src='$file'; document.getElementById('modal').style.display='flex'\">";
    }
    ?>
</body>
</html>
