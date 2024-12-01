<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';
require './fpdf/fpdf186/fpdf.php';

session_start();

// Conexión a la base de datos
$host = 'localhost'; // Cambia esto según tu configuración
$db = 'prueba'; // Cambia esto al nombre de tu base de datos.
$user = 'daniels'; // Cambia esto a tu usuario de la base de datos
$pass = 'daniels'; // Cambia esto a tu contraseña de la base de datos

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart = json_decode($_POST['cart'], true);
    $email = $_POST['email'];
    $total = 0;

    // Asegúrate de que el usuario esté logueado y que el usuario_id esté en la sesión
    $usuario_id = $_SESSION['usuario_id'] ?? null; // Asegúrate de establecer esto al iniciar sesión
    if ($usuario_id === null) {
        die("Usuario no autenticado.");
    }

    // Crear el PDF usando FPDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Tienda de Joyeria');
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);

    // Añadir detalles del carrito
    $pdf->Ln();
    $pdf->Cell(40, 10, 'Ticket de Compra');
    $pdf->Ln();
    $pdf->Cell(40, 10, '--------------------------');
    $pdf->Ln(10);

    foreach ($cart as $clave => $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $total += $subtotal;

        // Añadir datos del producto
        $pdf->Cell(40, 10, "Producto: " . $item['nombre']);
        $pdf->Ln();
        $pdf->Cell(40, 10, "Precio: $" . number_format($item['precio'], 2));
        $pdf->Ln();
        $pdf->Cell(40, 10, "Cantidad: " . $item['cantidad']);
        $pdf->Ln();
        
        // Usar la URL completa para obtener la imagen
        $imageUrl = "http://localhost/parteuno/imagen3.php?clave=" . $clave; // Asegúrate de que la URL sea correcta

        // Obtén el contenido de la imagen
        $imageContent = file_get_contents($imageUrl);
        if ($imageContent !== false) {
            // Guardar la imagen en un archivo temporal
            $tempImage = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
            file_put_contents($tempImage, $imageContent);
            // Usar la imagen temporal en el PDF
            $pdf->Image($tempImage, $pdf->GetX(), $pdf->GetY(), 40, 30); // Colocar la imagen
            // Eliminar la imagen temporal después de usarla
            unlink($tempImage);
        } else {
            $pdf->Cell(40, 10, "Imagen no disponible");
        }

        // Línea de separación
        $pdf->Ln(35); // Espacio entre productos
        $pdf->Cell(0, 0, '', 'T'); // Línea horizontal
        $pdf->Ln(5); // Espacio después de la línea

        // Registrar la compra en la base de datos
        $stmt = $conn->prepare("INSERT INTO compras (usuario_id, fecha, total) VALUES (?, NOW(), ?)");
        $stmt->bind_param("id", $usuario_id, $subtotal); // Asegúrate de usar los tipos correctos
        $stmt->execute();
        $compra_id = $stmt->insert_id; // Obtener el ID de la compra recién insertada

        // Actualizar el stock
        $stmt = $conn->prepare("UPDATE joyas SET stock = stock - ? WHERE clave = ?");
        $stmt->bind_param("is", $item['cantidad'], $clave);
        $stmt->execute();
    }

    // Total
    $pdf->Ln();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 10, "Total: $" . number_format($total, 2));

    // Guardar el PDF temporalmente
    $file = tempnam(sys_get_temp_dir(), 'ticket_') . '.pdf';
    $pdf->Output($file, 'F');

    // Configurar PHPMailer
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joyeriamitienda@gmail.com';
        $mail->Password = 'yiis ekkx wfba borb'; // Cambia esto a una variable de entorno para seguridad
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuración de los destinatarios
        $mail->setFrom('joyeriamitienda@gmail.com', 'Tienda de Joyeria');
        $mail->addAddress($email);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Tu ticket de compra';
        $mail->Body = 'Gracias por tu compra. Te adjuntamos el ticket.';

        // Adjuntar el PDF
        $mail->addAttachment($file);

        // Enviar el correo
        $mail->send();
        echo 'Ticket enviado con éxito';

    } catch (Exception $e) {
        echo "No se pudo enviar el correo. Error: {$mail->ErrorInfo}";
    }

    // Eliminar el archivo temporal
    unlink($file);
    $conn->close(); // Cerrar la conexión a la base de datos
}
?>
