<?php

$api="ТВОЙ_APPS_SCRIPT_URL";

if($_SERVER['REQUEST_METHOD']==='POST'){

$url=$api.
"?action=set_name".
"&ip=".urlencode($_POST['ip']).
"&name=".urlencode($_POST['name']);

file_get_contents($url);

header("Location: /");

exit;

}

$json=file_get_contents($api);

$devices=json_decode($json,true);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PC Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{
background:#020617;
color:white;
font-family:Arial;
}
.card{
background:#0f172a;
border:1px solid #1e293b;
border-radius:18px;
padding:20px;
height:100%;
}
input{
background:#111827!important;
color:white!important;
border:1px solid #334155!important;
}
.container{
max-width:1200px;
}
</style>
</head>
<body>

<div class="container py-5">

<h1 class="mb-5">🖥 PC Panel</h1>

<div class="row g-4">

<?php foreach($devices as $d): ?>

<div class="col-md-4">

<div class="card">

<h4>
<?= $d['name'] ? htmlspecialchars($d['name']) : '⚠️ Новый ПК' ?>
</h4>

<p><b>IP:</b> <?= htmlspecialchars($d['ip']) ?></p>

<p>
<b>Статус:</b>

<?= $d['status']=='online'
? '🟢 Online'
: '🔴 Offline' ?>

</p>

<p>
<b>Последний сигнал:</b><br>
<?= htmlspecialchars($d['time']) ?>
</p>

<?php if(empty($d['name'])): ?>

<form method="POST">

<input type="hidden"
name="ip"
value="<?= htmlspecialchars($d['ip']) ?>">

<input
type="text"
name="name"
class="form-control mb-3"
placeholder="Имя ПК"
required>

<button class="btn btn-primary w-100">
Сохранить
</button>

</form>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

</body>
</html>
