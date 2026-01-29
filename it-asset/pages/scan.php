<?php
// pages/scan.php - 手机扫码（中文）
?>
<?php include __DIR__ . "/../templates/header.php"; ?>
<h1>手机扫码识别</h1>
<p>在手机浏览器打开此页面，允许访问摄像头，点击“开始摄像头”即可扫描并跳转到对应资产详情。</p>
<video id="video" autoplay playsinline class="w-100" style="max-height:60vh;background:#000;"></video>
<div id="output" class="mt-2">等待扫码…</div>
<button id="startBtn" class="btn btn-primary mt-2">开始摄像头</button>

<script src="https://unpkg.com/jsqr/dist/jsQR.js"></script>
<script src="wwwroot/js/asset-scan.js"></script>

<?php include __DIR__ . "/../templates/footer.php"; ?>