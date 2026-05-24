<?php
// Включаем сессии, чтобы Vercel мог хранить команды и логи в памяти сервера
session_start();

// Если файлов сессии еще нет, создаем их пустыми
if (!isset($_SESSION['cmd'])) $_SESSION['cmd'] = 'none';
if (!isset($_SESSION['log'])) $_SESSION['log'] = 'Нет данных от ПК';
if (!isset($_SESSION['screen'])) $_SESSION['screen'] = '';
if (!isset($_SESSION['screen_time'])) $_SESSION['screen_time'] = 'нет';

// 1. Программа запрашивает команду
if (isset($_GET['get_cmd'])) {
    echo $_SESSION['cmd'];
    exit;
}

// 2. Программа стирает команду после выполнения
if (isset($_GET['clear_cmd'])) {
    $_SESSION['cmd'] = 'none';
    echo "ok";
    exit;
}

// 3. Программа присылает текстовый отчет
if (isset($_POST['report'])) {
    $_SESSION['log'] = $_POST['report'];
    echo "ok";
    exit;
}

// 4. Программа присылает скриншот (сохраняем картинку прямо в память в виде текста Base64)
if (isset($_FILES['screen'])) {
    $imgData = file_get_contents($_FILES['screen']['tmp_name']);
    $_SESSION['screen'] = base64_encode($imgData);
    $_SESSION['screen_time'] = date("H:i:s");
    echo "ok";
    exit;
}

// 5. Кнопка на сайте ставит новую команду
if (isset($_POST['set_cmd'])) {
    $cmd = $_POST['set_cmd'];
    if ($cmd == 'delete' && !empty($_POST['proc_name'])) {
        $cmd .= ' ' . trim($_POST['proc_name']);
    }
    $_SESSION['cmd'] = $cmd;
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель Управления ПК</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a24; color: #fff; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .container { max-width: 900px; width: 100%; background: #242432; padding: 25px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
        h1 { text-align: center; color: #4f46e5; margin-top: 0; font-size: 24px; }
        .status-bar { background: #1e1e2a; padding: 12px; border-radius: 8px; text-align: center; font-size: 14px; margin-bottom: 20px; border-left: 4px solid #4f46e5; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 25px; }
        button { background: #4f46e5; color: white; border: none; padding: 14px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.2s; width: 100%; }
        button:hover { background: #4338ca; transform: translateY(-2px); }
        button.danger { background: #dc2626; }
        button.danger:hover { background: #b91c1c; }
        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; color: #a5b4fc; }
        .console { background: #11111b; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 13px; max-height: 200px; overflow-y: auto; white-space: pre-wrap; color: #34d399; border: 1px solid #2e2e3e; }
        .screen-box { text-align: center; margin-top: 25px; background: #1e1e2a; padding: 15px; border-radius: 8px; }
        .screen-box img { max-width: 100%; height: auto; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.5); border: 1px solid #3e3e4e; }
        .delete-box { display: flex; gap: 8px; background: #1e1e2a; padding: 10px; border-radius: 8px; grid-column: span 2; align-items: center; }
        .delete-box input { flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #3e3e4e; background: #11111b; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🖥 Моя Контрольная Панель (Vercel Cloud)</h1>
    <div class="status-bar">Ожидающая команда: <span style="color: #60a5fa; font-weight:bold;"><?php echo htmlspecialchars($_SESSION['cmd']); ?></span></div>
    <div class="section-title">🎮 Команды управления</div>
    <div class="grid">
        <form method="POST" style="display:contents;"><button name="set_cmd" value="screen">📸 Сделать Скриншот</button></form>
        <form method="POST" style="display:contents;"><button name="set_cmd" value="app">📊 Список Приложений</button></form>
        <form method="POST" style="display:contents;"><button name="set_cmd" value="off_pc" class="danger">🔌 Выключить Компьютер</button></form>
        <form method="POST" style="display:contents;"><button name="set_cmd" value="off_programm" class="danger">🛑 Закрыть Программу</button></form>
        <form method="POST" class="delete-box">
            <input type="text" name="proc_name" placeholder="Имя процесса (например: chrome)" required>
            <button name="set_cmd" value="delete" class="danger" style="padding: 10px 15px; width:auto;">Убить</button>
        </form>
    </div>
    <div class="section-title">📋 Отчет о процессах / Системный лог</div>
    <div class="console"><?php echo htmlspecialchars($_SESSION['log']); ?></div>
    <div class="screen-box">
        <div class="section-title" style="margin-bottom:15px;">📺 Монитор экрана (Обновлен: <?php echo $_SESSION['screen_time']; ?>)</div>
        <?php if(!empty($_SESSION['screen'])): ?>
            <img src="data:image/jpeg;base64,<?php echo $_SESSION['screen']; ?>" alt="Экран ПК">
        <?php else: ?>
            <p style="color: #6b7280; font-style: italic;">Скриншотов пока не поступало</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
