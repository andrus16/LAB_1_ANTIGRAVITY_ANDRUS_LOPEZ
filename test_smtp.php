<?php
// ARCHIVO DE DIAGNÓSTICO TEMPORAL - Probar conexión de red desde el VPS
header('Content-Type: text/html; charset=utf-8');
echo "<h2>Diagnóstico de Red: Conexión a Gmail SMTP</h2>";
echo "<p>Este script verifica si el proveedor de tu VPS bloquea las conexiones salientes a los puertos de correo.</p>";

$targets = [
    ['host' => 'smtp.gmail.com', 'port' => 587, 'name' => 'TLS/STARTTLS'],
    ['host' => 'smtp.gmail.com', 'port' => 465, 'name' => 'SSL/Implicit'],
    ['host' => 'smtp.gmail.com', 'port' => 25,  'name' => 'SMTP estándar sin cifrar']
];

echo "<table border='1' cellpadding='10' style='border-collapse:collapse; font-family:monospace;'>";
echo "<tr style='background:#eee;'><th>Servidor</th><th>Puerto</th><th>Tipo</th><th>Resultado</th><th>Detalle</th></tr>";

foreach ($targets as $target) {
    echo "<tr>";
    echo "<td>{$target['host']}</td>";
    echo "<td>{$target['port']}</td>";
    echo "<td>{$target['name']}</td>";
    
    $start = microtime(true);
    $fp = @fsockopen($target['host'], $target['port'], $errno, $errstr, 5);
    $duration = round((microtime(true) - $start) * 1000, 2);
    
    if (!$fp) {
        echo "<td style='color:red; font-weight:bold;'>BLOQUEADO / FALLÓ</td>";
        echo "<td>Error #$errno: $errstr (Tiempo de intento: {$duration}ms)</td>";
    } else {
        echo "<td style='color:green; font-weight:bold;'>ABIERTO / CONECTADO</td>";
        echo "<td>Conexión exitosa en {$duration}ms</td>";
        fclose($fp);
    }
    echo "</tr>";
}
echo "</table>";

echo "<br><h3>¿Qué significa esto?</h3>";
echo "<ul>";
echo "<li>Si todos los puertos dicen <b>BLOQUEADO (Error #110: Connection timed out)</b>, tu proveedor de VPS tiene un firewall externo que prohíbe de forma absoluta conectarse a servidores de correo externos para evitar SPAM.</li>";
echo "<li>Si alguno dice <b>ABIERTO</b>, ese puerto es el que debemos configurar en PHPMailer.</li>";
echo "</ul>";
echo "<p><a href='panel.php'>← Volver al Panel</a></p>";
?>
