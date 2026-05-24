<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Control Center | RAT Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #e2e8f0; font-family: 'Segoe UI', sans-serif; }
        .card { background: #1e293b; border: 1px solid #334155; transition: 0.3s; }
        .btn-sm { font-size: 0.75rem; }
        .status-pill { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; }
        .console { font-family: monospace; font-size: 11px; background: #020617; color: #38bdf8; height: 100px; overflow-y: auto; padding: 8px; }
        .img-thumb { width: 100px; height: 60px; object-fit: cover; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body class="p-4">
    <div class="container-fluid">
        <h2 class="mb-4 text-white">Управление устройствами</h2>
        <div id="main-grid" class="row g-3">
            </div>
    </div>

    <script>
        // Функция автообновления данных каждые 5 секунд без перезагрузки
        function updateDashboard() {
            fetch(window.location.href)
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    document.getElementById('main-grid').innerHTML = doc.getElementById('main-grid').innerHTML;
                });
        }
        setInterval(updateDashboard, 5000);
    </script>
</body>
</html>
