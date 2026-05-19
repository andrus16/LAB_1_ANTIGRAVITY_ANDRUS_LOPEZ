<?php
session_start();

if (!isset($_SESSION["estudiante_logged_in"]) || $_SESSION["estudiante_logged_in"] !== true) {
    header("Location: login_estudiante.php");
    exit;
}

$id_estudiante = $_SESSION["estudiante_id"];

// Credenciales de la base de datos
$host = "localhost";
$dbname = "laboratorio_ia";
$username = "root"; 
$password = "123456789"; 

$error = "";
$success = "";
$estudiante_data = [];

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Procesar actualización del perfil
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_POST['action'] ?? '';

        if ($action == 'update_datos') {
            $telefono = htmlspecialchars($_POST['telefono'] ?? '');
            $biografia = htmlspecialchars($_POST['biografia'] ?? '');
            $foto_path = null;
            
            // Manejo de la foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['foto']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_filename = "perfil_" . $id_estudiante . "_" . time() . "." . $filetype;
                    $upload_dir = "uploads/";
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $new_filename)) {
                        $foto_path = $upload_dir . $new_filename;
                    } else {
                        $error = "Error al subir la imagen.";
                    }
                } else {
                    $error = "Formato de imagen no permitido. Solo JPG, PNG o GIF.";
                }
            }

            if (empty($error)) {
                $update_fields = ["telefono = :telefono", "biografia = :biografia"];
                $params = [':telefono' => $telefono, ':biografia' => $biografia, ':id' => $id_estudiante];
                if ($foto_path !== null) {
                    $update_fields[] = "foto_perfil = :foto";
                    $params[':foto'] = $foto_path;
                }
                
                $sql_update = "UPDATE estudiantes SET " . implode(", ", $update_fields) . " WHERE id = :id";
                $stmt_up = $conn->prepare($sql_update);
                if ($stmt_up->execute($params)) $success = "Datos actualizados exitosamente.";
            }

        } elseif ($action == 'update_password') {
            $nueva_password = $_POST['nueva_password'] ?? '';
            $confirmar_password = $_POST['confirmar_password'] ?? '';

            if (empty($nueva_password) || $nueva_password !== $confirmar_password) {
                $error = "Las contraseñas no coinciden o están vacías.";
            } else if (strlen($nueva_password) < 6) {
                $error = "La nueva contraseña debe tener al menos 6 caracteres.";
            } else {
                $sql_update = "UPDATE estudiantes SET password_hash = :password_hash WHERE id = :id";
                $stmt_up = $conn->prepare($sql_update);
                $stmt_up->execute([
                    ':password_hash' => password_hash($nueva_password, PASSWORD_DEFAULT),
                    ':id' => $id_estudiante
                ]);
                $success = "Contraseña modificada exitosamente.";
            }
        }
    }
    
    // Obtener datos actuales
    $sql = "SELECT * FROM estudiantes WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id_estudiante);
    $stmt->execute();
    $estudiante_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
}

$nombre_curso_amigable = ucwords(str_replace('_', ' ', $estudiante_data['curso']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Estudiante</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', sans-serif; }
        body { display: flex; height: 100vh; background-color: #0a0b0e; color: #e2e8f0; overflow: hidden; }
        
        /* Top Header (Simulando DGH azul) */
        .top-header { position: absolute; top: 0; left: 0; width: 250px; height: 50px; background-color: #00b4d8; color: white; display: flex; align-items: center; padding: 0 15px; font-weight: bold; font-size: 1.2rem; z-index: 10; }
        
        /* Sidebar (Negro/Gris Oscuro) */
        .sidebar { width: 250px; background-color: #2b2b2b; color: #ccc; height: 100vh; padding-top: 50px; position: relative; z-index: 5; flex-shrink: 0; }
        
        /* Usuario Box */
        .user-box { padding: 15px; display: flex; align-items: center; gap: 10px; cursor: pointer; border-bottom: 1px solid #444; position: relative; }
        .user-box:hover { background-color: #333; }
        .user-photo { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #fff; }
        .user-name { font-size: 0.85rem; font-weight: bold; text-transform: uppercase; color: #fff; }
 
        /* Dropdown Menu */
        .user-dropdown { display: none; background: #1e1e24; color: #fff; position: absolute; top: 100%; left: 10; width: 90%; margin: 0 5%; border-radius: 5px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); z-index: 20; overflow: hidden; border: 1px solid #444; }
        .user-dropdown.active { display: block; }
        .user-dropdown a { display: block; padding: 10px 15px; color: #ccc; text-decoration: none; font-size: 0.9rem; border-bottom: 1px solid #333; }
        .user-dropdown a:hover { background: #333; color: #00b4d8; }
        .user-dropdown a:last-child { border-bottom: none; }
 
        /* Menú Lateral Normal */
        .nav-menu { list-style: none; margin-top: 10px; }
        .nav-menu li { border-bottom: 1px solid #333; }
        .nav-menu a { display: block; padding: 12px 20px; color: #ccc; text-decoration: none; font-size: 0.9rem; transition: 0.2s; }
        .nav-menu a:hover, .nav-menu a.active { background: #444; color: #fff; border-left: 4px solid #00b4d8; }
 
        /* Main Content */
        .main-content { flex: 1; padding: 30px; background-color: #0f1015; overflow-y: auto; padding-top: 60px; }
        
        /* Page Title */
        .page-title { font-size: 1.5rem; color: #ffffff; margin-bottom: 30px; border-bottom: 2px solid rgba(0, 180, 216, 0.25); padding-bottom: 10px; }
 
        /* Grid Tarjetas */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        
        /* Card Estilo (Similar a la imagen) */
        .course-card { background: rgba(255, 255, 255, 0.03); border-radius: 8px; border: 1px solid rgba(0, 180, 216, 0.15); overflow: hidden; box-shadow: 0 4px 20px rgba(0,212,255,0.03); position: relative; }
        .card-header { background: rgba(255, 255, 255, 0.02); padding: 10px 15px; font-weight: bold; border-bottom: 1px solid rgba(0, 180, 216, 0.15); color: #fff; }
        .card-body { padding: 20px; display: flex; align-items: center; justify-content: space-between; position: relative; overflow: hidden; }
        
        /* Efecto color en el lado derecho de la tarjeta */
        .card-bg-shape { position: absolute; right: -30px; top: -30px; width: 120px; height: 120px; border-radius: 50%; opacity: 0.15; z-index: 1; }
        .bg-green { background: #4caf50; }
        .bg-purple { background: #9c27b0; }
 
        .course-title { font-size: 1.1rem; color: #fff; z-index: 2; position: relative; margin-bottom: 10px; }
        .badge { display: inline-block; padding: 5px 10px; background: #4caf50; color: white; font-size: 0.75rem; border-radius: 12px; font-weight: bold; z-index: 2; position: relative; }
        
        .btn-ingresar { display: block; text-align: center; background: transparent; border: none; color: #00b4d8; font-weight: bold; padding: 15px; text-transform: uppercase; cursor: pointer; text-decoration: none; border-top: 1px solid rgba(255, 255, 255, 0.08); transition: 0.2s; }
        .btn-ingresar:hover { background: rgba(0, 180, 216, 0.05); }
 
        /* Secciones (Formularios) */
        .section-panel { display: none; max-width: 600px; }
        .section-panel.active { display: block; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #b026ff; font-weight: bold; font-size: 0.9rem; }
        input[type="text"], input[type="password"], textarea, input[type="file"] { width: 100%; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(176,38,255,0.3); color: #fff; border-radius: 4px; font-size: 0.95rem; }
        button.btn-submit { background: #00b4d8; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 10px; }
        button.btn-submit:hover { background: #0096c7; }
 
        /* Mensajes */
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9rem; }
        .msg.error { background: rgba(255, 0, 60, 0.08); color: #ff003c; border: 1px solid rgba(255, 0, 60, 0.3); }
        .msg.success { background: rgba(0, 255, 136, 0.08); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.3); }

    </style>
</head>
<body>

    <!-- Header superior para el logo/sistema -->
    <div class="top-header">
        ≡ PANEL IA
    </div>

    <!-- Barra Lateral Izquierda -->
    <aside class="sidebar">
        <!-- Caja de Usuario (Click para abrir menú) -->
        <div class="user-box" id="userBox">
            <?php if (!empty($estudiante_data['foto_perfil'])): ?>
                <img src="<?php echo htmlspecialchars($estudiante_data['foto_perfil']); ?>" alt="Perfil" class="user-photo">
            <?php else: ?>
                <img src="https://via.placeholder.com/40/cccccc/ffffff?text=U" alt="Perfil" class="user-photo">
            <?php endif; ?>
            <span class="user-name"><?php echo htmlspecialchars($estudiante_data['nombre']); ?></span>
            
            <!-- Menú Desplegable -->
            <div class="user-dropdown" id="userDropdown">
                <a href="#" onclick="showSection('datos')">👤 Actualizar Datos</a>
                <a href="#" onclick="showSection('password')">🔑 Actualizar Contraseña</a>
                <a href="logout_estudiante.php" style="color: #c62828;">🚪 Cerrar Sesión</a>
            </div>
        </div>

        <ul class="nav-menu">
            <li><a href="#" onclick="showSection('cursos')" class="active" id="linkCursos">📚 Mis Cursos</a></li>
        </ul>
    </aside>

    <!-- Área Principal Derecha -->
    <main class="main-content">
        
        <?php if (!empty($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="msg success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Sección: Mis Cursos -->
        <div id="sec-cursos" class="section-panel active">
            <h2 class="page-title">Cursos Aprobados</h2>
            
            <div class="cards-grid">
                <?php 
                $tiene_curso_aprobado = false;
                if ($estudiante_data['estado'] === 'Aprobado') {
                    if ($estudiante_data['curso'] === 'regresion_lineal') {
                        $tiene_curso_aprobado = true;
                        ?>
                        <!-- Tarjeta: Regresión Lineal -->
                        <div class="course-card">
                            <div class="card-header">Módulo 1</div>
                            <div class="card-body">
                                <div>
                                    <div class="course-title">Regresión Lineal</div>
                                    <div class="badge">Nota: <?php echo $estudiante_data['calificacion'] !== null ? $estudiante_data['calificacion'].'/100' : 'Sin evaluar'; ?></div>
                                </div>
                                <div class="card-bg-shape bg-green"></div>
                            </div>
                            <a href="curso_regresion_lineal.php" class="btn-ingresar">+ INGRESAR</a>
                            <?php if ((int)$estudiante_data['certificado_listo'] === 1): ?>
                                <a href="generar_certificado.php" target="_blank" class="btn-ingresar" style="color:#00ff88; border-top: 1px solid rgba(255, 255, 255, 0.08); background: rgba(0, 255, 136, 0.03);">🎓 VER CERTIFICADO</a>
                            <?php endif; ?>
                        </div>
                        <?php
                    } elseif ($estudiante_data['curso'] === 'algoritmos_geneticos') {
                        $tiene_curso_aprobado = true;
                        ?>
                        <!-- Tarjeta: Algoritmos Genéticos -->
                        <div class="course-card">
                            <div class="card-header">Módulo 2</div>
                            <div class="card-body">
                                <div>
                                    <div class="course-title">Algoritmos Genéticos</div>
                                    <div class="badge">Nota: <?php echo $estudiante_data['calificacion_algoritmos_geneticos'] !== null ? $estudiante_data['calificacion_algoritmos_geneticos'].'/100' : 'Sin evaluar'; ?></div>
                                </div>
                                <div class="card-bg-shape bg-purple"></div>
                            </div>
                            <a href="curso_algoritmos_geneticos.php" class="btn-ingresar">+ INGRESAR</a>
                            <?php if ((int)$estudiante_data['certificado_listo'] === 1): ?>
                                <a href="generar_certificado.php" target="_blank" class="btn-ingresar" style="color:#00ff88; border-top: 1px solid rgba(255, 255, 255, 0.08); background: rgba(0, 255, 136, 0.03);">🎓 VER CERTIFICADO</a>
                            <?php endif; ?>
                        </div>
                        <?php
                    } elseif ($estudiante_data['curso'] === 'machine_learning') {
                        $tiene_curso_aprobado = true;
                        ?>
                        <!-- Tarjeta: Machine Learning -->
                        <div class="course-card">
                            <div class="card-header">Módulo 3</div>
                            <div class="card-body">
                                <div>
                                    <div class="course-title">Machine Learning</div>
                                    <div class="badge">Nota: <?php echo $estudiante_data['calificacion_machine_learning'] !== null ? $estudiante_data['calificacion_machine_learning'].'/100' : 'Sin evaluar'; ?></div>
                                </div>
                                <div class="card-bg-shape bg-green" style="background: #00b4d8 !important;"></div>
                            </div>
                            <a href="curso_machine_learning.php" class="btn-ingresar">+ INGRESAR</a>
                            <?php if ((int)$estudiante_data['certificado_listo'] === 1): ?>
                                <a href="generar_certificado.php" target="_blank" class="btn-ingresar" style="color:#00ff88; border-top: 1px solid rgba(255, 255, 255, 0.08); background: rgba(0, 255, 136, 0.03);">🎓 VER CERTIFICADO</a>
                            <?php endif; ?>
                        </div>
                        <?php
                    } elseif ($estudiante_data['curso'] === 'procesamiento_lenguaje') {
                        $tiene_curso_aprobado = true;
                        ?>
                        <!-- Tarjeta: Procesamiento de Lenguaje Natural -->
                        <div class="course-card">
                            <div class="card-header">Módulo 4</div>
                            <div class="card-body">
                                <div>
                                    <div class="course-title">Procesamiento de Lenguaje Natural</div>
                                    <div class="badge">Nota: <?php echo $estudiante_data['calificacion_procesamiento_lenguaje'] !== null ? $estudiante_data['calificacion_procesamiento_lenguaje'].'/100' : 'Sin evaluar'; ?></div>
                                </div>
                                <div class="card-bg-shape bg-purple" style="background: #ff00c1 !important;"></div>
                            </div>
                            <a href="curso_procesamiento_lenguaje.php" class="btn-ingresar">+ INGRESAR</a>
                            <?php if ((int)$estudiante_data['certificado_listo'] === 1): ?>
                                <a href="generar_certificado.php" target="_blank" class="btn-ingresar" style="color:#00ff88; border-top: 1px solid rgba(255, 255, 255, 0.08); background: rgba(0, 255, 136, 0.03);">🎓 VER CERTIFICADO</a>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                }
                
                if (!$tiene_curso_aprobado) {
                    echo '<p style="color:#94a3b8; font-size:0.9rem; grid-column: 1 / -1; text-align: center; padding: 20px;">No tienes ningún curso aprobado por el administrador en este momento o tu cuenta está pendiente.</p>';
                }
                ?>
            </div>
        </div>

        <!-- Sección: Actualizar Datos -->
        <div id="sec-datos" class="section-panel">
            <h2 class="page-title">Actualizar Datos Personales</h2>
            <form method="POST" action="perfil.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_datos">
                
                <div class="form-group">
                    <label>Teléfono (Enlace Alternativo):</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($estudiante_data['telefono'] ?? ''); ?>" autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label>Biografía:</label>
                    <textarea name="biografia" rows="4"><?php echo htmlspecialchars($estudiante_data['biografia'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Foto de Perfil:</label>
                    <input type="file" name="foto" accept="image/*">
                </div>
                
                <button type="submit" class="btn-submit">Guardar Datos</button>
            </form>
        </div>

        <!-- Sección: Actualizar Contraseña -->
        <div id="sec-password" class="section-panel">
            <h2 class="page-title">Actualizar Contraseña</h2>
            <form method="POST" action="perfil.php">
                <input type="hidden" name="action" value="update_password">
                
                <div class="form-group">
                    <label>Nueva Contraseña:</label>
                    <input type="password" name="nueva_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirmar Nueva Contraseña:</label>
                    <input type="password" name="confirmar_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn-submit">Cambiar Contraseña</button>
            </form>
        </div>

    </main>

    <script>
        // Toggle Menu Desplegable del Usuario
        const userBox = document.getElementById('userBox');
        const userDropdown = document.getElementById('userDropdown');

        userBox.addEventListener('click', function(e) {
            // Evitar cerrar inmediatamente si se hace click en el menú mismo
            userDropdown.classList.toggle('active');
        });

        // Cerrar menú si hace click afuera
        document.addEventListener('click', function(e) {
            if (!userBox.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });

        // Lógica para cambiar entre secciones
        function showSection(sectionName) {
            // Ocultar todas las secciones
            document.querySelectorAll('.section-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Quitar clase active de los links del sidebar
            document.querySelectorAll('.nav-menu a').forEach(link => {
                link.classList.remove('active');
            });

            // Mostrar la sección correspondiente
            document.getElementById('sec-' + sectionName).classList.add('active');

            if(sectionName === 'cursos') {
                document.getElementById('linkCursos').classList.add('active');
            }
        }
    </script>
</body>
</html>
