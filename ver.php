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

if (strpos($user['usuario'], 'admin') !== false || strpos($user['correo'], 'admin') !== false) {
    header("Location: alta.php");
    exit();
}

$sql = "SELECT * FROM joyas";
$joyas_result = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $nombre = $_POST['nombre'];
        $usuario = $_POST['usuario'];
        $contrasena = $_POST['contrasena'];
        
        $updateQuery = "UPDATE usuarios SET nombre = ?, usuario = ?";
        
        if (!empty($contrasena)) {
            $updateQuery .= ", contrasena = ?";
        }

        $updateQuery .= " WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);

        if (!empty($contrasena)) {
            $stmt->bind_param("sssi", $nombre, $usuario, $contrasena, $userid);
        } else {
            $stmt->bind_param("ssi", $nombre, $usuario, $userid);
        }

        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['delete'])) {
        $deleteQuery = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        session_destroy();
        header("Location: index.php");
        exit();
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit();
    }
}

//registrar compras pdf
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_compra'])) {
    $cart = json_decode($_POST['cart'], true);
    $usuario_id = $_SESSION['usuario_id'];
    $total_compra = 0; // Variable para almacenar el total de la compra

    foreach ($cart as $clave_producto => $item) {
        $cantidad = $item['cantidad'];
        $precio = $item['precio'];
        $subtotal = $cantidad * $precio;
        $total_compra += $subtotal; // Sumar el subtotal al total

        // Registrar la compra en la base de datos
        $stmt = $conn->prepare("INSERT INTO compras (usuario_id, fecha, total) VALUES (?, NOW(), ?)");
        $stmt->bind_param("id", $usuario_id, $subtotal);
        $stmt->execute();
        $compra_id = $stmt->insert_id;

        // Actualizar el stock usando el identificador correcto
        $stmt = $conn->prepare("UPDATE joyas SET stock = stock - ? WHERE clave = ?");
        $stmt->bind_param("is", $cantidad, $clave_producto);
        $stmt->execute();
    }

    // Enviar el total de la compra
    echo "Compra registrada exitosamente. Total: $" . number_format($total_compra, 2);
    exit();
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Joyas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #DFDBE5; /* Color de fondo suave */
            background-image: url("data:image/svg+xml,%3Csvg width='180' height='180' viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M81.28 88H68.413l19.298 19.298L81.28 88zm2.107 0h13.226L90 107.838 83.387 88zm15.334 0h12.866l-19.298 19.298L98.72 88zm-32.927-2.207L73.586 78h32.827l.5.5 7.294 7.293L115.414 87l-24.707 24.707-.707.707L64.586 87l1.207-1.207zm2.62.207L74 80.414 79.586 86H68.414zm16 0L90 80.414 95.586 86H84.414zm16 0L106 80.414 111.586 86h-11.172zm-8-6h11.173L98 85.586 92.414 80zM82 85.586L87.586 80H76.414L82 85.586zM17.414 0L.707 16.707 0 17.414V0h17.414zM4.28 0L0 12.838V0h4.28zm10.306 0L2.288 12.298 6.388 0h8.198zM180 17.414L162.586 0H180v17.414zM165.414 0l12.298 12.298L173.612 0h-8.198zM180 12.838L175.72 0H180v12.838zM0 163h16.413l.5.5 7.294 7.293L25.414 172l-8 8H0v-17zm0 10h6.613l-2.334 7H0v-7zm14.586 7l7-7H8.72l-2.333 7h8.2zM0 165.414L5.586 171H0v-5.586zM10.414 171L16 165.414 21.586 171H10.414zm-8-6h11.172L8 170.586 2.414 165zM180 163h-16.413l-7.794 7.793-1.207 1.207 8 8H180v-17zm-14.586 17l-7-7h12.865l2.333 7h-8.2zM180 173h-6.613l2.334 7H180v-7zm-21.586-2l5.586-5.586 5.586 5.586h-11.172zM180 165.414L174.414 171H180v-5.586zm-8 5.172l5.586-5.586h-11.172l5.586 5.586zM152.933 25.653l1.414 1.414-33.94 33.942-1.416-1.416 33.943-33.94zm1.414 127.28l-1.414 1.414-33.942-33.94 1.416-1.416 33.94 33.943zm-127.28 1.414l-1.414-1.414 33.94-33.942 1.416 1.416-33.943 33.94zm-1.414-127.28l1.414-1.414 33.942 33.94-1.416 1.416-33.94-33.943zM0 85c2.21 0 4 1.79 4 4s-1.79 4-4 4v-8zm180 0c-2.21 0-4 1.79-4 4s1.79 4 4 4v-8zM94 0c0 2.21-1.79 4-4 4s-4-1.79-4-4h8zm0 180c0-2.21-1.79-4-4-4s-4 1.79-4 4h8z' fill='%239C92AC' fill-opacity='0.4' fill-rule='evenodd'/%3E%3C/svg%3E"); /* SVG como patrón de fondo */
            background-repeat: repeat; /* Repite el patrón para llenar el fondo */
            margin: 0;
            padding: 20px;
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

        img {
            max-width: 50px;
            height: auto;
        }
        .account-btn, .cart-btn {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .account-btn:hover, .cart-btn:hover {
            background-color: #0056b3;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover, .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .cart-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
        }
        .cart-item img {
            margin-right: 10px;
            width: 50px;
            height: auto;
        }
        .cart-item .remove-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px;
            cursor: pointer;
            border-radius: 4px;
        }
        .cart-item .remove-btn:hover {
            background-color: #c82333;
        }
        .cart-total, .cart-count {
            font-weight: bold;
        }
        .buy-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: none;
            margin: 20px 0;
            width: 100%;
        }
        .buy-btn:hover {
            background-color: #218838;
        }

        .btn-redirigir-modelo {
            padding: 10px 20px;
            top: 20px;
            right: 20px;
            background-color: #7a2c9068;
            color: white;
            border: none;
            border-radius: 5px;
            position: absolute;
            bottom: 20px; /* Distancia desde la parte inferior */
            left: 50%;
            transform: translateX(-50%);
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: auto; /* Evita que ocupe toda la pantalla */
            max-width: 200px; /* Define un ancho máximo */
            max-height: 40px;
            text-align: center; /* Asegura el texto centrado */
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); /* Sombra ligera para estilo */
        }

        .btn-redirigir-modelo:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <h1>Joyas Disponibles</h1>

    <form method="post" style="display:inline;">
        <button class="logout-btn" type="submit" name="logout">Cerrar Sesión</button>
    
    </form>

    <button class="account-btn" id="accountBtn">Cuenta</button>
    <button class="cart-btn" onclick="verCarrito()">Ver Carrito</button>

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
                <th>Cantidad</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $joyas_result->fetch_assoc()): ?>
                <tr data-clave="<?= htmlspecialchars($row['clave']) ?>" data-nombre="<?= htmlspecialchars($row['nombre']) ?>" data-precio="<?= htmlspecialchars($row['precio']) ?>" data-imagen="imagen.php?clave=<?= htmlspecialchars($row['clave']) ?>">
                    <td><?= htmlspecialchars($row['clave']) ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['tipo']) ?></td>
                    <td><?= htmlspecialchars($row['talla']) ?></td>
                    <td><?= htmlspecialchars($row['stock']) ?></td>
                    <td>$<?= number_format($row['precio'], 2) ?></td>
                    <td>
                        <img src="imagen.php?clave=<?= htmlspecialchars($row['clave']) ?>" alt="Imagen de la joya">
                    </td>
                    <td>
                        <span class="cantidad">0</span>
                    </td>
                    <td>
                        <button onclick="modificarCantidad(this, 'sumar')">+</button>
                        <button onclick="modificarCantidad(this, 'restar')">-</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <button class="btn-redirigir-modelo" onclick="window.location.href='modelo.php'">Ir al Modelo 3D</button>

  <!-- Modal del Carrito -->
<div id="cartModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeCartModal">&times;</span>
        <h2>Carrito de Compras</h2>
        <div id="cartItems"></div>
        <div class="cart-total">Total: $<span id="cartTotal">0.00</span></div>
        
        <!-- Botón para Ticket PDF -->
        <button class="cart-btn" id="pdfBtn" onclick="registrarCompra(); generarPDF()">Ticket PDF</button>
        <button class="cart-btn" id="emailBtn" onclick="enviarCorreo()">Enviar por Correo</button>
       
    </div>
</div>

<script>
    // Función para generar el PDF en una nueva ventana para imprimir
    function generarPDF() {
    // Crear contenido de la factura en formato HTML
    let contenidoPDF = `
        <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        text-align: center;
                    }
                    h2 {
                        color: #333;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    table, th, td {
                        border: 1px solid #ddd;
                    }
                    th, td {
                        padding: 8px;
                        text-align: center;
                    }
                    th {
                        background-color: #f2f2f2;
                    }
                    .total {
                        font-weight: bold;
                        font-size: 1.2em;
                    }
                    .product-image {
                        max-width: 50px;
                        height: auto;
                    }
                </style>
            </head>
            <body>
                <h1>Tienda de Joyeria</h1>
                <h2>Ticket de Compra</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th>Imagen</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    let total = 0;
    for (const clave in cart) {
        const item = cart[clave];
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        contenidoPDF += `
            <tr>
                <td>${item.nombre}</td>
                <td>${item.cantidad}</td>
                <td>$${item.precio.toFixed(2)}</td>
                <td>$${subtotal.toFixed(2)}</td>
                <td><img src="imagen.php?clave=${clave}" alt="Imagen de la joya" class="product-image"></td>
            </tr>
        `;
    }

    contenidoPDF += `
                    </tbody>
                </table>
                <div class="total">Total: $${total.toFixed(2)}</div>
            </body>
        </html>
    `;

    // Abrir nueva ventana y escribir el contenido
    const ventana = window.open('', '_blank');
    ventana.document.open();
    ventana.document.write(contenidoPDF);
    ventana.document.close();

    // Iniciar impresión automática
    ventana.print();
}



    function enviarCorreo() {
        const email = prompt("Ingresa tu correo electrónico para recibir el ticket:");
    if (email) {
        const formData = new FormData();
        formData.append('cart', JSON.stringify(cart));  // Asegúrate de que 'cart' esté bien definido.
        formData.append('email', email);

        fetch('enviar_ticket.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(response => alert(response))
        .catch(error => alert("Error al enviar el correo."));
    }
}

document.getElementById('closeCartModal').onclick = function() {
    document.getElementById('cartModal').style.display = 'none';
}

document.onclick = function(event) {
    if (event.target == document.getElementById('cartModal')) {
        document.getElementById('cartModal').style.display = 'none';
    }
}

</script>


    <!-- Modal de cuenta -->
    <div id="accountModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAccountModal">&times;</span>
            <h2>Mi Cuenta</h2>
            <form method="POST" class="account-form">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required><br><br>

                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($user['usuario']) ?>" required><br><br>

                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" value=""><br><br>

                <button type="submit" name="update">Actualizar</button>
                <button type="submit" name="delete" style="background-color: red; color: white;">Eliminar Cuenta</button>
            </form>
        </div>
    </div>

    <script>
        let cart = {};

        function modificarCantidad(btn, action) {
            const row = btn.closest('tr');
            const clave = row.getAttribute('data-clave');
            const nombre = row.getAttribute('data-nombre');
            const precio = parseFloat(row.getAttribute('data-precio'));
            const cantidadElem = row.querySelector('.cantidad');
            let cantidad = parseInt(cantidadElem.innerText);
            const stockElem = row.querySelector('td:nth-child(5)');
            let stock = parseInt(stockElem.innerText);

            if (action === 'sumar' && stock > 0) {
                cantidad++;
                stock--;
            } else if (action === 'restar' && cantidad > 0) {
                cantidad--;
                stock++;
            }

            cantidadElem.innerText = cantidad;
            stockElem.innerText = stock;

            if (cantidad > 0) {
                cart[clave] = {
                    nombre: nombre,
                    precio: precio,
                    cantidad: cantidad
                };
            } else {
                delete cart[clave];
            }

            actualizarCarrito();
        }

        function actualizarCarrito() {
            const cartItems = document.getElementById('cartItems');
            cartItems.innerHTML = '';

            let total = 0;
            let count = 0;

            for (const clave in cart) {
                const item = cart[clave];
                const subtotal = item.precio * item.cantidad;
                total += subtotal;
                count += item.cantidad;

                const cartItem = document.createElement('div');
                cartItem.classList.add('cart-item');
                cartItem.innerHTML = `
                    <img src="imagen.php?clave=${clave}" alt="Imagen de la joya">
                    <span>${item.nombre} (x${item.cantidad})</span>
                    <span>$${subtotal.toFixed(2)}</span>
                    <button class="remove-btn" onclick="eliminarProducto('${clave}')">Eliminar</button>
                `;
                cartItems.appendChild(cartItem);
            }

            document.getElementById('cartTotal').innerText = total.toFixed(2);
            document.getElementById('buyBtn').style.display = count > 0 ? 'block' : 'none';
        }

        function verCarrito() {
            document.getElementById('cartModal').style.display = 'block';
        }

        function eliminarProducto(clave) {
            const row = document.querySelector(`tr[data-clave="${clave}"]`);
            const cantidadElem = row.querySelector('.cantidad');
            let cantidadActual = parseInt(cantidadElem.innerText);
            const stockElem = row.querySelector('td:nth-child(5)');
            let stock = parseInt(stockElem.innerText);

            if (cantidadActual === 0) {
                alert('No tienes este producto en tu carrito.');
                return;
            }

            let cantidadAEliminar = parseInt(prompt(`¿Cuántos productos deseas eliminar? (Máximo: ${cantidadActual})`, "1"));

            if (isNaN(cantidadAEliminar) || cantidadAEliminar < 1 || cantidadAEliminar > cantidadActual) {
                alert('Por favor, ingresa una cantidad válida.');
                return;
            }

            let confirmacion = confirm(`¿Estás seguro de eliminar ${cantidadAEliminar} productos de tu carrito?`);
            if (!confirmacion) {
                return;
            }

            cantidadActual -= cantidadAEliminar;
            stock += cantidadAEliminar;

            cantidadElem.innerText = cantidadActual;
            stockElem.innerText = stock;

            if (cantidadActual === 0) {
                delete cart[clave];
            } else {
                cart[clave].cantidad = cantidadActual;
            }

            actualizarCarrito();
        }

        function comprar() {
            alert('Gracias por tu compra!');
            cart = {};
            actualizarCarrito();
        }

        const cartModal = document.getElementById('cartModal');
        const closeCartModal = document.getElementById('closeCartModal');
        closeCartModal.onclick = function() {
            cartModal.style.display = 'none';
        }

        const accountBtn = document.getElementById('accountBtn');
        const accountModal = document.getElementById('accountModal');
        const closeAccountModal = document.getElementById('closeAccountModal');
        accountBtn.onclick = function() {
            accountModal.style.display = 'block';
        }
        closeAccountModal.onclick = function() {
            accountModal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == cartModal) {
                cartModal.style.display = 'none';
            } else if (event.target == accountModal) {
                accountModal.style.display = 'none';
            }
        }


        function registrarCompra() {
    const formData = new FormData();
    formData.append('registrar_compra', true);
    formData.append('cart', JSON.stringify(cart));

    fetch('ver.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(response => alert(response))
    .catch(error => alert("Error al registrar la compra."));
}

    </script>

</body>
</html>



<?php
$conn->close();
?>