<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$api="https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

if($_SERVER['REQUEST_METHOD']==='POST'){

if(isset($_POST['name'])){
$url=$api."?action=set_name&ip=".urlencode($_POST['ip'])."&name=".urlencode($_POST['name']);
file_get_contents($url);
}

header("Location: /");
exit;
}

$ch=curl_init($api);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
$json=curl_exec($ch);
curl_close($ch);

$devices=json_decode($json,true);
if(!is_array($devices)) $devices=[];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PC Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0f172a;color:white;font-family:Arial}
.card{background:#111827;border:1px solid #1f2937;border-radius:14px;padding:15px}
input{background:#0b1220!important;color:white!important;border:1px solid #334155!important}
</style>
</head>
<body>
<div class="container py-4">
<h2 class="mb-4">🖥 PC Panel</h2>
<div class="row g-3">
<?php foreach($devices as $d): ?>
<div class="col-md-4">
<div class="card">
<h5><?= $d['name'] ? htmlspecialchars($d['name']) : "⚠️ Новый ПК" ?></h5>
<p>IP: <?=htmlspecialchars($d['ip'])?></p>
<p>Status: <?=htmlspecialchars($d['status'])?></p>
<p>Time: <?=htmlspecialchars($d['time'])?></p>

<?php if(empty($d['name'])): ?>
<form method="POST">
<input type="hidden" name="ip" value="<?=htmlspecialchars($d['ip'])?>">
<input class="form-control mb-2" name="name" placeholder="Имя ПК" required>
<button class="btn btn-primary w-100">Сохранить</button>
</form>
<?php endif; ?>

</div>
</div>
<?php endforeach; ?>
</div>
</div>
</body>
</html>
