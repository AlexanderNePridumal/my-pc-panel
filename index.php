<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$csvUrl="https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv";

$data=@file_get_contents($csvUrl);
$rows=array_map('str_getcsv',explode("\n",trim($data)));

$devices=[];

foreach($rows as $r){
if(!isset($r[1])) continue;

$ip=trim($r[1]);
if($ip=="") continue;

$devices[$ip]=[
"time"=>$r[0]??"",
"ip"=>$ip,
"status"=>$r[2]??"",
"name"=>$r[3]??""
];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>PC Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{
background:#0b1220;
color:white;
font-family:Arial;
}

.title{
font-size:28px;
font-weight:bold;
margin-bottom:20px;
}

.card{
background:#111827;
border:1px solid #1f2937;
border-radius:15px;
padding:15px;
box-shadow:0 0 10px rgba(0,0,0,0.3);
transition:0.2s;
}

.card:hover{
transform:scale(1.02);
}

.status{
padding:4px 10px;
border-radius:10px;
font-size:12px;
display:inline-block;
}

.online{background:#16a34a;}
.offline{background:#dc2626;}

input{
background:#0b1220!important;
color:white!important;
border:1px solid #334155!important;
}

.btn{
width:100%;
}
</style>
</head>
<body>

<div class="container py-4">

<div class="title">🖥 PC Panel</div>

<div class="row g-3">

<?php foreach($devices as $d): ?>

<div class="col-md-4">

<div class="card">

<h5>
<?= $d['name'] ? htmlspecialchars($d['name']) : "⚠️ Новый ПК" ?>
</h5>

<div class="mb-2">IP: <?=htmlspecialchars($d['ip'])?></div>
<div class="mb-2">Time: <?=htmlspecialchars($d['time'])?></div>

<span class="status <?=($d['status']=='online')?'online':'offline'?>">
<?=htmlspecialchars($d['status'])?>
</span>

<?php if(empty($d['name'])): ?>

<form method="POST" class="mt-3">
<input type="hidden" name="ip" value="<?=htmlspecialchars($d['ip'])?>">
<input class="form-control mb-2" name="name" placeholder="Введите имя ПК" required>
<button class="btn btn-primary">Сохранить</button>
</form>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

</body>
</html>
