<?php ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Climat Local – Audit & Prospec</title>

<style>
    body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:20px; }
    h1 { margin-bottom:20px; }
    #scanButton {
        padding:12px 20px; background:#2b7cff; color:white;
        border:none; border-radius:6px; cursor:pointer; font-size:16px;
    }
    #scanButton:hover { background:#1a5fd1; }
    #result {
        margin-top:25px; padding:15px; background:white;
        border-radius:6px; box-shadow:0 0 5px rgba(0,0,0,0.1);
        white-space:pre-wrap;
    }
</style>
</head>

<body>

<h1>Audit Climat Local</h1>

<button id="scanButton">Lancer le Scan</button>

<div id="result">Résultat du scan affiché ici…</div>

<script src="assets/js/scan.js"></script>

</body>
</html>
