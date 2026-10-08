<?php
// /api/prospect.php
// Redirection transparente vers le sous-onglet Kit Prospection de l'analyse client
$idParam = isset($_GET['id']) ? '&id=' . intval($_GET['id']) : '';
$urlParam = isset($_GET['url']) ? '&url=' . urlencode($_GET['url']) : '';
header('Location: /api/analyse.php?tab=kit' . $idParam . $urlParam);
exit;
