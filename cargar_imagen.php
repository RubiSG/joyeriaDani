<?php
// Datos de conexión a la base de datos
$host = 'localhost';
$db = 'prueba';
$user = 'daniels';
$password = 'daniels';

$conn = new mysqli($host, $user, $password, $db);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si el formulario de agregar fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $talla = $_POST['talla'];
    $stock = $_POST['stock'];
    $precio = $_POST['precio'];

    // Procesar la imagen si fue subida
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = file_get_contents($_FILES['imagen']['tmp_name']); // Convertir la imagen a BLOB
    } else {
        // Si no se subió una imagen, asignar null
        $imagen = null;
    }

    // Preparar la consulta SQL para insertar una nueva joya con imagen
    $sql = "INSERT INTO joyas (nombre, tipo, talla, stock, precio, imagen) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssids', $nombre, $tipo, $talla, $stock, $precio, $imagen); // 's' = string, 'i' = int, 'd' = double (precio), 'b' = blob (imagen)

    if ($stmt->execute()) {
        echo "Nueva joya agregada exitosamente.";
        header("Location: alta.php"); // Redirigir a la página principal
    } else {
        echo "Error al agregar la joya: " . $stmt->error;
    }
}

// Cerrar conexión
$conn->close();
?>
