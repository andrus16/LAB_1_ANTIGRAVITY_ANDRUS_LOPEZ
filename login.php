<?php
session_start();
if (isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true) {
    header("Location: panel.php"); exit;
}
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario        = $_POST["usuario"] ?? '';
    $password_input = $_POST["password"] ?? '';
    $host = "localhost"; $dbname = "laboratorio_ia";
    $db_user = "root";   $db_pass = "123456789";
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql  = "SELECT id, password_hash FROM administradores WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $usuario);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password_input, $row['password_hash'])) {
                $_SESSION["admin_logged_in"] = true;
                $_SESSION["admin_username"]  = $usuario;
                header("Location: panel.php"); exit;
            } else { $error = "Credenciales inválidas."; }
        } else { $error = "Credenciales inválidas."; }
    } catch(PDOException $e) { $error = "Error crítico: " . $e->getMessage(); }
    $conn = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrador - Laboratorio IA</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { flex-direction: column; overflow-y: auto; }
        .login-wrapper {
            position: relative; z-index: 2; display: flex; flex-direction: column;
            align-items: center; padding: 40px 20px; width: 100%; min-height: 100vh; justify-content: center;
        }
        .login-card { width: 100%; max-width: 420px; }
        /* Badge de admin */
        .admin-badge {
            display: inline-block;
            background: rgba(0, 212, 255, 0.08);
            border: 1px solid rgba(0, 212, 255, 0.25);
            border-radius: 50px;
            padding: 4px 16px;
            font-size: 0.65rem;
            font-family: var(--font-display);
            letter-spacing: 2px;
            color: var(--cyan);
            text-transform: uppercase;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    <div class="login-wrapper">
        <div class="admin-badge">▸ Acceso Restringido</div>
        <h1 class="page-title glitch" data-text="PANEL ADMIN">PANEL ADMIN</h1>

        <div class="ia-card login-card">
            <div class="form-wrap">
                <a href="index.html" class="back-link">◄ Volver al inicio</a>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label for="usuario" class="form-label">ID Administrador:</label>
                        <input type="text" id="usuario" name="usuario" class="form-input" required placeholder="Nombre de usuario">
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Código de Acceso:</label>
                        <input type="password" id="password" name="password" class="form-input" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;">Autenticar Identidad</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
