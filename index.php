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

    <div style="position: fixed; top: 0; left: 0; right: 0; background: #333; color: white; padding: 10px; z-index: 2000;">
        <button onclick="document.getElementById('settings-panel').style.display = 'block'" style="padding: 5px 10px; margin-right: 10px;">⚙ Configurar y regenerar</button>
        <span style="font-weight: bold;">Mapa Hexagonal</span>
    </div>

    <?php include 'components/map_generator_panel.php'; ?>

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

    <?php include 'components/info_panel.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>
