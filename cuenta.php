<?php
session_start();
require 'db.php'; // Archivo de conexión a la base de datos

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userid = $_SESSION['userid'];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update'])) {
        $new_name = $_POST['name'];
        $new_username = $_POST['username'];
        $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $update_sql = "UPDATE usuarios SET nombre = ?, usuario = ?, contrasena = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $new_name, $new_username, $new_password, $userid);
        $update_stmt->execute();

        echo "Información actualizada con éxito.";
    } elseif (isset($_POST['delete'])) {
        $delete_sql = "DELETE FROM usuarios WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $userid);
        $delete_stmt->execute();

        session_destroy();
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cuenta</title>
</head>
<body>
    <h2>Editar Cuenta</h2>
    <form method="post">
        <label for="name">Nombre:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['nombre']); ?>" required><br>

        <label for="username">Usuario:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['usuario']); ?>" required><br>

        <label for="password">Nueva Contraseña:</label>
        <input type="password" name="password" required><br>

        <button type="submit" name="update">Actualizar</button>
    </form>

    <h2>Borrar Cuenta</h2>
    <form method="post">
        <button type="submit" name="delete">Borrar Cuenta</button>
    </form>
</body>
</html>
