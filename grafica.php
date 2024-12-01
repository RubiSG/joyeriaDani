<?php
// Conexión a la base de datos
$host = 'localhost';
$db = 'prueba';
$user = 'daniels';
$password = 'daniels';

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}



// Evitar que el navegador almacene en caché la página
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");



// Definir el número de semanas que quieres analizar
$numSemanas = 4;

// Función para obtener el conteo semanal de usuarios
function obtenerConteoUsuarios($conn, $filtro, $numSemanas) {
    $datosSemanas = [];
    for ($i = $numSemanas - 1; $i >= 0; $i--) {
        $fechaInicio = date('Y-m-d', strtotime("-$i week"));
        $fechaFin = date('Y-m-d', strtotime("-" . ($i - 1) . " week"));
        
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE fecha_creacion BETWEEN '$fechaInicio' AND '$fechaFin' $filtro";
        $resultado = $conn->query($sql);
        $fila = $resultado->fetch_assoc();
        $datosSemanas[] = (int)$fila['total'];
    }
    return $datosSemanas;
}

// Obtener datos para cada tipo de usuario
$filtroAdmin = "AND (nombre LIKE '%admin%' OR usuario LIKE '%admin%' OR correo LIKE '%admin%')";
$filtroComun = "AND (nombre NOT LIKE '%admin%' AND usuario NOT LIKE '%admin%' AND correo NOT LIKE '%admin%')";

$datosAdmin = obtenerConteoUsuarios($conn, $filtroAdmin, $numSemanas);
$datosComun = obtenerConteoUsuarios($conn, $filtroComun, $numSemanas);
$datosTotal = obtenerConteoUsuarios($conn, "", $numSemanas);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficas de Usuarios</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles.css"> <!-- Enlazar el archivo CSS -->
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


<div class="grafica-container">
    <h2>Gráfica de Usuarios Administradores</h2>
    <canvas id="graficaAdmin"></canvas>
</div>

<div class="grafica-container">
    <h2>Gráfica de Usuarios Comunes</h2>
    <canvas id="graficaComun"></canvas>
</div>

<div class="grafica-container">
    <h2>Gráfica de Todos los Usuarios</h2>
    <canvas id="graficaTotal"></canvas>
</div>

<script>
// Etiquetas de semanas (últimas N semanas)
const etiquetasSemanas = [
    <?php for ($i = $numSemanas - 1; $i >= 0; $i--): ?>
        "Semana <?= $i + 1 ?>",
    <?php endfor; ?>
];

// Datos de PHP a JavaScript
const datosAdmin = <?= json_encode($datosAdmin) ?>;
const datosComun = <?= json_encode($datosComun) ?>;
const datosTotal = <?= json_encode($datosTotal) ?>;

// Configuración de gráficos
function crearGrafico(idCanvas, datos, label) {
    new Chart(document.getElementById(idCanvas), {
        type: 'line',
        data: {
            labels: etiquetasSemanas,
            datasets: [{
                label: label,
                data: datos,
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Crear los gráficos
crearGrafico("graficaAdmin", datosAdmin, "Usuarios Administradores");
crearGrafico("graficaComun", datosComun, "Usuarios Comunes");
crearGrafico("graficaTotal", datosTotal, "Todos los Usuarios");

</script>
</body>
</html>
