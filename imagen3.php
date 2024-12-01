<?php
$clave = $_GET['clave'];

// Conectar a la base de datos
$conn = new mysqli("localhost", "daniels", "daniels", "prueba"); // Cambia estos parámetros según tu configuración

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Preparar la consulta para obtener la imagen
$sql = "SELECT imagen FROM joyas WHERE clave = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $clave);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($imagen);

// Verificar si se encontró la imagen
if ($stmt->fetch()) {
    // Asegurarse de que la imagen no esté vacía
    if (!empty($imagen)) {
        // Determinar el tipo de imagen
        header('Content-Type: image/jpeg'); // Cambia a 'image/png' si es necesario
        echo $imagen; // Mostrar la imagen
    } else {
        http_response_code(404); // Imagen no encontrada
        echo "Imagen vacía.";
    }
} else {
    http_response_code(404); // Manejar el caso donde no se encontró la imagen
    echo "No se encontró la imagen con la clave proporcionada.";
}

$stmt->close();
$conn->close();
?>
