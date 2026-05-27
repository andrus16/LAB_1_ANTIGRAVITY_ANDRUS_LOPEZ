<?php
// RECEPTOR DE CORREOS LOCAL (Bypass de Bloqueo de Puertos)
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$token = $_POST['token'] ?? '';
if ($token !== 'supersecret123') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$email = $_POST['email'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$username_generado = $_POST['username'] ?? '';
$random_password = $_POST['password'] ?? '';

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
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Acceso Concedido - Laboratorio IA';
    $mail->Body = "
    <div style='background:#050505;color:#e0e0e0;font-family:sans-serif;padding:30px;max-width:500px;border:1px solid #b026ff;border-radius:10px;'>
      <h2 style='color:#00d4ff;font-family:monospace;'>LABORATORIO IA</h2>
      <p style='color:#b026ff;font-size:0.85rem;'>SOLICITUD APROBADA</p>
      <hr style='border-color:#b026ff33;margin:15px 0;'>
      <p>Hola, <strong>{$nombre}</strong>. Tu registro ha sido <strong style='color:#00ff88;'>APROBADO</strong>.</p>
      <p>Tus credenciales de acceso son:</p>
      <div style='background:#0a0a14;border:1px solid #00d4ff33;padding:15px;border-radius:6px;margin:15px 0;'>
        <p><strong>Usuario:</strong> $username_generado</p>
        <p><strong>Contraseña temporal:</strong> <code style='color:#00d4ff;'>$random_password</code></p>
      </div>
      <p style='font-size:0.8rem;color:#888;'>Cambia tu contraseña al ingresar por primera vez.</p>
    </div>";
    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
