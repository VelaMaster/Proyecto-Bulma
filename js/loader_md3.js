/* =========================================
   LÓGICA DEL LOADER MD3 (MULTIFORMAS RÁPIDAS Y ALEATORIAS)
   ========================================= */
let canvasLoader, ctxLoader, rafLoader;
let t_loader = 0;
let morphPhase = 0;
let rotation = 0;

const radius = 32; 
// 1. Aumentamos la velocidad al doble para que se vea la transformación en milisegundos
const morphSpeed = 0.06; 
const rotSpeed = 0.04;   

// 2. Catálogo expandido con todo el arsenal de MD3 Expressive
const SHAPES = [
    { sides: 4,  maxAmp: 0.18 }, // Cuadrado redondeado
    { sides: 10, maxAmp: 0.12 }, // Estrella Wavy (10 puntas)
    { sides: 2,  maxAmp: 0.25 }, // Óvalo / Píldora
    { sides: 6,  maxAmp: 0.15 }, // Hexágono
    { sides: 8,  maxAmp: 0.14 }, // Estrella de 8 puntas
    { sides: 3,  maxAmp: 0.22 }, // Triángulo redondeado / Escudo
    { sides: 5,  maxAmp: 0.16 }, // Pentágono / Flor de 5 pétalos
    { sides: 12, maxAmp: 0.08 }, // Engrane / Sol de 12 puntas
    { sides: 7,  maxAmp: 0.12 }  // Heptágono ondulado
];

let shapeIndex = 0;
let currentSides = SHAPES[0].sides;
let targetAmp = SHAPES[0].maxAmp;
let lastCycle = -1;
let colorPrimario = '#D0BCFF';

document.addEventListener('DOMContentLoaded', () => {
    canvasLoader = document.getElementById('loader-canvas');
    if (canvasLoader) ctxLoader = canvasLoader.getContext('2d');
});

function resizeCanvas() {
    if (!canvasLoader) return;
    const w = canvasLoader.parentElement.clientWidth;
    const h = canvasLoader.parentElement.clientHeight;
    const dpr = window.devicePixelRatio || 1;
    
    canvasLoader.width = w * dpr;
    canvasLoader.height = h * dpr;
    ctxLoader.scale(dpr, dpr);
}

function loopLoader() {
    if (!ctxLoader) return;
    
    t_loader++;
    const w = canvasLoader.parentElement.clientWidth;
    const h = canvasLoader.parentElement.clientHeight;
    const cx = w / 2;
    const cy = h / 2;

    ctxLoader.clearRect(0, 0, w, h);

    rotation += rotSpeed;
    morphPhase += morphSpeed;
    
    // Cada vez que Math.sin cruza por el cero, contamos un ciclo
    const currentCycle = Math.floor(morphPhase / Math.PI);

    if (currentCycle !== lastCycle) {
        lastCycle = currentCycle;
        
        // 3. Magia Aleatoria: Escoge la siguiente forma al azar (asegurando que no se repita la misma)
        let nextIndex;
        do {
            nextIndex = Math.floor(Math.random() * SHAPES.length);
        } while (nextIndex === shapeIndex);
        
        shapeIndex = nextIndex;
        currentSides = SHAPES[shapeIndex].sides;
        targetAmp = SHAPES[shapeIndex].maxAmp;
    }

    const currentAmplitude = Math.abs(Math.sin(morphPhase)) * targetAmp;

    ctxLoader.beginPath();
    const resolution = 200; 

    for (let i = 0; i <= resolution; i++) {
        const theta = (i / resolution) * Math.PI * 2;
        const r = radius * (1 + currentAmplitude * Math.cos(currentSides * (theta + rotation)));
        
        const px = cx + Math.cos(theta) * r;
        const py = cy + Math.sin(theta) * r;
        
        if (i === 0) ctxLoader.moveTo(px, py);
        else ctxLoader.lineTo(px, py);
    }

    ctxLoader.closePath();
    ctxLoader.fillStyle = colorPrimario;
    ctxLoader.fill();

    rafLoader = requestAnimationFrame(loopLoader);
}

window.mostrarLoader = function() {
    const pantallaElemento = document.getElementById('pantalla-carga');
    if (pantallaElemento) {
        pantallaElemento.classList.add('active'); 
    }
    
    colorPrimario = getComputedStyle(document.body).getPropertyValue('--md-sys-color-primary').trim() || '#D0BCFF';
    
    // Iniciar con una forma aleatoria y en su punto máximo de deformación
    // Así al usuario no le sale primero un círculo aburrido, sino una figura lista
    shapeIndex = Math.floor(Math.random() * SHAPES.length);
    currentSides = SHAPES[shapeIndex].sides;
    targetAmp = SHAPES[shapeIndex].maxAmp;
    morphPhase = Math.PI / 2; 
    lastCycle = Math.floor(morphPhase / Math.PI);
    
    resizeCanvas();
    if(rafLoader) cancelAnimationFrame(rafLoader);
    loopLoader();
};

window.cambiarPagina = function(url) {
    mostrarLoader();
    
    setTimeout(() => {
        window.location.href = url;
    }, 350); 
};

window.ocultarLoader = function() {
    const pantallaElemento = document.getElementById('pantalla-carga');
    if (pantallaElemento) {
        pantallaElemento.classList.remove('active'); 
        setTimeout(() => {
            if(rafLoader) cancelAnimationFrame(rafLoader);
        }, 150); 
    }
};

window.addEventListener('resize', () => {
    const pantallaElemento = document.getElementById('pantalla-carga');
    if(pantallaElemento && pantallaElemento.classList.contains('active')) {
        resizeCanvas();
    }
});