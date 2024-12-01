<?php
// Datos de conexión
$host = 'localhost';
$db = 'prueba';
$user = 'daniels';
$password = 'daniels';

$conn = new mysqli($host, $user, $password, $db);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener la imagen
if (isset($_GET['clave'])) {
    $clave = $_GET['clave'];
    $sql = "SELECT imagen FROM joyas WHERE clave = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $clave);
    $stmt->execute();
    $stmt->bind_result($imagen);
    $stmt->fetch();

    // Configurar cabeceras y mostrar imagen
    if ($imagen) {
        header("Content-Type: image/jpeg");
        echo $imagen;
    } else {
        echo "No se encontró la imagen.";
    }
}

$conn->close();
?>
