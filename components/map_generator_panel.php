    <div id="settings-panel">
        <h2>Opciones de Generación del Mapa</h2>
        <form id="regenerar-form">
        
            <label>Probabilidad Montaña:<br>
                <input type="number" name="pm" id="pm-input" min="0" max="1" step="0.01" value="<?= $_GET['pm'] ?? 0.15 ?>" required>
                <input type="range" id="pm-slider" min="0" max="1" step="0.01" value="<?= $_GET['pm'] ?? 0.15 ?>">
            </label><br><br>

            <label>Probabilidad Lago:<br>
                <input type="number" name="pl" id="pl-input" min="0" max="1" step="0.01" value="<?= $_GET['pl'] ?? 0.15 ?>" required>
                <input type="range" id="pl-slider" min="0" max="1" step="0.01" value="<?= $_GET['pl'] ?? 0.15 ?>">
            </label>

            <br><br>

            <label>Columnas (cols):<br>
                <input type="number" name="cols" min="3" max="100" value="<?= $_GET['cols'] ?? 10 ?>" required>
            </label><br><br>
            <label>Filas (rows):<br>
                <input type="number" name="rows" min="3" max="50" value="<?= $_GET['rows'] ?? 7 ?>" required>
            </label><br><br>

            <button type="submit">Regenerar Mapa</button>
            <button type="button" onclick="document.getElementById('settings-panel').style.display='none'">Cancelar</button>

        
        </form>
    </div>