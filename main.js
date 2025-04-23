document.getElementById('regenerar-form').addEventListener('submit', e => {
    e.preventDefault();
    const form = e.target;
    const pm = form.pm.value;
    const pl = form.pl.value;
    const cols = form.cols.value;
    const rows = form.rows.value;

    window.location.href = `?pm=${pm}&pl=${pl}&cols=${cols}&rows=${rows}`;
});

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
