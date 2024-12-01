
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

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$userid = $_SESSION['usuario_id'];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (strpos($user['usuario'], 'admin') === false && strpos($user['correo'], 'admin') === false) {
    header("Location: alta.php");
    exit();
}

$inicio = isset($_POST['inicio']) ? $_POST['inicio'] : null;
$fin = isset($_POST['fin']) ? $_POST['fin'] : null;

$sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(total) AS total_compras 
        FROM compras ";

if ($inicio && $fin) {
    $sql .= "WHERE DATE_FORMAT(fecha, '%Y-%m') BETWEEN ? AND ? ";
}

$sql .= "GROUP BY mes 
         ORDER BY mes DESC";

$stmt = $conn->prepare($sql);

if ($inicio && $fin) {
    $stmt->bind_param("ss", $inicio, $fin);
}

$stmt->execute();
$compras_result = $stmt->get_result();

setlocale(LC_TIME, 'es_ES.UTF-8');
$total_acumulado = 0; // Variable para acumular el total
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Compras</title>
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
    padding: 20px 20px; /* Aumentado para mayor claridad */
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


    .grafica-container {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 15px;
    margin-bottom: 20px;
    margin-top: 80px; /* Agregado para separación con la barra de navegación */
    width: 90%; /* Ajustar el ancho según sea necesario */
    max-width: 600px; /* Limitar el ancho máximo */
    margin-left: auto; /* Centrar horizontalmente */
    margin-right: auto; /* Centrar horizontalmente */
}

h1 {
    text-align: center;
    color: #333;
}

canvas {
    max-width: 100%; /* Asegura que el canvas no exceda el ancho del contenedor */
    height: 300px; /* Ajustar la altura del canvas */
}


        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #6c3f8c;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #7b5a9e;
            color: white;
        }
        tbody tr:nth-child(even) {
            background-color: #e0d1e3;
        }
        tbody tr:nth-child(odd) {
            background-color: #dfdbf0;
        }


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
            padding: 20px 20px; /* Aumentado para mayor claridad */
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

        .grafica-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 20px;
            margin-top: 80px; /* Agregado para separación con la barra de navegación */
            width: 90%; /* Ajustar el ancho según sea necesario */
            max-width: 600px; /* Limitar el ancho máximo */
            margin-left: auto; /* Centrar horizontalmente */
            margin-right: auto; /* Centrar horizontalmente */
        }

        h1 {
            text-align: center;
            color: #333;
            margin-top: 80px; /* Ajusta este valor para mayor o menor espacio */
        }

        canvas {
            max-width: 100%; /* Asegura que el canvas no exceda el ancho del contenedor */
            height: 300px; /* Ajustar altura según necesidad */
        }

        .admin-panel {
            text-align: center;
            margin-top: 40px;
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
</nav>

<h1>Reporte de Compras Mensuales</h1>

<form method="POST" action="">
    <label for="inicio">Mes de inicio:</label>
    <select name="inicio" id="inicio">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <?php $month = date('Y-m', mktime(0, 0, 0, $m, 1)); ?>
            <option value="<?= $month ?>"><?= date('F Y', mktime(0, 0, 0, $m, 1)) ?></option>
        <?php endfor; ?>
    </select>

    <label for="fin">Mes de fin:</label>
    <select name="fin" id="fin">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <?php $month = date('Y-m', mktime(0, 0, 0, $m, 1)); ?>
            <option value="<?= $month ?>"><?= date('F Y', mktime(0, 0, 0, $m, 1)) ?></option>
        <?php endfor; ?>
    </select>

    <button type="submit">Filtrar</button>
</form>

<table>
    <thead>
        <tr>
            <th>Mes</th>
            <th>Total Compras</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($compras_result->num_rows > 0): ?>
            <?php while ($row = $compras_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['mes']) ?></td>
                    <td>$<?= number_format($row['total_compras'], 2) ?></td>
                </tr>
                <?php $total_acumulado += $row['total_compras']; // Suma al total acumulado ?>
            <?php endwhile; ?>
            <tr>
                <td><strong>Total Acumulado:</strong></td>
                <td><strong>$<?= number_format($total_acumulado, 2) ?></strong></td>
            </tr>
        <?php else: ?>
            <tr>
                <td colspan="2">No hay compras registradas en el rango seleccionado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>

<?php
$conn->close();
?>
