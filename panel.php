<?php
session_start();

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: login.php"); exit;
}

$host = "localhost"; $dbname = "laboratorio_ia";
$username = "root";  $password = "123456789";

$estudiantes = []; $error = ""; $success = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && isset($_POST['id_estudiante'])) {
        $id     = (int)$_POST['id_estudiante'];
        $accion = $_POST['accion'];

        if ($accion === 'aprobar') {
            $random_password  = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
            $hashed_password  = password_hash($random_password, PASSWORD_DEFAULT);
            $stmt_email = $conn->prepare("SELECT email, nombre FROM estudiantes WHERE id = :id");
            $stmt_email->bindParam(':id', $id); $stmt_email->execute();
            $estudiante_data  = $stmt_email->fetch(PDO::FETCH_ASSOC);
            $username_generado = $estudiante_data['email'];

            $conn->prepare("UPDATE estudiantes SET estado='Aprobado', username=:u, password_hash=:p WHERE id=:id")
                ->execute([':u' => $username_generado, ':p' => $hashed_password, ':id' => $id]);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'andruslopez88@gmail.com';
                $mail->Password   = 'ftttchahpskhuiup';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->Timeout    = 10;
                $mail->SMTPDebug  = 0;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    ]
                ];
                $mail->setFrom('andruslopez88@gmail.com', 'Laboratorio IA');
                $mail->addAddress($estudiante_data['email']);
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = 'Acceso Concedido - Laboratorio IA';
                $mail->Body = "
                <div style='background:#050505;color:#e0e0e0;font-family:sans-serif;padding:30px;max-width:500px;border:1px solid #b026ff;border-radius:10px;'>
                  <h2 style='color:#00d4ff;font-family:monospace;'>LABORATORIO IA</h2>
                  <p style='color:#b026ff;font-size:0.85rem;'>SOLICITUD APROBADA</p>
                  <hr style='border-color:#b026ff33;margin:15px 0;'>
                  <p>Hola, <strong>{$estudiante_data['nombre']}</strong>. Tu registro ha sido <strong style='color:#00ff88;'>APROBADO</strong>.</p>
                  <p>Tus credenciales de acceso son:</p>
                  <div style='background:#0a0a14;border:1px solid #00d4ff33;padding:15px;border-radius:6px;margin:15px 0;'>
                    <p><strong>Usuario:</strong> $username_generado</p>
                    <p><strong>Contraseña temporal:</strong> <code style='color:#00d4ff;'>$random_password</code></p>
                  </div>
                  <p style='font-size:0.8rem;color:#888;'>Cambia tu contraseña al ingresar por primera vez.</p>
                </div>";
                $mail->send();
                $success = "Estudiante #$id aprobado. Usuario: <b>$username_generado</b> | Contraseña: <b>$random_password</b> &nbsp;<span style='color:var(--green);font-size:0.8rem;'>✓ Correo enviado correctamente.</span>";
            } catch (Exception $e) {
                $success = "Estudiante #$id aprobado. Usuario: <b>$username_generado</b> | Contraseña: <b>$random_password</b> &nbsp;<span style='color:var(--red);font-size:0.8rem;'>✗ Error al enviar correo: " . $e->getMessage() . "</span>";
            }

        } elseif ($accion === 'rechazar') {
            $conn->prepare("UPDATE estudiantes SET estado='Rechazado' WHERE id=:id")->execute([':id' => $id]);
            $success = "Estudiante #$id rechazado.";
        } elseif ($accion === 'generar_certificado') {
            $conn->prepare("UPDATE estudiantes SET certificado_listo=1 WHERE id=:id")->execute([':id' => $id]);
            $success = "Certificado generado y disponible con éxito para el estudiante #$id.";
        }
    }

    $sql = "SELECT id, nombre, email, curso, fecha_registro, estado, calificacion, calificacion_algoritmos_geneticos, calificacion_machine_learning, calificacion_procesamiento_lenguaje, certificado_listo FROM estudiantes ORDER BY fecha_registro DESC";
    $estudiantes = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) { $error = "Error de base de datos: " . $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Laboratorio IA</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { overflow: hidden; }

        .dashboard {
            display: flex;
            width: 100%;
            height: 100vh;
            position: relative;
            z-index: 2;
        }

        /* ---- Sidebar ---- */
        .sidebar {
            width: 240px;
            flex-shrink: 0;
            background: #2b2b2b;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        .sidebar-header {
            padding: 15px 20px;
            background-color: #00b4d8;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            height: 50px;
        }
        .sidebar-logo {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .sidebar-logo span { color: #ffffff; }

        .admin-info {
            padding: 18px 20px;
            border-bottom: 1px solid #444;
        }
        .admin-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), var(--violet));
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-display);
            font-size: 1rem;
            color: #fff;
            margin-bottom: 8px;
        }
        .admin-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .admin-role {
            font-size: 0.68rem;
            color: var(--cyan);
            letter-spacing: 1px;
        }

        .sidebar-nav { flex: 1; padding: 10px 0; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #ccc;
            font-size: 0.85rem;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border-left: 4px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            color: #fff;
            background: #444;
            border-left-color: var(--cyan);
        }
        .nav-icon { font-size: 1rem; width: 20px; text-align: center; }

        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid #444;
        }
        .logout-btn {
            display: flex; align-items: center; gap: 8px;
            color: var(--red);
            text-decoration: none;
            font-size: 0.85rem;
            padding: 8px 10px;
            border-radius: 6px;
            transition: var(--transition);
        }
        .logout-btn:hover { background: rgba(255,0,60,0.1); }

        /* ---- Main ---- */
        .main-area {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            background: #0f1015;
        }

        .topbar {
            padding: 16px 30px;
            border-bottom: 1px solid rgba(0, 212, 255, 0.15);
            background: #15161c;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 50px;
        }
        .topbar-title {
            font-family: var(--font-display);
            font-size: 1rem;
            color: #ffffff;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .content-area { padding: 30px; flex: 1; z-index: 2; }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: var(--radius);
            padding: 18px 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 212, 255, 0.03);
        }
        .stat-card .num {
            font-family: var(--font-display);
            font-size: 2.2rem;
            font-weight: 900;
            text-shadow: 0 0 10px currentColor;
        }
        .stat-card .lbl { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; font-weight: 600; }
        .stat-card.cyan  .num { color: var(--cyan); }
        .stat-card.violet .num { color: var(--violet); }
        .stat-card.yellow .num { color: #f59e0b; }
        .stat-card.green  .num { color: var(--green); }

        /* Tabla */
        .table-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 212, 255, 0.03);
        }
        .table-card-header {
            padding: 16px 22px;
            border-bottom: 1px solid rgba(0, 212, 255, 0.15);
            font-family: var(--font-display);
            font-size: 0.8rem;
            color: #ffffff;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 700;
        }

        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 12px 16px;
            text-align: left;
            font-family: var(--font-display);
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: rgba(255, 255, 255, 0.01);
            border-bottom: 1px solid rgba(0, 212, 255, 0.15);
            font-weight: 700;
        }
        td {
            padding: 13px 16px;
            font-size: 0.85rem;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }
        tr:hover td { background: rgba(0, 212, 255, 0.04); }
        tr:last-child td { border-bottom: none; }

        .course-tag {
            background: rgba(0, 212, 255, 0.08);
            color: #00d4ff;
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.72rem;
            font-family: var(--font-display);
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .s-pendiente { background: rgba(255,204,0,0.12);  color: #ffcc00;       border: 1px solid rgba(255,204,0,0.3); }
        .s-aprobado  { background: rgba(0,255,136,0.12);  color: var(--green);  border: 1px solid rgba(0,255,136,0.3); }
        .s-rechazado { background: rgba(255,0,60,0.12);   color: var(--red);    border: 1px solid rgba(255,0,60,0.3); }

        .btn-tbl {
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-family: var(--font-display);
            cursor: pointer;
            border: 1px solid;
            background: transparent;
            transition: var(--transition);
            margin-right: 4px;
        }
        .btn-tbl.approve { border-color: var(--green); color: var(--green); }
        .btn-tbl.approve:hover { background: rgba(0,255,136,0.15); }
        .btn-tbl.reject  { border-color: var(--red);   color: var(--red);   }
        .btn-tbl.reject:hover  { background: rgba(255,0,60,0.15); }

        /* Mensajes en el topbar */
        .flash-msg {
            position: fixed; top: 15px; right: 20px;
            max-width: 420px;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 0.83rem;
            z-index: 999;
            animation: slideIn 0.4s ease;
        }
        @keyframes slideIn { from { opacity:0; transform: translateX(30px); } to { opacity:1; transform: translateX(0); } }
        /* Estilos Reportes */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .report-card {
            background: #111116;
            border: 1px solid rgba(0, 212, 255, 0.1);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .report-card-header {
            font-family: var(--font-display);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #00d4ff;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .report-stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #ccc;
            margin-bottom: 10px;
        }
        .report-stat-row span.val {
            color: #fff;
            font-weight: bold;
        }
        .report-progress-bar {
            width: 100%;
            height: 6px;
            background: #222;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        .report-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--cyan), var(--violet));
        }
        .promedio-badge {
            display: inline-block;
            background: rgba(176,38,255,0.1);
            color: #b026ff;
            border: 1px solid rgba(176,38,255,0.3);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div id="particles-js"></div>

<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">Lab<span>IA</span></div>
        </div>
        <div class="admin-info">
            <div class="admin-avatar">A</div>
            <div class="admin-name"><?php echo htmlspecialchars($_SESSION["admin_username"]); ?></div>
            <div class="admin-role">Administrador del Sistema</div>
        </div>
        <nav class="sidebar-nav">
            <a class="nav-item <?php echo (!isset($_GET['view']) || $_GET['view'] !== 'reportes') ? 'active' : ''; ?>" href="panel.php">
                <span class="nav-icon">📊</span> Gestión de Estudiantes
            </a>
            <a class="nav-item <?php echo (isset($_GET['view']) && $_GET['view'] === 'reportes') ? 'active' : ''; ?>" href="panel.php?view=reportes">
                <span class="nav-icon">📈</span> Reportes de Rendimiento
            </a>
            <a class="nav-item" href="index.html">
                <span class="nav-icon">🏠</span> Inicio
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <span>🚪</span> Cerrar Sesión
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-area">
        <div class="topbar">
            <span class="topbar-title">Panel de Control</span>
            <span style="font-size:0.75rem; color: var(--text-muted);">
                <?php echo date("d/m/Y H:i"); ?>
            </span>
        </div>

        <div class="content-area">
            <!-- Flash messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="margin-bottom:20px;"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="flash-msg alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['view']) && $_GET['view'] === 'reportes'): ?>
                <!-- REPORTES VIEW -->
                <?php
                $stats_cursos = [
                    'regresion_lineal' => ['titulo' => 'Regresión Lineal', 'columna' => 'calificacion', 'inscritos' => 0, 'evaluados' => 0, 'aprobados' => 0, 'reprobados' => 0, 'suma' => 0],
                    'algoritmos_geneticos' => ['titulo' => 'Algoritmos Genéticos', 'columna' => 'calificacion_algoritmos_geneticos', 'inscritos' => 0, 'evaluados' => 0, 'aprobados' => 0, 'reprobados' => 0, 'suma' => 0],
                    'machine_learning' => ['titulo' => 'Machine Learning', 'columna' => 'calificacion_machine_learning', 'inscritos' => 0, 'evaluados' => 0, 'aprobados' => 0, 'reprobados' => 0, 'suma' => 0],
                    'procesamiento_lenguaje' => ['titulo' => 'Procesamiento de Lenguaje Natural', 'columna' => 'calificacion_procesamiento_lenguaje', 'inscritos' => 0, 'evaluados' => 0, 'aprobados' => 0, 'reprobados' => 0, 'suma' => 0]
                ];

                $aprobados_total = 0;
                $rechazados_total = 0;
                $pendientes_total = 0;

                foreach ($estudiantes as $est) {
                    $est_estado = $est['estado'] ?? 'Pendiente';
                    if ($est_estado === 'Aprobado') {
                        $aprobados_total++;
                        $c = $est['curso'];
                        if (isset($stats_cursos[$c])) {
                            $stats_cursos[$c]['inscritos']++;
                            
                            $col = $stats_cursos[$c]['columna'];
                            if ($est[$col] !== null) {
                                $stats_cursos[$c]['evaluados']++;
                                $grade = (int)$est[$col];
                                $stats_cursos[$c]['suma'] += $grade;
                                if ($grade >= 60) {
                                    $stats_cursos[$c]['aprobados']++;
                                } else {
                                    $stats_cursos[$c]['reprobados']++;
                                }
                            }
                        }
                    } elseif ($est_estado === 'Rechazado') {
                        $rechazados_total++;
                    } else {
                        $pendientes_total++;
                    }
                }
                ?>

                <h2 style="font-family: var(--font-display); font-size: 0.95rem; color: #fff; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; font-weight:700;">📊 Resumen General de Admisiones</h2>

                <div class="stats-row" style="margin-bottom: 30px;">
                    <div class="stat-card green">
                        <div class="num"><?php echo $aprobados_total; ?></div>
                        <div class="lbl">Aprobados</div>
                    </div>
                    <div class="stat-card violet">
                        <div class="num"><?php echo $rechazados_total; ?></div>
                        <div class="lbl">Rechazados</div>
                    </div>
                    <div class="stat-card yellow">
                        <div class="num"><?php echo $pendientes_total; ?></div>
                        <div class="lbl">Pendientes</div>
                    </div>
                </div>

                <h2 style="font-family: var(--font-display); font-size: 0.95rem; color: #fff; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; font-weight:700;">📈 Desempeño por Curso Académico</h2>

                <div class="report-grid">
                    <?php foreach ($stats_cursos as $slug => $info): ?>
                        <?php 
                        $promedio = $info['evaluados'] > 0 ? round($info['suma'] / $info['evaluados'], 1) : 0;
                        $pct_aprobados = $info['evaluados'] > 0 ? round(($info['aprobados'] / $info['evaluados']) * 100) : 0;
                        ?>
                        <div class="report-card">
                            <div class="report-card-header"><?php echo htmlspecialchars($info['titulo']); ?></div>
                            
                            <div class="report-stat-row">
                                <span>Alumnos Matriculados:</span>
                                <span class="val"><?php echo $info['inscritos']; ?></span>
                            </div>
                            <div class="report-stat-row">
                                <span>Alumnos Evaluados:</span>
                                <span class="val"><?php echo $info['evaluados']; ?></span>
                            </div>
                            <div class="report-stat-row">
                                <span>Aprobaron Examen (Nota ≥ 60):</span>
                                <span class="val" style="color:var(--green);"><?php echo $info['aprobados']; ?></span>
                            </div>
                            <div class="report-stat-row">
                                <span>Reprobaron Examen (Nota < 60):</span>
                                <span class="val" style="color:var(--red);"><?php echo $info['reprobados']; ?></span>
                            </div>
                            <div class="report-stat-row" style="margin-top: 15px; border-top: 1px dashed rgba(255,255,255,0.05); padding-top: 10px;">
                                <span>Nota Promedio:</span>
                                <span class="val promedio-badge"><?php echo $promedio; ?> / 100</span>
                            </div>

                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 15px; display: flex; justify-content: space-between;">
                                <span>Tasa de Aprobación</span>
                                <span><?php echo $pct_aprobados; ?>%</span>
                            </div>
                            <div class="report-progress-bar">
                                <div class="report-progress-fill" style="width: <?php echo $pct_aprobados; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="table-card">
                    <div class="table-card-header">Detalle Académico Completo (Estudiantes Aprobados)</div>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Módulo Inscrito</th>
                                    <th>Regresión Lineal</th>
                                    <th>Algoritmos Genéticos</th>
                                    <th>Machine Learning</th>
                                    <th>Procesamiento Lenguaje</th>
                                    <th>Certificado</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $aprobados_list = array_filter($estudiantes, function($e) { return ($e['estado'] ?? 'Pendiente') === 'Aprobado'; });
                            if (count($aprobados_list) > 0): 
                            ?>
                                <?php foreach ($aprobados_list as $est): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($est['nombre']); ?></strong><br>
                                            <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($est['email']); ?></span>
                                        </td>
                                        <td><span class="course-tag"><?php echo htmlspecialchars($est['curso']); ?></span></td>
                                        
                                        <!-- Regresión -->
                                        <td>
                                            <?php 
                                            if ($est['calificacion'] !== null) {
                                                $cl = (int)$est['calificacion'] >= 60 ? 'color:var(--green)' : 'color:var(--red)';
                                                echo "<span style='font-weight:bold; $cl'>{$est['calificacion']}/100</span>";
                                            } else {
                                                echo "<span style='color:var(--text-muted); font-size:0.8rem;'>Sin evaluar</span>";
                                            }
                                            ?>
                                        </td>

                                        <!-- Algoritmos Genéticos -->
                                        <td>
                                            <?php 
                                            if ($est['calificacion_algoritmos_geneticos'] !== null) {
                                                $cl = (int)$est['calificacion_algoritmos_geneticos'] >= 60 ? 'color:var(--green)' : 'color:var(--red)';
                                                echo "<span style='font-weight:bold; $cl'>{$est['calificacion_algoritmos_geneticos']}/100</span>";
                                            } else {
                                                echo "<span style='color:var(--text-muted); font-size:0.8rem;'>Sin evaluar</span>";
                                            }
                                            ?>
                                        </td>

                                        <!-- Machine Learning -->
                                        <td>
                                            <?php 
                                            if ($est['calificacion_machine_learning'] !== null) {
                                                $cl = (int)$est['calificacion_machine_learning'] >= 60 ? 'color:var(--green)' : 'color:var(--red)';
                                                echo "<span style='font-weight:bold; $cl'>{$est['calificacion_machine_learning']}/100</span>";
                                            } else {
                                                echo "<span style='color:var(--text-muted); font-size:0.8rem;'>Sin evaluar</span>";
                                            }
                                            ?>
                                        </td>

                                        <!-- NLP -->
                                        <td>
                                            <?php 
                                            if ($est['calificacion_procesamiento_lenguaje'] !== null) {
                                                $cl = (int)$est['calificacion_procesamiento_lenguaje'] >= 60 ? 'color:var(--green)' : 'color:var(--red)';
                                                echo "<span style='font-weight:bold; $cl'>{$est['calificacion_procesamiento_lenguaje']}/100</span>";
                                            } else {
                                                echo "<span style='color:var(--text-muted); font-size:0.8rem;'>Sin evaluar</span>";
                                            }
                                            ?>
                                        </td>

                                        <!-- Certificado -->
                                        <td>
                                            <?php
                                            $c = $est['curso'];
                                            $score = null;
                                            if ($c === 'regresion_lineal') { $score = $est['calificacion']; }
                                            elseif ($c === 'algoritmos_geneticos') { $score = $est['calificacion_algoritmos_geneticos']; }
                                            elseif ($c === 'machine_learning') { $score = $est['calificacion_machine_learning']; }
                                            elseif ($c === 'procesamiento_lenguaje') { $score = $est['calificacion_procesamiento_lenguaje']; }

                                            if ($score !== null && (int)$score >= 60) {
                                                if ((int)$est['certificado_listo'] === 1) {
                                                    echo '<a href="generar_certificado.php?id=' . $est['id'] . '" target="_blank" class="status-badge s-aprobado" style="text-decoration:none; display:inline-block; font-size:0.75rem; font-weight:bold;">👁️ Ver Certificado</a>';
                                                } else {
                                                    echo '<form method="POST" action="panel.php?view=reportes" style="display:inline;">
                                                            <input type="hidden" name="id_estudiante" value="' . $est['id'] . '">
                                                            <input type="hidden" name="accion" value="generar_certificado">
                                                            <button type="submit" class="btn-tbl approve" style="background:var(--cyan); border-color:var(--cyan); color:#000; font-size:0.72rem; padding: 4px 10px; font-weight:bold;">Emitir</button>
                                                          </form>';
                                                }
                                            } else {
                                                echo '<span style="color:var(--text-muted); font-size:0.78rem;">Pendiente Examen</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">
                                        No hay estudiantes aprobados en este momento.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <!-- Stats -->
                <?php
                    $total     = count($estudiantes);
                    $aprobados = count(array_filter($estudiantes, function($e) { return ($e['estado'] ?? 'Pendiente') === 'Aprobado'; }));
                    $pendientes= count(array_filter($estudiantes, function($e) { return ($e['estado'] ?? 'Pendiente') === 'Pendiente'; }));
                    $rechazados= count(array_filter($estudiantes, function($e) { return ($e['estado'] ?? 'Pendiente') === 'Rechazado'; }));
                ?>
                <div class="stats-row">
                    <div class="stat-card cyan">
                        <div class="num"><?php echo $total; ?></div>
                        <div class="lbl">Total Registros</div>
                    </div>
                    <div class="stat-card green">
                        <div class="num"><?php echo $aprobados; ?></div>
                        <div class="lbl">Aprobados</div>
                    </div>
                    <div class="stat-card yellow">
                        <div class="num"><?php echo $pendientes; ?></div>
                        <div class="lbl">Pendientes</div>
                    </div>
                    <div class="stat-card violet">
                        <div class="num"><?php echo $rechazados; ?></div>
                        <div class="lbl">Rechazados</div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-card">
                    <div class="table-card-header">Registros de Estudiantes</div>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Módulo (Curso)</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (count($estudiantes) > 0): ?>
                                <?php foreach ($estudiantes as $est): ?>
                                    <?php
                                        $estado = $est['estado'] ?? 'Pendiente';
                                        $sc = ($estado==='Aprobado') ? 's-aprobado' : (($estado==='Rechazado') ? 's-rechazado' : 's-pendiente');
                                    ?>
                                    <tr>
                                        <td style="color:var(--text-muted); font-size:0.78rem;">#<?php echo $est['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($est['nombre']); ?></strong></td>
                                        <td style="color:var(--text-muted); font-size:0.82rem;"><?php echo htmlspecialchars($est['email']); ?></td>
                                        <td><span class="course-tag"><?php echo htmlspecialchars($est['curso']); ?></span></td>
                                        <td><span class="status-badge <?php echo $sc; ?>"><?php echo htmlspecialchars($estado); ?></span></td>
                                        <td>
                                            <?php if ($estado === 'Pendiente'): ?>
                                                <form method="POST" action="panel.php" style="display:inline;">
                                                    <input type="hidden" name="id_estudiante" value="<?php echo $est['id']; ?>">
                                                    <input type="hidden" name="accion" value="aprobar">
                                                    <button type="submit" class="btn-tbl approve">Aprobar</button>
                                                </form>
                                                <form method="POST" action="panel.php" style="display:inline;">
                                                    <input type="hidden" name="id_estudiante" value="<?php echo $est['id']; ?>">
                                                    <input type="hidden" name="accion" value="rechazar">
                                                    <button type="submit" class="btn-tbl reject">Rechazar</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color:#444; font-size:0.78rem;">— N/A —</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">
                                        No hay registros en la base de datos todavía.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div><!-- /table-card -->
            <?php endif; ?>
        </div><!-- /content-area -->
    </div><!-- /main-area -->
</div><!-- /dashboard -->

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script src="script.js"></script>
<script>
    // Auto-ocultar flash message después de 60 s
    const flash = document.querySelector('.flash-msg');
    if (flash) setTimeout(() => flash.style.display = 'none', 60000);
</script>
</body>
</html>
