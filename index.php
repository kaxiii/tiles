<?php
// hex_map.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mapa Hexagonal</title>
    <style>
        body {
            margin: 0;
            overflow: hidden;
            touch-action: none;
            font-family: Arial, sans-serif;
            background: #f0f0f0;
        }

        .container {
            position: fixed;
            cursor: grab;
            user-select: none;
            transition: transform 0.25s ease-out;
            transform-origin: 0 0;
        }

        .container.grabbing {
            cursor: grabbing;
        }

        .hex {
            width: 60px;
            height: 52px;
            background: #76b676;
            position: absolute;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 10px;
            text-shadow: 1px 1px 1px black;
            transition: all 0.3s;
            clip-path: polygon(
                25% 0%,
                75% 0%,
                100% 50%,
                75% 100%,
                25% 100%,
                0% 50%
            );
        }

        .hex span {
            line-height: 1;
        }

        .earth {
            background: #76b676;
        }

        .hex.selected {
            outline: 3px solid #ff4444;
            z-index: 10;
        }

        #hex-info-panel {
            position: fixed;
            top: 20px;
            left: 10px;
            background: white;
            border: 1px solid #ccc;
            padding: 20px 15px 15px 15px;
            z-index: 1001;
            border-radius: 5px;
            width: 200px;
            height: calc(100vh - 150px);
            display: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        #hex-info-panel p {
            margin: 10px 0;
        }

        #close-panel-btn {
            position: absolute;
            top: 5px;
            right: 8px;
            background: transparent;
            color: #e74c3c;
            font-weight: bold;
            font-size: 18px;
            border: none;
            cursor: pointer;
        }

        #close-panel-btn:hover {
            color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container" id="map">
        <?php
        $cols = 10;
        $rows = 7;
        $hexWidth = 60;
        $hexHeight = 52;
        $hexHSpacing = 0.75 * $hexWidth;
        $hexVSpacing = $hexHeight;

        function generarNombreAleatorio() {
            $adjetivos = ["Verde", "Esmeralda", "Turquesa", "Ocre", "Antigua", "Mística", "Ventosa", "Primaveral", "Roja", "Escarlata", "Dorada", 
                "Zafiro", "Azul", "Fértil", "Sagrada", "Misteriosa", "Rubí", "Soleada", "Nublada", "Plateada",
                "Tórrida", "Cálida", "Fría", "Brumosa", "Serena", "Tempestuosa",];
            $sustantivos = ["Colina", "Llanura", "Selva", "Cuenca", "Villa", "Aldea", "Pradera", "Laguna", "Cascada"];
            return $sustantivos[array_rand($sustantivos)] . ' ' . $adjetivos[array_rand($adjetivos)];
        }

        $archivoNombres = 'hex_names.json';
        $nombresHex = file_exists($archivoNombres) ? json_decode(file_get_contents($archivoNombres), true) : [];

        for ($q = 0; $q < $cols; $q++) {
            for ($r = 0; $r < $rows; $r++) {
                $id = $q * 100 + $r;
                if (!isset($nombresHex[$id])) {
                    $nombresHex[$id] = generarNombreAleatorio();
                }
            }
        }

        file_put_contents($archivoNombres, json_encode($nombresHex, JSON_PRETTY_PRINT));

        for ($q = 0; $q < $cols; $q++) {
            for ($r = 0; $r < $rows; $r++) {
                $left = $q * $hexHSpacing;
                $top = $r * $hexHeight + ($q % 2 ? $hexHeight / 2 : 0);
                $id = $q * 100 + $r;
                $nombre = htmlspecialchars($nombresHex[$id]);

                echo "<div class='hex earth' data-q='$q' data-r='$r' data-id='{$id}' data-nombre='{$nombre}' title='{$nombre}' style='left: {$left}px; top: {$top}px;'>
                        <span>{$q},{$r}</span>
                        
                      </div>";
            }
        }
        ?>
    </div>

    <div id="hex-info-panel">
        <button id="close-panel-btn">×</button>
        <p><strong>Coordenadas:</strong> <span id="info-coords"></span></p>
        <p><strong>ID:</strong> <span id="info-id"></span></p>
        <p><strong>Nombre:</strong> <span id="info-nombre"></span></p>
    </div>

    <script>
        const container = document.getElementById('map');
        const panel = document.getElementById('hex-info-panel');
        const infoCoords = document.getElementById('info-coords');
        const infoId = document.getElementById('info-id');
        const infoNombre = document.getElementById('info-nombre');
        const closeBtn = document.getElementById('close-panel-btn');

        let selectedQ = null;
        let selectedR = null;
        let selectedHex = null;

        container.addEventListener('click', e => {
            const hex = e.target.closest('.hex');
            if (!hex) return;

            const q = hex.dataset.q;
            const r = hex.dataset.r;
            const id = hex.dataset.id;
            const nombre = hex.dataset.nombre;

            if (selectedHex && selectedHex.classList.contains('selected')) {
                selectedHex.classList.remove('selected');
            }

            if (panel.style.display === 'block' && q === selectedQ && r === selectedR) {
                panel.style.display = 'none';
                selectedQ = null;
                selectedR = null;
                selectedHex = null;
                return;
            }

            selectedHex = hex;
            selectedHex.classList.add('selected');
            selectedQ = q;
            selectedR = r;

            infoCoords.textContent = `(${q}, ${r})`;
            infoId.textContent = id;
            infoNombre.textContent = nombre;

            panel.style.display = 'block';
        });

        closeBtn.addEventListener('click', () => {
            panel.style.display = 'none';
            if (selectedHex) selectedHex.classList.remove('selected');
            selectedHex = null;
            selectedQ = null;
            selectedR = null;
        });

        // Zoom y arrastre
        let isDragging = false;
        let startX, startY;
        let currentX = 0, currentY = 0;
        let targetX = 0, targetY = 0;
        let scale = 1;
        const minScale = 0.5;
        const maxScale = 3;
        const smoothFactor = 0.1;

        function applyTransform() {
            container.style.transform = `
                translate3d(${currentX}px, ${currentY}px, 0)
                scale(${scale})
            `;
        }

        function animate() {
            const dx = (targetX - currentX) * smoothFactor;
            const dy = (targetY - currentY) * smoothFactor;

            if (Math.abs(dx) > 0.1 || Math.abs(dy) > 0.1) {
                currentX += dx;
                currentY += dy;
                applyTransform();
                requestAnimationFrame(animate);
            }
        }

        container.addEventListener('wheel', e => {
            e.preventDefault();
            const zoomIntensity = 0.1;
            const oldScale = scale;

            scale *= e.deltaY > 0 ? 1 - zoomIntensity : 1 + zoomIntensity;
            scale = Math.min(Math.max(scale, minScale), maxScale);

            const rect = container.getBoundingClientRect();
            const mouseX = (e.clientX - rect.left - currentX) / oldScale;
            const mouseY = (e.clientY - rect.top - currentY) / oldScale;

            targetX -= (mouseX * (scale - oldScale));
            targetY -= (mouseY * (scale - oldScale));

            animate();
        });

        container.addEventListener('mousedown', e => {
            isDragging = true;
            startX = e.clientX - targetX;
            startY = e.clientY - targetY;
            container.classList.add('grabbing');
        });

        document.addEventListener('mousemove', e => {
            if (!isDragging) return;
            targetX = e.clientX - startX;
            targetY = e.clientY - startY;
            animate();
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            container.classList.remove('grabbing');
        });

        container.addEventListener('dblclick', () => {
            targetX = 0;
            targetY = 0;
            scale = 1;
            animate();
        });

        animate();
    </script>
</body>
</html>
