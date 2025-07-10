<?php
// hex_map.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mapa Hexagonal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- BARRA SUPERIOR -->
    <div style="position: fixed; top: 0; left: 0; right: 0; background: #333; color: white; padding: 10px; z-index: 2000; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <button onclick="guardarEstadoMapa()" style="padding: 5px 10px; margin-right: 10px;">💾 Guardar partida</button>
            <button onclick="document.getElementById('settings-panel').style.display = 'block'" style="padding: 5px 10px; margin-right: 10px;">⚙ Nueva Partida</button>
            <span style="font-weight: bold;">Mapa Hexagonal</span>
        </div>
        <button onclick="siguiente_turno()" title="Siguiente turno" style="padding: 5px 10px; font-size: 18px; background: #2ecc71; color: white; border: none; border-radius: 4px; cursor: pointer;">
            ➤
        </button>
    </div>

    <?php include 'components/map_generator_panel.php'; ?>

    <?php include 'components/layers.php'; ?>

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

    <canvas id="hexCanvas" width="1000" height="700">
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

        // Generador de nombres aleatorios para la casilla según tipo
        function generarNombreAleatorio($tipo) {
            static $nombres = null;
        
            if ($nombres === null) {
                $json = file_get_contents('nombres_terrenos.json');
                $nombres = json_decode($json, true);
            }
        
            $tipo = $nombres[$tipo] ?? $nombres['earth'];

        
            $adj = $tipo['adjetivos'];
            $sus = $tipo['sustantivos'];
        
            return $sus[array_rand($sus)] . ' ' . $adj[array_rand($adj)];
        }

        function calcularFish($q, $r, $terrenos) {
            $vecinos = [
                [$q, $r - 1], [$q, $r + 1],
                [$q - 1, $r],
                [$q + 1, $r],
                [$q - 1, $r + ($q % 2 === 0 ? -1 : 0)],
                [$q + 1, $r + ($q % 2 === 0 ? -1 : 0)]
            ];

            $aguaAlrededor = 0;
            foreach ($vecinos as [$vq, $vr]) {
                $vid = $vq * 100 + $vr;
                if (isset($terrenos[$vid]) && $terrenos[$vid] === 'lago') {
                    $aguaAlrededor++;
                }
            }
            return rand(1, 3) + $aguaAlrededor * rand(1, 2);
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

                // $esEsquina = (
                //     ($q === 0 && $r === 0) || // esquina superior izquierda
                //     ($q === $cols - 1 && $r === 0) || // esquina superior derecha
                //     ($q === 0 && $r === $rows - 1) || // esquina inferior izquierda
                //     ($q === $cols - 1 && $r === $rows - 1) // esquina inferior derecha
                // );
        
                // if ($esEsquina) continue; // omitir esta celda

                $esEsquinaExtendida = (
                    // esquina superior izquierda
                    ($q === 0 && $r === 0) || 
                    ($q === 1 && $r === 0) ||
                    ($q === 0 && $r === 1) ||
                
                    // esquina superior derecha
                    ($q === $cols - 1 && $r === 0) ||
                    ($q === $cols - 2 && $r === 0) ||
                    ($q === $cols - 1 && $r === 1) ||
                
                    // esquina inferior izquierda
                    ($q === 0 && $r === $rows - 1) ||
                    ($q === 0 && $r === $rows - 2) ||
                    ($q === 1 && $r === $rows - 1) ||
                
                    // esquina inferior derecha
                    ($q === $cols - 1 && $r === $rows - 1) ||
                    ($q === $cols - 2 && $r === $rows - 1) ||
                    ($q === $cols - 1 && $r === $rows - 2)
                );
                
                if ($esEsquinaExtendida) continue;

                $left = $q * $hexHSpacing;
                $top = $r * $hexHeight + ($q % 2 ? $hexHeight / 2 : 0);
                $id = $q * 100 + $r;
                $nombre = htmlspecialchars($nombresHex[$id]);
                $tipo = $terrenos[$id];

                // Asignar fertilidad si es tierra
                $fertilidad = ($tipo === 'earth') ? rand(0,30) : '';

                // Calcular fish si es lago
                $fish = '';
                if ($tipo === 'lago') {
                    $vecinos = [
                        [$q, $r - 1], [$q, $r + 1],
                        [$q - 1, $r],
                        [$q + 1, $r],
                        [$q - 1, $r + ($q % 2 === 0 ? -1 : 0)],
                        [$q + 1, $r + ($q % 2 === 0 ? -1 : 0)]
                    ];

                    $aguaAlrededor = 0;
                    foreach ($vecinos as [$vq, $vr]) {
                        $vid = $vq * 100 + $vr;
                        if (isset($terrenos[$vid]) && $terrenos[$vid] === 'lago') {
                            $aguaAlrededor++;
                        }
                    }
                    $fish = rand(1, 3) + $aguaAlrededor * rand(1, 2);
                }
                

                $color = match ($tipo) {
                    'montaña' => '#888',
                    'lago'     => '#3b9ae1',
                    default    => '#76b676'
                };

                echo "<div class='hex $tipo' 
                            data-q='$q' 
                            data-r='$r' 
                            data-id='{$id}' 
                            data-nombre='{$nombre}' 
                            data-tipo='{$tipo}' 
                            data-fertilidad='{$fertilidad}'
                            data-fish='{$fish}' 
                            title='{$nombre}' 
                            style='left: {$left}px; top: {$top}px; background: {$color};'>
                        <span>{$q},{$r}</span>
                    </div>";
            }
        }
        ?>
    </canvas>

    <?php include 'components/info_panel.php'; ?>

    <div id="save-modal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:white; border:1px solid #ccc; padding:20px; z-index:3000; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.3); width:300px;">
        <h3>Guardar partida</h3>
        <label>Nombre:</label>
        <input type="text" id="nombre-partida" placeholder="Mi partida" style="width:100%; margin-bottom:10px;" />
        
        <label>Slot:</label>
        <select id="slot-partida" style="width:100%; margin-bottom:10px;">
            <option value="0">Slot 0</option>
            <option value="1">Slot 1</option>
            <option value="2">Slot 2</option>
            <option value="3">Slot 3</option>
            <option value="4">Slot 4</option>
            <option value="5">Slot 5</option>
            <option value="6">Slot 6</option>
            <option value="7">Slot 7</option>
            <option value="8">Slot 8</option>
            <option value="9">Slot 9</option>
        </select>

        <button onclick="confirmarGuardado()" style="width:100%; background:#3498db; color:white; padding:10px; border:none; border-radius:5px;">Guardar</button>
        <button onclick="cerrarModalGuardado()" style="margin-top:10px; width:100%; background:#ccc; padding:8px; border:none; border-radius:5px;">Cancelar</button>
    </div>


    <script src="assets/js/main.js"></script>
</body>
</html>
