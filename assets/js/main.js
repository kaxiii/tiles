document.getElementById('regenerar-form').addEventListener('submit', e => {
    e.preventDefault();
    const form = e.target;
    const pm = form.pm.value;
    const pl = form.pl.value;
    const cols = form.cols.value;
    const rows = form.rows.value;

    window.location.href = `?pm=${pm}&pl=${pl}&cols=${cols}&rows=${rows}`;
});

// Obtener los parámetros de la URL
const container = document.getElementById('map');
const panel = document.getElementById('hex-info-panel');
const infoCoords = document.getElementById('info-coords');
const infoId = document.getElementById('info-id');
const infoNombre = document.getElementById('info-nombre');
const infoTipo = document.getElementById('info-tipo');
const closeBtn = document.getElementById('close-panel-btn');
const infoFertilidad = document.getElementById('info-fertilidad');
const infoFish = document.getElementById('info-fish');

let selectedHex = null;

container.addEventListener('click', e => {
    const hex = e.target.closest('.hex');
    if (!hex) return;

    // Obtener los datos del hexágono
    const q = hex.dataset.q;
    const r = hex.dataset.r;
    const id = hex.dataset.id;
    const nombre = hex.dataset.nombre;
    const tipo = hex.dataset.tipo;
    const fish = hex.dataset.fish;
    const fishContainer = document.getElementById('fish-container');
    const fertilidad = hex.dataset.fertilidad;

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

    // Actualizar el panel de información
    infoCoords.textContent = `(${q}, ${r})`;
    infoId.textContent = id;
    infoNombre.textContent = nombre;
    infoTipo.textContent = tipo;
    if (tipo === 'lago') {
      fishContainer.style.display = 'block';
      infoFish.textContent = fish;
    } else {
        fishContainer.style.display = 'none';
    }
    infoFertilidad.textContent = fertilidad;

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

window.guardarEstadoMapa = function () {
    const hexData = [];

    document.querySelectorAll('.hex').forEach(hex => {
        hexData.push({
            id: hex.dataset.id,
            q: hex.dataset.q,
            r: hex.dataset.r,
            nombre: hex.dataset.nombre,
            tipo: hex.dataset.tipo,
            fertilidad: hex.dataset.fertilidad,
            fish: hex.dataset.fish
        });
    });

    fetch('guardar_partida.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(hexData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Partida guardada en: ' + data.filename);
        } else {
            alert('Error al guardar: ' + data.error);
        }
    })
    .catch(err => {
        console.error('Error en la petición:', err);
        alert('Error al guardar');
    });
};


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

function syncSliderAndInput(inputId, sliderId) {
    const input = document.getElementById(inputId);
    const slider = document.getElementById(sliderId);

    input.addEventListener('input', () => {
        const val = Math.max(0, Math.min(1, parseFloat(input.value) || 0));
        slider.value = val;
    });

    slider.addEventListener('input', () => {
        input.value = slider.value;
    });
}

syncSliderAndInput('pm-input', 'pm-slider');
syncSliderAndInput('pl-input', 'pl-slider');

const editBtn = document.getElementById('edit-nombre-btn');
const editContainer = document.getElementById('edit-nombre-container');
const editInput = document.getElementById('edit-nombre-input');
const saveBtn = document.getElementById('save-nombre-btn');

editBtn.addEventListener('click', () => {
  editInput.value = infoNombre.textContent;
  editContainer.style.display = 'block';
});

saveBtn.addEventListener('click', () => {
  const newName = editInput.value.trim();
  if (newName) {
    infoNombre.textContent = newName;
    selectedHex.dataset.nombre = newName;
    selectedHex.querySelector('span').textContent = newName;

    // Aquí podrías agregar una llamada a fetch o AJAX para guardar en el servidor
    // fetch('guardar_nombre.php', { method: 'POST', body: JSON.stringify({ id: infoId.textContent, nombre: newName }) })

    editContainer.style.display = 'none';
    fetch('guardar_nombre.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id: infoId.textContent,
          nombre: newName
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          console.log('Nombre guardado:', data.nombre);
        } else {
          alert('Error al guardar');
        }
      })
      .catch(err => {
        console.error('Error en la petición:', err);
      });
  }
});

let currentView = 'coords';

document.querySelectorAll('.hex-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        currentView = btn.dataset.mode;

        document.querySelectorAll('.hex-toggle').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('.hex').forEach(hex => {
            const span = hex.querySelector('span');
            if (!span) return;

            if (currentView === 'coords') {
                span.textContent = `${hex.dataset.q},${hex.dataset.r}`;
            } 
              else if (currentView === 'fertilidad') {
                if (hex.dataset.tipo === 'earth' || hex.classList.contains('earth')) {
                  span.textContent = `🌱 ${hex.dataset.fertilidad}`;
                } else {
                  span.textContent = ''; // vaciar si no es tierra
                }
              }

              else if (currentView === 'fish') {
                if (hex.dataset.tipo === 'lago' || hex.classList.contains('lago')) {
                    span.textContent = `🐟 ${hex.dataset.fish}`;
                  } else {
                      span.textContent = '';
                  }
              }
              
        });
    });
});
