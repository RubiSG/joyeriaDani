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

// Evitar que el navegador almacene en caché la página
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


// Eliminar joya
if (isset($_GET['eliminar'])) {
    $clave = $_GET['eliminar'];
    $sql = "DELETE FROM joyas WHERE clave = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $clave);
    $stmt->execute();
    header("Location: alta.php");
    exit();
}

// Actualizar joya
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $clave = $_POST['clave'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $talla = $_POST['talla'];
    $stock = $_POST['stock'];
    $precio = $_POST['precio'];
    
    // Comprobar si se ha subido una nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
        $sql = "UPDATE joyas SET nombre=?, tipo=?, talla=?, stock=?, precio=?, imagen=? WHERE clave=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssidsi', $nombre, $tipo, $talla, $stock, $precio, $imagen, $clave);
    } else {
        // Si no se sube imagen, actualizar sin cambiar el campo de imagen
        $sql = "UPDATE joyas SET nombre=?, tipo=?, talla=?, stock=?, precio=? WHERE clave=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssidi', $nombre, $tipo, $talla, $stock, $precio, $clave);
    }
    
    $stmt->execute();
    header("Location: alta.php");
    exit();
}

// Obtener joyas de la base de datos
$sql = "SELECT * FROM joyas";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabla de Joyas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #DFDBE5; /* Color de fondo suave */
            background-image: url("data:image/svg+xml,%3Csvg width='180' height='180' viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M81.28 88H68.413l19.298 19.298L81.28 88zm2.107 0h13.226L90 107.838 83.387 88zm15.334 0h12.866l-19.298 19.298L98.72 88zm-32.927-2.207L73.586 78h32.827l.5.5 7.294 7.293L115.414 87l-24.707 24.707-.707.707L64.586 87l1.207-1.207zm2.62.207L74 80.414 79.586 86H68.414zm16 0L90 80.414 95.586 86H84.414zm16 0L106 80.414 111.586 86h-11.172zm-8-6h11.173L98 85.586 92.414 80zM82 85.586L87.586 80H76.414L82 85.586zM17.414 0L.707 16.707 0 17.414V0h17.414zM4.28 0L0 12.838V0h4.28zm10.306 0L2.288 12.298 6.388 0h8.198zM180 17.414L162.586 0H180v17.414zM165.414 0l12.298 12.298L173.612 0h-8.198zM180 12.838L175.72 0H180v12.838zM0 163h16.413l.5.5 7.294 7.293L25.414 172l-8 8H0v-17zm0 10h6.613l-2.334 7H0v-7zm14.586 7l7-7H8.72l-2.333 7h8.2zM0 165.414L5.586 171H0v-5.586zM10.414 171L16 165.414 21.586 171H10.414zm-8-6h11.172L8 170.586 2.414 165zM180 163h-16.413l-7.794 7.793-1.207 1.207 8 8H180v-17zm-14.586 17l-7-7h12.865l2.333 7h-8.2zM180 173h-6.613l2.334 7H180v-7zm-21.586-2l5.586-5.586 5.586 5.586h-11.172zM180 165.414L174.414 171H180v-5.586zm-8 5.172l5.586-5.586h-11.172l5.586 5.586zM152.933 25.653l1.414 1.414-33.94 33.942-1.416-1.416 33.943-33.94zm1.414 127.28l-1.414 1.414-33.942-33.94 1.416-1.416 33.94 33.943zm-127.28 1.414l-1.414-1.414 33.94-33.942 1.416 1.416-33.943 33.94zm-1.414-127.28l1.414-1.414 33.942 33.94-1.416 1.416-33.94-33.943zM0 85c2.21 0 4 1.79 4 4s-1.79 4-4 4v-8zm180 0c-2.21 0-4 1.79-4 4s1.79 4 4 4v-8zM94 0c0 2.21-1.79 4-4 4s-4-1.79-4-4h8zm0 180c0-2.21-1.79-4-4-4s-4 1.79-4 4h8z' fill='%239C92AC' fill-opacity='0.4' fill-rule='evenodd'/%3E%3C/svg%3E"); /* SVG como patrón de fondo */
            background-repeat: repeat; /* Repite el patrón para llenar el fondo */
            margin: 0;
            padding: 20px;
        }

        nav {
            background-color: #4f2c54;
            padding: 10px 20px; /* Ajustado para mayor claridad */
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 8px 8px;
            z-index: 100; /* Para que esté por encima de la tabla */
        }

        nav ul {
            list-style: none;
            padding: 0;
            display: flex;
            margin: 0;
        }

        nav ul li {
            margin: 0 15px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: #f2f2f2;
        }

        table {     
            width: 100%;
            border-collapse: collapse;
            margin-top: 80px; /* Aumentado para dejar más espacio */
        }

        table, th, td {     
    border: 1px solid #6c3f8c; /* Color del borde */
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #7b5a9e; /* Color de fondo para los encabezados */
            color: white; /* Color del texto en los encabezados */
        }

        tbody tr:nth-child(even) {
            background-color: #e0d1e3; /* Color de fondo para filas pares */
        }

        tbody tr:nth-child(odd) {
            background-color: #dfdbf0; /* Color de fondo para filas impares */
        }

        tbody tr:hover {
            background-color: #c6a4d4; /* Color de fondo al pasar el mouse */
        }

        .actions {
            display: flex;
            justify-content: space-between;
        }

        .action-buttons a {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            color: white;
        }

        .edit {
            background-color: #28a745; /* Verde */
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .edit:hover {
            background-color: #218838;
        }

        .delete {
            background-color: #dc3545;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .delete:hover {
            background-color: #c82333;
        }

        .add-button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .add-button:hover {
            background-color: #0056b3;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal form input {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal form input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        .modal form input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <nav>
        <ul>
            <li><a href="alta.php">Joyas</a></li>
            <li><a href="usuarios.php">Usuarios</a></li>
            <li><a href="grafica.php">Estadisticas</a></li>
            <li><a href="reporte.php">Reporte Global</a></li>
            <li><a href="index.php">Cerrar Sesión</a></li> 
        </ul>
        <button class="add-button" id="addButton">Agregar Nueva Joya</button>
    </nav>

    <h1 style="margin-top: 80px;">Tabla de Joyas</h1> <!-- Ajuste de margen superior para evitar que el título quede cubierto -->

    <table>
        <thead>
            <tr>
                <th>Clave</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Talla</th>
                <th>Stock</th>
                <th>Precio</th>
                <th>Imagen</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['clave'] ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['tipo']) ?></td>
            <td><?= htmlspecialchars($row['talla']) ?></td>
            <td><?= htmlspecialchars($row['stock']) ?></td>
            <td>$<?= number_format($row['precio'], 2) ?></td>
            <td>
                <?php if ($row['imagen']): ?>
                    <img src="imagendos.php?clave=<?= $row['clave'] ?>" alt="Imagen de la joya" style="max-width: 50px; height: auto;">
                <?php else: ?>
                    <p>No hay imagen disponible</p>
                <?php endif; ?>
            </td>
            <td class="actions">
                <div class="action-buttons">
                    <button class="edit" onclick="openEditModal(<?= $row['clave'] ?>, '<?= htmlspecialchars($row['nombre']) ?>', '<?= htmlspecialchars($row['tipo']) ?>', '<?= htmlspecialchars($row['talla']) ?>', <?= $row['stock'] ?>, <?= $row['precio'] ?>)">Editar</button>
                    <a href="?eliminar=<?= $row['clave'] ?>" class="delete" onclick="return confirm('¿Estás seguro de que deseas eliminar esta joya?')">Eliminar</a> <!-- Añadida la confirmación -->
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Modal para agregar nueva joya -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAddModal">&times;</span>
            <h2>Agregar Nueva Joya</h2>
            <form action="imagen.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="clave" value="">
                <label for="nombre">Nombre:</label>
                <input type="text" name="nombre" required>
                <label for="tipo">Tipo:</label>
                <input type="text" name="tipo" required>
                <label for="talla">Talla:</label>
                <input type="text" name="talla" required>
                <label for="stock">Stock:</label>
                <input type="number" name="stock" required>
                <label for="precio">Precio:</label>
                <input type="number" step="0.01" name="precio" required>
                <label for="imagen">Imagen:</label>
                <input type="file" name="imagen">
                <input type="submit" value="Agregar Joya">
            </form>
        </div>
    </div>

    <script>
        // Mostrar modal de agregar
        const addButton = document.getElementById('addButton');
        const addModal = document.getElementById('addModal');
        const closeAddModal = document.getElementById('closeAddModal');

        addButton.onclick = function() {
            addModal.style.display = 'flex';
        };

        closeAddModal.onclick = function() {
            addModal.style.display = 'none';
        };

        window.onclick = function(event) {
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
        };

        // Modal para editar joya
        function openEditModal(clave, nombre, tipo, talla, stock, precio) {
            const modal = document.createElement('div');
            modal.classList.add('modal');
            modal.style.display = 'flex';

            const modalContent = document.createElement('div');
            modalContent.classList.add('modal-content');

            modalContent.innerHTML = `
                <span class="close">&times;</span>
                <h2>Editar Joya</h2>
                <form action="alta.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="clave" value="${clave}">
                    <label for="nombre">Nombre:</label>
                    <input type="text" name="nombre" value="${nombre}" required>
                    <label for="tipo">Tipo:</label>
                    <input type="text" name="tipo" value="${tipo}" required>
                    <label for="talla">Talla:</label>
                    <input type="text" name="talla" value="${talla}" required>
                    <label for="stock">Stock:</label>
                    <input type="number" name="stock" value="${stock}" required>
                    <label for="precio">Precio:</label>
                    <input type="number" step="0.01" name="precio" value="${precio}" required>
                    <label for="imagen">Imagen:</label>
                    <input type="file" name="imagen">
                    <input type="submit" name="actualizar" value="Actualizar Joya">
                </form>
            `;

            modal.appendChild(modalContent);
            document.body.appendChild(modal);

            const closeModal = modalContent.querySelector('.close');
            closeModal.onclick = function() {
                modal.style.display = 'none';
                document.body.removeChild(modal);
            };

            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.removeChild(modal);
                }
            };
        }
    </script>

</body>
</html>

<?php
$conn->close();
?>
