<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Control Center</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; align-items: center; }
        .container { width: 90%; max-width: 800px; background: #1e293b; padding: 20px; border-radius: 12px; margin-top: 20px; }
        .pc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .pc-card { background: #334155; padding: 15px; border-radius: 8px; text-align: center; border: 2px solid #475569; }
        button { background: #6366f1; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; width: 100%; margin-top: 5px; }
        button:hover { background: #4f46e5; }
        .screenshot-area { margin-top: 30px; border-top: 2px solid #334155; padding-top: 20px; }
        img { max-width: 100%; border-radius: 8px; border: 2px solid #6366f1; }
    </style>
</head>
<body>

<div class="container">
    <h1>🚀 Панель управления</h1>
    
    <div class="pc-grid">
        <?php
        // Список активных ПК (можно хранить в файле)
        $pcs = ['PC-01', 'PC-02', 'PC-03']; 
        foreach ($pcs as $pc) {
            echo "<div class='pc-card'>
                    <h3>$pc</h3>
                    <form method='POST'>
                        <input type='hidden' name='pc_id' value='$pc'>
                        <button name='set_cmd' value='screen'>📸 Скриншот</button>
                        <button name='set_cmd' value='app'>📋 Список окон</button>
                    </form>
                  </div>";
        }
        ?>
    </div>

    <div class="screenshot-area">
        <h2>🖼 Последние скриншоты</h2>
        <?php
        $files = glob("*.jpg"); // Ищем все скриншоты
        foreach ($files as $file) {
            echo "<div style='margin-bottom: 20px;'>
                    <p>Файл: $file</p>
                    <img src='$file?" . time() . "'>
                  </div>";
        }
        ?>
    </div>
</div>

</body>
</html>
