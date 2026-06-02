<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$result = $conn->query("
    SELECT * FROM clientes
    WHERE estado IN ('vencido','por_vencer')
    ORDER BY estado DESC, fecha_vencimiento ASC
");
$total_vencidos   = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='vencido'")->fetch_assoc()['t'];
$total_por_vencer = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='por_vencer'")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow — Avisos</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
:root {
  --bg:#0a0a0a; --card:#141414; --card2:#1a1a1a;
  --border:#222; --green:#00ff88; --green-s:rgba(0,255,136,.1);
  --red:#ff3333; --red-s:rgba(255,51,51,.1);
  --yellow:#ffcc00; --yellow-s:rgba(255,204,0,.1);
  --text:#f0f0f0; --muted:#555;
}
body { background:var(--bg); color:var(--text); font-family:'Segoe UI',sans-serif; }
nav { background:var(--card); border-bottom:1px solid var(--border); padding:1rem 2rem; display:flex; align-items:center; justify-content:space-between; }
.logo { font-size:1.3rem; font-weight:900; letter-spacing:2px; color:var(--green); }
.logo span { color:var(--text); }
.nav-links a { color:var(--muted); font-size:.8rem; text-decoration:none; margin-left:1.5rem; text-transform:uppercase; letter-spacing:1px; transition:color .15s; }
.nav-links a:hover { color:var(--green); }
.nav-links a.active { color:var(--green); }
main { max-width:900px; margin:0 auto; padding:2rem; }
h1 { font-size:1.6rem; font-weight:800; margin-bottom:.25rem; }
.sub { color:var(--muted); font-size:.85rem; margin-bottom:1.5rem; }
.top-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:2rem; }
.stats { display:flex; gap:1rem; flex-wrap:wrap; }
.stat { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:1rem 1.5rem; }
.stat-num { font-size:2.2rem; font-weight:900; line-height:1; }
.stat-label { font-size:.65rem; color:var(--muted); text-transform:uppercase; letter-spacing:1px; margin-top:.2rem; }
.red { color:var(--red); } .yellow { color:var(--yellow); }
.btn-auto { background:var(--card); border:1px solid var(--border); color:var(--text); padding:.75rem 1.5rem; border-radius:8px; cursor:pointer; font-size:.85rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:border-color .15s; }
.btn-auto:hover { border-color:var(--green); color:var(--green); }
.card { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:1.5rem; margin-bottom:.875rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; transition:border-color .15s; }
.card:hover { border-color:var(--border); }
.card.vencido   { border-left:3px solid var(--red); }
.card.por_vencer { border-left:3px solid var(--yellow); }
.info-nombre { font-weight:700; font-size:1rem; margin-bottom:.3rem; }
.info-detalle { font-size:.76rem; color:var(--muted); display:flex; flex-wrap:wrap; gap:.75rem; margin-bottom:.5rem; }
.badge { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:20px; font-size:.7rem; font-weight:700; letter-spacing:.3px; }
.badge.vencido   { background:var(--red-s); color:var(--red); border:1px solid rgba(255,51,51,.3); }
.badge.por_vencer { background:var(--yellow-s); color:var(--yellow); border:1px solid rgba(255,204,0,.3); }
.dias-badge { font-size:.72rem; font-weight:700; padding:3px 10px; border-radius:6px; }
.dias-badge.vencido   { background:var(--red-s); color:var(--red); }
.dias-badge.por_vencer { background:var(--yellow-s); color:var(--yellow); }
.actions { display:flex; gap:.6rem; flex-wrap:wrap; }
.btn-wa { display:inline-flex; align-items:center; gap:7px; background:#25D366; color:#fff; font-weight:700; font-size:.82rem; padding:.65rem 1.25rem; border-radius:8px; text-decoration:none; transition:background .15s; white-space:nowrap; }
.btn-wa:hover { background:#1cb254; }
.btn-renovar-link { display:inline-flex; align-items:center; gap:6px; background:var(--card2); border:1px solid var(--border); color:var(--text); font-weight:600; font-size:.78rem; padding:.6rem 1rem; border-radius:8px; text-decoration:none; transition:border-color .15s; white-space:nowrap; }
.btn-renovar-link:hover { border-color:var(--green); color:var(--green); }
.empty { text-align:center; padding:4rem 2rem; color:var(--muted); }
.empty-icon { font-size:3rem; margin-bottom:1rem; }
</style>
</head>
<body>

<nav>
  <div class="logo">GYM<span>FLOW</span></div>
  <div class="nav-links">
    <a href="automatizacion.php">Panel</a>
    <a href="avisos.php" class="active">Avisos</a>
    <a href="renovar.php">Renovar</a>
    <a href="estadisticas.php">Estadísticas</a>
    <a href="logout.php">Salir</a>
  </div>
</nav>

<main>
  <h1>📋 Avisos del día</h1>
  <p class="sub">Socios que necesitan recordatorio · <?= date('d/m/Y') ?></p>

  <div class="top-bar">
    <div class="stats">
      <div class="stat">
        <div class="stat-num red"><?= $total_vencidos ?></div>
        <div class="stat-label">Vencidos</div>
      </div>
      <div class="stat">
        <div class="stat-num yellow"><?= $total_por_vencer ?></div>
        <div class="stat-label">Por vencer</div>
      </div>
    </div>
    <a href="cron_estados.php" class="btn-auto">⚡ Ejecutar automatización</a>
  </div>

  <?php if ($result->num_rows === 0): ?>
    <div class="empty">
      <div class="empty-icon">✅</div>
      <p>No hay socios con pagos pendientes hoy.<br><small>Todos están al día.</small></p>
    </div>
  <?php else: ?>
    <?php while ($s = $result->fetch_assoc()):
      $primer_nombre = explode(' ', $s['nombre'])[0];
      $estado        = $s['estado'];

      // Calcular días
      $hoy   = new DateTime();
      $vence = new DateTime($s['fecha_vencimiento']);
      $diff  = (int)$hoy->diff($vence)->days;
      if ($estado === 'vencido') {
          $dias_txt = "Vencido hace {$diff} " . ($diff === 1 ? 'día' : 'días');
      } else {
          $dias_txt = "Vence en {$diff} " . ($diff === 1 ? 'día' : 'días');
      }

      // Mensaje automático
      if ($estado === 'vencido') {
          $msg   = "Hola {$primer_nombre} 👋 Te recordamos que tu membresía del gimnasio está vencida. Cuando puedas, comunicate con nosotros para renovarla. ¡Gracias! 💪";
          $label = "Vencido";
      } else {
          $msg   = "Hola {$primer_nombre} 👋 Te avisamos que tu membresía vence pronto. ¡Renovála para seguir entrenando sin interrupciones! 💪";
          $label = "Por vencer";
      }
      $link_wa = "https://wa.me/{$s['telefono']}?text=" . urlencode($msg);

      // Guardar en historial (evita duplicados del mismo día)
      $hoy_str     = date('Y-m-d');
      $ya_existe   = $conn->query("SELECT id FROM avisos_log WHERE cliente_id={$s['id']} AND DATE(fecha_envio)='{$hoy_str}' LIMIT 1")->num_rows;
      if (!$ya_existe) {
          $msg_esc = $conn->real_escape_string($msg);
          $conn->query("INSERT INTO avisos_log (cliente_id, tipo_aviso, mensaje) VALUES ({$s['id']}, '{$estado}', '{$msg_esc}')");
      }
    ?>
    <div class="card <?= $estado ?>">
      <div style="flex:1; min-width:200px">
        <span class="badge <?= $estado ?>"><?= $label ?></span>
        <div class="info-nombre"><?= htmlspecialchars($s['nombre']) ?></div>
        <div class="info-detalle">
          <span>📞 <?= $s['telefono'] ?></span>
          <span>📦 <?= ucfirst($s['plan']) ?></span>
          <span>📅 <?= date('d/m/Y', strtotime($s['fecha_vencimiento'])) ?></span>
        </div>
        <span class="dias-badge <?= $estado ?>"><?= $dias_txt ?></span>
      </div>
      <div class="actions">
        <a class="btn-wa" href="<?= $link_wa ?>" target="_blank">📲 Enviar aviso</a>
        <a class="btn-renovar-link" href="renovar.php">🔄 Renovar</a>
      </div>
    </div>
    <?php endwhile; ?>
  <?php endif; ?>
</main>

</body>
</html>
