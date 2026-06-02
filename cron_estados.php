<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$hoy          = date('Y-m-d');
$limite_aviso = date('Y-m-d', strtotime('+3 days'));

// 1. Marcar VENCIDO
$conn->query("UPDATE clientes SET estado='vencido'    WHERE fecha_vencimiento < '$hoy' AND estado != 'vencido'");
$vencidos   = $conn->affected_rows;

// 2. Marcar POR VENCER
$conn->query("UPDATE clientes SET estado='por_vencer' WHERE fecha_vencimiento BETWEEN '$hoy' AND '$limite_aviso' AND estado='activo'");
$por_vencer = $conn->affected_rows;

// 3. Confirmar ACTIVOS
$conn->query("UPDATE clientes SET estado='activo'     WHERE fecha_vencimiento > '$limite_aviso' AND estado != 'activo'");
$activos    = $conn->affected_rows;

// Si lo llaman por fetch (desde automatizacion.php) devuelve JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'vencidos'   => $vencidos,
        'por_vencer' => $por_vencer,
        'activos'    => $activos,
        'fecha'      => $hoy
    ]);
    exit;
}

// Si lo abren directo en el navegador, muestra resultado visual
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>GymFlow — Automatización ejecutada</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#0a0a0a; color:#f0f0f0; font-family:'Segoe UI',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; }
.box { background:#141414; border:1px solid #222; border-radius:16px; padding:2.5rem; max-width:420px; width:90%; text-align:center; }
h2 { font-size:1.4rem; font-weight:800; margin-bottom:1.5rem; color:#00ff88; }
.item { display:flex; align-items:center; gap:.75rem; background:#1a1a1a; border-radius:10px; padding:1rem; margin-bottom:.75rem; }
.item-num { font-size:1.8rem; font-weight:900; width:50px; text-align:center; }
.item-label { font-size:.85rem; color:#888; }
.red { color:#ff3333; } .yellow { color:#ffcc00; } .green { color:#00ff88; }
.fecha { font-size:.78rem; color:#555; margin-top:1rem; }
.btn { display:block; margin-top:1.5rem; background:#00ff88; color:#000; font-weight:800; padding:.875rem; border-radius:10px; text-decoration:none; font-size:.9rem; }
.btn:hover { opacity:.85; }
</style>
</head>
<body>
<div class="box">
  <h2>⚡ Automatización ejecutada</h2>
  <div class="item"><span class="item-num red"><?= $vencidos ?></span><div><div class="item-label">🔴 Vencidos actualizados</div></div></div>
  <div class="item"><span class="item-num yellow"><?= $por_vencer ?></span><div><div class="item-label">🟡 Por vencer actualizados</div></div></div>
  <div class="item"><span class="item-num green"><?= $activos ?></span><div><div class="item-label">🟢 Activos confirmados</div></div></div>
  <div class="fecha">📅 Fecha analizada: <?= date('d/m/Y') ?></div>
  <a href="automatizacion.php" class="btn">← Volver al panel</a>
</div>
</body>
</html>
