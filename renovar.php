<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$mensaje = '';
$tipo    = '';

// Procesar renovación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id'])) {
    $id   = (int)$_POST['cliente_id'];
    $plan = $conn->real_escape_string($_POST['plan']);

    // Días según plan
    $dias = ['semanal' => 7, 'quincenal' => 15, 'mensual' => 30];
    $sumar = $dias[$plan] ?? 30;

    // Calcular nueva fecha desde HOY
    $nueva_fecha = date('Y-m-d', strtotime("+{$sumar} days"));

    $conn->query("
        UPDATE clientes
        SET plan = '{$plan}',
            fecha_inicio = CURDATE(),
            fecha_vencimiento = '{$nueva_fecha}',
            estado = 'activo'
        WHERE id = {$id}
    ");

    // Registrar en historial
    $conn->query("
        INSERT INTO avisos_log (cliente_id, tipo_aviso, mensaje)
        VALUES ({$id}, 'por_vencer', 'Membresía renovada — plan {$plan} hasta {$nueva_fecha}')
    ");

    $cliente = $conn->query("SELECT nombre FROM clientes WHERE id=$id")->fetch_assoc();
    $mensaje = "✅ Membresía de {$cliente['nombre']} renovada hasta " . date('d/m/Y', strtotime($nueva_fecha));
    $tipo = 'ok';
}

// Traer todos los socios
$socios = $conn->query("SELECT * FROM clientes ORDER BY estado DESC, fecha_vencimiento ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFlow — Renovar Membresía</title>
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
main { max-width:900px; margin:0 auto; padding:2rem; }
h1 { font-size:1.6rem; font-weight:800; margin-bottom:.25rem; }
.sub { color:var(--muted); font-size:.85rem; margin-bottom:2rem; }

.alerta { padding:1rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-size:.9rem; }
.alerta.ok { background:var(--green-s); border:1px solid rgba(0,255,136,.3); color:var(--green); }

.card { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:.75rem; display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
.card.vencido   { border-left:3px solid var(--red); }
.card.por_vencer { border-left:3px solid var(--yellow); }
.card.activo    { border-left:3px solid var(--green); }

.c-nombre { font-weight:700; font-size:.95rem; }
.c-detalle { font-size:.75rem; color:var(--muted); margin-top:3px; }

.dias-vencido { font-size:.72rem; font-weight:700; padding:3px 10px; border-radius:20px; }
.dias-vencido.vencido   { background:var(--red-s); color:var(--red); }
.dias-vencido.por_vencer { background:var(--yellow-s); color:var(--yellow); }
.dias-vencido.activo    { background:var(--green-s); color:var(--green); }

.renovar-form { display:flex; align-items:center; gap:.5rem; margin-left:auto; }
select {
  background:var(--card2); border:1px solid var(--border); color:var(--text);
  padding:.5rem .875rem; border-radius:7px; font-size:.82rem; outline:none; cursor:pointer;
}
select:focus { border-color:var(--green); }
.btn-renovar {
  background:var(--green); color:#000; font-weight:700; font-size:.8rem;
  border:none; padding:.55rem 1.25rem; border-radius:7px; cursor:pointer; transition:all .15s;
  white-space:nowrap;
}
.btn-renovar:hover { opacity:.85; }
</style>
</head>
<body>

<nav>
  <div class="logo">GYM<span>FLOW</span></div>
  <div class="nav-links">
    <a href="automatizacion.php">← Panel</a>
    <a href="avisos.php">Avisos</a>
    <a href="logout.php">Salir</a>
  </div>
</nav>

<main>
  <h1>🔄 Renovar Membresías</h1>
  <p class="sub">Elegí el plan nuevo y presioná Renovar. La fecha se calcula automáticamente desde hoy.</p>

  <?php if ($mensaje): ?>
    <div class="alerta ok"><?= $mensaje ?></div>
  <?php endif; ?>

  <?php while ($s = $socios->fetch_assoc()):
    // Calcular días vencido / restantes
    $hoy = new DateTime();
    $vence = new DateTime($s['fecha_vencimiento']);
    $diff = $hoy->diff($vence);
    $dias_num = (int)$diff->days;

    if ($s['estado'] === 'vencido') {
      $dias_label = "Vencido hace {$dias_num} " . ($dias_num === 1 ? 'día' : 'días');
    } elseif ($s['estado'] === 'por_vencer') {
      $dias_label = "Vence en {$dias_num} " . ($dias_num === 1 ? 'día' : 'días');
    } else {
      $dias_label = "Activo · {$dias_num} días restantes";
    }
  ?>
  <div class="card <?= $s['estado'] ?>">
    <div style="flex:1; min-width:200px">
      <div class="c-nombre"><?= htmlspecialchars($s['nombre']) ?></div>
      <div class="c-detalle">
        📞 <?= $s['telefono'] ?> &nbsp;·&nbsp;
        Plan actual: <?= ucfirst($s['plan']) ?> &nbsp;·&nbsp;
        Vence: <?= date('d/m/Y', strtotime($s['fecha_vencimiento'])) ?>
      </div>
    </div>
    <span class="dias-vencido <?= $s['estado'] ?>"><?= $dias_label ?></span>
    <form method="POST" class="renovar-form">
      <input type="hidden" name="cliente_id" value="<?= $s['id'] ?>">
      <select name="plan">
        <option value="semanal"   <?= $s['plan']==='semanal'   ? 'selected':'' ?>>Semanal (7d)</option>
        <option value="quincenal" <?= $s['plan']==='quincenal' ? 'selected':'' ?>>Quincenal (15d)</option>
        <option value="mensual"   <?= $s['plan']==='mensual'   ? 'selected':'' ?>>Mensual (30d)</option>
      </select>
      <button class="btn-renovar" type="submit">🔄 Renovar</button>
    </form>
  </div>
  <?php endwhile; ?>
</main>
</body>
</html>
