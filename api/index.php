<?php
// Путь к файлу базы данных SQLite
$dbFile = __DIR__ . '/database.sqlite';

// Открываем соединение с базой данных
try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Создаем таблицу, если она еще не создана
    $db->exec("CREATE TABLE IF NOT EXISTS control (key TEXT PRIMARY KEY, value TEXT)");
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// Функции работы с базой
function setVal($k, $v) { 
    global $db; 
    $stmt = $db->prepare("INSERT OR REPLACE INTO control (key, value) VALUES (?, ?)"); 
    $stmt->execute([$k, $v]); 
}

function getVal($k) { 
    global $db; 
    $stmt = $db->prepare("SELECT value FROM control WHERE key = ?"); 
    $stmt->execute([$k]); 
    return $stmt->fetchColumn() ?: 'none'; 
}

// --- ЛОГИКА ОБЩЕНИЯ С C# ПРОГРАММОЙ ---

// 1. Программа спрашивает: "Есть команды?"
if (isset($_GET['get_cmd'])) {
    echo getVal('cmd');
    exit;
}

// 2. Программа говорит: "Команду выполнил, сотри её"
if (isset($_GET['clear_cmd'])) {
    setVal('cmd', 'none');
    echo "ok";
    exit;
}

// 3. Программа отправляет лог или отчет
if (isset($_POST['report'])) {
    setVal('log', $_POST['report']);
    echo "ok";
    exit;
}

// 4. Программа присылает скриншот (в Base64)
if (isset($_FILES['screen'])) {
    $imgData = file_get_contents($_FILES['screen']['tmp_name']);
    setVal('screen', base64_encode($imgData));
    setVal('screen_time', date("H:i:s"));
    echo "ok";
    exit;
}

// --- ЛОГИКА ИНТЕРФЕЙСА (САЙТА) ---

// Кнопки управления на сайте
if (isset($_POST['set_cmd'])) {
    $cmd = $_POST['set_cmd'];
    // Если команда delete, добавляем имя процесса
    if ($cmd == 'delete' && !empty($_POST['proc_name'])) {
        $cmd .= ' ' . trim($_POST['proc_name']);
    }
    setVal('cmd', $cmd);
    header("Location: /");
    exit;
}

$currentCmd = getVal('cmd');
$currentLog = getVal('log');
$screenBase64 = getVal('screen');
$screenTime = getVal('screen_time');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления</title>
    <style>
        body { background: #1a1a24; color: #fff; font-family: sans-serif; text-align: center; padding: 20px; }
        button { background: #4f46e5; color: white; border: none; padding: 15px; margin: 5px; border-radius: 8px; cursor: pointer; }
        .console { background: #000; padding: 15px; border-radius: 8px; color: #34d399; margin: 20px auto; max-width: 600px; text-align: left; }
    </style>
</head>
<body>
    <h1>Управление ПК</h1>
    <p>Текущая команда: <b><?php echo htmlspecialchars($currentCmd); ?></b></p>
    
    <form method="POST">
        <button name="set_cmd" value="screen">Сделать скриншот</button>
        <button name="set_cmd" value="app">Список окон</button>
        <input type="text" name="proc_name" placeholder="Процесс для kill">
        <button name="set_cmd" value="delete">Убить процесс</button>
    </form>

    <div class="console">
        <h3>Лог работы:</h3>
        <?php echo nl2br(htmlspecialchars($currentLog)); ?>
    </div>

    <?php if($screenBase64 !== 'none'): ?>
        <h3>Экран (<?php echo $screenTime; ?>):</h3>
        <img src="data:image/jpeg;base64,<?php echo $screenBase64; ?>" style="max-width:800px;">
    <?php endif; ?>
</body>
</html>
