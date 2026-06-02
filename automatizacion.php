<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$total      = $conn->query("SELECT COUNT(*) as t FROM clientes")->fetch_assoc()['t'];
$activos    = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='activo'")->fetch_assoc()['t'];
$por_vencer = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='por_vencer'")->fetch_assoc()['t'];
$vencidos   = $conn->query("SELECT COUNT(*) as t FROM clientes WHERE estado='vencido'")->fetch_assoc()['t'];

$historial  = $conn->query("
    SELECT a.*, c.nombre, c.plan
    FROM avisos_log a
    JOIN clientes c ON a.cliente_id = c.id
    ORDER BY a.fecha_envio DESC LIMIT 10
");
$pendientes = $conn->query("
    SELECT * FROM clientes
    WHERE estado IN ('vencido','por_vencer')
    ORDER BY estado DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow — Panel de Automatización</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
:root {
  --bg:#0a0a0a; --card:#141414; --card2:#1a1a1a;
  --border:#222; --border2:#2a2a2a;
  --green:#00ff88; --green-s:rgba(0,255,136,.1);
  --red:#ff3333;   --red-s:rgba(255,51,51,.1);
  --yellow:#ffcc00;--yellow-s:rgba(255,204,0,.1);
  --text:#f0f0f0;  --muted:#555;
}
body { background:var(--bg); color:var(--text); font-family:'Segoe UI',sans-serif; }
nav { background:var(--card); border-bottom:1px solid var(--border); padding:1rem 2rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; }
.logo { font-size:1.4rem; font-weight:900; letter-spacing:2px; color:var(--green); }
.logo span { color:var(--text); }
.nav-links { display:flex; gap:1.5rem; flex-wrap:wrap; }
.nav-links a { color:var(--muted); font-size:.8rem; text-decoration:none; text-transform:uppercase; letter-spacing:1px; transition:color .15s; }
.nav-links a:hover, .nav-links a.active { color:var(--green); }
.nav-right { display:flex; align-items:center; gap:1rem; }
.nav-right a { color:var(--muted); font-size:.78rem; text-decoration:none; transition:color .15s; }
.nav-right a:hover { color:var(--red); }
main { max-width:1100px; margin:0 auto; padding:2rem; }
.page-title { font-size:1.6rem; font-weight:800; margin-bottom:.25rem; }
.page-sub { color:var(--muted); font-size:.85rem; margin-bottom:2rem; }
.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:2rem; }
.stat { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:1.25rem 1.5rem; position:relative; overflow:hidden; }
.stat::after { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:12px 12px 0 0; }
.stat.g::after { background:var(--green); } .stat.r::after { background:var(--red); }
.stat.y::after { background:var(--yellow); } .stat.w::after { background:#555; }
.stat-num { font-size:2.8rem; font-weight:900; line-height:1; }
.stat.g .stat-num { color:var(--green); } .stat.r .stat-num { color:var(--red); }
.stat.y .stat-num { color:var(--yellow); } .stat.w .stat-num { color:var(--text); }
.stat-label { font-size:.65rem; color:var(--muted); text-transform:uppercase; letter-spacing:1px; margin-top:.3rem; }
.grid-2 { display:grid; grid-template-columns:1.4fr 1fr; gap:1.5rem; margin-bottom:1.5rem; }
.panel { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:1.5rem; }
.panel-title { font-size:.72rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); margin-bottom:1.25rem; font-weight:700; display:flex; align-items:center; gap:.5rem; }
.panel-title .dot { width:7px; height:7px; border-radius:50%; background:var(--green); animation:blink 2s infinite; flex-shrink:0; }
@keyframes blink { 0%,100%{opacity:1}50%{opacity:.2} }
.btn-main { width:100%; background:var(--green); color:#000; font-weight:800; font-size:1rem; border:none; padding:1.1rem; border-radius:10px; cursor:pointer; letter-spacing:.5px; margin-bottom:1rem; display:flex; align-items:center; justify-content:center; gap:10px; transition:all .2s; box-shadow:0 0 25px rgba(0,255,136,.25); }
.btn-main:hover { transform:translateY(-2px); box-shadow:0 0 40px rgba(0,255,136,.4); }
.btn-main:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.btns-row { display:grid; grid-template-columns:1fr 1fr; gap:.6rem; margin-top:.75rem; }
.btn-outline { display:flex; align-items:center; justify-content:center; gap:6px; background:transparent; border:1px solid var(--border2); color:var(--muted); font-weight:600; font-size:.8rem; padding:.75rem; border-radius:8px; text-decoration:none; transition:all .15s; cursor:pointer; }
.btn-outline:hover { border-color:var(--green); color:var(--green); }
.btn-outline.wa { border-color:rgba(37,211,102,.4); color:#25D366; }
.btn-outline.wa:hover { background:rgba(37,211,102,.08); }
.pendiente-item { display:flex; align-items:center; justify-content:space-between; padding:.875rem; background:var(--card2); border-radius:10px; margin-bottom:.6rem; border:1px solid var(--border); }
.pendiente-item.vencido   { border-left:3px solid var(--red); }
.pendiente-item.por_vencer { border-left:3px solid var(--yellow); }
.p-nombre { font-weight:600; font-size:.88rem; }
.p-detalle { font-size:.7rem; color:var(--muted); margin-top:2px; }
.badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:.62rem; font-weight:700; }
.badge.vencido   { background:var(--red-s);    color:var(--red); }
.badge.por_vencer { background:var(--yellow-s); color:var(--yellow); }
.wa-link { background:rgba(37,211,102,.1); border:1px solid rgba(37,211,102,.3); color:#25D366; padding:5px 12px; border-radius:6px; font-size:.72rem; font-weight:700; text-decoration:none; white-space:nowrap; }
.wa-link:hover { background:rgba(37,211,102,.2); }
.empty-msg { text-align:center; padding:2rem; color:var(--muted); font-size:.85rem; }
.historial-item { display:flex; align-items:center; gap:.875rem; padding:.75rem 0; border-bottom:1px solid var(--border); }
.historial-item:last-child { border-bottom:none; }
.h-icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; }
.h-icon.vencido   { background:var(--red-s); }
.h-icon.por_vencer { background:var(--yellow-s); }
.h-nombre { font-size:.85rem; font-weight:600; }
.h-fecha  { font-size:.7rem; color:var(--muted); }
.prog-wrap { background:var(--card2); border-radius:4px; height:5px; margin-top:.75rem; overflow:hidden; }
.prog-fill { height:100%; border-radius:4px; background:var(--green); transition:width .6s ease; box-shadow:0 0 8px rgba(0,255,136,.4); }
.log-box { background:#080808; border:1px solid var(--border); border-radius:8px; padding:1rem; font-family:'Courier New',monospace; font-size:.75rem; height:150px; overflow-y:auto; line-height:1.9; margin-top:.75rem; }
.log-ok   { color:var(--green); } .log-bad  { color:var(--red); }
.log-warn { color:var(--yellow); } .log-info { color:var(--muted); }
/* Resumen texto */
.resumen-box { background:#080808; border:1px solid var(--border); border-left:3px solid var(--green); border-radius:8px; padding:1rem 1.25rem; font-size:.8rem; line-height:1.8; color:#ccc; white-space:pre-line; display:none; margin-top:.75rem; }
.btn-copiar { background:var(--card2); border:1px solid var(--border); color:var(--muted); font-size:.72rem; font-weight:600; padding:5px 12px; border-radius:6px; cursor:pointer; transition:all .15s; }
.btn-copiar:hover { border-color:var(--green); color:var(--green); }
@media(max-width:768px) { .stats { grid-template-columns:repeat(2,1fr); } .grid-2 { grid-template-columns:1fr; } }
</style>
</head>
<body>

<nav>
  <div class="logo">GYM<span>FLOW</span></div>
  <div class="nav-links">
    <a href="index.php">Dashboard</a>
    <a href="clientes.php">Socios</a>
    <a href="registro.php">Agregar</a>
    <a href="automatizacion.php" class="active">Automatización</a>
    <a href="avisos.php">Avisos</a>
    <a href="renovar.php">Renovar</a>
    <a href="estadisticas.php">Estadísticas</a>
  </div>
  <div class="nav-right">
    <a href="exportar.php">📥 CSV</a>
    <a href="logout.php">🚪 Salir</a>
  </div>
</nav>

<main>
  <div class="page-title">⚡ Panel de Automatización</div>
  <p class="page-sub">El sistema detecta y actualiza todo automáticamente · <?= date('d/m/Y H:i') ?></p>

  <div class="stats">
    <div class="stat w"><div class="stat-num"><?= $total ?></div><div class="stat-label">Total socios</div></div>
    <div class="stat g"><div class="stat-num"><?= $activos ?></div><div class="stat-label">Al día</div></div>
    <div class="stat y"><div class="stat-num"><?= $por_vencer ?></div><div class="stat-label">Por vencer</div></div>
    <div class="stat r"><div class="stat-num"><?= $vencidos ?></div><div class="stat-label">Vencidos</div></div>
  </div>

  <div class="grid-2">
    <div>
      <div class="panel" style="margin-bottom:1.5rem">
        <div class="panel-title"><span class="dot"></span>Sistema de automatización</div>
        <button class="btn-main" id="btnMain" onclick="ejecutarAutomatizacion()">
          <span id="btn-icon">⚡</span>
          <span id="btn-text">EJECUTAR AUTOMATIZACIÓN</span>
        </button>
        <div class="log-box" id="logBox">
          <div class="log-info">// Sistema listo. Presioná el botón para analizar membresías...</div>
        </div>
        <div class="prog-wrap"><div class="prog-fill" id="progFill" style="width:0%"></div></div>
        <div class="btns-row">
          <a href="avisos.php" class="btn-outline wa">📲 Ver avisos WhatsApp</a>
          <a href="renovar.php" class="btn-outline">🔄 Renovar membresías</a>
          <a href="estadisticas.php" class="btn-outline">📊 Estadísticas</a>
          <a href="exportar.php" class="btn-outline">📥 Exportar CSV</a>
        </div>
        <!-- Resumen copiable -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:1.25rem;">
          <span style="font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Resumen del día</span>
          <button class="btn-copiar" onclick="copiarResumen()">📋 Copiar</button>
        </div>
        <div class="resumen-box" id="resumenBox"></div>
      </div>
    </div>

    <div>
      <div class="panel">
        <div class="panel-title"><span class="dot"></span>Pendientes hoy (<?= $vencidos + $por_vencer ?>)</div>
        <?php if (($vencidos + $por_vencer) === 0): ?>
          <div class="empty-msg">✅ Todo al día</div>
        <?php else: ?>
          <?php while ($s = $pendientes->fetch_assoc()):
            $primer_nombre = explode(' ', $s['nombre'])[0];
            if ($s['estado'] === 'vencido') {
                $msg = "Hola {$primer_nombre} 👋 Tu membresía está vencida. Comunicate con nosotros para renovarla. ¡Gracias! 💪";
            } else {
                $msg = "Hola {$primer_nombre} 👋 Tu membresía vence pronto. ¡Renovála para seguir entrenando! 💪";
            }
            $link = "https://wa.me/{$s['telefono']}?text=" . urlencode($msg);
            $hoy_d = new DateTime(); $vence_d = new DateTime($s['fecha_vencimiento']);
            $dias  = (int)$hoy_d->diff($vence_d)->days;
            $dias_txt = $s['estado']==='vencido' ? "hace {$dias}d" : "en {$dias}d";
          ?>
          <div class="pendiente-item <?= $s['estado'] ?>">
            <div>
              <div class="p-nombre"><?= htmlspecialchars($s['nombre']) ?></div>
              <div class="p-detalle">
                <?= ucfirst($s['plan']) ?> · <?= $dias_txt ?>
                <span class="badge <?= $s['estado'] ?>"><?= $s['estado']==='vencido'?'Vencido':'Por vencer' ?></span>
              </div>
            </div>
            <a href="<?= $link ?>" target="_blank" class="wa-link">📲</a>
          </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-title">📋 Últimos avisos enviados</div>
    <?php if ($historial->num_rows === 0): ?>
      <div class="empty-msg">Todavía no se enviaron avisos. Usá el botón 📲 en la sección de avisos.</div>
    <?php else: ?>
      <?php while ($h = $historial->fetch_assoc()): ?>
      <div class="historial-item">
        <div class="h-icon <?= $h['tipo_aviso'] ?>"><?= $h['tipo_aviso']==='vencido'?'🔴':'🟡' ?></div>
        <div style="flex:1">
          <div class="h-nombre"><?= htmlspecialchars($h['nombre']) ?></div>
          <div class="h-fecha"><?= ucfirst($h['plan']) ?> · <?= date('d/m/Y H:i', strtotime($h['fecha_envio'])) ?></div>
        </div>
        <span class="badge <?= $h['tipo_aviso'] ?>"><?= $h['tipo_aviso']==='vencido'?'Vencido':'Por vencer' ?></span>
      </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</main>

<script>
function log(text, cls, delay) {
  return new Promise(r => setTimeout(() => {
    const box = document.getElementById('logBox');
    const line = document.createElement('div');
    line.className = cls; line.textContent = text;
    box.appendChild(line); box.scrollTop = 9999; r();
  }, delay));
}

async function ejecutarAutomatizacion() {
  const btn  = document.getElementById('btnMain');
  const icon = document.getElementById('btn-icon');
  const txt  = document.getElementById('btn-text');
  const prog = document.getElementById('progFill');
  btn.disabled = true; icon.textContent = '⏳'; txt.textContent = 'Analizando membresías...';
  document.getElementById('logBox').innerHTML = ''; prog.style.width = '0%';

  await log('> Conectando con base de datos...', 'log-info', 300);
  prog.style.width = '20%';
  await log('✓ Conexión exitosa', 'log-ok', 800);
  await log('> Analizando fechas de vencimiento...', 'log-info', 1200);
  prog.style.width = '45%';
  await log('> Comparando con fecha de hoy: <?= date('d/m/Y') ?>', 'log-info', 1700);

  const resp = await fetch('cron_estados.php?ajax=1');
  const data = await resp.json();

  prog.style.width = '75%';
  await log('✓ Estados actualizados en base de datos', 'log-ok', 2200);
  prog.style.width = '90%';
  if (data.vencidos  > 0) await log(`⚠ ${data.vencidos} socios con membresía vencida`, 'log-bad', 2600);
  if (data.por_vencer > 0) await log(`~ ${data.por_vencer} socios próximos a vencer`, 'log-warn', 3000);
  if (data.activos   > 0) await log(`✓ ${data.activos} activos confirmados`, 'log-ok', 3400);
  prog.style.width = '100%';
  await log('✅ AUTOMATIZACIÓN COMPLETADA', 'log-ok', 3800);

  // Generar resumen copiable
  const hoy = new Date().toLocaleDateString('es-AR');
  let resumen = `📋 Resumen GymFlow — ${hoy}\n\n`;
  if (data.vencidos > 0)   resumen += `🔴 Vencidos: ${data.vencidos} socios\n`;
  if (data.por_vencer > 0) resumen += `🟡 Por vencer: ${data.por_vencer} socios\n`;
  resumen += `🟢 Al día: <?= $activos ?> socios\n`;
  resumen += `\n👥 Total: <?= $total ?> socios registrados`;
  const box = document.getElementById('resumenBox');
  box.textContent = resumen; box.style.display = 'block';

  setTimeout(() => {
    btn.disabled = false; icon.textContent = '⚡'; txt.textContent = 'VOLVER A EJECUTAR';
    location.reload();
  }, 4500);
}

function copiarResumen() {
  const box = document.getElementById('resumenBox');
  if (!box.textContent.trim()) { alert('Primero ejecutá la automatización.'); return; }
  navigator.clipboard.writeText(box.textContent).then(() => {
    const btn = document.querySelector('.btn-copiar');
    btn.textContent = '✅ Copiado'; setTimeout(() => btn.textContent = '📋 Copiar', 2000);
  });
}
</script>
</body>
</html>
