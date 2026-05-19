<?php
session_start();
if (isset($_SESSION["estudiante_logged_in"]) && $_SESSION["estudiante_logged_in"] === true) {
    header("Location: perfil.php"); exit;
}
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_input    = $_POST["email"] ?? '';
    $password_input = $_POST["password"] ?? '';
    $host = "localhost"; $dbname = "laboratorio_ia";
    $db_user = "root";   $db_pass = "123456789";
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql  = "SELECT id, nombre, password_hash, estado FROM estudiantes WHERE email = :email OR username = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email_input);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row['estado'] !== 'Aprobado') {
                $error = "Acceso Denegado: Su cuenta aún no ha sido aprobada.";
            } elseif (password_verify($password_input, $row['password_hash'])) {
                $_SESSION["estudiante_logged_in"] = true;
                $_SESSION["estudiante_id"]        = $row['id'];
                $_SESSION["estudiante_nombre"]    = $row['nombre'];
                header("Location: perfil.php"); exit;
            } else {
                $error = "Acceso Denegado: Contraseña incorrecta.";
            }
        } else {
            $error = "Acceso Denegado: Usuario no encontrado.";
        }
    } catch(PDOException $e) {
        $error = "Error crítico: " . $e->getMessage();
    }
    $conn = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Estudiante - Laboratorio IA</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { flex-direction: column; overflow-y: auto; }

        .login-wrapper {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            width: 100%;
            min-height: 100vh;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>

    <div class="login-wrapper">
        <h1 class="page-title glitch" data-text="ACCESO ESTUDIANTE">ACCESO ESTUDIANTE</h1>

        <div class="ia-card login-card">
            <div class="form-wrap">
                <a href="index.html" class="back-link">◄ Volver al inicio</a>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="login_estudiante.php">
                    <div class="form-group">
                        <label for="email" class="form-label">Usuario (Correo):</label>
                        <input type="text" id="email" name="email" class="form-input" required placeholder="tu@correo.com" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Contraseña Temporal:</label>
                        <input type="password" id="password" name="password" class="form-input" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn-primary">Iniciar Enlace Neuronal</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
