<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
$csvUrl="https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=".time();
$scriptUrl="https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";
if($_SERVER['REQUEST_METHOD']==='POST'){

$params=[];

if(isset($_POST['update_name'])){
$params=[
'update_name'=>'1',
'ip'=>$_POST['ip'],
'name'=>$_POST['name']
];
}

if(isset($_POST['delete_name'])){
$params=[
'delete_name'=>'1',
'ip'=>$_POST['ip']
];
}

$ch=curl_init();

curl_setopt($ch,CURLOPT_URL,$scriptUrl);

curl_setopt($ch,CURLOPT_POST,true);

curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($params));

curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);

curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);

curl_setopt($ch,CURLOPT_TIMEOUT,20);

curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0');

$response=curl_exec($ch);

$error=curl_error($ch);

$http=curl_getinfo($ch,CURLINFO_HTTP_CODE);

curl_close($ch);

echo "<pre>";

echo "HTTP CODE:\n";
var_dump($http);

echo "\nRESPONSE:\n";
var_dump($response);

echo "\nERROR:\n";
var_dump($error);

echo "</pre>";

exit;
}
$ch=curl_init($csvUrl);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
$data=curl_exec($ch);
curl_close($ch);
$rows=[];
if($data&&trim($data)!=""){
$rows=array_map('str_getcsv',preg_split("/\r\n|\n|\r/",trim($data)));
}
$newDevices=[];
$knownDevices=[];
$usedIps=[];
foreach($rows as $index=>$row){
if($index==0)continue;
if(count($row)<3)continue;
$ip=isset($row[1])?trim($row[1]):'';
if($ip==''||$ip=='ip')continue;
if(isset($usedIps[$ip]))continue;
$usedIps[$ip]=true;
$time=isset($row[0])?trim($row[0]):'';
$status=isset($row[2])?trim($row[2]):'offline';
$name=isset($row[3])?trim($row[3]):'';
$device=['time'=>$time,'status'=>$status,'name'=>$name];
if($name==''){
$newDevices[$ip]=$device;
}else{
$knownDevices[$ip]=$device;
}
}
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
.container{
max-width:1200px;
}
.title{
font-size:34px;
font-weight:bold;
margin-bottom:30px;
}
.section{
margin-top:40px;
margin-bottom:20px;
font-size:24px;
font-weight:bold;
}
.card-box{
background:#0f172a;
border:1px solid #1e293b;
border-radius:18px;
padding:20px;
transition:0.2s;
height:100%;
}
.card-box:hover{
transform:translateY(-4px);
border-color:#3b82f6;
}
.pc-name{
font-size:22px;
font-weight:bold;
margin-bottom:15px;
}
.pc-info{
margin-bottom:8px;
color:#cbd5e1;
word-break:break-word;
}
.online{
color:#22c55e;
font-weight:bold;
}
.offline{
color:#ef4444;
font-weight:bold;
}
.custom-input{
background:#111827!important;
color:white!important;
border:1px solid #334155!important;
border-radius:12px!important;
padding:12px!important;
}
.custom-input::placeholder{
color:#94a3b8!important;
}
.custom-input:focus{
background:#111827!important;
color:white!important;
border-color:#3b82f6!important;
box-shadow:none!important;
}
.btn-save{
width:100%;
background:#2563eb;
border:none;
border-radius:12px;
padding:12px;
color:white;
font-weight:bold;
}
.btn-save:hover{
background:#1d4ed8;
}
.btn-delete{
width:100%;
background:#dc2626;
border:none;
border-radius:12px;
padding:12px;
color:white;
font-weight:bold;
}
.btn-delete:hover{
background:#b91c1c;
}
.empty{
background:#111827;
border-radius:14px;
padding:20px;
color:#94a3b8;
}
</style>
</head>
<body>
<div class="container py-5">
<div class="title">🖥 PC Control Panel</div>
<div class="section">🆕 Новые ПК</div>
<?php if(empty($newDevices)): ?>
<div class="empty">Нет новых устройств</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($newDevices as $ip=>$d): ?>
<div class="col-md-4">
<div class="card-box">
<div class="pc-name">⚠️ Новый ПК</div>
<div class="pc-info"><b>IP:</b> <?=htmlspecialchars($ip)?></div>
<div class="pc-info"><b>Статус:</b> <span class="<?=$d['status']=='online'?'online':'offline'?>"><?=$d['status']?></span></div>
<form method="POST" class="mt-4">
<input type="hidden" name="update_name" value="1">
<input type="hidden" name="ip" value="<?=htmlspecialchars($ip)?>">
<input type="text" name="name" class="form-control custom-input mb-3" placeholder="Введите имя ПК" required>
<button class="btn-save">Сохранить</button>
</form>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="section">💻 Известные ПК</div>
<?php if(empty($knownDevices)): ?>
<div class="empty">Нет известных устройств</div>
<?php else: ?>
<div class="row g-4">
<?php foreach($knownDevices as $ip=>$d): ?>
<div class="col-md-4">
<div class="card-box">
<div class="pc-name"><?=htmlspecialchars($d['name'])?></div>
<div class="pc-info"><b>IP:</b> <?=htmlspecialchars($ip)?></div>
<div class="pc-info"><b>Статус:</b> <span class="<?=$d['status']=='online'?'online':'offline'?>"><?=$d['status']=='online'?'🟢 Online':'🔴 Offline'?></span></div>
<div class="pc-info"><b>Последний сигнал:</b><br><?=htmlspecialchars($d['time'])?></div>
<form method="POST" class="mt-4">
<input type="hidden" name="delete_name" value="1">
<input type="hidden" name="ip" value="<?=htmlspecialchars($ip)?>">
<button class="btn-delete">Удалить имя</button>
</form>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</body>
</html>
