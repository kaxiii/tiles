class HexCanvasRenderer {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.hexSize = 30; // Tamaño del hexágono (radio)
        this.hexHeight = this.hexSize * 2;
        this.hexWidth = Math.sqrt(3)/2 * this.hexHeight;
        this.terrainColors = {
            'earth': '#76b676',
            'montaña': '#888',
            'lago': '#3b9ae1'
        };
        this.hexData = [];
        this.selectedHex = null;
        
        // Ajustar tamaño del canvas
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());
        
        // Manejo de eventos
        this.canvas.addEventListener('click', (e) => this.handleClick(e));
    }

    resizeCanvas() {
        // Ajustar según el tamaño del mapa necesario
        this.canvas.width = this.canvas.offsetWidth;
        this.canvas.height = this.canvas.offsetHeight;
        this.renderAll();
    }

    loadHexData(hexData) {
        this.hexData = hexData;
        this.renderAll();
    }

    hexToPixel(q, r) {
        const x = q * this.hexWidth * 0.75;
        const y = r * this.hexHeight + (q % 2) * this.hexHeight/2;
        return { x, y };
    }

    drawHexagon(q, r, type, nombre, fertilidad, fish, isSelected = false) {
        const { x, y } = this.hexToPixel(q, r);
        
        // Dibujar hexágono
        this.ctx.beginPath();
        for (let i = 0; i < 6; i++) {
            const angle = Math.PI / 3 * i + Math.PI/6; // Ajuste para punto arriba
            const xPos = x + this.hexSize * Math.cos(angle);
            const yPos = y + this.hexSize * Math.sin(angle);
            this.ctx.lineTo(xPos, yPos);
        }
        this.ctx.closePath();
        
        // Relleno
        this.ctx.fillStyle = this.terrainColors[type] || '#76b676';
        this.ctx.fill();
        
        // Borde
        this.ctx.strokeStyle = isSelected ? '#ff0000' : '#000';
        this.ctx.lineWidth = isSelected ? 3 : 1;
        this.ctx.stroke();
        
        // Texto (coordenadas)
        this.ctx.fillStyle = '#000';
        this.ctx.font = '10px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(`${q},${r}`, x, y + 3);
        
        return { x, y };
    }

    renderAll() {
        // Limpiar canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Dibujar todos los hexágonos
        this.hexData.forEach(hex => {
            const isSelected = this.selectedHex && 
                              this.selectedHex.q === hex.q && 
                              this.selectedHex.r === hex.r;
            this.drawHexagon(
                hex.q, 
                hex.r, 
                hex.tipo, 
                hex.nombre, 
                hex.fertilidad, 
                hex.fish, 
                isSelected
            );
        });
    }

    handleClick(event) {
        const rect = this.canvas.getBoundingClientRect();
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;
        
        // Encontrar hexágono clickeado (simplificado)
        for (const hex of this.hexData) {
            const { x, y } = this.hexToPixel(hex.q, hex.r);
            const distance = Math.sqrt((mouseX - x)**2 + (mouseY - y)**2);
            
            if (distance < this.hexSize) {
                this.selectedHex = hex;
                this.renderAll();
                
                // Disparar evento o actualizar panel de información
                document.dispatchEvent(new CustomEvent('hexSelected', {
                    detail: hex
                }));
                break;
            }
        }
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    const renderer = new HexCanvasRenderer('hexCanvas');
    
    // Obtener datos del mapa desde PHP (puedes usar AJAX o inyectarlos)
    const hexData = JSON.parse(document.getElementById('mapData').textContent);
    renderer.loadHexData(hexData);
    
    // Escuchar selección de hexágono
    document.addEventListener('hexSelected', (e) => {
        updateInfoPanel(e.detail);
    });
});