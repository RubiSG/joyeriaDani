<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anillo 3D con Diamante</title>
    <style>
        body {
            margin: 0;
            overflow: hidden;
            background-color: #f8f8f8;
            font-family: Arial, sans-serif;
        }

        canvas {
            display: block;
        }

        /* Estilo del cuadro de información */
        #infoBox {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #ffffff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
            font-size: 14px;
            line-height: 1.5;
            display: none;
            z-index: 10;
            transition: opacity 0.3s ease;
            white-space: pre-line;  /* Respetar saltos de línea */
        }

        #infoBox h3 {
            margin-top: 0;
            font-size: 16px;
            color: #333;
            font-weight: bold;
        }

        /* Estilo del botón de regresar */
        #backButton {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background-color: #8e44ad; /* Púrpura */
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s;
        }

        #backButton:hover {
            background-color: #9b59b6; /* Púrpura más claro */
        }

    </style>
</head>
<body>
<div id="infoBox">
    <h3>Información</h3>
    <p id="infoText">Contenido aquí</p>
</div>

<button id="backButton" onclick="window.location.href='ver.php'">Regresar</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<script>
    // Configuración de la escena
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer();
    renderer.setSize(window.innerWidth, window.innerHeight);
    document.body.appendChild(renderer.domElement);

    // Caja de información
    const infoBox = document.getElementById('infoBox');
    const infoText = document.getElementById('infoText');

    function showInfo(message) {
        infoText.textContent = message;
        infoBox.style.display = 'block';
    }

    function hideInfo() {
        infoBox.style.display = 'none';
    }

    // Luz ambiental
    const ambientLight = new THREE.AmbientLight(0xffffff, 10.0);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 14.0);
    directionalLight.position.set(5, 10, 5);
    scene.add(directionalLight);

    // Materiales
    const goldMaterial = new THREE.MeshStandardMaterial({ color: 0xFFD700, metalness: 1, roughness: 0.3 });
    const diamondMaterial = new THREE.MeshStandardMaterial({
        color: 0x87CEEB,
        metalness: 0.9,
        roughness: 0.05,
        transparent: true,
        opacity: 0.9
    });
    const supportMaterial = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 1, roughness: 0.3 });

    // Geometrías
    const torusGeometry = new THREE.TorusGeometry(1, 0.2, 30, 200);
    const diamondGeometry = new THREE.OctahedronGeometry(0.3);
    const supportGeometry = new THREE.CylinderGeometry(0.1, 0.1, 0.4, 32);

    // Meshes
    const torus = new THREE.Mesh(torusGeometry, goldMaterial);
    torus.rotation.x = Math.PI / 2;

    const diamond = new THREE.Mesh(diamondGeometry, diamondMaterial);
    diamond.position.set(1.4, 0.0, 0);

    const support = new THREE.Mesh(supportGeometry, supportMaterial);
    support.position.set(1.1, 0, 0);
    support.rotation.z = Math.PI / 2;

    // Añadir objetos a la escena
    const ringWithDiamond = new THREE.Object3D();
    ringWithDiamond.add(torus);
    ringWithDiamond.add(support);
    ringWithDiamond.add(diamond);
    scene.add(ringWithDiamond);

    camera.position.z = 5;

    // Raycaster para detección de clics
    const raycaster = new THREE.Raycaster();
    const mouse = new THREE.Vector2();

    // Control de arrastre
    let isDragging = false;
    let previousMousePosition = { x: 0, y: 0 };

    function onMouseMove(event) {
        if (isDragging) {
            const deltaMove = {
                x: event.offsetX - previousMousePosition.x,
                y: event.offsetY - previousMousePosition.y
            };

            const rotationSpeed = 0.005;
            ringWithDiamond.rotation.y += deltaMove.x * rotationSpeed;
            ringWithDiamond.rotation.x += deltaMove.y * rotationSpeed;

            previousMousePosition = {
                x: event.offsetX,
                y: event.offsetY
            };
        }
    }

    function onMouseDown(event) {
        isDragging = true;
        previousMousePosition = {
            x: event.offsetX,
            y: event.offsetY
        };
    }

    function onMouseUp(event) {
        isDragging = false;

        // Detección de clic al soltar
        mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
        mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
        raycaster.setFromCamera(mouse, camera);
        const intersects = raycaster.intersectObjects([diamond, torus, support]);

        if (intersects.length > 0) {
            const object = intersects[0].object;

            if (object === diamond) {
                showInfo("DIAMANTE:\n\nLos quilates dependen del modelo que usted elija.");
            } else if (object === torus) {
                showInfo("ARO:\n\nEl aro puede ser bañado o fabricado todo en oro o plata de los quilates que usted desee.");
            } else if (object === support) {
                showInfo("BASE DE DIAMANTE:\n\nPuede ser del mismo material del aro o otro distinto, dependerá del modelo.");
            }
        } else {
            hideInfo();
        }
    }

    renderer.domElement.addEventListener('mousedown', onMouseDown);
    renderer.domElement.addEventListener('mousemove', onMouseMove);
    renderer.domElement.addEventListener('mouseup', onMouseUp);

    // Animación
    function animate() {
        requestAnimationFrame(animate);
        renderer.render(scene, camera);
    }

    animate();
</script>
</body>
</html>
