<?php
$mensaje = "";
$error   = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = htmlspecialchars($_POST["nombre"]);
    $email  = htmlspecialchars($_POST["email"]);
    $curso  = htmlspecialchars($_POST["curso"]);
    $host = "localhost"; $dbname = "laboratorio_ia";
    $username = "root";  $password = "123456789";
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql  = "INSERT INTO estudiantes (nombre, email, curso) VALUES (:nombre, :email, :curso)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email',  $email);
        $stmt->bindParam(':curso',  $curso);
        $stmt->execute();
        $mensaje = "¡Registro exitoso! Bienvenido/a al sistema, <strong>$nombre</strong>. Tu solicitud está en revisión.";
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "El correo electrónico ya está registrado en el sistema.";
        } else {
            $error = "Error de conexión: " . $e->getMessage();
        }
    }
    $conn = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Laboratorio IA</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { flex-direction: column; overflow-y: auto; }
        .reg-wrapper {
            position: relative; z-index: 2; display: flex; flex-direction: column;
            align-items: center; padding: 50px 20px; width: 100%; min-height: 100vh; justify-content: center;
        }
        .reg-card { width: 100%; max-width: 480px; }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2300d4ff' stroke-width='2' fill='none'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
            cursor: pointer;
        }
        select.form-input option { background: #0a0a14; color: #e0e0e0; }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    <div class="reg-wrapper">
        <h1 class="page-title glitch" data-text="INSCRIPCIÓN IA">INSCRIPCIÓN IA</h1>

        <div class="ia-card reg-card">
            <div class="form-wrap">
                <a href="index.html" class="back-link">◄ Volver al inicio</a>

                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="registro.php">
                    <div class="form-group">
                        <label for="nombre" class="form-label">Nombre Completo:</label>
                        <input type="text" id="nombre" name="nombre" class="form-input" required placeholder="Ej. Alan Turing" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Correo Electrónico:</label>
                        <input type="email" id="email" name="email" class="form-input" required placeholder="alan@correo.com" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="curso" class="form-label">Módulo de Especialización:</label>
                        <select id="curso" name="curso" class="form-input" required>
                            <option value="">— Seleccione un módulo —</option>
                            <option value="machine_learning">Machine Learning</option>
                            <option value="procesamiento_lenguaje">Procesamiento de Lenguaje Natural</option>
                            <option value="regresion_lineal">Regresión Lineal</option>
                            <option value="algoritmos_geneticos">Algoritmos Genéticos</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;">Ejecutar Registro</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
