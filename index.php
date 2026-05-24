<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$api="https://script.google.com/macros/s/AKfycbz0WiJxJzgOqGKnoxrbV44oetcbcBvcBTjv5d1q1D8DYL9eqUZv95Rcf3IRWRSIkImiiw/exec";

function getData($url){

$ch=curl_init($url);

curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($ch,CURLOPT_TIMEOUT,15);
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0');

$response=curl_exec($ch);
$error=curl_error($ch);
$http=curl_getinfo($ch,CURLINFO_HTTP_CODE);

curl_close($ch);

return [
'response'=>$response,
'error'=>$error,
'http'=>$http
];

}

$data=getData($api);

$json=$data['response'];

$devices=json_decode($json,true);

$jsonError=json_last_error_msg();

$valid=is_array($devices);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PC Panel Debug</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0f172a;color:white;font-family:Arial}
.box{background:#111827;padding:15px;border-radius:12px;margin-bottom:15px}
.bad{color:#ef4444}
.good{color:#22c55e}
</style>
</head>
<body>
<div class="container py-4">

<h2>🖥 PC Panel DEBUG</h2>

<div class="box">
<h4>HTTP CODE:</h4>
<pre><?php var_dump($data['http']); ?></pre>

<h4>CURL ERROR:</h4>
<pre class="bad"><?php var_dump($data['error']); ?></pre>

<h4>RAW RESPONSE:</h4>
<pre><?php echo htmlspecialchars($json); ?></pre>

<h4>JSON ERROR:</h4>
<pre class="bad"><?php var_dump($jsonError); ?></pre>

<h4>IS VALID JSON:</h4>
<pre><?php var_dump($valid); ?></pre>
</div>

<hr>

<h3>DATA:</h3>

<?php if(!$valid): ?>
<div class="box bad">
❌ Данные не загружены или не JSON
</div>
<?php else: ?>

<div class="row g-3">

<?php foreach($devices as $d): ?>
<div class="col-md-4">
<div class="box">
<b>Name:</b> <?=htmlspecialchars($d['name']??'')?> <br>
<b>IP:</b> <?=htmlspecialchars($d['ip']??'')?> <br>
<b>Status:</b> <?=htmlspecialchars($d['status']??'')?> <br>
<b>Time:</b> <?=htmlspecialchars($d['time']??'')?>
</div>
</div>
<?php endforeach; ?>

</div>

<?php endif; ?>

</div>
</body>
</html>
