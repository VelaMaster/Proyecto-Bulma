/**
 * hero_physics.js
 * Física MD3 Mejorada — Fluidos orgánicos, spring physics y tilt 3D
 * Material Design 3 · Motion easing · Interacción inmersiva
 */

(function () {
  'use strict';

  /* ─── Configuración Física ─────────────────────────────────── */
  const CONFIG = {
    blobCount: 5, // Un blob extra para más riqueza visual
    springStiffness: 0.025, // Menor rigidez = movimiento más suave (MD3 easing)
    springDamping: 0.88,    // Mayor amortiguación = no tiembla, fluye
    idleAmplitude: 35,
    idleSpeed: 0.0004,
    morphSpeed: 0.0006,
    interactRadius: 250,    // Radio de interacción del mouse más grande
    interactForce: 80,      // Repulsión más amigable
    colors: [],
  };

  /* ─── Utilidades ─────────────────────────────────────────────── */
  const lerp = (a, b, t) => a + (b - a) * t;
  const rand = (min, max) => Math.random() * (max - min) + min;

  /* ─── Clase Blob (Fluidos) ─────────────────────────────────── */
  class Blob {
    constructor(canvas, index) {
      this.canvas = canvas;
      this.index = index;
      this.reset();
    }

    reset() {
      const w = this.canvas.width;
      const h = this.canvas.height;

      const cols = 2;
      const col = this.index % cols;
      const row = Math.floor(this.index / cols);
      
      // Distribución más esparcida para cubrir el fondo
      this.baseX = (w * (col + 0.5)) / cols + rand(-w * 0.2, w * 0.2);
      this.baseY = (h * (row + 0.5)) / Math.ceil(CONFIG.blobCount / cols) + rand(-h * 0.2, h * 0.2);

      this.x = this.baseX;
      this.y = this.baseY;
      this.vx = 0;
      this.vy = 0;

      // Blobs más grandes para generar un efecto de resplandor
      this.baseRadius = rand(h * 0.4, h * 0.6);
      this.radius = this.baseRadius;
      this.radiusV = 0;

      this.phaseX = rand(0, Math.PI * 2);
      this.phaseY = rand(0, Math.PI * 2);
      this.phaseR = rand(0, Math.PI * 2);
      this.phaseMorph = rand(0, Math.PI * 2);

      this.points = 8;
      this.morphOffsets = Array.from({ length: this.points }, () => rand(0, Math.PI * 2));
      this.morphAmps = Array.from({ length: this.points }, () => rand(0.08, 0.25)); // Deformación más notoria
      this.morphSpeeds = Array.from({ length: this.points }, () => rand(0.4, 1.2));
    }

    update(t, mouse, w, h) {
      // Movimiento natural y lento
      const targetX = this.baseX + Math.sin(t * CONFIG.idleSpeed + this.phaseX) * CONFIG.idleAmplitude * (w / 300);
      const targetY = this.baseY + Math.cos(t * CONFIG.idleSpeed * 0.8 + this.phaseY) * CONFIG.idleAmplitude * (h / 200);

      // Repulsión suave y magnética con el ratón
      let mouseForceX = 0;
      let mouseForceY = 0;
      if (mouse.x !== null) {
        const dx = this.x - mouse.x;
        const dy = this.y - mouse.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < CONFIG.interactRadius && dist > 0) {
          // Curva de fuerza exponencial invertida para un rechazo suave
          const force = Math.pow(1 - dist / CONFIG.interactRadius, 2) * CONFIG.interactForce;
          mouseForceX = (dx / dist) * force;
          mouseForceY = (dy / dist) * force;
        }
      }

      // Spring physics aplicadas
      const fx = (targetX - this.x + mouseForceX) * CONFIG.springStiffness;
      const fy = (targetY - this.y + mouseForceY) * CONFIG.springStiffness;
      this.vx = this.vx * CONFIG.springDamping + fx;
      this.vy = this.vy * CONFIG.springDamping + fy;
      this.x += this.vx;
      this.y += this.vy;

      // Pulsación del tamaño
      const targetR = this.baseRadius + Math.sin(t * CONFIG.idleSpeed * 1.5 + this.phaseR) * this.baseRadius * 0.15;
      const fr = (targetR - this.radius) * 0.02;
      this.radiusV = this.radiusV * 0.9 + fr;
      this.radius += this.radiusV;
    }

    draw(ctx, color, t) {
      ctx.save();
      ctx.globalCompositeOperation = 'screen'; // Fusión de luz estilo MD3

      ctx.beginPath();
      const angleStep = (Math.PI * 2) / this.points;

      for (let i = 0; i <= this.points; i++) {
        const idx = i % this.points;
        const angle = idx * angleStep;
        const morphFactor = 1 + Math.sin(t * CONFIG.morphSpeed * this.morphSpeeds[idx] + this.morphOffsets[idx]) * this.morphAmps[idx];
        const r = this.radius * morphFactor;

        const cx1 = this.x + Math.cos(angle) * r;
        const cy1 = this.y + Math.sin(angle) * r;

        if (i === 0) {
          ctx.moveTo(cx1, cy1);
        } else {
          const prevIdx = idx === 0 ? this.points - 1 : idx - 1;
          const prevAngle = prevIdx * angleStep;
          const prevMorph = 1 + Math.sin(t * CONFIG.morphSpeed * this.morphSpeeds[prevIdx] + this.morphOffsets[prevIdx]) * this.morphAmps[prevIdx];
          const prevR = this.radius * prevMorph;
          const px = this.x + Math.cos(prevAngle) * prevR;
          const py = this.y + Math.sin(prevAngle) * prevR;

          const tension = 0.4;
          const cp1x = px + Math.cos(prevAngle + Math.PI / 2) * prevR * tension;
          const cp1y = py + Math.sin(prevAngle + Math.PI / 2) * prevR * tension;
          const cp2x = cx1 - Math.cos(angle + Math.PI / 2) * r * tension;
          const cp2y = cy1 - Math.sin(angle + Math.PI / 2) * r * tension;

          ctx.bezierCurveTo(cp1x, cp1y, cp2x, cp2y, cx1, cy1);
        }
      }
      ctx.closePath();

      const m = color.match(/rgba?\((\d+),(\d+),(\d+),([\d.]+)\)/);
      const rgb = m ? `${m[1]},${m[2]},${m[3]}` : '138,43,226';

      // Gradiente radial más difuso
      const grad = ctx.createRadialGradient(
        this.x, this.y, 0,
        this.x, this.y, this.radius * 1.2
      );
      grad.addColorStop(0,   `rgba(${rgb},0.8)`);
      grad.addColorStop(0.5, `rgba(${rgb},0.4)`);
      grad.addColorStop(1,   `rgba(${rgb},0.0)`);

      ctx.fillStyle = grad;
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
      this.mouse = { x: null, y: null, clientX: 0, clientY: 0 };
      this.t = 0;
      this.raf = null;
      this.resizeObs = null;
      
      // Variables para suavizado del Tilt 3D
      this.tilt = { currentX: 0, currentY: 0, targetX: 0, targetY: 0 };

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
        const b = new Blob(this.canvas, i);
        b.canvas.width = this.w;
        b.canvas.height = this.h;
        b.reset();
        return b;
      });
    }

    setupEvents() {
      const onMove = (e) => {
        const touch = e.touches ? e.touches[0] : e;
        const rect = this.card.getBoundingClientRect();
        this.mouse.x = touch.clientX - rect.left;
        this.mouse.y = touch.clientY - rect.top;
        
        // Calcular target para el Tilt 3D
        const xCenter = rect.width / 2;
        const yCenter = rect.height / 2;
        // Max tilt de 3 grados para que sea sutil y elegante
        this.tilt.targetY = ((this.mouse.x - xCenter) / xCenter) * 3;
        this.tilt.targetX = -((this.mouse.y - yCenter) / yCenter) * 3;
      };
      
      const onLeave = () => { 
        this.mouse.x = null; 
        this.mouse.y = null; 
        // Volver al centro
        this.tilt.targetX = 0;
        this.tilt.targetY = 0;
      };

      this.card.addEventListener('mousemove', onMove, { passive: true });
      this.card.addEventListener('touchmove', onMove, { passive: true });
      this.card.addEventListener('mouseleave', onLeave);
      this.card.addEventListener('touchend', onLeave);

      if (window.ResizeObserver) {
        this.resizeObs = new ResizeObserver(() => this.resize());
        this.resizeObs.observe(this.card);
      } else {
        window.addEventListener('resize', () => this.resize());
      }
    }

    applyTilt() {
      // Lerp para suavizar el movimiento 3D (Efecto inercia)
      this.tilt.currentX = lerp(this.tilt.currentX, this.tilt.targetX, 0.1);
      this.tilt.currentY = lerp(this.tilt.currentY, this.tilt.targetY, 0.1);
      
      this.card.style.transform = `perspective(1000px) rotateX(${this.tilt.currentX}deg) rotateY(${this.tilt.currentY}deg)`;
    }

    loop() {
      this.t++;
      const ctx = this.ctx;
      const w = this.w;
      const h = this.h;

      ctx.clearRect(0, 0, w, h);

      // Fondo sutil
      ctx.save();
      ctx.fillStyle = 'rgba(0,0,0,0.15)'; // Un poco más oscuro para que resalten los colores
      ctx.fillRect(0, 0, w, h);
      ctx.restore();

      // Dibujar fluidos
      this.blobs.forEach((blob, i) => {
        // Reciclar colores si hay más blobs que colores
        const colorIndex = i % CONFIG.colors.length;
        blob.update(this.t, this.mouse, w, h);
        blob.draw(ctx, CONFIG.colors[colorIndex], this.t);
      });

      this.drawParticles(ctx, w, h);
      this.applyTilt(); // Aplicar física 3D a la tarjeta en cada frame

      this.raf = requestAnimationFrame(() => this.loop());
    }

    drawParticles(ctx, w, h) {
      if (!this._particles) {
        // Más partículas para un ambiente más mágico
        this._particles = Array.from({ length: 20 }, () => ({
          x: rand(0, w), y: rand(0, h),
          r: rand(1, 3.5),
          vx: rand(-0.2, 0.2),
          vy: rand(-0.4, -0.1),
          alpha: rand(0.1, 0.6),
          phase: rand(0, Math.PI * 2),
        }));
      }
      ctx.save();
      ctx.globalCompositeOperation = 'screen';
      this._particles.forEach(p => {
        // Movimiento flotante senoidal
        p.x += p.vx + Math.sin(this.t * 0.015 + p.phase) * 0.3;
        p.y += p.vy;
        
        // Reset si salen por arriba
        if (p.y < -10) { 
            p.y = h + 10; 
            p.x = rand(0, w); 
        }
        
        const pulse = 0.5 + Math.sin(this.t * 0.05 + p.phase) * 0.5;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(255,255,255,${p.alpha * pulse})`;
        ctx.fill();
      });
      ctx.restore();
    }
  }

  /* ─── Inicialización ────────────────────────────────────────── */
  function init() {
    const card = document.querySelector('.md3-hero-card');
    if (!card) return;

    injectColorVars();
    window._heroPhysics = new HeroPhysics(card);
  }

  function injectColorVars() {
    const accent = document.documentElement.dataset.themeAccent || 'violeta';

    // Paletas afinadas para brillar bonito en modo oscuro
    const themeColors = {
      violeta: [
        'rgba(147,112,219,0.6)', // MediumPurple
        'rgba(138,43,226,0.5)',  // BlueViolet
        'rgba(218,112,214,0.4)', // Orchid
        'rgba(75,0,130,0.6)',    // Indigo
        'rgba(238,130,238,0.4)', // Violet
      ],
      azul: [
        'rgba(100,149,237,0.6)', 
        'rgba(30,144,255,0.5)',  
        'rgba(0,191,255,0.4)',   
        'rgba(65,105,225,0.6)',  
        'rgba(135,206,235,0.4)', 
      ],
      verde: [
        'rgba(60,179,113,0.6)',
        'rgba(46,204,113,0.5)',
        'rgba(0,250,154,0.4)',
        'rgba(34,139,34,0.6)',
        'rgba(144,238,144,0.4)',
      ],
      rojo: [
        'rgba(205,92,92,0.6)',
        'rgba(255,99,71,0.5)',
        'rgba(250,128,114,0.4)',
        'rgba(178,34,34,0.6)',
        'rgba(255,160,122,0.4)',
      ],
      naranja: [
        'rgba(255,140,0,0.6)',
        'rgba(255,165,0,0.5)',
        'rgba(255,127,80,0.4)',
        'rgba(210,105,30,0.6)',
        'rgba(255,218,185,0.4)',
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