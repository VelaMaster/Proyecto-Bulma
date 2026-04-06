(function () {
  'use strict';

  const CONFIG = {
    blobCount: 7,           // Cantidad de vectores flotantes
    moveSpeed: 0.0004,      // Velocidad base de desplazamiento
    rotSpeedBase: 0.0015,
    colors: [],
  };

  const rand = (min, max) => Math.random() * (max - min) + min;

  // Catálogo de formas MD3 Expressive
  const SHAPES = [
    { sides: 2, maxAmp: 0.35 }, // Óvalo marcado
    { sides: 4, maxAmp: 0.28 }, // Estrella 4 puntas (Sparkle)
    { sides: 5, maxAmp: 0.18 }, // Pentágono redondeado
    { sides: 5, maxAmp: 0.30 }, // Estrella de 5 puntas suave
    { sides: 3, maxAmp: 0.15 }, // Triángulo / Forma de púa
    { sides: 6, maxAmp: 0.12 }, // Hexágono
  ];

  /* ─── Clase MorphingVector ─────────────────────────────────── */
  class MorphingVector {
    constructor(canvas, index) {
      this.canvas = canvas;
      this.index = index;
      this.reset();
    }

    reset() {
      const w = this.canvas.width;
      const h = this.canvas.height;

      // Tamaños más pequeños y elegantes (8% al 18% del alto)
      this.radius = rand(h * 0.08, h * 0.18);

      // Posición central inicial aleatoria
      this.baseX = rand(w * 0.2, w * 0.8);
      this.baseY = rand(h * 0.2, h * 0.8);

      this.x = this.baseX;
      this.y = this.baseY;

      // --- RUTA CAÓTICA (Múltiples ondas combinadas) ---
      // Para que no repitan el mismo círculo aburrido, combinamos 2 frecuencias por eje
      this.fX1 = rand(0.5, 1.5); this.fX2 = rand(0.5, 1.5);
      this.fY1 = rand(0.5, 1.5); this.fY2 = rand(0.5, 1.5);

      this.pX1 = rand(0, Math.PI * 2); this.pX2 = rand(0, Math.PI * 2);
      this.pY1 = rand(0, Math.PI * 2); this.pY2 = rand(0, Math.PI * 2);

      this.aX1 = rand(w * 0.1, w * 0.25); this.aX2 = rand(w * 0.05, w * 0.15);
      this.aY1 = rand(h * 0.1, h * 0.25); this.aY2 = rand(h * 0.05, h * 0.15);

      // --- ROTACIÓN ---
      this.rotation = rand(0, Math.PI * 2);
      this.rotDirection = Math.random() > 0.5 ? 1 : -1;
      this.rotSpeed = CONFIG.rotSpeedBase * rand(0.5, 1.5) * this.rotDirection;

      // --- MORFOLOGÍA ---
      this.morphPhase = rand(0, Math.PI * 2);
      this.morphSpeed = rand(0.004, 0.007); // Velocidad a la que "respira" la forma

      // Estado inicial de la forma
      const initialShape = SHAPES[Math.floor(Math.random() * SHAPES.length)];
      this.currentSides = initialShape.sides;
      this.targetAmp = initialShape.maxAmp;
      this.lastCycle = -1;
    }

    update(t) {
      // 1. Movimiento impredecible sumando senos y cosenos a diferentes ritmos
      this.x = this.baseX +
        Math.sin(t * CONFIG.moveSpeed * this.fX1 + this.pX1) * this.aX1 +
        Math.cos(t * CONFIG.moveSpeed * this.fX2 + this.pX2) * this.aX2;

      this.y = this.baseY +
        Math.cos(t * CONFIG.moveSpeed * this.fY1 + this.pY1) * this.aY1 +
        Math.sin(t * CONFIG.moveSpeed * this.fY2 + this.pY2) * this.aY2;

      // 2. Rotación constante
      this.rotation += this.rotSpeed;

      // 3. Lógica de Morfología Mágica
      // morphAngle es el "tiempo" de la respiración de la forma
      const morphAngle = t * this.morphSpeed + this.morphPhase;

      // Cada vez que la onda cruza por Pi (es decir, el seno se vuelve 0 y la forma es un círculo perfecto)
      // contamos un nuevo ciclo.
      const currentCycle = Math.floor(morphAngle / Math.PI);

      if (currentCycle !== this.lastCycle) {
        this.lastCycle = currentCycle;

        // ¡Magia! Como en este milisegundo la amplitud es 0 (es un círculo), 
        // cambiamos el número de lados sin que el usuario vea un "salto" raro.
        const nextShape = SHAPES[Math.floor(Math.random() * SHAPES.length)];
        this.currentSides = nextShape.sides;
        this.targetAmp = nextShape.maxAmp;
      }

      // La amplitud actual va de 0 a targetAmp suavemente
      this.currentAmplitude = Math.sin(morphAngle) * this.targetAmp;
    }

    draw(ctx, color) {
      ctx.save();
      ctx.globalCompositeOperation = 'normal';

      ctx.beginPath();
      const resolution = 120; // Resolución alta para picos muy definidos

      for (let i = 0; i <= resolution; i++) {
        const theta = (i / resolution) * Math.PI * 2;

        // La ecuación polar que genera todas las formas
        const r = this.radius * (1 + this.currentAmplitude * Math.cos(this.currentSides * (theta + this.rotation)));

        const cx = this.x + Math.cos(theta) * r;
        const cy = this.y + Math.sin(theta) * r;

        if (i === 0) {
          ctx.moveTo(cx, cy);
        } else {
          ctx.lineTo(cx, cy);
        }
      }
      ctx.closePath();

      // Color sólido semi-transparente
      ctx.fillStyle = color;
      ctx.fill();

      ctx.restore();
    }
  }

  /* ─── Clase Principal ──────────────────────────────────────── */
  class HeroPhysics {
    constructor(card) {
      this.card = card;
      this.canvas = document.createElement('canvas');
      this.ctx = this.canvas.getContext('2d');
      this.blobs = [];
      this.t = 0;
      this.raf = null;
      this.resizeObs = null;

      this.setupCanvas();
      this.setupBlobs();
      this.setupEvents();
      this.loop();
    }

    setupCanvas() {
      const c = this.canvas;
      c.style.cssText = `
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        border-radius: inherit;
        opacity: 0;
        transition: opacity 1s ease;
      `;
      this.card.insertBefore(c, this.card.firstChild);
      this.resize();
      requestAnimationFrame(() => { c.style.opacity = '1'; });
    }

    resize() {
      const rect = this.card.getBoundingClientRect();
      const dpr = window.devicePixelRatio || 1;
      this.canvas.width = rect.width * dpr;
      this.canvas.height = rect.height * dpr;
      this.canvas.style.width = rect.width + 'px';
      this.canvas.style.height = rect.height + 'px';
      this.ctx.scale(dpr, dpr);
      this.w = rect.width;
      this.h = rect.height;
      this.blobs.forEach(b => b.reset && (b.canvas = this.canvas, b.reset()));
    }

    setupBlobs() {
      this.blobs = Array.from({ length: CONFIG.blobCount }, (_, i) => {
        const b = new MorphingVector(this.canvas, i);
        b.canvas.width = this.w;
        b.canvas.height = this.h;
        b.reset();
        return b;
      });
    }

    setupEvents() {
      if (window.ResizeObserver) {
        this.resizeObs = new ResizeObserver(() => this.resize());
        this.resizeObs.observe(this.card);
      } else {
        window.addEventListener('resize', () => this.resize());
      }
    }

    loop() {
      this.t++;
      const ctx = this.ctx;
      const w = this.w;
      const h = this.h;
      ctx.clearRect(0, 0, w, h);

      // Actualizar y dibujar
      this.blobs.forEach((blob, i) => {
        const colorIndex = i % CONFIG.colors.length;
        blob.update(this.t);
        blob.draw(ctx, CONFIG.colors[colorIndex]);
      });

      this.raf = requestAnimationFrame(() => this.loop());
    }
  }
  function init() {
    const card = document.querySelector('.md3-hero-card');
    if (!card) return;

    injectColorVars();
    window._heroPhysics = new HeroPhysics(card);
  }

  function injectColorVars() {
    const accent = document.documentElement.dataset.themeAccent || 'violeta';

    const themeColors = {
      violeta: [
        'rgba(147, 112, 219, 0.40)',
        'rgba(138, 43, 226, 0.45)',
        'rgba(218, 112, 214, 0.35)',
        'rgba(75, 0, 130, 0.50)',
      ],
      azul: [
        'rgba(100, 149, 237, 0.40)',
        'rgba(30, 144, 255, 0.45)',
        'rgba(0, 191, 255, 0.35)',
        'rgba(65, 105, 225, 0.50)',
      ],
      verde: [
        'rgba(60, 179, 113, 0.40)',
        'rgba(46, 204, 113, 0.45)',
        'rgba(0, 250, 154, 0.35)',
        'rgba(34, 139, 34, 0.50)',
      ],
      rojo: [
        'rgba(205, 92, 92, 0.40)',
        'rgba(255, 99, 71, 0.45)',
        'rgba(250, 128, 114, 0.35)',
        'rgba(178, 34, 34, 0.50)',
      ],
      naranja: [
        'rgba(255, 140, 0, 0.40)',
        'rgba(255, 165, 0, 0.45)',
        'rgba(255, 127, 80, 0.35)',
        'rgba(210, 105, 30, 0.50)',
      ],
    };

    CONFIG.colors = themeColors[accent] || themeColors.violeta;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();