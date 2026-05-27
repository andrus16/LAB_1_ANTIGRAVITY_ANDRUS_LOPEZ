<?php
// ARCHIVO TEMPORAL - Eliminar después de usar
$host = "localhost"; $dbname = "laboratorio_ia";
$username = "root";  $password = "123456789";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("TRUNCATE TABLE estudiantes");
    echo "<h2 style='color:green; font-family:monospace;'>✅ Tabla estudiantes limpiada exitosamente.</h2>";
    echo "<p style='font-family:monospace;'>Ahora <strong>ELIMINA este archivo</strong> del servidor por seguridad.</p>";
    echo "<p><a href='panel.php'>← Ir al Panel</a></p>";
} catch(PDOException $e) {
    echo "<h2 style='color:red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>
