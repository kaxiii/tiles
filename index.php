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

    <div style="position: fixed; top: 0; left: 0; right: 0; background: #333; color: white; padding: 10px; z-index: 2000;">
        <button onclick="document.getElementById('settings-panel').style.display = 'block'" style="padding: 5px 10px; margin-right: 10px;">⚙ Configurar y regenerar</button>
        <span style="font-weight: bold;">Mapa Hexagonal</span>
    </div>

    <div id="settings-panel" style="display:none; position: fixed; top: 50px; left: 50%; transform: translateX(-50%); background: white; padding: 20px; border: 1px solid #ccc; z-index: 2001; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.3);">
        <form id="regenerar-form">
            <label>Probabilidad Montaña (ej. 0.15):<br>
                <input type="number" name="pm" min="0" max="1" step="0.01" value="<?= $_GET['pm'] ?? 0.15 ?>" required>
            </label><br><br>
            <label>Probabilidad Lago (ej. 0.15):<br>
                <input type="number" name="pl" min="0" max="1" step="0.01" value="<?= $_GET['pl'] ?? 0.15 ?>" required>
            </label><br><br>
            <button type="submit">Regenerar Mapa</button>
            <button type="button" onclick="document.getElementById('settings-panel').style.display='none'">Cancelar</button>

            <label>Columnas (cols):<br>
                <input type="number" name="cols" min="3" max="100" value="<?= $_GET['cols'] ?? 10 ?>" required>
            </label><br><br>
            <label>Filas (rows):<br>
                <input type="number" name="rows" min="3" max="50" value="<?= $_GET['rows'] ?? 7 ?>" required>
            </label><br><br>
        
        </form>
    </div>

    <script>
        document.getElementById('regenerar-form').addEventListener('submit', e => {
            e.preventDefault();
            const form = e.target;
            const pm = form.pm.value;
            const pl = form.pl.value;
            const cols = form.cols.value;
            const rows = form.rows.value;

            window.location.href = `?pm=${pm}&pl=${pl}&cols=${cols}&rows=${rows}`;
        });
    </script>

    <div class="container" id="map">
        <?php

        $cols = isset($_GET['cols']) ? intval($_GET['cols']) : 10;
        $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 7;

        $hexWidth = 60;
        $hexHeight = 52;
        $hexHSpacing = 0.75 * $hexWidth;
        $hexVSpacing = $hexHeight;

        $probabilidad_montain = isset($_GET['pm']) ? floatval($_GET['pm']) : 0.20;
        $probabilidad_lake = isset($_GET['pl']) ? floatval($_GET['pl']) : 0.15;

        // Borrar nombres si se está regenerando
        if (isset($_GET['pm']) || isset($_GET['pl']) || isset($_GET['cols']) || isset($_GET['rows'])) {
            @unlink('hex_names.json');
        }

        // Función para decidir tipo de terreno con influencia vecinal
        function decidirTerreno($q, $r, $terrenos) {
            $vecinos = [
                [$q, $r - 1], [$q, $r + 1],
                [$q - 1, $r + ($q % 2 ? 0 : -1)],
                [$q - 1, $r + ($q % 2 ? 1 : 0)],
                [$q + 1, $r + ($q % 2 ? 0 : -1)],
                [$q + 1, $r + ($q % 2 ? 1 : 0)],
            ];

            $montanas = 0;
            $lagos = 0;

            foreach ($vecinos as [$nq, $nr]) {
                $nid = $nq * 100 + $nr;
                if (isset($terrenos[$nid])) {
                    if ($terrenos[$nid] === 'montaña') $montanas++;
                    if ($terrenos[$nid] === 'lago') $lagos++;
                }
            }

            $rand = mt_rand() / mt_getrandmax();

            global $probabilidad_montain, $probabilidad_lake;
            if ($rand < 0.07 + $montanas * $probabilidad_montain) return 'montaña';
            if ($rand < 0.14 + $lagos * $probabilidad_lake) return 'lago';

            return 'earth';
        }

        // Generador de nombres según tipo
        function generarNombreAleatorio($tipo) {
            $adjEarth = ["Verde", "Esmeralda", "Turquesa", "Ocre", "Antigua", "Mística", "Ventosa", "Primaveral", "Roja", "Escarlata", "Dorada", 
                "Zafiro", "Azul", "Fértil", "Sagrada", "Misteriosa", "Rubí", "Soleada", "Nublada", "Plateada",
                "Tórrida", "Cálida", "Fría", "Brumosa", "Serena", "Tempestuosa"];
            $susEarth = ["Colina", "Llanura", "Selva", "Cuenca", "Villa", "Aldea", "Pradera", "Laguna", "Cascada"];

            $adjMontana = ["Rocosa", "Nevada", "Eterna", "Escarpada", "Sagrada", "Mística", "Áspera", "Sombría", "Inhóspita", "Elevada"];
            $susMontana = ["Cumbre", "Peñón", "Monte", "Cordillera", "Macizo", "Pico", "Coloso", "Altiplano", "Risco", "Abismo"];

            $adjLago = ["Profundo", "Sereno", "Oscuro", "Misterioso", "Cristalino", "Azul", "Turquesa", "Sagrado", "Reflejado", "Silencioso"];
            $susLago = ["Balsa", "Laguna", "Estanque", "Charca", "Agua", "Espejo", "Pantano", "Delta", "Poza", "Arroyo"];

            return match ($tipo) {
                'montaña' => $susMontana[array_rand($susMontana)] . ' ' . $adjMontana[array_rand($adjMontana)],
                'lago'     => $susLago[array_rand($susLago)] . ' ' . $adjLago[array_rand($adjLago)],
                default    => $susEarth[array_rand($susEarth)] . ' ' . $adjEarth[array_rand($adjEarth)],
            };
        }

        $archivoNombres = 'hex_names.json';
        $nombresHex = file_exists($archivoNombres) ? json_decode(file_get_contents($archivoNombres), true) : [];
        $terrenos = [];

        // Generar tipos de terreno y nombres
        for ($q = 0; $q < $cols; $q++) {
            for ($r = 0; $r < $rows; $r++) {
                $id = $q * 100 + $r;
                $tipo = decidirTerreno($q, $r, $terrenos);
                $terrenos[$id] = $tipo;

                if (!isset($nombresHex[$id])) {
                    $nombresHex[$id] = generarNombreAleatorio($tipo);
                }
            }
        }

        file_put_contents($archivoNombres, json_encode($nombresHex, JSON_PRETTY_PRINT));

        // Pintar el mapa
        for ($q = 0; $q < $cols; $q++) {
            for ($r = 0; $r < $rows; $r++) {
                $left = $q * $hexHSpacing;
                $top = $r * $hexHeight + ($q % 2 ? $hexHeight / 2 : 0);
                $id = $q * 100 + $r;
                $nombre = htmlspecialchars($nombresHex[$id]);
                $tipo = $terrenos[$id];

                $color = match ($tipo) {
                    'montaña' => '#888',
                    'lago'     => '#3b9ae1',
                    default    => '#76b676'
                };

                echo "<div class='hex $tipo' data-q='$q' data-r='$r' data-id='{$id}' data-nombre='{$nombre}' data-tipo='{$tipo}' title='{$nombre}' style='left: {$left}px; top: {$top}px; background: {$color};'>
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
        <p><strong>Terreno:</strong> <span id="info-tipo"></span></p>
    </div>

    <script>
        const container = document.getElementById('map');
        const panel = document.getElementById('hex-info-panel');
        const infoCoords = document.getElementById('info-coords');
        const infoId = document.getElementById('info-id');
        const infoNombre = document.getElementById('info-nombre');
        const infoTipo = document.getElementById('info-tipo');
        const closeBtn = document.getElementById('close-panel-btn');

        let selectedHex = null;

        container.addEventListener('click', e => {
            const hex = e.target.closest('.hex');
            if (!hex) return;

            const q = hex.dataset.q;
            const r = hex.dataset.r;
            const id = hex.dataset.id;
            const nombre = hex.dataset.nombre;
            const tipo = hex.dataset.tipo;

            if (selectedHex && selectedHex.classList.contains('selected')) {
                selectedHex.classList.remove('selected');
            }

            if (panel.style.display === 'block' && hex === selectedHex) {
                panel.style.display = 'none';
                selectedHex = null;
                return;
            }

            selectedHex = hex;
            selectedHex.classList.add('selected');

            infoCoords.textContent = `(${q}, ${r})`;
            infoId.textContent = id;
            infoNombre.textContent = nombre;
            infoTipo.textContent = tipo;

            panel.style.display = 'block';
        });

        closeBtn.addEventListener('click', () => {
            panel.style.display = 'none';
            if (selectedHex) selectedHex.classList.remove('selected');
            selectedHex = null;
        });

        // Zoom y arrastre
        let isDragging = false, startX, startY;
        let currentX = 0, currentY = 0, targetX = 0, targetY = 0;
        let scale = 1, minScale = 0.5, maxScale = 3, smoothFactor = 0.1;

        function applyTransform() {
            container.style.transform = `translate3d(${currentX}px, ${currentY}px, 0) scale(${scale})`;
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
