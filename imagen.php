<?php 
session_start();
$host = 'localhost';
$db = 'prueba';
$user = 'daniels';
$password = 'daniels';

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si se ha pasado un ID
if (isset($_GET['clave'])) {
    $clave = intval($_GET['clave']); 
    $sql = "SELECT imagen FROM joyas WHERE clave = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clave);
    $stmt->execute();
    $stmt->bind_result($imagen);
    $stmt->fetch();
    
    if ($imagen) {
        // Determinar el tipo de imagen
        $img_info = getimagesizefromstring($imagen);
        if ($img_info !== false) {
            header("Content-type: " . $img_info['mime']); 
            echo $imagen; 
        } else {
            header("HTTP/1.0 404 Not Found");
            echo "Tipo de imagen no válido.";
        }
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "Imagen no encontrada.";
    }
} else {
    header("HTTP/1.0 400 Bad Request");
    echo "ID no proporcionado.";
}


$conn->close();
?>
