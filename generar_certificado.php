<?php
session_start();

$admin_logged = isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true;
$estudiante_logged = isset($_SESSION["estudiante_logged_in"]) && $_SESSION["estudiante_logged_in"] === true;

if (!$admin_logged && !$estudiante_logged) {
    echo "<div style='color:#ff003c; text-align:center; font-family:sans-serif; margin-top:50px;'><h2>Acceso Denegado: Debes iniciar sesión.</h2></div>";
    exit;
}

$id_estudiante = null;
if (isset($_GET['id'])) {
    $id_estudiante = (int)$_GET['id'];
} elseif ($estudiante_logged) {
    $id_estudiante = (int)$_SESSION["estudiante_id"];
}

// Si es estudiante (y no administrador), forzar a que solo pueda consultar su propio ID (seguridad ante alteración de URL)
if (!$admin_logged && $estudiante_logged) {
    $id_estudiante = (int)$_SESSION["estudiante_id"];
}

if (!$id_estudiante) {
    echo "<div style='color:#ff003c; text-align:center; font-family:sans-serif; margin-top:50px;'><h2>ID de estudiante no especificado.</h2></div>";
    exit;
}

// Conectar DB
$host = "localhost"; $dbname = "laboratorio_ia";
$db_user = "root";   $db_pass = "123456789";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT nombre, email, curso, calificacion, calificacion_algoritmos_geneticos, calificacion_machine_learning, calificacion_procesamiento_lenguaje, certificado_listo FROM estudiantes WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id_estudiante]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$est) {
        echo "<div style='color:#ff003c; text-align:center; font-family:sans-serif; margin-top:50px;'><h2>Estudiante no encontrado.</h2></div>";
        exit;
    }

    // Validar si el estudiante tiene el certificado habilitado
    if (!$admin_logged && (int)$est['certificado_listo'] !== 1) {
        echo "<div style='color:#ff003c; text-align:center; font-family:sans-serif; margin-top:50px;'><h2>Acceso Denegado: Su certificado aún no ha sido emitido.</h2></div>";
        exit;
    }

    // Obtener la calificación del curso correspondiente
    $curso_slug = $est['curso'];
    $nota = 0;
    $nombre_curso = "";

    switch ($curso_slug) {
        case 'regresion_lineal':
            $nota = $est['calificacion'];
            $nombre_curso = "Regresión Lineal";
            break;
        case 'algoritmos_geneticos':
            $nota = $est['calificacion_algoritmos_geneticos'];
            $nombre_curso = "Algoritmos Genéticos";
            break;
        case 'machine_learning':
            $nota = $est['calificacion_machine_learning'];
            $nombre_curso = "Machine Learning";
            break;
        case 'procesamiento_lenguaje':
            $nota = $est['calificacion_procesamiento_lenguaje'];
            $nombre_curso = "Procesamiento de Lenguaje Natural";
            break;
    }

    if ($nota === null) {
        $nota = 0;
    }

} catch(PDOException $e) {
    echo "<div style='color:#ff003c; text-align:center; font-family:sans-serif; margin-top:50px;'><h2>Error: " . $e->getMessage() . "</h2></div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado - <?php echo htmlspecialchars($est['nombre']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;600&family=Alex+Brush&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0a0b0e;
            --cyan: #00d4ff;
            --violet: #b026ff;
            --magenta: #ff00ea;
            --border-glow: rgba(0, 212, 255, 0.4);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-dark);
            color: #fff;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Contenedor de acciones web */
        .web-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            z-index: 10;
        }

        .btn-action {
            padding: 10px 24px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--cyan), var(--violet));
            border: none;
            color: #000;
        }

        .btn-print:hover {
            box-shadow: 0 0 15px var(--cyan);
            transform: translateY(-2px);
        }

        .btn-back {
            background: transparent;
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .btn-back:hover {
            border-color: #fff;
            background: rgba(255,255,255,0.05);
        }

        /* Diploma Card */
        .diploma-container {
            width: 100%;
            max-width: 850px;
            aspect-ratio: 1.414 / 1; /* Proporción A4 Horizontal */
            background: #0f1118;
            border: 4px solid var(--cyan);
            border-radius: 12px;
            padding: 40px;
            position: relative;
            box-shadow: 0 0 40px var(--border-glow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
        }

        /* Esquinas de diseño futurista */
        .diploma-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border: 1px solid rgba(176, 38, 255, 0.3);
            margin: 10px;
            pointer-events: none;
            border-radius: 8px;
        }

        .corner {
            position: absolute;
            width: 30px;
            height: 30px;
            border: 3px solid var(--magenta);
            pointer-events: none;
        }
        .c-tl { top: 15px; left: 15px; border-right: none; border-bottom: none; }
        .c-tr { top: 15px; right: 15px; border-left: none; border-bottom: none; }
        .c-bl { bottom: 15px; left: 15px; border-right: none; border-top: none; }
        .c-br { bottom: 15px; right: 15px; border-left: none; border-top: none; }

        /* Contenido del Diploma */
        .header-logo {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 1.2rem;
            letter-spacing: 4px;
            color: var(--cyan);
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.4);
            margin-top: 15px;
        }

        .header-subtitle {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.65rem;
            letter-spacing: 3px;
            color: var(--violet);
            text-transform: uppercase;
            margin-top: 5px;
        }

        .main-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 2.2rem;
            color: #fff;
            letter-spacing: 2px;
            margin-top: 25px;
            text-transform: uppercase;
            text-shadow: 0 0 8px rgba(255,255,255,0.1);
        }

        .certify-text {
            font-size: 0.95rem;
            color: #a0aec0;
            max-width: 600px;
            line-height: 1.6;
            margin: 15px auto 0 auto;
        }

        .student-name {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 2rem;
            color: var(--cyan);
            letter-spacing: 1px;
            margin: 15px 0;
            text-shadow: 0 0 15px rgba(0,212,255,0.3);
        }

        .course-name {
            font-size: 1.3rem;
            color: #fff;
            font-weight: 700;
            text-decoration: underline;
            text-decoration-color: var(--violet);
            text-underline-offset: 6px;
            margin: 10px 0;
        }

        .footer-details {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 20px;
            margin-bottom: 10px;
        }

        .sign-block {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .signature {
            font-family: 'Alex Brush', cursive;
            font-size: 1.9rem;
            color: var(--magenta);
            transform: rotate(-3deg);
            margin-bottom: 5px;
        }

        .line {
            width: 150px;
            height: 1px;
            background-color: rgba(255,255,255,0.2);
            margin-bottom: 5px;
        }

        .sign-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.6rem;
            letter-spacing: 1px;
            color: #718096;
            text-transform: uppercase;
        }

        .info-block {
            text-align: right;
            font-size: 0.75rem;
            color: #718096;
            line-height: 1.5;
        }

        .info-block strong {
            color: #a0aec0;
        }

        /* Estilos de Impresión */
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
                padding: 0;
                margin: 0;
                display: block;
            }
            .web-controls {
                display: none !important;
            }
            .diploma-container {
                box-shadow: none !important;
                border: 4px solid #000 !important;
                background: #fff !important;
                color: #000 !important;
                border-radius: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                height: 100% !important;
                page-break-after: avoid;
            }
            .diploma-container::before {
                border-color: rgba(0,0,0,0.2) !important;
            }
            .corner {
                border-color: #000 !important;
            }
            .header-logo, .student-name {
                color: #000 !important;
                text-shadow: none !important;
            }
            .header-subtitle {
                color: #555 !important;
            }
            .main-title {
                color: #000 !important;
                text-shadow: none !important;
            }
            .certify-text {
                color: #333 !important;
            }
            .course-name {
                color: #000 !important;
                text-decoration-color: #000 !important;
            }
            .signature {
                color: #000 !important;
            }
            .line {
                background-color: #000 !important;
            }
            .sign-title, .info-block {
                color: #555 !important;
            }
            .info-block strong {
                color: #000 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Controles Web -->
    <div class="web-controls">
        <?php if ($admin_logged): ?>
            <a href="panel.php?view=reportes" class="btn-action btn-back">← Volver a Reportes</a>
        <?php else: ?>
            <a href="perfil.php" class="btn-action btn-back">← Volver a mi Perfil</a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn-action btn-print">🖨️ Imprimir / Guardar PDF</button>
    </div>

    <!-- Contenedor del Diploma -->
    <div class="diploma-container">
        <!-- Esquinas decorativas -->
        <div class="corner c-tl"></div>
        <div class="corner c-tr"></div>
        <div class="corner c-bl"></div>
        <div class="corner c-br"></div>

        <!-- Encabezado -->
        <div>
            <div class="header-logo">LABORATORIO DE APRENDIZAJE IA</div>
            <div class="header-subtitle">Certificado de Excelencia Académica</div>
        </div>

        <!-- Título -->
        <div>
            <h1 class="main-title">CERTIFICADO DE APROBACIÓN</h1>
            <p class="certify-text">Se otorga con orgullo el presente reconocimiento a:</p>
            <div class="student-name"><?php echo htmlspecialchars($est['nombre']); ?></div>
            <p class="certify-text">Por haber culminado y aprobado con éxito la evaluación final del módulo:</p>
            <div class="course-name"><?php echo htmlspecialchars($nombre_curso); ?></div>
            <p class="certify-text">Con una calificación de <strong><?php echo $nota; ?> / 100</strong> puntos.</p>
        </div>

        <!-- Pie de página -->
        <div class="footer-details">
            <!-- Firma -->
            <div class="sign-block">
                <div class="signature">Admins. Laboratorio IA</div>
                <div class="line"></div>
                <div class="sign-title">Firma de Certificación</div>
            </div>

            <!-- Información Adicional -->
            <div class="info-block">
                ID Alumno: <strong>#<?php echo $id_estudiante; ?></strong><br>
                Fecha de Emisión: <strong><?php echo date('d / m / Y'); ?></strong><br>
                Estado: <strong>Verificado</strong>
            </div>
        </div>
    </div>

</body>
</html>
