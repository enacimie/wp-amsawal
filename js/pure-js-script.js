'use strict';

/**
 * duo-path — Duolingo-style path interactions
 * Celebration on activity click, smooth scroll to current node
 */

/**
 * Navegación cliente. Usa history.pushState para SPA-like feel; cae a
 * location.assign si pushState no está disponible.
 */
function navigate(path) {
  if (window.history && window.history.pushState) {
    window.history.pushState({}, '', path);
    window.dispatchEvent(new PopStateEvent('popstate'));
  } else {
    window.location.assign(path);
  }
}

document.addEventListener('DOMContentLoaded', function () {

  /**
   * Helper de i18n: usa los strings localizados en window.wpAmsawalAjax.i18n
   * si existen, si no cae al fallback español (compatibilidad hacia atrás).
   */
  const t = (key, fallback) => {
    const dict = (window.wpAmsawalAjax && window.wpAmsawalAjax.i18n) || {};
    return dict[key] || fallback;
  };

  // ── Audio Feedback System (Web Audio API - Enhanced with multiple sounds) ──
  const DuoAudio = (function() {
    let ctx = null;
    let enabled = true;
    
    function init() {
      if (!ctx) {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (AudioContext) ctx = new AudioContext();
      }
      if (ctx && ctx.state === 'suspended') ctx.resume();
    }
    
    function playTone(freq, type, duration, vol, startTime) {
      if (!ctx || !enabled) return;
      vol = vol || 0.1;
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = type;
      osc.frequency.setValueAtTime(freq, startTime || ctx.currentTime);
      gain.gain.setValueAtTime(vol, startTime || ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, (startTime || ctx.currentTime) + duration);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(startTime || ctx.currentTime);
      osc.stop((startTime || ctx.currentTime) + duration);
    }
    
    // Play chord (multiple tones at once)
    function playChord(frequencies, type, duration, vol) {
      if (!ctx || !enabled) return;
      const now = ctx.currentTime;
      for (let i = 0; i < frequencies.length; i++) {
        playTone(frequencies[i], type, duration, vol, now);
      }
    }
    
    // Arpeggio effect (rapid sequence of notes)
    function playArpeggio(frequencies, type, duration, vol) {
      if (!ctx || !enabled) return;
      const now = ctx.currentTime;
      const noteDuration = duration / frequencies.length;
      for (let i = 0; i < frequencies.length; i++) {
        playTone(frequencies[i], type, noteDuration, vol, now + (i * noteDuration * 0.8));
      }
    }
    
    return {
      playClick: function() {
        init();
        playTone(600, 'sine', 0.1, 0.05);
        setTimeout(function() { playTone(800, 'sine', 0.15, 0.05); }, 40);
      },
      
      playSuccess: function() {
        // Major triad: C-E-G (celebration)
        init();
        playChord([523.25, 659.25, 783.99], 'sine', 0.4, 0.1);
        setTimeout(function() { 
          playArpeggio([523.25, 659.25, 783.99, 1046.50], 'sine', 0.3, 0.08); 
        }, 150);
      },
      
      playError: function() {
        // Dissonant interval
        init();
        playTone(220, 'triangle', 0.15, 0.1);
        setTimeout(function() { playTone(180, 'triangle', 0.3, 0.1); }, 100);
      },
      
      playLevelUp: function() {
        // Epic level up fanfare
        init();
        playChord([523.25, 659.25, 783.99], 'sine', 0.3, 0.12);
        setTimeout(function() { 
          playChord([659.25, 783.99, 987.77], 'sine', 0.4, 0.12); 
        }, 200);
        setTimeout(function() { 
          playChord([783.99, 987.77, 1174.66], 'sine', 0.6, 0.15); 
        }, 400);
      },
      
      playAchievement: function() {
        // Achievement unlock sparkle
        init();
        playArpeggio([783.99, 987.77, 1174.66, 1318.51], 'sine', 0.5, 0.1);
        setTimeout(function() { 
          playTone(1567.98, 'sine', 0.4, 0.08); 
        }, 300);
      },
      
      playStreak: function() {
        // Streak celebration (rising)
        init();
        playTone(523.25, 'sine', 0.15, 0.1);
        setTimeout(function() { playTone(659.25, 'sine', 0.15, 0.1); }, 100);
        setTimeout(function() { playTone(783.99, 'sine', 0.2, 0.1); }, 200);
        setTimeout(function() { playTone(1046.50, 'sine', 0.3, 0.12); }, 300);
      },
      
      playCoins: function() {
        // Coin collection sound
        init();
        playTone(1046.50, 'sine', 0.1, 0.08);
        setTimeout(function() { playTone(1318.51, 'sine', 0.15, 0.08); }, 80);
      },
      
      playPerfect: function() {
        // Perfect answer - magical sparkle
        init();
        playArpeggio([659.25, 783.99, 987.77, 1174.66, 1318.51], 'sine', 0.6, 0.1);
        setTimeout(function() { 
          playChord([783.99, 987.77, 1318.51], 'sine', 0.5, 0.12); 
        }, 200);
      },
      
      playCombo: function(comboCount) {
        // Combo sound - pitch increases with combo
        init();
        const baseFreq = 440 + (Math.min(comboCount, 10) * 50);
        playTone(baseFreq, 'sine', 0.15, 0.1);
        setTimeout(function() { playTone(baseFreq * 1.5, 'sine', 0.2, 0.1); }, 100);
      },
      
      playCheckpoint: function() {
        // Checkpoint reached - heroic
        init();
        playChord([523.25, 783.99, 1046.50], 'sine', 0.5, 0.12);
      },
      
      enable: function() { enabled = true; },
      disable: function() { enabled = false; },
      isEnabled: function() { return enabled; }
    };
  })();
  
  // ── Haptic Feedback (Vibration API for mobile) ──
  const DuoHaptics = (function() {
    function vibrate(pattern) {
      if ('vibrate' in navigator) {
        navigator.vibrate(pattern);
      }
    }
    
    return {
      light: function() {
        vibrate(10);
      },
      medium: function() {
        vibrate(20);
      },
      heavy: function() {
        vibrate(40);
      },
      success: function() {
        vibrate([30, 50, 30]); // Double tap
      },
      error: function() {
        vibrate([50, 50, 50]); // Triple tap
      },
      levelUp: function() {
        vibrate([50, 50, 50, 50, 50, 50, 100]); // Fanfare pattern
      },
      achievement: function() {
        vibrate([100, 50, 100, 50, 100]); // Achievement pattern
      }
    };
  })();

  // ── Confetti (Canvas, sin librerías) — Enhanced with multiple effects ──
  const DuoConfetti = (function() {
    let canvas = null, ctx = null, particles = [], raf = null;
    // Amsawal brand colors
    // F7-10: Colores desde CSS tokens
        const COLORS = [
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-primary').trim() || '#2c5f8d',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-accent').trim() || '#3498db',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-secondary').trim() || '#e67e22',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-tifinagh').trim() || '#d4af37',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-success').trim() || '#27ae60',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-warning').trim() || '#f39c12'
        ];
    
    // Particle types: square, circle, star, triangle
    const SHAPES = ['square', 'circle', 'star', 'triangle'];

    function setup() {
      if (canvas) return ctx;
      canvas = document.createElement('canvas');
      canvas.className = 'duo-confetti-canvas';
      canvas.setAttribute('aria-hidden', 'true');
      canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;';
      document.body.appendChild(canvas);
      ctx = canvas.getContext('2d');
      resize();
      window.addEventListener('resize', resize);
      return ctx;
    }
    
    function resize() {
      if (!canvas) return;
      canvas.width  = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    
    function createParticle(x, y, type) {
      const angle = Math.random() * Math.PI * 2;
      const speed = 4 + Math.random() * 6;
      const shape = SHAPES[Math.floor(Math.random() * SHAPES.length)];
      
      return {
        x: x, y: y,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed - 6,
        g: 0.18, 
        size: 4 + Math.random() * 6,
        color: COLORS[(Math.random() * COLORS.length) | 0],
        rot: Math.random() * Math.PI,
        vr: (Math.random() - 0.5) * 0.3,
        life: 1.0,
        shape: shape,
        wobble: Math.random() * Math.PI * 2,
        wobbleSpeed: 0.05 + Math.random() * 0.05
      };
    }
    
    function drawShape(shape, x, y, size, rot, color) {
      ctx.fillStyle = color;
      ctx.beginPath();
      
      if (shape === 'square') {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(rot);
        ctx.fillRect(-size / 2, -size / 2, size, size * 0.6);
        ctx.restore();
      } 
      else if (shape === 'circle') {
        ctx.arc(x, y, size / 2, 0, Math.PI * 2);
        ctx.fill();
      }
      else if (shape === 'star') {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(rot);
        for (let i = 0; i < 5; i++) {
          ctx.lineTo(Math.cos((18 + i * 72) * Math.PI / 180) * size / 2, 
                    -Math.sin((18 + i * 72) * Math.PI / 180) * size / 2);
          ctx.lineTo(Math.cos((54 + i * 72) * Math.PI / 180) * size / 4, 
                    -Math.sin((54 + i * 72) * Math.PI / 180) * size / 4);
        }
        ctx.closePath();
        ctx.fill();
        ctx.restore();
      }
      else if (shape === 'triangle') {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(rot);
        ctx.moveTo(0, -size / 2);
        ctx.lineTo(size / 2, size / 2);
        ctx.lineTo(-size / 2, size / 2);
        ctx.closePath();
        ctx.fill();
        ctx.restore();
      }
    }
    
    function fire(options) {
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      
      const opts = options || {};
      const count = opts.count || 60;
      const origin = opts.origin || 'center'; // 'center', 'bottom', 'top', 'left', 'right'
      const colors = opts.colors || COLORS;
      
      if (!setup()) return;
      
      let startX, startY;
      if (origin === 'center') {
        startX = window.innerWidth / 2;
        startY = window.innerHeight / 3;
      } else if (origin === 'bottom') {
        startX = window.innerWidth / 2;
        startY = window.innerHeight;
      } else if (origin === 'top') {
        startX = window.innerWidth / 2;
        startY = 0;
      } else if (origin === 'left') {
        startX = 0;
        startY = window.innerHeight / 2;
      } else if (origin === 'right') {
        startX = window.innerWidth;
        startY = window.innerHeight / 2;
      }
      
      for (let i = 0; i < count; i++) {
        const p = createParticle(startX, startY, origin);
        p.color = colors[(Math.random() * colors.length) | 0];
        particles.push(p);
      }
      
      if (raf) cancelAnimationFrame(raf);
      
      const tick = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles = particles.filter(function(p) { return p.life > 0; });
        
        for (let i = 0; i < particles.length; i++) {
          const p = particles[i];
          p.vy += p.g; 
          p.x += p.vx; 
          p.y += p.vy; 
          p.rot += p.vr;
          p.wobble += p.wobbleSpeed;
          p.life -= 0.015;
          
          ctx.save();
          ctx.globalAlpha = Math.max(0, p.life);
          // Add wobble effect
          const wobbleX = Math.sin(p.wobble) * 10;
          drawShape(p.shape, p.x + wobbleX, p.y, p.size, p.rot, p.color);
          ctx.restore();
        }
        
        if (particles.length > 0) {
          raf = requestAnimationFrame(tick);
        } else {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          raf = null;
        }
      };
      
      raf = requestAnimationFrame(tick);
    }
    
    // Firework explosion effect
    function firework(x, y, colors) {
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      if (!setup()) return;
      
      const particleCount = 80;
      const fireworkColors = colors || COLORS;
      
      for (let i = 0; i < particleCount; i++) {
        const angle = (Math.PI * 2 / particleCount) * i;
        const speed = 2 + Math.random() * 4;
        particles.push({
          x: x, y: y,
          vx: Math.cos(angle) * speed,
          vy: Math.sin(angle) * speed,
          g: 0.05,
          size: 3 + Math.random() * 4,
          color: fireworkColors[(Math.random() * fireworkColors.length) | 0],
          rot: Math.random() * Math.PI,
          vr: (Math.random() - 0.5) * 0.2,
          life: 1.0,
          shape: 'circle',
          wobble: 0,
          wobbleSpeed: 0
        });
      }
      
      if (!raf) {
        const tick = () => {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          particles = particles.filter(function(p) { return p.life > 0; });
          
          for (let i = 0; i < particles.length; i++) {
            const p = particles[i];
            p.vy += p.g; 
            p.x += p.vx; 
            p.y += p.vy; 
            p.rot += p.vr;
            p.life -= 0.02;
            
            ctx.save();
            ctx.globalAlpha = Math.max(0, p.life);
            ctx.fillStyle = p.color;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size / 2, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
          }
          
          if (particles.length > 0) {
            raf = requestAnimationFrame(tick);
          } else {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            raf = null;
          }
        };
        raf = requestAnimationFrame(tick);
      }
    }
    
    // Cascade effect (for level up)
    function cascade() {
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      if (!setup()) return;
      
      for (let i = 0; i < 150; i++) {
        setTimeout(() => {
          const x = Math.random() * window.innerWidth;
          particles.push(createParticle(x, -20, 'cascade'));
        }, i * 20);
      }
      
      if (!raf) {
        const tick = () => {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          particles = particles.filter(function(p) { return p.life > 0; });
          
          for (let i = 0; i < particles.length; i++) {
            const p = particles[i];
            p.vy += 0.3; 
            p.x += p.vx; 
            p.y += p.vy; 
            p.rot += p.vr;
            p.wobble += p.wobbleSpeed;
            p.life -= 0.008;
            
            ctx.save();
            ctx.globalAlpha = Math.max(0, p.life);
            const wobbleX = Math.sin(p.wobble) * 10;
            drawShape(p.shape, p.x + wobbleX, p.y, p.size, p.rot, p.color);
            ctx.restore();
          }
          
          if (particles.length > 0) {
            raf = requestAnimationFrame(tick);
          } else {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            raf = null;
          }
        };
        raf = requestAnimationFrame(tick);
      }
    }
    
    return { 
      fire: fire,
      firework: firework,
      cascade: cascade
    };
  })();

  // Multiple celebration functions
  function fireConfetti() { DuoConfetti.fire(); }
  function fireConfettiOptions(opts) { DuoConfetti.fire(opts); }
  function fireFirework(x, y, colors) { DuoConfetti.firework(x, y, colors); }
  function fireCascade() { DuoConfetti.cascade(); }
  
  // Celebration composer (multiple effects)
  function celebrate(type) {
    if (!type) type = 'default';
    
    if (type === 'default') {
      DuoAudio.playSuccess();
      DuoConfetti.fire();
    } 
    else if (type === 'levelup') {
      DuoAudio.playSuccess();
      DuoConfetti.cascade();
      setTimeout(() => DuoConfetti.fire({ count: 80, origin: 'bottom' }), 300);
      setTimeout(() => DuoConfetti.fire({ count: 80, origin: 'bottom' }), 600);
    }
    else if (type === 'achievement') {
      DuoAudio.playSuccess();
      DuoConfetti.firework(window.innerWidth / 2, window.innerHeight / 3);
      setTimeout(() => DuoConfetti.firework(window.innerWidth / 3, window.innerHeight / 2), 200);
      setTimeout(() => DuoConfetti.firework(window.innerWidth * 2 / 3, window.innerHeight / 2), 400);
    }
    else if (type === 'perfect') {
      DuoAudio.playSuccess();
      DuoConfetti.fire({ count: 100, origin: 'center', colors: ['#d4af37', '#f39c12'] });
      setTimeout(() => DuoConfetti.fire({ count: 100, origin: 'left' }), 200);
      setTimeout(() => DuoConfetti.fire({ count: 100, origin: 'right' }), 200);
    }
  }

  // ── Micro-interactions: Audio + Haptics + Visual Feedback ──
  document.body.addEventListener('mousedown', function(e) {
    const target = e.target.closest('button, .duo-course-btn, .duo-node-circle, .duo-act-icon, .nav-item, .duo-mobile-nav a, .duo-topbar-stat, .duo-stat-item');
    if (target) {
      // Audio feedback
      DuoAudio.playClick();
      // Haptic feedback (mobile only)
      if (window.innerWidth < 768) {
        DuoHaptics.light();
      }
    }
  });
  
  // Add success feedback helper
  function triggerSuccessFeedback(element) {
    // Visual
    element.classList.add('duo-success-pulse');
    setTimeout(function() { element.classList.remove('duo-success-pulse'); }, 600);
    // Audio
    DuoAudio.playSuccess();
    // Haptic
    if (window.innerWidth < 768) {
      DuoHaptics.success();
    }
  }
  
  // Add error feedback helper
  function triggerErrorFeedback(element) {
    // Visual
    element.style.animation = 'duo-shake 400ms var(--amsawal-ease-out)';
    setTimeout(function() { element.style.animation = ''; }, 400);
    // Audio
    DuoAudio.playError();
    // Haptic
    if (window.innerWidth < 768) {
      DuoHaptics.error();
    }
  }

  // ── TTS (Web Speech API) ──
  const DuoTTS = (function() {
    let enabled = true, voice = null;
    function pickVoice() {
      if (voice || !('speechSynthesis' in window)) return;
      const voices = window.speechSynthesis.getVoices();
      voice = voices.find(function(v) { return /es[-_]ES|es[-_]MX|es/i.test(v.lang); }) || voices[0] || null;
    }
    if ('speechSynthesis' in window) {
      window.speechSynthesis.onvoiceschanged = pickVoice;
      pickVoice();
    }
    function speak(text, opts) {
      if (!enabled || !('speechSynthesis' in window) || !text) return;
      const u = new SpeechSynthesisUtterance(text);
      if (voice) u.voice = voice;
      u.lang = (voice && voice.lang) || 'es-ES';
      u.rate = (opts && opts.rate) || 0.95;
      u.pitch = (opts && opts.pitch) || 1.0;
      u.volume = (opts && opts.volume) || 0.8;
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(u);
    }
    function cancel() {
      if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    }
    return {
      speak: speak, cancel: cancel,
      isEnabled: function() { return enabled; },
      setEnabled: function(v) { enabled = !!v; if (!enabled) cancel(); },
      hasVoices: function() { return 'speechSynthesis' in window && (window.speechSynthesis.getVoices().length > 0); }
    };
  })();

  // TTS toggle
  function initTTSToggle() {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let stored = null;
    try { stored = localStorage.getItem('amsawal-tts-enabled'); } catch (e) {}
    if (stored === null) { DuoTTS.setEnabled(!reduced); }
    else { DuoTTS.setEnabled(stored === '1'); }
    const btn = document.querySelector('.duo-topbar-tts');
    if (!btn) return;
    const sync = function() {
      const on = DuoTTS.isEnabled();
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      btn.setAttribute('aria-label', on ? t('ttsOn', 'Voz activada. Pulse para silenciar.') : t('ttsOff', 'Voz silenciada. Pulse para activar.'));
      btn.textContent = on ? '🔊' : '🔇';
    };
    sync();
    btn.addEventListener('click', function() {
      const next = !DuoTTS.isEnabled();
      DuoTTS.setEnabled(next);
      try { localStorage.setItem('amsawal-tts-enabled', next ? '1' : '0'); } catch (e) {}
      sync();
      if (next) DuoTTS.speak(t('ttsTest', 'Voz activada.'));
    });
  }
  initTTSToggle();

  // High contrast toggle (replaces dark mode)
  function initThemeToggle() {
    const btn = document.querySelector('.duo-topbar-theme');
    if (!btn) return;
    let theme = null;
    try { theme = localStorage.getItem('amsawal-theme'); } catch (e) {}
    
    // Support both old 'dark' value and new 'high-contrast' value
    if (theme === 'high-contrast') {
      document.documentElement.setAttribute('data-theme', 'high-contrast');
    } else if (theme === 'dark') {
      // Migrate old dark mode preference to high-contrast
      document.documentElement.setAttribute('data-theme', 'high-contrast');
      try { localStorage.setItem('amsawal-theme', 'high-contrast'); } catch (e) {}
    }
    
    const sync = function() {
      const current = document.documentElement.getAttribute('data-theme');
      btn.setAttribute('aria-pressed', current === 'high-contrast' ? 'true' : 'false');
      btn.setAttribute('aria-label', current === 'high-contrast'
        ? t('themeHighContrast', 'Modo accesibilidad activo. Pulse para volver a modo normal.')
        : t('themeNormal', 'Modo normal activo. Pulse para activar modo accesibilidad.'));
      btn.textContent = current === 'high-contrast' ? '🔆' : '♿';
    };
    sync();
    btn.addEventListener('click', function() {
      const current = document.documentElement.getAttribute('data-theme');
      const next = current === 'high-contrast' ? 'normal' : 'high-contrast';
      document.documentElement.setAttribute('data-theme', next);
      try { localStorage.setItem('amsawal-theme', next); } catch (e) {}
      sync();
    });
  }
  initThemeToggle();

  // Topbar dropdown menu toggle
  function initTopbarDropdown() {
    var toggle = document.querySelector('.duo-topbar-toggle');
    var dropdown = document.getElementById('duo-topbar-dropdown');
    if (!toggle || !dropdown) return;

    function close() {
      dropdown.setAttribute('hidden', '');
      toggle.setAttribute('aria-expanded', 'false');
    }

    function open() {
      dropdown.removeAttribute('hidden');
      toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function(e) {
      e.stopPropagation();
      var isOpen = toggle.getAttribute('aria-expanded') === 'true';
      if (isOpen) {
        close();
      } else {
        open();
      }
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
      if (!dropdown.contains(e.target) && !toggle.contains(e.target)) {
        close();
      }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        close();
        toggle.focus();
      }
    });
  }
  initTopbarDropdown();

  // Sticky unit header
  function initStickyUnitHeader() {
    const header = document.querySelector('.duo-unit-header');
    if (!header) return;
    let ticking = false;
    const update = function() {
      const stuck = header.offsetTop > 78 && window.scrollY > (header.offsetTop - 78);
      header.classList.toggle('duo-unit-header--stuck', stuck);
      ticking = false;
    };
    window.addEventListener('scroll', function() {
      if (!ticking) { requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    update();
  }
  initStickyUnitHeader();

  // Topbar scroll-shadow
  function initTopbarScrollShadow() {
    const topbar = document.querySelector('.duo-topbar');
    if (!topbar) return;
    let ticking = false;
    const update = function() {
      topbar.setAttribute('data-scrolled', window.scrollY > 8 ? 'true' : 'false');
      ticking = false;
    };
    window.addEventListener('scroll', function() {
      if (!ticking) { requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    update();
  }
  initTopbarScrollShadow();

  // Streak ring SVG
  function initStreakRing() {
    const el = document.querySelector('.duo-stat-streak .duo-streak-ring');
    if (!el) return;
    const n = parseInt((el.getAttribute('data-days') || '0'), 10);
    const goal = Math.max(1, parseInt((el.getAttribute('data-goal') || '7'), 10));
    const ratio = Math.max(0, Math.min(1, n / goal));
    const radius = 14;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference * (1 - ratio);
    const ns = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', '0 0 36 36');
    svg.setAttribute('width', '36');
    svg.setAttribute('height', '36');
    svg.setAttribute('aria-hidden', 'true');
    const track = document.createElementNS(ns, 'circle');
    track.setAttribute('class', 'duo-streak-ring__track');
    track.setAttribute('cx', '18');
    track.setAttribute('cy', '18');
    track.setAttribute('r', String(radius));
    const progress = document.createElementNS(ns, 'circle');
    progress.setAttribute('class', 'duo-streak-ring__progress');
    progress.setAttribute('cx', '18');
    progress.setAttribute('cy', '18');
    progress.setAttribute('r', String(radius));
    progress.setAttribute('stroke-dasharray', String(circumference));
    progress.setAttribute('stroke-dashoffset', String(offset));
    svg.appendChild(track);
    svg.appendChild(progress);
    el.textContent = '';
    el.appendChild(svg);
  }
  initStreakRing();

  // ARIA progressbars
  document.querySelectorAll('.duo-progress-bar').forEach(function(bar) {
    const wrap = bar.closest('.duo-progress');
    if (!wrap) return;
    const wrapper = bar.parentElement;
    wrapper.setAttribute('role', 'progressbar');
    const text = wrap.querySelector('.duo-progress-text');
    let now = 0, min = 0, max = 100, label = '';
    if (text) {
      const m = text.textContent.match(/(\d+)\s*\/\s*(\d+)/);
      if (m) { now = parseInt(m[1], 10); max = parseInt(m[2], 10); label = text.textContent.trim(); }
    }
    wrapper.setAttribute('aria-valuemin', String(min));
    wrapper.setAttribute('aria-valuemax', String(max));
    wrapper.setAttribute('aria-valuenow', String(now));
    wrapper.setAttribute('aria-valuetext', label);
  });

  // ARIA current/disabled on nodes (renders as aria-current="step" on the current lesson)
  document.querySelectorAll('.duo-node--current .duo-node-circle').forEach(function(c) {
    c.setAttribute('aria-current', 'step');
  });
  document.querySelectorAll('.duo-node--locked .duo-node-circle').forEach(function(c) {
    c.setAttribute('aria-disabled', 'true');
  });

  // Mobile drawer (with DuoFocusTrap + inert for WCAG 2.4.3)
  let drawerDestroyTrap = null;
  function initDrawer() {
    const toggle = document.querySelector('.duo-drawer-toggle');
    const scrim  = document.querySelector('.duo-drawer-scrim');
    const sidebar = document.querySelector('.duo-sidebar');
    if (!toggle || !scrim || !sidebar) return;
    const isDesktop = function() { return window.matchMedia('(min-width: 992px)').matches; };

    // Mark background content inert while drawer is open (WCAG 4.1.2 / 2.4.3)
    function setInert(on) {
      const main = document.getElementById('duo-main-content') || document.querySelector('main');
      const footer = document.querySelector('.duo-mobile-nav');
      const topbar = document.querySelector('.duo-topbar');
      [main, footer, topbar].forEach(function(el) {
        if (el) el.inert = on;
      });
    }

    const open = function() {
      if (isDesktop()) return;
      document.body.setAttribute('data-drawer', 'open');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', t('drawerClose', 'Cerrar menú de navegación'));
      scrim.classList.add('is-open');
      setInert(true);
      drawerDestroyTrap = DuoFocusTrap.create(sidebar, {
        initialFocus: sidebar.querySelector('.nav-item')
      });
    };
    const close = function() {
      if (drawerDestroyTrap) {
        drawerDestroyTrap();
        drawerDestroyTrap = null;
      }
      document.body.removeAttribute('data-drawer');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', t('drawerOpen', 'Abrir menú de navegación'));
      scrim.classList.remove('is-open');
      setInert(false);
      toggle.focus();
    };
    const isOpen = function() { return document.body.getAttribute('data-drawer') === 'open'; };
    toggle.addEventListener('click', function() { isOpen() ? close() : open(); });
    scrim.addEventListener('click', close);
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && isOpen()) close();
    });
    // Auto-close if viewport resizes to desktop while drawer is open
    window.matchMedia('(min-width: 992px)').addEventListener('change', function(mq) {
      if (mq.matches && isOpen()) close();
    });
  }
  initDrawer();

  // Haptics (second declaration removed — first DuoHaptics at line ~175 is authoritative)

  // Keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    const tag = (document.activeElement && document.activeElement.tagName) || '';
    const isTyping = tag === 'INPUT' || tag === 'TEXTAREA' || (document.activeElement && document.activeElement.isContentEditable);
    if (isTyping) return;
    if (e.key === '?' && e.shiftKey) { e.preventDefault(); DuoAudio.playClick(); }
  });

  // Highlight current nav item
  const currentPath = window.location.pathname;
  document.querySelectorAll('.duo-sidebar-nav .nav-item, .duo-mobile-nav a').forEach(function(el) {
    if (el.getAttribute('href') === window.location.origin + currentPath || el.getAttribute('href') === currentPath) {
      el.classList.add('active');
    } else {
      el.classList.remove('active');
    }
  });

  // Rank-change animation
  document.querySelectorAll('.duo-leader-card[data-prev-rank]').forEach(function(card) {
    const cur = parseInt(card.getAttribute('data-rank') || '0', 10);
    const prev = parseInt(card.getAttribute('data-prev-rank') || '0', 10);
    if (!cur || !prev || cur === prev) return;
    const badge = document.createElement('span');
    badge.className = 'duo-rank-change ' + (cur < prev ? 'duo-rank-up' : 'duo-rank-down');
    badge.textContent = (cur < prev ? '↑' : '↓') + ' ' + Math.abs(cur - prev);
    badge.setAttribute('aria-label', cur < prev ? t('rankUp', 'Subiste') : t('rankDown', 'Bajaste'));
    const rankEl = card.querySelector('.duo-leader-rank');
    if (rankEl) rankEl.appendChild(badge);
  });

  // Quest cards - display only, no interaction (data driven by PHP backend)
  // Removed fake click handler that was incrementing progress bars arbitrarily

  // Mascot TTS
  const mascotBubble = document.querySelector('.duo-mascot-bubble');
  if (mascotBubble) {
    mascotBubble.setAttribute('role', 'button');
    mascotBubble.setAttribute('tabindex', '0');
    mascotBubble.setAttribute('aria-label', t('mascotSpeak', 'Escuchar el saludo del mascot'));
    const speakBubble = function() {
      if (!DuoTTS.isEnabled()) return;
      const text = mascotBubble.textContent.replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '').trim();
      DuoTTS.speak(text);
    };
    mascotBubble.addEventListener('click', speakBubble);
    mascotBubble.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); speakBubble(); }
    });
  }

  // Scroll to current lesson
  const currentNode = document.querySelector('.duo-node--current');
  if (currentNode) {
    requestAnimationFrame(function() {
      requestAnimationFrame(function() {
        currentNode.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });
  }

  // Guide button - show modal with section theory
  document.querySelectorAll('.duo-unit-guide-btn[data-guide]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      var guideContent = btn.getAttribute('data-guide');
      var sectionKey = btn.getAttribute('data-section');
      
      // Create modal overlay
      var overlay = document.createElement('div');
      overlay.className = 'duo-guide-overlay';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.setAttribute('aria-labelledby', 'guide-modal-title');
      
      // Create modal content
      var modal = document.createElement('div');
      modal.className = 'duo-guide-modal';
      
      // Header
      var header = document.createElement('div');
      header.className = 'duo-guide-modal-header';
      
      var title = document.createElement('h2');
      title.className = 'duo-guide-modal-title';
      title.id = 'guide-modal-title';
      title.textContent = '📖 Guía de estudio';
      
      var closeBtn = document.createElement('button');
      closeBtn.className = 'duo-guide-modal-close';
      closeBtn.setAttribute('aria-label', 'Cerrar guía');
      closeBtn.textContent = '✕';
      
      header.appendChild(title);
      header.appendChild(closeBtn);
      
      // Body
      var body = document.createElement('div');
      body.className = 'duo-guide-modal-body';
      body.innerHTML = guideContent;
      
      // Footer
      var footer = document.createElement('div');
      footer.className = 'duo-guide-modal-footer';
      
      var startBtn = document.createElement('button');
      startBtn.className = 'duo-guide-modal-btn';
      startBtn.textContent = '¡Empezar!';
      startBtn.addEventListener('click', function() {
        overlay.remove();
        // Scroll to first lesson of section
        var firstNode = document.querySelector('.duo-node[data-lesson]');
        if (firstNode) {
          firstNode.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
      
      footer.appendChild(startBtn);
      
      // Assemble modal
      modal.appendChild(header);
      modal.appendChild(body);
      modal.appendChild(footer);
      overlay.appendChild(modal);
      document.body.appendChild(overlay);
      
      // Close handlers
      closeBtn.addEventListener('click', function() {
        overlay.remove();
      });
      
      overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
          overlay.remove();
        }
      });
      
      // Trap focus
      closeBtn.focus();
      
      // ESC to close
      var escHandler = function(e) {
        if (e.key === 'Escape') {
          overlay.remove();
          document.removeEventListener('keydown', escHandler);
        }
      };
      document.addEventListener('keydown', escHandler);
    });
  });

  // Leaderboard tabs
  document.querySelectorAll('.duo-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      const parent = tab.closest('.duo-leader-tabs');
      if (!parent) return;
      parent.querySelectorAll('.duo-tab').forEach(function(s) { s.classList.remove('active'); });
      tab.classList.add('active');
      const section = tab.closest('.duo-leader-section');
      if (!section) return;
      const tabId = tab.getAttribute('data-tab');
      section.querySelectorAll('.duo-tab-content').forEach(function(c) { c.classList.remove('active'); });
      const target = section.querySelector('#' + tabId);
      if (target) target.classList.add('active');
    });
  });

  // Celebration particles
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!prefersReducedMotion) {
    const activityLinks = document.querySelectorAll('.duo-activity, .duo-test-btn');
    const emojis = ['⭐', '🌟', '✨', '🎉', '🎊', '💫', '🔥', '💪', '🏆'];
    activityLinks.forEach(function(link) {
      link.addEventListener('click', function() {
        const rect = link.getBoundingClientRect();
        const cx = rect.left + rect.width / 2;
        const cy = rect.top + rect.height / 2;
        const container = document.createElement('div');
        container.className = 'duo-celebration';
        for (let i = 0; i < 12; i++) {
          const particle = document.createElement('span');
          particle.className = 'duo-particle';
          particle.textContent = emojis[Math.floor(Math.random() * emojis.length)];
          particle.style.left = (cx + (Math.random() - 0.5) * 120) + 'px';
          particle.style.top = cy + 'px';
          particle.style.animationDelay = (Math.random() * 0.3) + 's';
          particle.style.animationDuration = (1 + Math.random() * 1.5) + 's';
          container.appendChild(particle);
        }
        document.body.appendChild(container);
        setTimeout(function() { container.remove(); }, 2000);
      });
    });
  }

  // Toasts auto-dismiss
  document.querySelectorAll('.duo-toast').forEach(function(toast) {
    const duration = parseInt(toast.getAttribute('data-duration') || '4500', 10);
    let timer = null;
    const dismiss = function() {
      if (toast.classList.contains('duo-toast--leaving')) return;
      toast.classList.add('duo-toast--leaving');
      setTimeout(function() { toast.remove(); }, 300);
    };
    if (duration > 0) {
      timer = setTimeout(dismiss, duration);
      toast.addEventListener('mouseenter', function() { if (timer) clearTimeout(timer); });
      toast.addEventListener('mouseleave', function() { timer = setTimeout(dismiss, 1500); });
    }
    const closeBtn = toast.querySelector('.duo-toast-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function() { if (timer) clearTimeout(timer); dismiss(); });
    }
  });

  // Course switch confirmation
  const courseForm = document.getElementById('wp_amsawal_homeform');
  if (courseForm) {
    courseForm.addEventListener('submit', function(e) {
      const submitter = e.submitter;
      if (!submitter || !submitter.dataset || submitter.dataset.confirm !== 'true') return;
      const name = submitter.dataset.courseName || 'este curso';
      const msg = '¿Cambiar a "' + name + '"?\n\nTu progreso en el curso actual se guarda y puedes volver cuando quieras desde aquí.';
      if (!window.confirm(msg)) e.preventDefault();
    });
  }

  // ── Tutor virtual ──
  const tutor       = document.getElementById('duo-tutor');
  const tutorToggle = document.getElementById('duo-tutor-toggle');
  if (tutor && tutorToggle) {
    const tutorClose  = tutor.querySelector('.duo-tutor-close');
    const tutorForm   = tutor.querySelector('.duo-tutor-form');
    const tutorInput  = tutor.querySelector('.duo-tutor-input');
    const tutorSend   = tutor.querySelector('.duo-tutor-send');
    const tutorClear  = tutor.querySelector('.duo-tutor-clear');
    const tutorLog    = tutor.querySelector('.duo-tutor-log');
    const tutorStatus = tutor.querySelector('.duo-tutor-status');
    const tutorNonce  = tutor.querySelector('.duo-tutor-nonce').value;
    const tutorAjax   = tutor.querySelector('.duo-tutor-ajaxurl').value;
    const tutorCourse = tutor.querySelector('.duo-tutor-course').value;

    const open = function() {
      tutor.hidden = false;
      tutorToggle.setAttribute('aria-expanded', 'true');
      requestAnimationFrame(function() { tutorInput.focus(); });
    };
    const close = function() {
      tutor.hidden = true;
      tutorToggle.setAttribute('aria-expanded', 'false');
      tutorToggle.focus();
    };
    tutorToggle.addEventListener('click', open);
    if (tutorClose) tutorClose.addEventListener('click', close);
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && !tutor.hidden) close();
    });
    if (tutorInput) {
      tutorInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          tutorForm.dispatchEvent(new Event('submit', { cancelable: true }));
        }
      });
    }
    if (tutorForm) {
      tutorForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = (tutorInput.value || '').trim();
        if (!message || tutorSend.disabled) return;
        appendMsg('user', message);
        tutorInput.value = '';
        tutorSend.disabled = true;
        tutorStatus.textContent = t('thinking', 'Pensando…');
        tutorStatus.classList.remove('error');
        const thinking = appendMsg('thinking', '');
        const xhr = new XMLHttpRequest();
        xhr.open('POST', tutorAjax, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.timeout = 180000;
        xhr.onload = function() {
          thinking.remove();
          tutorSend.disabled = false;
          let resp = null;
          try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = null; }
          if (xhr.status >= 200 && xhr.status < 300 && resp && resp.success) {
            appendMsg('assistant', resp.data.reply || '(sin respuesta)');
            tutorStatus.textContent = '';
            scrollLogToBottom();
          } else {
            const err = (resp && resp.data) ? resp.data : ('HTTP ' + xhr.status);
            tutorStatus.textContent = '❌ ' + err;
            tutorStatus.classList.add('error');
          }
        };
        xhr.onerror = function() {
          thinking.remove(); tutorSend.disabled = false;
          DuoAudio.playError();
          tutorStatus.textContent = t('netError', '❌ Error de red');
          tutorStatus.classList.add('error');
        };
        xhr.ontimeout = function() {
          thinking.remove(); tutorSend.disabled = false;
          DuoAudio.playError();
          tutorStatus.textContent = t('timeoutRetry', '⏱️ Tiempo agotado');
          tutorStatus.classList.add('error');
        };
        xhr.send(
          'action=wp_amsawal_tutor_ask' +
          '&_ajax_nonce=' + encodeURIComponent(tutorNonce) +
          '&message=' + encodeURIComponent(message) +
          '&course=' + encodeURIComponent(tutorCourse)
        );
      });
    }
    if (tutorClear) {
      tutorClear.addEventListener('click', function() {
        if (!window.confirm('¿Borrar el historial de esta conversación?')) return;
        const xhr = new XMLHttpRequest();
        xhr.open('POST', tutorAjax, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.onload = function() {
          let resp = null;
          try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = null; }
          if (resp && resp.success) {
            while (tutorLog.firstChild) tutorLog.removeChild(tutorLog.firstChild);
            const empty = document.createElement('div');
            empty.className = 'duo-tutor-empty';
            empty.textContent = t('historyCleared', 'Historial borrado. ¡Pregúntame lo que quieras!');
            tutorLog.appendChild(empty);
            tutorStatus.textContent = '';
          }
        };
        xhr.send(
          'action=wp_amsawal_tutor_clear' +
          '&_ajax_nonce=' + encodeURIComponent(tutorNonce) +
          '&course=' + encodeURIComponent(tutorCourse)
        );
      });
    }
    function appendMsg(role, content) {
      const div = document.createElement('div');
      if (role === 'thinking') {
        div.className = 'duo-tutor-msg duo-tutor-msg--thinking';
        div.setAttribute('aria-label', 'El tutor está pensando');
        for (let i = 0; i < 3; i++) { const dot = document.createElement('span'); div.appendChild(dot); }
      } else {
        div.className = 'duo-tutor-msg duo-tutor-msg--' + role;
        // Insert text with line breaks as <br> elements to avoid innerHTML.
        const lines = (content || '').split(/\n/);
        lines.forEach(function(line, idx) {
          div.appendChild(document.createTextNode(line));
          if (idx < lines.length - 1) {
            div.appendChild(document.createElement('br'));
          }
        });
      }
      tutorLog.appendChild(div);
      scrollLogToBottom();
      return div;
    }
    function scrollLogToBottom() { tutorLog.scrollTop = tutorLog.scrollHeight; }
  }

  // ── Stats tooltips (ARIA) ──
  const streakEl = document.querySelector('.duo-topbar-streak, .duo-stat-streak');
  const dueEl    = document.querySelector('.duo-topbar-due');
  if (streakEl && !streakEl.hasAttribute('title')) {
    const n = (streakEl.textContent.match(/\d+/) || ['0'])[0];
    const tmpl = n === '1' ? 'streakLabelOne' : 'streakLabelMany';
    const fallback = n === '1' ? 'Racha de 1 día' : 'Racha de %d días';
    const label = t(tmpl, fallback).replace('%d', n);
    streakEl.setAttribute('title', label); streakEl.setAttribute('aria-label', label);
  }
  if (dueEl) {
    const n = parseInt((dueEl.textContent.match(/\d+/) || ['0'])[0], 10);
    if (n > 0) dueEl.setAttribute('data-due', '1');
  }

  // ── Rank-change toast: fires when leaderboard detects position change ──
  function showRankToast(title, message, type) {
    var stack = document.getElementById('duo-toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.id = 'duo-toast-stack';
      stack.className = 'duo-toast-stack';
      stack.setAttribute('role', 'region');
      stack.setAttribute('aria-label', 'Notificaciones');
      stack.setAttribute('aria-live', 'polite');
      document.body.appendChild(stack);
    }

    var toast = document.createElement('div');
    toast.className = 'duo-toast duo-toast--' + (type || 'success');
    toast.setAttribute('role', 'status');

    // Add icon based on type
    var icon = document.createElement('div');
    icon.className = 'duo-toast-icon';
    icon.textContent = type === 'success' ? '✓' : (type === 'error' ? '✕' : '⚠');
    toast.appendChild(icon);

    var body = document.createElement('div');
    body.className = 'duo-toast-body';

    var h = document.createElement('div');
    h.className = 'duo-toast-title';
    h.textContent = title || '';

    var p = document.createElement('div');
    p.className = 'duo-toast-message';
    p.textContent = message || '';

    var closeBtn = document.createElement('button');
    closeBtn.className = 'duo-toast-close';
    closeBtn.setAttribute('aria-label', 'Cerrar');
    closeBtn.textContent = '×';

    body.appendChild(h);
    body.appendChild(p);
    toast.appendChild(body);
    toast.appendChild(closeBtn);
    stack.appendChild(toast);

    // Trigger enter animation
    requestAnimationFrame(function() {
      toast.classList.add('is-visible');
    });

    // Auto-dismiss after 8s (increased from 5s for better UX)
    var timer = setTimeout(function() { dismiss(); }, 8000);

    var dismiss = function() {
      if (toast.classList.contains('duo-toast--leaving')) return;
      clearTimeout(timer);
      toast.classList.remove('is-visible');
      toast.classList.add('duo-toast--leaving');
      setTimeout(function() { toast.remove(); }, 300);
    };

    closeBtn.addEventListener('click', dismiss);
    toast.addEventListener('mouseenter', function() { clearTimeout(timer); });
    toast.addEventListener('mouseleave', function() { timer = setTimeout(dismiss, 2000); });
  }

  // ── showH5PFeedbackBar: Duolingo feedback bar for H5P completion ──
  function showH5PFeedbackBar(data) {
    console.log('[Amsawal] showH5PFeedbackBar called with:', data);
    const pct = data.pct || 0;
    const xpEarned = data.xp_earned || 0;
    const title = data.title || '';
    const rank_up = data.rank_up || false;
    const next_lesson_url = data.next_lesson_url || '';
    const section_completed = data.section_completed || null;
    const isPerfect = pct >= 90;
    const isGood = pct >= 70;

    // Find insertion point
    var targetDoc = document;
    var insertionPoint = null;

    function findQuestionButtons(doc, depth) {
      if (depth > 5) return null;
      try {
        var btn = doc.querySelector('.h5p-question-buttons');
        if (btn) return btn;
        var iframes = doc.querySelectorAll('.h5p-iframe, iframe.h5p-iframe');
        for (var i = 0; i < iframes.length; i++) {
          var f = iframes[i];
          if (f.contentDocument) {
            var found = findQuestionButtons(f.contentDocument, depth + 1);
            if (found) return found;
          }
        }
      } catch (e) {}
      return null;
    }

    insertionPoint = findQuestionButtons(document, 0);
    if (insertionPoint) {
      targetDoc = insertionPoint.ownerDocument;
    }

    var placement = insertionPoint ? insertionPoint.parentNode : (document.querySelector('.duo-container') || document.body);

    // Inject feedback bar CSS into target document (iframe) if not already present
    if (targetDoc !== document && !targetDoc.getElementById('duo-feedback-bar-inline-css')) {
      var styleEl = targetDoc.createElement('style');
      styleEl.id = 'duo-feedback-bar-inline-css';
      styleEl.textContent = [
        '.duo-feedback-bar{position:relative;margin:12px 0;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-radius:12px;opacity:0;animation:duo-feedback-enter 350ms ease-out forwards;box-shadow:0 2px 12px rgba(0,0,0,0.1);z-index:9999;}',
        '.duo-feedback-bar.is-correct{background:linear-gradient(135deg,#7AA829,#99CC33);color:#fff;}',
        '.duo-feedback-bar.is-wrong{background:linear-gradient(135deg,#A30029,#CC0033);color:#fff;}',
        '.duo-feedback-bar.active{opacity:1;transform:translateY(0);}',
        '.duo-feedback-icon{font-size:1.6rem;width:42px;height:42px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.2);border-radius:50%;flex-shrink:0;}',
        '.duo-feedback-body{flex:1;min-width:0;}',
        '.duo-feedback-title{font-size:1rem;font-weight:700;margin:0 0 2px;}',
        '.duo-feedback-message{font-size:0.85rem;margin:0;opacity:0.9;}',
        '.duo-feedback-actions{flex-shrink:0;}',
        '.duo-feedback-btn{background:rgba(255,255,255,0.25);color:#fff;border:2px solid rgba(255,255,255,0.4);border-radius:8px;padding:10px 20px;font-size:0.85rem;font-weight:700;cursor:pointer;text-transform:uppercase;letter-spacing:0.05em;transition:background 150ms ease,border-color 150ms ease;}',
        '.duo-feedback-btn:hover{background:rgba(255,255,255,0.35);border-color:rgba(255,255,255,0.6);}',
        '.duo-feedback-btn:active{transform:scale(0.96);}',
        '@keyframes duo-feedback-enter{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}',
        '@media (max-width:768px){.duo-feedback-bar{flex-direction:column;text-align:center;gap:12px;}.duo-feedback-actions{width:100%;justify-content:center;}}'
      ].join('');
      targetDoc.head.appendChild(styleEl);
    }

    // Remove any existing bar before inserting
    var existingBar = targetDoc.querySelector('.duo-feedback-bar');
    if (existingBar) existingBar.parentNode.removeChild(existingBar);

    var bar = targetDoc.createElement('div');
    bar.className = 'duo-feedback-bar ' + (isGood ? 'is-correct' : 'is-wrong');
    bar.setAttribute('role', 'status');
    bar.setAttribute('aria-live', 'polite');
    bar.setAttribute('aria-atomic', 'true');

    // Icon
    var iconDiv = targetDoc.createElement('div');
    iconDiv.className = 'duo-feedback-icon';
    iconDiv.textContent = rank_up ? '🔓' : (isPerfect ? '🎉' : (isGood ? '👍' : '💪'));

    var titleEl = targetDoc.createElement('h3');
    titleEl.className = 'duo-feedback-title';
    if (section_completed) {
      titleEl.textContent = '¡' + section_completed.title + ' completada!';
    } else if (rank_up) {
      titleEl.textContent = '¡Siguiente lección desbloqueada!';
    } else {
      titleEl.textContent = isPerfect ? '¡Perfecto!' : (isGood ? '¡Buen trabajo!' : '¡Sigue practicando!');
    }

    var message = targetDoc.createElement('p');
    message.className = 'duo-feedback-message';
    var messageText = '';
    if (section_completed) {
      messageText = section_completed.desc;
    } else if (isGood) {
      messageText = pct >= 100 ? '¡Perfecto!' : '¡Bien hecho!';
    } else {
      messageText = '¡Sigue practicando!';
    }
    if (xpEarned > 0) messageText += ' +' + xpEarned + ' XP';
    if (rank_up && data.new_level) messageText += ' · Nivel ' + data.new_level;
    message.textContent = messageText;

    var body = targetDoc.createElement('div');
    body.className = 'duo-feedback-body';
    body.appendChild(titleEl);
    body.appendChild(message);

    var actions = targetDoc.createElement('div');
    actions.className = 'duo-feedback-actions';

    if (isGood) {
      // Success: show CONTINUAR button
      var btn = targetDoc.createElement('button');
      btn.className = 'duo-feedback-btn';
      btn.type = 'button';
      btn.textContent = 'CONTINUAR';
      btn.setAttribute('aria-label', 'Continuar a la siguiente lección');
      actions.appendChild(btn);

      btn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('[Amsawal] CONTINUAR button clicked');
        console.log('[Amsawal] next_lesson_url:', next_lesson_url);
        
        bar.classList.remove('is-correct', 'is-wrong');
        document.body.classList.remove('duo-feedback-active');
        
        setTimeout(function() {
          if (next_lesson_url) {
            console.log('[Amsawal] Navigating to:', next_lesson_url);
            window.location.href = next_lesson_url;
          } else {
            console.log('[Amsawal] Navigating to home');
            window.location.href = '/';
          }
        }, 300);
      };
    } else {
      // Failure: show REINTENTAR and IR AL HOME buttons
      var retryBtn = targetDoc.createElement('button');
      retryBtn.className = 'duo-feedback-btn';
      retryBtn.type = 'button';
      retryBtn.textContent = 'REINTENTAR';
      retryBtn.setAttribute('aria-label', 'Reintentar la actividad');
      actions.appendChild(retryBtn);

      retryBtn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('[Amsawal] REINTENTAR button clicked');
        window.location.reload();
      };

      var homeBtn = targetDoc.createElement('button');
      homeBtn.className = 'duo-feedback-btn';
      homeBtn.type = 'button';
      homeBtn.textContent = 'IR AL HOME';
      homeBtn.setAttribute('aria-label', 'Volver al inicio');
      homeBtn.style.marginLeft = '8px';
      actions.appendChild(homeBtn);

      homeBtn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('[Amsawal] IR AL HOME button clicked');
        window.location.href = '/';
      };
    }

    bar.appendChild(iconDiv);
    bar.appendChild(body);
    bar.appendChild(actions);

    // Insert after question buttons if found, otherwise append to placement
    if (insertionPoint && insertionPoint.parentNode) {
      insertionPoint.parentNode.insertBefore(bar, insertionPoint.nextSibling);
      console.log('[Amsawal] Feedback bar inserted after .h5p-question-buttons');
    } else {
      placement.appendChild(bar);
      console.log('[Amsawal] Feedback bar appended to placement');
    }
    document.body.classList.add('duo-feedback-active');
    
    // Force visibility after a short delay to ensure animation runs
    setTimeout(function() {
      bar.style.opacity = '1';
      bar.style.transform = 'translateY(0)';
      console.log('[Amsawal] Feedback bar forced visible');
    }, 50);
    
    // Verify bar is visible after insertion
    setTimeout(function() {
      var rect = bar.getBoundingClientRect();
      var isVisible = rect.width > 0 && rect.height > 0;
      console.log('[Amsawal] Feedback bar visibility check:', {
        isVisible: isVisible,
        width: rect.width,
        height: rect.height,
        top: rect.top,
        left: rect.left
      });
      if (!isVisible) {
        console.warn('[Amsawal] Feedback bar is not visible, forcing display');
        bar.style.display = 'flex';
        bar.style.opacity = '1';
        bar.style.visibility = 'visible';
      }
    }, 400);

    if (isGood) DuoAudio.playSuccess(); else DuoAudio.playError();
    if (isPerfect) fireConfetti();

    if (isGood) {
      const currentPost = window.wpAmsawalAjax && window.wpAmsawalAjax.postId;
      const course = title ? title.split(' ')[0] : 'unknown';
      DuoAnalytics.lessonComplete(currentPost, course, xpEarned, 0, pct);
    }
  }



  // ── Focus Trap Utility (WCAG 2.1 compliant) ──
  const DuoFocusTrap = (function() {
    const FOCUSABLE_SELECTORS = [
      'button:not([disabled])',
      'a[href]',
      'input:not([disabled]):not([type="hidden"])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])',
      'audio[controls]',
      'video[controls]',
      '[contenteditable]:not([contenteditable="false"])'
    ].join(', ');
    
    let activeTraps = [];
    
    function getFocusableElements(container) {
      const elements = container.querySelectorAll(FOCUSABLE_SELECTORS);
      return Array.from(elements).filter(function(el) {
        return el.offsetParent !== null && 
               getComputedStyle(el).visibility !== 'hidden' &&
               !el.hasAttribute('inert');
      });
    }
    
    function trap(e) {
      if (e.key !== 'Tab') return;
      
      const trap = activeTraps[activeTraps.length - 1];
      if (!trap) return;
      
      const focusable = getFocusableElements(trap.container);
      if (focusable.length === 0) return;
      
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      
      if (e.shiftKey) {
        // Shift + Tab: going backwards
        if (document.activeElement === first) {
          e.preventDefault();
          last.focus();
        }
      } else {
        // Tab: going forwards
        if (document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    }
    
    return {
      create: function(container, options) {
        const opts = options || {};
        const previouslyFocused = document.activeElement;
        const focusable = getFocusableElements(container);
        
        // Add to active traps stack
        const trapIndex = activeTraps.length;
        activeTraps.push({
          container: container,
          previouslyFocused: previouslyFocused,
          handlers: {}
        });
        
        // Focus first element (or specified element)
        setTimeout(function() {
          const focusTarget = opts.initialFocus || focusable[0] || container;
          if (focusTarget && typeof focusTarget.focus === 'function') {
            focusTarget.focus();
          }
        }, 50);
        
        // Add trap handler only if not already added
        if (activeTraps.length === 1) {
          document.addEventListener('keydown', trap, { capture: true });
        }
        
        // Return destroy function
        return function destroy() {
          const trapIndex = activeTraps.findIndex(function(t) { 
            return t.container === container; 
          });
          
          if (trapIndex === -1) return;
          
          const trap = activeTraps[trapIndex];
          
          // Remove from stack
          activeTraps.splice(trapIndex, 1);
          
          // Remove trap handler if no more traps
          if (activeTraps.length === 0) {
            document.removeEventListener('keydown', trap, { capture: true });
          }
          
          // Restore focus
          if (trap.previouslyFocused && 
              typeof trap.previouslyFocused.focus === 'function' &&
              trap.previouslyFocused.offsetParent !== null) {
            trap.previouslyFocused.focus();
          }
        };
      }
    };
  })();
  
  // ── Accessible Modal Utility ──
  const DuoModal = (function() {
    let currentModal = null;
    let destroyFocusTrap = null;

    function create(content, options) {
      const opts = options || {};
      
      // Create overlay
      const overlay = document.createElement('div');
      overlay.className = 'duo-modal-overlay';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      if (opts.ariaLabel) {
        overlay.setAttribute('aria-labelledby', opts.ariaLabel);
      }
      
      // Create card
      const card = document.createElement('div');
      card.className = 'duo-modal-card';
      // Append content: accepts DOM Node (preferred) or safe HTML string.
      if (content instanceof DocumentFragment || content instanceof HTMLElement) {
        card.appendChild(content);
      } else if (typeof content === 'string') {
        // Fallback only for trusted HTML built from i18n literals.
        card.textContent = '';
        const range = document.createRange();
        range.selectNode(card);
        card.appendChild(range.createContextualFragment(content));
      }

      overlay.appendChild(card);
      document.body.appendChild(overlay);
      currentModal = overlay;
      
      // Trigger open animation
      requestAnimationFrame(function() {
        overlay.classList.add('is-open');
      });
      
      // Setup focus trap
      destroyFocusTrap = DuoFocusTrap.create(card, {
        initialFocus: card.querySelector('[autofocus]') || 
                      card.querySelector('.duo-modal-close') ||
                      card.querySelector('button')
      });
      
      // Close handlers
      const closeBtn = card.querySelector('.duo-modal-close');
      if (closeBtn) {
        closeBtn.addEventListener('click', destroy);
      }
      
      overlay.addEventListener('click', function(e) {
        if (e.target === overlay && opts.closeOnOverlay !== false) {
          destroy();
        }
      });
      
      document.addEventListener('keydown', escHandler);
      
      return {
        element: overlay,
        card: card,
        destroy: destroy
      };
      
      function escHandler(e) {
        if (e.key === 'Escape' && opts.closeOnEscape !== false) {
          destroy();
        }
      }
      
      function destroy() {
        overlay.classList.remove('is-open');
        
        if (destroyFocusTrap) {
          destroyFocusTrap();
          destroyFocusTrap = null;
        }
        
        document.removeEventListener('keydown', escHandler);
        
        setTimeout(function() {
          if (overlay.parentNode) {
            overlay.remove();
          }
          if (currentModal === overlay) {
            currentModal = null;
          }
        }, 300); // Match animation duration
        
        if (opts.onClose) {
          opts.onClose();
        }
      }
    }
    
    return { create: create };
  })();
  
  // ── Performance: Lazy Loading Images (Native + Intersection Observer) ──
  const DuoLazyLoad = (function() {
    let observer = null;
    let loadedImages = new Set();
    
    function loadImage(img) {
      if (!img || loadedImages.has(img)) return;
      
      const src = img.dataset.src;
      const srcset = img.dataset.srcset;
      
      if (src) {
        img.src = src;
        loadedImages.add(img);
        
        // Remove placeholder class when loaded
        img.addEventListener('load', function() {
          img.classList.remove('lazy');
          img.classList.add('loaded');
        });
      }
      
      if (srcset) {
        img.srcset = srcset;
      }
    }
    
    function observeAll() {
      // Native lazy loading support
      if ('loading' in HTMLImageElement.prototype) {
        document.querySelectorAll('img[loading="lazy"]').forEach(function(img) {
          if (img.dataset.src) {
            img.src = img.dataset.src;
          }
          if (img.dataset.srcset) {
            img.srcset = img.dataset.srcset;
          }
          loadedImages.add(img);
        });
        return;
      }
      
      // Fallback: Intersection Observer
      if (!('IntersectionObserver' in window)) {
        // No observer support, load all images
        document.querySelectorAll('img[data-src]').forEach(loadImage);
        return;
      }
      
      if (observer) observer.disconnect();
      
      observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) {
            const img = entry.target;
            loadImage(img);
            observer.unobserve(img);
          }
        });
      }, {
        rootMargin: '50px 0px', // Load 50px before visible
        threshold: 0.01
      });
      
      document.querySelectorAll('img[data-src]').forEach(function(img) {
        observer.observe(img);
      });
    }
    
    return {
      init: observeAll,
      loadImage: loadImage,
      // Manual trigger for dynamically added content
      refresh: function() {
        setTimeout(observeAll, 100);
      }
    };
  })();
  
  // ── Performance: Critical CSS Loader ──
  const DuoCriticalCSS = (function() {
    function loadNonCriticalCSS() {
      // Load non-critical CSS after initial render
      const link = document.createElement('link');
      link.rel = 'preload';
      link.as = 'style';
      link.href = '/wp-content/plugins/amsawal/css/wp-amsawal-style-h5p.css';
      link.onload = function() {
        this.onload = null;
        this.rel = 'stylesheet';
      };
      document.head.appendChild(link);
    }
    
    return {
      init: function() {
        // Load non-critical CSS after page load
        if (document.readyState === 'complete') {
          loadNonCriticalCSS();
        } else {
          window.addEventListener('load', loadNonCriticalCSS);
        }
      }
    };
  })();
  
  // ── Performance: Animation Optimizer (GPU acceleration) ──
  const DuoAnimationOptimizer = (function() {
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    
    // Check for GPU acceleration support
    const supportsGPU = (function() {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
      return !!ctx;
    })();
    
    function optimizeElement(el) {
      if (prefersReducedMotion.matches) {
        el.style.animation = 'none';
        return;
      }
      
      // Force GPU acceleration for animations
      if (supportsGPU) {
        el.style.willChange = 'transform, opacity';
        el.style.transform = 'translateZ(0)';
        el.style.backfaceVisibility = 'hidden';
      }
    }
    
    function optimizeAll() {
      // Optimize animated elements
      document.querySelectorAll('.duo-node-circle, .duo-confetti-canvas, .duo-level-up-icon, [class*="duo-"]').forEach(optimizeElement);
    }
    
    return {
      init: optimizeAll,
      prefersReducedMotion: prefersReducedMotion,
      supportsGPU: supportsGPU
    };
  })();
  
  // ── Initialize Performance Optimizations ──
  document.addEventListener('DOMContentLoaded', function() {

// F12-6: Intersection Observer removed - causing visibility issues


// F12-4: Ripple effect on button clicks
function createRipple(event) {
    if (prefersReducedMotion) return;
    
    const button = event.currentTarget;
    const circle = document.createElement('span');
    const diameter = Math.max(button.clientWidth, button.clientHeight);
    const radius = diameter / 2;
    
    const rect = button.getBoundingClientRect();
    
    circle.style.width = circle.style.height = `${diameter}px`;
    circle.style.left = `${event.clientX - rect.left - radius}px`;
    circle.style.top = `${event.clientY - rect.top - radius}px`;
    circle.classList.add('duo-ripple');
    
    const ripple = button.querySelector('.duo-ripple');
    if (ripple) {
        ripple.remove();
    }
    
    button.appendChild(circle);
    
    setTimeout(() => circle.remove(), 600);
}

// Attach ripple to all buttons
document.addEventListener('click', function(e) {
    const button = e.target.closest('.duo-activity, .duo-test-btn, .duo-info-btn, .duo-course-btn, .duo-unit-guide-btn');
    if (button) {
        createRipple(e);
    }
});


// F11-3: Client-side translation system
const DuoI18n = {
    currentLang: 'es_ES',
    translations: {},
    
    async load(lang) {
        this.currentLang = lang;
        try {
            const response = await fetch(`/wp-content/plugins/wp-amsawal/languages/${lang}.json`);
            this.translations = await response.json();
        } catch (e) {
            this.translations = {};
        }
    },
    
    t(key, params = {}) {
        let text = this.translations[key] || key;
        for (const [param, value] of Object.entries(params)) {
            text = text.replace(`{${param}}`, value);
        }
        return text;
    },
    
    updateDOM() {
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            el.textContent = this.t(key);
        });
        
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            el.placeholder = this.t(key);
        });
        
        document.querySelectorAll('[data-i18n-aria]').forEach(el => {
            const key = el.getAttribute('data-i18n-aria');
            el.setAttribute('aria-label', this.t(key));
        });
    }
};

// Load translations on init
if (typeof DuoI18n !== 'undefined') {
    DuoI18n.load(DuoI18n.currentLang).then(() => {
        DuoI18n.updateDOM();
    });
}

    DuoLazyLoad.init();
    DuoCriticalCSS.init();
    DuoAnimationOptimizer.init();
  });
  
  // ── Analytics: Event Tracking System ──
  const DuoAnalytics = (function() {
    let queue = [];
    let userId = null;
    let nonce = null;
    let ajaxUrl = null;
    
    // Get config from wpAmsawalAjax if available
    function init() {
      if (window.wpAmsawalAjax) {
        userId = window.wpAmsawalAjax.userId || null;
        nonce = window.wpAmsawalAjax.trackNonce || null;
        ajaxUrl = window.wpAmsawalAjax.ajaxUrl || '/wp-admin/admin-ajax.php';
      }
      
      // Process queued events
      queue.forEach(function(item) {
        send(item);
      });
      queue = [];
    }
    
    function send(event) {
      if (!userId || !nonce || !ajaxUrl) {
        queue.push(event);
        return;
      }
      
      // Send via fetch (non-blocking)
      const formData = new FormData();
      formData.append('action', 'wp_amsawal_track_event');
      formData.append('_ajax_nonce', nonce);
      formData.append('event_type', event.event_type);
      formData.append('event_data', JSON.stringify(event.data || {}));
      formData.append('post_id', event.post_id || '');
      formData.append('course', event.course || '');
      
      // Use sendBeacon if available for better reliability
      if (navigator.sendBeacon) {
        navigator.sendBeacon(ajaxUrl, formData);
      } else {
        // Fallback to fetch
        fetch(ajaxUrl, {
          method: 'POST',
          body: formData,
          keepalive: true
        }).catch(function() {
          // Silent fail - analytics should not break UX
        });
      }
    }
    
    return {
      init: init,
      
      // Track lesson start
      lessonStart: function(postId, course) {
        send({
          event_type: 'lesson_start',
          data: { source: document.referrer },
          post_id: postId,
          course: course
        });
      },
      
      // Track lesson complete
      lessonComplete: function(postId, course, xpEarned, timeSpent) {
        send({
          event_type: 'lesson_complete',
          data: {
            xp_earned: xpEarned,
            time_spent: timeSpent,
            pct: arguments[4] || 0
          },
          post_id: postId,
          course: course
        });
      },
      
      // Track quiz answer
      quizAnswer: function(postId, course, correct, questionIndex) {
        send({
          event_type: 'quiz_answer',
          data: {
            correct: correct,
            question_index: questionIndex
          },
          post_id: postId,
          course: course
        });
      },
      
      // Track streak milestone
      streakMilestone: function(days) {
        send({
          event_type: 'streak_milestone',
          data: { milestone: days }
        });
      },
      
      // Track achievement earned
      achievementEarned: function(achievementId, achievementName) {
        send({
          event_type: 'achievement_earned',
          data: {
            achievement_id: achievementId,
            achievement_name: achievementName
          }
        });
      },
      
      // Track level up
      levelUp: function(course, oldLevel, newLevel) {
        send({
          event_type: 'level_up',
          data: {
            old_level: oldLevel,
            new_level: newLevel
          },
          course: course
        });
      },
      
      // Track custom event
      track: function(eventType, data, postId, course) {
        send({
          event_type: eventType,
          data: data,
          post_id: postId,
          course: course
        });
      }
    };
  })();
  
  // Initialize analytics when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {

    DuoAnalytics.init();
  });
  
  // ── H5P Tracker (xAPI) + Essay Evaluator + Completion Feedback ──
  function initH5PTracker() {
    // Essay AI Evaluation
    const essaySubmitBtns = document.querySelectorAll('.duo-ai-essay-submit');
    essaySubmitBtns.forEach(function(btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = 'true';
      btn.addEventListener('click', function() {
        const container = btn.closest('.duo-ai-essay');
        const textarea = container.querySelector('.duo-ai-essay-textarea');
        const feedback = container.querySelector('.duo-ai-essay-feedback');
        const text = textarea.value.trim();
        if (!text) { alert(t('writeSomething', 'Por favor, escribe algo primero.')); return; }

        DuoAudio.playClick();
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        btn.textContent = t('analyzing', '⏳ Analizando...');
        feedback.style.display = 'none';

        const ajaxUrl = document.querySelector('.duo-tutor-ajaxurl') || {value: '/wp-admin/admin-ajax.php'};
        const nonce = btn.getAttribute('data-nonce');
        const data = new URLSearchParams();
        data.append('action', 'wp_amsawal_evaluate_essay');
        data.append('text', text);
        data.append('_ajax_nonce', nonce);

        const controller = new AbortController();
        const timeoutId = setTimeout(function() { controller.abort(); }, 90000);

        fetch(ajaxUrl.value, {
          method: 'POST', body: data,
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          signal: controller.signal
        }).then(function(resp) { return resp.json(); }).then(function(res) {
          clearTimeout(timeoutId);
          btn.textContent = t('submit', '✅ Enviar para corrección (IA)');
          btn.disabled = false;
          btn.removeAttribute('aria-busy');

          if (res.success && res.data) {
            let bar = document.querySelector('.duo-feedback-bar');
            if (!bar) {
              bar = document.createElement('div');
              bar.className = 'duo-feedback-bar';
              bar.setAttribute('role', 'status');
              bar.setAttribute('aria-live', 'polite');
              bar.setAttribute('aria-atomic', 'true');
              document.body.appendChild(bar);
            }

            const isWin = res.data.success;
            bar.className = 'duo-feedback-bar ' + (isWin ? 'is-correct' : 'is-wrong');
            document.body.classList.add('duo-feedback-active');

            while (bar.firstChild) { bar.removeChild(bar.firstChild); }
            // Build feedback bar DOM safely using existing duo-feedback-* classes.
            var fbBody = document.createElement('div');
            fbBody.className = 'duo-feedback-body';

            var fbTitle = document.createElement('h3');
            fbTitle.className = 'duo-feedback-title';
            fbTitle.textContent = isWin ? '¡Excelente trabajo!' : '¡Casi lo tienes!';
            fbBody.appendChild(fbTitle);

            var fbPara = document.createElement('p');
            fbPara.className = 'duo-feedback-message';
            fbPara.textContent = res.data.feedback || '';
            fbBody.appendChild(fbPara);

            if (res.data.corrected_text && res.data.corrected_text !== text) {
              var fbCorrection = document.createElement('div');
              fbCorrection.className = 'duo-feedback-message';
              fbCorrection.style.marginTop = '8px';
              fbCorrection.appendChild(document.createElement('strong')).textContent = 'Corrección:';
              fbCorrection.appendChild(document.createTextNode(' ' + res.data.corrected_text));
              fbBody.appendChild(fbCorrection);
            }

            var fbActions = document.createElement('div');
            fbActions.className = 'duo-feedback-actions';
            var fbBtn = document.createElement('button');
            fbBtn.className = 'duo-feedback-btn duo-ripple';
            fbBtn.textContent = isWin ? 'CONTINUAR' : 'ENTENDIDO';
            fbActions.appendChild(fbBtn);

            bar.appendChild(fbBody);
            bar.appendChild(fbActions);
            bar.style.display = 'block';
            setTimeout(function() { bar.classList.add('active'); }, 50);

            if (isWin) DuoAudio.playSuccess(); else DuoAudio.playError();
            if (isWin && res.data && res.data.is_level_up) fireConfetti();

            if (window.wpAmsawalAjax && window.wpAmsawalAjax.trackNonce) {
              const postId = window.wpAmsawalAjax.postId || 0;
              const trackData = new URLSearchParams();
              trackData.append('action', 'wp_amsawal_track_item');
              trackData.append('item_text', 'essay-' + postId);
              trackData.append('success', isWin ? '1' : '0');
              trackData.append('_ajax_nonce', window.wpAmsawalAjax.trackNonce);
              fetch((window.wpAmsawalAjax.ajaxUrl || ajaxUrl.value), {
                method: 'POST', body: trackData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
              }).catch(function() { /* silent: track sync is best-effort */ });
            }

            bar.querySelector('.duo-feedback-btn').onclick = function() {
              bar.classList.remove('active');
              document.body.classList.remove('duo-feedback-active');
              setTimeout(function() { bar.style.display = 'none'; }, 400);
              if (isWin) navigate('/');
            };

            // Show coins earned via toast
            if (res.data.coins && res.data.coins > 0) {
              showRankToast('+' + res.data.coins + ' 🪙', 'Corrección completada. ¡Monedas ganadas!', 'success');
            }
          } else {
            /* Essay evaluation returned non-success; handled via alert() above */
            alert(t('evaluateFailed', 'No se pudo evaluar el ensayo. Inténtalo de nuevo en unos segundos.'));
          }
        }).catch(function(err) {
          clearTimeout(timeoutId);
          /* Essay Evaluate Error: handled below via UI retry button */
          const isAbort = err && err.name === 'AbortError';
          btn.textContent = isAbort ? t('timeoutRetry', '⏱️ Tiempo agotado (Reintentar)') : t('errorRetry', '❌ Error (Reintentar)');
          btn.disabled = false;
          btn.removeAttribute('aria-busy');
        });
      });
    });

    // H5P xAPI tracker — Bridge to GamiPress via server-side transient
    //
    // Flow: H5P fires xAPI → we detect completion → server-side h5p_alter_user_result
    // hook stores feedback in transient → we poll the bridge AJAX endpoint → show feedback bar
    //
    // GamiPress-H5P integration handles rank/achievement/points natively.
    // This bridge only reads the result and displays the feedback bar.
    if (window.H5P && window.H5P.externalDispatcher && !window.H5P._amsawalTracked) {
      window.H5P._amsawalTracked = true;
      console.log('[Amsawal] H5P xAPI listener registered');
      
      window.H5P.externalDispatcher.on('xAPI', function(event) {
        console.log('[Amsawal] xAPI event fired:', event.data);
        
        var stmt = event.data && event.data.statement ? event.data.statement : null;
        if (!stmt) {
          console.warn('[Amsawal] xAPI event has no statement');
          return;
        }

        var verbId = stmt.verb && stmt.verb.id ? stmt.verb.id : '';
        var hasResult = stmt.result ? true : false;
        var isComplete = stmt.result && stmt.result.completion === true;
        var hasScore = stmt.result && stmt.result.score ? true : false;
        
        console.log('[Amsawal] xAPI details:', {
          verb: verbId,
          hasResult: hasResult,
          isComplete: isComplete,
          hasScore: hasScore
        });

        var isCompletion = verbId === 'http://adlnet.gov/expapi/verbs/completed' ||
                           verbId === 'http://adlnet.gov/expapi/verbs/answered' ||
                           verbId.indexOf('completed') !== -1 ||
                           verbId.indexOf('answered') !== -1 ||
                           isComplete ||
                           (hasScore && verbId.indexOf('question') !== -1);
        
        if (!isCompletion) {
          console.log('[Amsawal] xAPI event not a completion, skipping');
          return;
        }

        console.log('[Amsawal] ✓ xAPI completion detected:', verbId);
        
        // Prevent duplicate processing
        if (window.H5P._amsawalProcessing) {
          console.log('[Amsawal] Already processing, skipping duplicate');
          return;
        }
        window.H5P._amsawalProcessing = true;
        
        // xAPI fired → H5P has already saved the result server-side (h5p_alter_user_result)
        // Now poll the GamiPress bridge for feedback data
        amsawalPollBridgeFeedback();
      });
    }
  }

  // ── Poll GamiPress bridge for pending feedback ──
  // Retries with backoff because GamiPress hooks may fire slightly after H5P saves.
  function amsawalPollBridgeFeedback() {
    console.log('[Amsawal] Starting bridge polling...');
    
    if (!window.wpAmsawalBridge || !window.wpAmsawalBridge.nonce) {
      console.warn('[Amsawal] Bridge nonce not available, falling back to track_item');
      amsawalFallbackTrackItem();
      return;
    }

    var ajaxUrl = window.wpAmsawalBridge.ajaxUrl || '/wp-admin/admin-ajax.php';
    var maxAttempts = 10;
    var delays = [300, 600, 1000, 1500, 2000, 2500, 3000, 4000, 5000, 6000];
    var attempt = 0;

    function poll() {
      if (attempt >= maxAttempts) {
        console.warn('[Amsawal] Bridge polling exhausted after ' + maxAttempts + ' attempts');
        amsawalFallbackTrackItem();
        return;
      }

      attempt++;
      console.log('[Amsawal] Bridge poll attempt ' + attempt + '/' + maxAttempts);

      var data = new URLSearchParams();
      data.append('action', 'amsawal_get_gamipress_feedback');
      data.append('_ajax_nonce', window.wpAmsawalBridge.nonce);

      fetch(ajaxUrl, {
        method: 'POST',
        body: data,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        credentials: 'same-origin'
      }).then(function(resp) { 
        console.log('[Amsawal] Bridge response status:', resp.status);
        return resp.json(); 
      }).then(function(res) {
        console.log('[Amsawal] Bridge response:', res);
        
        if (res.success && res.data) {
          console.log('[Amsawal] ✓ Feedback received from bridge');
          window.H5P._amsawalProcessing = false;
          
          var fb = res.data;

          // Update lives display if available
          if (typeof fb.lives !== 'undefined') {
            var livesEl = document.querySelector('.duo-topbar-lives');
            if (livesEl) {
              livesEl.textContent = '';
              livesEl.appendChild(document.createTextNode('❤️ ' + String(fb.lives)));
            }
          }

          // Show the feedback bar with data from GamiPress
          showH5PFeedbackBar({
            pct: fb.pct || 0,
            xp_earned: fb.xp_earned || 0,
            coins: fb.coins || 0,
            title: fb.title || '',
            rank_up: fb.rank_up || false,
            new_level: fb.new_level || 0,
            next_lesson_url: fb.next_lesson_url || ''
          });
        } else {
          console.log('[Amsawal] No feedback yet, retrying...');
          if (attempt < maxAttempts) {
            setTimeout(poll, delays[attempt] || 5000);
          } else {
            amsawalFallbackTrackItem();
          }
        }
      }).catch(function(err) {
        console.error('[Amsawal] Bridge poll error:', err);
        if (attempt < maxAttempts) {
          setTimeout(poll, delays[attempt] || 5000);
        } else {
          amsawalFallbackTrackItem();
        }
      });
    }

    // Initial delay: give server-side hooks time to process
    setTimeout(poll, delays[0]);
  }

  // ── Fallback: use old track_item endpoint if bridge is unavailable ──
  function amsawalFallbackTrackItem() {
    console.log('[Amsawal] Using fallback track_item');
    window.H5P._amsawalProcessing = false;
    
    if (!window.wpAmsawalAjax || !window.wpAmsawalAjax.trackNonce) {
      console.warn('[Amsawal] track_item nonce not available, showing generic feedback');
      showH5PFeedbackBar({
        pct: 100,
        xp_earned: 10,
        coins: 0,
        title: 'Actividad completada',
        rank_up: false,
        new_level: 0,
        next_lesson_url: ''
      });
      return;
    }

    var ajaxUrl = window.wpAmsawalAjax.ajaxUrl || '/wp-admin/admin-ajax.php';
    var itemText = 'h5p-activity';
    var h5pContentId = '';

    try {
      var iframe = document.querySelector('.h5p-iframe, iframe.h5p-iframe');
      if (iframe && iframe.contentWindow && iframe.contentWindow.H5PIntegration) {
        h5pContentId = String(iframe.contentWindow.H5PIntegration.contents || '');
      }
    } catch(e) {
      console.warn('[Amsawal] Could not get H5P content ID from iframe:', e);
    }
    
    if (!h5pContentId) {
      var wrapper = document.querySelector('[data-h5p-content-id]');
      if (wrapper) h5pContentId = wrapper.getAttribute('data-h5p-content-id');
    }
    
    console.log('[Amsawal] H5P content ID:', h5pContentId);

    var data = new URLSearchParams();
    data.append('action', 'wp_amsawal_track_item');
    data.append('item_text', itemText);
    data.append('success', '1');
    if (h5pContentId) data.append('content_id', h5pContentId);
    data.append('_ajax_nonce', window.wpAmsawalAjax.trackNonce);

    fetch(ajaxUrl, {
      method: 'POST', 
      body: data,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      credentials: 'same-origin'
    }).then(function(resp) { 
      console.log('[Amsawal] Fallback response status:', resp.status);
      return resp.json(); 
    }).then(function(res) {
      console.log('[Amsawal] Fallback response:', res);
      if (res.success && res.data && typeof res.data.pct !== 'undefined') {
        showH5PFeedbackBar({
          pct: res.data.pct,
          xp_earned: res.data.xp_earned || 0,
          coins: res.data.coins || 0,
          title: itemText,
          rank_up: res.data.rank_up || false,
          new_level: res.data.new_level || 0,
          next_lesson_url: res.data.next_lesson_url || ''
        });
      } else {
        console.warn('[Amsawal] Fallback did not return expected data, showing generic feedback');
        showH5PFeedbackBar({
          pct: 100,
          xp_earned: 10,
          coins: 0,
          title: 'Actividad completada',
          rank_up: false,
          new_level: 0,
          next_lesson_url: ''
        });
      }
    }).catch(function(err) {
      console.error('[Amsawal] Fallback error:', err);
      showH5PFeedbackBar({
        pct: 100,
        xp_earned: 10,
        coins: 0,
        title: 'Actividad completada',
        rank_up: false,
        new_level: 0,
        next_lesson_url: ''
      });
    });
  }

  // ── Check for deferred feedback on page load ──
  // If the user completed an activity and navigated before the bridge polling
  // picked up the result, the feedback is injected via wpAmsawalFeedback global.
  function amsawalCheckDeferredFeedback() {
    if (window.wpAmsawalFeedback && typeof window.wpAmsawalFeedback === 'object' && window.wpAmsawalFeedback.type) {
      var fb = window.wpAmsawalFeedback;
      showH5PFeedbackBar({
        pct: fb.pct || 0,
        xp_earned: fb.xp_earned || 0,
        coins: fb.coins || 0,
        title: fb.title || '',
        rank_up: fb.rank_up || false,
        new_level: fb.new_level || 0,
        next_lesson_url: fb.next_lesson_url || ''
      });
    }
  }

  // Run deferred feedback check after DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', amsawalCheckDeferredFeedback);
  } else {
    amsawalCheckDeferredFeedback();
  }

  initH5PTracker();
  setTimeout(initH5PTracker, 2000);

  // ═══════════════════════════════════════════════════════
  // LEADERBOARD — Real-time polling + position animations
  // ═══════════════════════════════════════════════════════

  function initLeaderboard() {
    var boards = document.querySelectorAll('.duo-leaderboard');
    boards.forEach(function(board) {
      if (board.dataset.leaderboardInit) return;
      board.dataset.leaderboardInit = 'true';

      var type      = board.getAttribute('data-type') || 'monedas';
      var limit     = board.getAttribute('data-limit') || '10';
      var friends   = board.getAttribute('data-friends') || '0';
      var nonce     = board.getAttribute('data-nonce') || '';
      var period    = board.getAttribute('data-period') || 'all-time';
      var doVirtualize = board.getAttribute('data-virtualize') === '1';
      var list      = board.querySelector('.duo-leaderboard-list');
      var refreshBtn = board.querySelector('.duo-leaderboard-refresh');
      var liveDot   = board.querySelector('.duo-leaderboard-live-dot');
      var srStatus  = board.querySelector('.duo-leaderboard-sr-status');
      var polling   = true;
      var timer     = null;
      var isFetching = false;
      var fetchRetryCount = 0;
      var prevData  = {}; // userId → position

      // ── Period tab switching ──
      var tabs = board.querySelectorAll('.duo-leaderboard-tab');
      tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
          var newPeriod = tab.getAttribute('data-period');
          if (newPeriod === period) return;
          period = newPeriod;

          // Update tab states.
          tabs.forEach(function(t) {
            t.classList.remove('active');
            t.setAttribute('aria-selected', 'false');
            t.setAttribute('tabindex', '-1');
          });
          tab.classList.add('active');
          tab.setAttribute('aria-selected', 'true');
          tab.setAttribute('tabindex', '0');
          tab.focus();

          // Show loading state.
          board.setAttribute('aria-busy', 'true');
          if (list) list.setAttribute('aria-busy', 'true');

          // Fetch with new period.
          fetchLeaderboard();
        });

        // Keyboard navigation for tabs (WAI-ARIA Tabs Pattern).
        tab.addEventListener('keydown', function(e) {
          var tabsArr = Array.from(tabs);
          var idx = tabsArr.indexOf(tab);
          var newIdx = idx;

          if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            e.preventDefault();
            newIdx = (idx + 1) % tabsArr.length;
          } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            e.preventDefault();
            newIdx = (idx - 1 + tabsArr.length) % tabsArr.length;
          } else if (e.key === 'Home') {
            e.preventDefault();
            newIdx = 0;
          } else if (e.key === 'End') {
            e.preventDefault();
            newIdx = tabsArr.length - 1;
          } else if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            tab.click();
            return;
          }

          if (newIdx !== idx) {
            tabsArr[newIdx].click();
          }
        });
      });

      // Snapshot current positions from DOM
      function snapshotPositions() {
        prevData = {};
        if (!list) return;
        list.querySelectorAll('.duo-leaderboard-card').forEach(function(card) {
          var uid = parseInt(card.getAttribute('data-user-id'), 10);
          var pos = parseInt(card.getAttribute('data-position'), 10);
          if (uid && pos) prevData[uid] = pos;
        });
      }
      snapshotPositions();

      // Fetch fresh data (with concurrent-fetch guard)
      function fetchLeaderboard() {
        if (isFetching || !polling || !list || !nonce) return;
        if (!window.wpAmsawalAjax || !window.wpAmsawalAjax.ajaxUrl) return;
        isFetching = true;

        var data = new URLSearchParams();
        data.append('action', 'amsawal_leaderboard_refresh');
        data.append('type', type);
        data.append('limit', limit);
        data.append('friends', friends);
        data.append('period', period);
        data.append('_ajax_nonce', nonce);

        if (liveDot) liveDot.classList.add('is-pulsing');

        fetch(window.wpAmsawalAjax.ajaxUrl, {
          method: 'POST',
          body: data,
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
        })
        .then(function(resp) { return resp.json(); })
        .then(function(res) {
          isFetching = false;
          if (liveDot) liveDot.classList.remove('is-pulsing');
          board.setAttribute('aria-busy', 'false');
          if (list) list.setAttribute('aria-busy', 'false');
          if (!res.success || !res.data || !res.data.users) {
            throw new Error('Invalid leaderboard response');
          }

          fetchRetryCount = 0; // Reset on success

          // ── Rank-change toast: detect if current user's position changed ──
          var meUser = null;
          res.data.users.forEach(function(u) {
            if (u.is_me) meUser = u;
          });
          if (meUser && prevData[meUser.id] && prevData[meUser.id] !== meUser.position) {
            var oldPos = prevData[meUser.id];
            var newPos = meUser.position;
            var diff = oldPos - newPos; // positive = moved UP
            var title = diff > 0 ? '⬆ ¡Subiste en el ranking!' : '⬇ Bajaste en el ranking';
            var msg = diff > 0
              ? 'Pasaste del puesto #' + oldPos + ' al #' + newPos + ' (+' + diff + ')'
              : 'Pasaste del puesto #' + oldPos + ' al #' + newPos + ' (' + diff + ')';
            showRankToast(title, msg, diff > 0 ? 'success' : 'warning');

            // Screen reader announcement (WCAG 4.1.3).
            if (srStatus) {
              srStatus.textContent = diff > 0
                ? 'Subiste del puesto ' + oldPos + ' al ' + newPos
                : 'Bajaste del puesto ' + oldPos + ' al ' + newPos;
              setTimeout(function() { srStatus.textContent = ''; }, 3000);
            }
          }

          updateBoard(res.data.users, prevData);
          // Update prevData for next poll
          prevData = {};
          res.data.users.forEach(function(u) { prevData[u.id] = u.position; });

          // Apply virtualization for large lists.
          if (doVirtualize) applyVirtualization();
        })
        .catch(function(err) {
          isFetching = false;
          if (liveDot) liveDot.classList.remove('is-pulsing');
          board.setAttribute('aria-busy', 'false');
          if (list) list.setAttribute('aria-busy', 'false');

          // Retry with exponential backoff (max 3 retries)
          if (fetchRetryCount < 3) {
            fetchRetryCount++;
            var delay = Math.min(1000 * Math.pow(2, fetchRetryCount), 8000); // 2s, 4s, 8s
            setTimeout(fetchLeaderboard, delay);
          }
        });
      }

      // Update DOM with diff-aware animations
      function updateBoard(users, oldPositions) {
        if (!list) return;

        // Build new HTML
        var rankEmoji = {1: '🥇', 2: '🥈', 3: '🥉'};
        var rankLabel = {1: t('rank1', 'Primero'), 2: t('rank2', 'Segundo'), 3: t('rank3', 'Tercero')};
        var xpLabel = type === 'monedas' ? '🪙' : '⭐';
        var xpAriaLabel = type === 'monedas' ? t('coinsLabel', 'monedas') : t('xpLabel', 'experiencia');
        var frag = document.createDocumentFragment();

        if (!users.length) {
          var emptyDiv = document.createElement('div');
          emptyDiv.className = 'duo-empty-state';
          var artDiv = document.createElement('div');
          artDiv.className = 'duo-empty-state__art';
          artDiv.setAttribute('aria-hidden', 'true');
          artDiv.textContent = '( •_•)';
          var emptyP = document.createElement('p');
          emptyP.textContent = t('lbEmpty', 'Aún no hay jugadores clasificados. ¡Sé el primero!');
          emptyDiv.appendChild(artDiv);
          emptyDiv.appendChild(emptyP);
          frag.appendChild(emptyDiv);
        }

        users.forEach(function(u) {
          var pos    = u.position;
          var emoji  = rankEmoji[pos] || String(pos);
          var change = u.position_change || 0;
          var cardClass = 'duo-leaderboard-card';
          if (u.is_me) cardClass += ' duo-leaderboard-card--me';
          if (pos > 10) cardClass += ' duo-leaderboard-card--far';

          // Detect rank change for animation
          var oldPos = oldPositions[u.id] || 0;
          var rankClass = '';
          if (oldPos && oldPos !== pos) {
            rankClass = pos < oldPos ? ' duo-leaderboard-rank-up' : ' duo-leaderboard-rank-down';
          }

          var card = document.createElement('div');
          card.className = cardClass;
          card.setAttribute('data-user-id', String(u.id));
          card.setAttribute('data-position', String(pos));
          card.setAttribute('data-xp', String(u.xp));

          var rankSpan = document.createElement('span');
          rankSpan.className = 'duo-leaderboard-rank' + rankClass;
          rankSpan.setAttribute('aria-label', rankLabel[pos] || t('rankN', 'Puesto') + ' ' + pos);
          rankSpan.textContent = emoji;
          card.appendChild(rankSpan);

          // Avatar or initials
          var avatarUrl = u.avatar_url || '';
          var avatarSpan = document.createElement('span');
          avatarSpan.className = 'duo-leaderboard-avatar';
          if (avatarUrl && avatarUrl.indexOf('gravatar.com/avatar/') === -1) {
            var img = document.createElement('img');
            img.src = avatarUrl;
            img.alt = '';
            img.loading = 'lazy';
            img.width = 40;
            img.height = 40;
            avatarSpan.appendChild(img);
          } else {
            avatarSpan.setAttribute('aria-hidden', 'true');
            var initialsSpan = document.createElement('span');
            initialsSpan.className = 'duo-leaderboard-avatar-initials';
            initialsSpan.textContent = (u.name || 'U').charAt(0).toUpperCase();
            avatarSpan.appendChild(initialsSpan);
          }
          card.appendChild(avatarSpan);

          var nameSpan = document.createElement('span');
          nameSpan.className = 'duo-leaderboard-name';
          nameSpan.textContent = u.name || '';
          if (change > 0) {
            nameSpan.appendChild(document.createTextNode(' '));
            var upSpan = document.createElement('span');
            upSpan.className = 'duo-leaderboard-change duo-leaderboard-change--up';
            upSpan.setAttribute('aria-label', 'Subió ' + change + ' puesto(s)');
            upSpan.textContent = '▲' + change;
            nameSpan.appendChild(upSpan);
          } else if (change < 0) {
            nameSpan.appendChild(document.createTextNode(' '));
            var downSpan = document.createElement('span');
            downSpan.className = 'duo-leaderboard-change duo-leaderboard-change--down';
            downSpan.setAttribute('aria-label', 'Bajó ' + Math.abs(change) + ' puesto(s)');
            downSpan.textContent = '▼' + Math.abs(change);
            nameSpan.appendChild(downSpan);
          }
          card.appendChild(nameSpan);

          var xpSpan = document.createElement('span');
          xpSpan.className = 'duo-leaderboard-xp';
          xpSpan.setAttribute('aria-label', xpAriaLabel + ' ' + u.xp.toLocaleString());
          xpSpan.textContent = xpLabel + ' ' + u.xp.toLocaleString();
          card.appendChild(xpSpan);

          frag.appendChild(card);
        });

        // Diff-aware replacement: only replace cards that changed
        var existingCards = list.querySelectorAll('.duo-leaderboard-card');
        var cardMap = {};
        existingCards.forEach(function(c) {
          cardMap[c.getAttribute('data-user-id')] = { el: c, pos: c.getAttribute('data-position'), xp: c.getAttribute('data-xp') };
        });

        // Check if anything changed
        var changed = false;
        users.forEach(function(u) {
          var cur = cardMap[String(u.id)];
          if (!cur || parseInt(cur.pos, 10) !== u.position || parseInt(cur.xp, 10) !== u.xp) changed = true;
        });
        if (users.length !== Object.keys(cardMap).length) changed = true;

        if (changed) {
          list.textContent = '';
          list.appendChild(frag);
          // Announce leaderboard update to screen readers (WCAG 4.1.3)
          list.setAttribute('aria-live', 'polite');
          list.setAttribute('aria-relevant', 'additions text');
          // Flash animation on changed cards
          list.querySelectorAll('.duo-leaderboard-rank-up, .duo-leaderboard-rank-down').forEach(function(rank) {
            var card = rank.closest('.duo-leaderboard-card');
            if (card) {
              card.classList.add('duo-leaderboard-card--flash');
              setTimeout(function() { card.classList.remove('duo-leaderboard-card--flash'); }, 1200);
            }
          });
          // Update live dot
          if (liveDot) {
            liveDot.classList.add('has-update');
            setTimeout(function() { liveDot.classList.remove('has-update'); }, 3000);
          }
        }
      }

      // Inject skeleton loaders before first fetch.
      // Clear any server-rendered cards first to avoid stacking both.
      var isWidget = board.closest('.widget_amsawal_leaderboard');
      var isPodium = board.closest('.duo-league-summary');
      var isCompact = isWidget || isPodium;
      if (isCompact && list) {
        list.textContent = '';
        var skelCount = isPodium ? 3 : 5;
        for (var i = 0; i < skelCount; i++) {
          var skel = document.createElement('div');
          skel.className = 'duo-leaderboard-skeleton';
          skel.setAttribute('aria-hidden', 'true');
          list.appendChild(skel);
        }
      }

      // ── Virtualization (IntersectionObserver for 30+ items) ──
      var virtualObserver = null;
      if (doVirtualize && list && 'IntersectionObserver' in window) {
        var CARD_HEIGHT = 68; // estimated card height in px
        var BUFFER = 5; // extra cards above/below viewport

        function setupVirtualization() {
          virtualObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
              if (entry.isIntersecting) {
                entry.target.style.visibility = 'visible';
                entry.target.style.position = '';
                entry.target.style.height = '';
                entry.target.style.overflow = '';
              }
            });
          }, { root: list, rootMargin: (BUFFER * CARD_HEIGHT) + 'px 0px' });
        }
        setupVirtualization();
      }

      function applyVirtualization() {
        if (!virtualObserver || !list) return;
        var cards = list.querySelectorAll('.duo-leaderboard-card');
        if (cards.length <= 30) return;

        cards.forEach(function(card, idx) {
          // Set fixed height for off-screen cards.
          if (idx < BUFFER || idx > cards.length - BUFFER - 1) {
            card.style.visibility = 'visible';
            card.style.position = '';
            card.style.height = '';
          } else {
            card.style.visibility = 'hidden';
            card.style.position = 'absolute';
            card.style.height = CARD_HEIGHT + 'px';
            card.style.overflow = 'hidden';
            virtualObserver.observe(card);
          }
        });
      }

      // ── Pull-to-refresh (mobile touch gesture) ──
      var pullStartY = 0;
      var isPulling = false;
      if (list && 'ontouchstart' in window) {
        list.addEventListener('touchstart', function(e) {
          if (list.scrollTop === 0) {
            pullStartY = e.touches[0].clientY;
            isPulling = true;
          }
        }, { passive: true });

        list.addEventListener('touchmove', function(e) {
          if (!isPulling) return;
          var pullY = e.touches[0].clientY;
          var pullDist = pullY - pullStartY;
          if (pullDist > 60 && list.scrollTop === 0) {
            list.classList.add('duo-leaderboard-list--pulling');
          }
        }, { passive: true });

        list.addEventListener('touchend', function() {
          if (list.classList.contains('duo-leaderboard-list--pulling')) {
            list.classList.remove('duo-leaderboard-list--pulling');
            fetchLeaderboard();
          }
          isPulling = false;
        }, { passive: true });
      }

      // ── WebSocket real-time (singleton shared connection) ──
      // Global WebSocket manager: one connection serves ALL leaderboard instances on the page.
      if (!window.AmsawalWSManager) {
        window.AmsawalWSManager = (function() {
          var ws = null;
          var wsRetry = 0;
          var wsMaxRetry = (window.wpAmsawalWS && window.wpAmsawalWS.wsMaxRetry) || 10;
          var wsReconnectDelay = (window.wpAmsawalWS && window.wpAmsawalWS.wsReconnect) || 3;
          var wsEnabled = window.wpAmsawalWS && window.wpAmsawalWS.wsEnabled;
          var subscribers = {}; // { type: [callback, callback, ...] }
          var connected = false;
          var reconnectTimer = null;

          function connect() {
            if (!wsEnabled || !window.wpAmsawalWS || !window.wpAmsawalWS.wsUrl) return;
            if (ws && ws.readyState <= 1) return;

            var restBase = (window.wpAmsawalAjax && window.wpAmsawalAjax.restUrl) || '/wp-json';
            fetch(restBase + '/amsawal/v1/ws-token', {
              credentials: 'same-origin',
              headers: { 'X-WP-Nonce': window.wpAmsawalAjax ? window.wpAmsawalAjax.nonce : '' }
            })
            .then(function(r) { return r.json(); })
            .then(function(auth) {
              if (!auth || !auth.token) return;

              try {
                ws = new WebSocket(window.wpAmsawalWS.wsUrl);
              } catch (e) {
                return;
              }

              ws.onopen = function() {
                ws.send(JSON.stringify({ event: 'auth', token: auth.token, userId: auth.userId }));
                // Subscribe to all active types.
                Object.keys(subscribers).forEach(function(t) {
                  ws.send(JSON.stringify({ event: 'subscribe', type: t }));
                });
                wsRetry = 0;
                connected = true;
              };

              ws.onmessage = function(evt) {
                var msg;
                try { msg = JSON.parse(evt.data); } catch (e) { return; }

                if (msg.event === 'leaderboard_update' && msg.type && subscribers[msg.type]) {
                  subscribers[msg.type].forEach(function(cb) {
                    try { cb(msg.data); } catch (e) { /* subscriber error */ }
                  });
                }
              };

              ws.onclose = function() {
                connected = false;
                if (wsRetry < wsMaxRetry) {
                  wsRetry++;
                  reconnectTimer = setTimeout(connect, wsReconnectDelay * wsRetry * 1000);
                }
              };

              ws.onerror = function() {
                connected = false;
              };
            })
            .catch(function() {
              // REST API unavailable — fall back to polling in all instances.
            });
          }

          function subscribe(type, callback) {
            if (!subscribers[type]) {
              subscribers[type] = [];
              // If already connected, subscribe immediately.
              if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ event: 'subscribe', type: type }));
              } else {
                connect(); // Initialize connection if not already.
              }
            }
            subscribers[type].push(callback);
          }

          function isConnected() {
            return connected;
          }

          return {
            subscribe: subscribe,
            isConnected: isConnected,
            init: connect
          };
        })();
      }

      // Subscribe this instance to WS updates.
      var useWebSocket = false;
      window.AmsawalWSManager.subscribe(type, function(data) {
        var users = data && data.users ? data.users : null;
        if (!users) {
          fetchLeaderboard();
          return;
        }

        var meUser = null;
        users.forEach(function(u) { if (u.is_me) meUser = u; });
        if (meUser && prevData[meUser.id] && prevData[meUser.id] !== meUser.position) {
          var oldPos = prevData[meUser.id];
          var newPos = meUser.position;
          var diff = oldPos - newPos;
          var title = diff > 0 ? '⬆ ¡Subiste en el ranking!' : '⬇ Bajaste en el ranking';
          var msgText = diff > 0
            ? 'Pasaste del puesto #' + oldPos + ' al #' + newPos + ' (+' + diff + ')'
            : 'Pasaste del puesto #' + oldPos + ' al #' + newPos + ' (' + diff + ')';
          showRankToast(title, msgText, diff > 0 ? 'success' : 'warning');

          if (srStatus) {
            srStatus.textContent = diff > 0
              ? 'Subiste del puesto ' + oldPos + ' al ' + newPos
              : 'Bajaste del puesto ' + oldPos + ' al ' + newPos;
            setTimeout(function() { srStatus.textContent = ''; }, 3000);
          }
        }

        updateBoard(users, prevData);
        prevData = {};
        users.forEach(function(u) { prevData[u.id] = u.position; });

        if (liveDot) {
          liveDot.classList.add('has-update');
          setTimeout(function() { liveDot.classList.remove('has-update'); }, 3000);
        }
      });

      // Check if WS is active (to suppress polling).
      function checkWebSocketStatus() {
        if (window.AmsawalWSManager && window.AmsawalWSManager.isConnected()) {
          useWebSocket = true;
          if (timer) { clearInterval(timer); timer = null; }
          if (liveDot) {
            liveDot.classList.add('is-ws-connected');
            liveDot.setAttribute('aria-label', 'Conexión en tiempo real activa');
          }
        }
      }
      setTimeout(checkWebSocketStatus, 2000); // Check after connection attempt.

      // Start with polling immediately; WS will take over if available.
      fetchLeaderboard();
      timer = setInterval(fetchLeaderboard, 30000);

      // Manual refresh button
      if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
          DuoAudio.playClick();
          // Always fetch via AJAX; WS is push-only from server.
          fetchLeaderboard();
        });
      }

      // Pause polling when tab is hidden
      document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
          polling = false;
          if (timer) { clearInterval(timer); timer = null; }
        } else {
          polling = true;
          if (!useWebSocket) {
            fetchLeaderboard();
            timer = setInterval(fetchLeaderboard, 30000);
          }
        }
      });
    });
  }

  initLeaderboard();

  // ═══════════════════════════════════════════════════════
  // RECOMMENDATIONS — Fetch personalized next-activity suggestions (F5-6)
  // ═══════════════════════════════════════════════════════

  function initRecommendations() {
    var list = document.getElementById('duo-recommend-list');
    var section = document.querySelector('.duo-recommend-section');
    if (!list || !section) return;

    if (!window.wpAmsawalAjax || !window.wpAmsawalAjax.trackNonce) return;

    // Show skeleton loaders
    for (var i = 0; i < 3; i++) {
      var sk = document.createElement('div');
      sk.className = 'duo-recommend-skeleton';
      sk.setAttribute('aria-hidden', 'true');
      list.appendChild(sk);
    }
    section.style.display = '';

    var data = new URLSearchParams();
    data.append('action', 'wp_amsawal_get_recommendations');
    data.append('_ajax_nonce', window.wpAmsawalAjax.trackNonce);

    fetch((window.wpAmsawalAjax.ajaxUrl || '/wp-admin/admin-ajax.php'), {
      method: 'POST',
      body: data,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    })
    .then(function(resp) { return resp.json(); })
    .then(function(res) {
      // Clear skeletons
      list.textContent = '';

      if (!res.success || !res.data || !res.data.recommendations || !res.data.recommendations.length) {
        section.style.display = 'none';
        return;
      }

      var recs = res.data.recommendations;
      var typeIcons = {
        'flashcards': '🃏', 'dialogcards': '💬', 'dictation': '🎧',
        'memory': '🧠', 'fill-blanks': '✏️', 'mark-the-words': '🔍',
        'multiple-choice': '📝', 'true-false': '✅', 'speak-the-words': '🎤',
        'essay': '📄', 'drag-drop': '🤏'
      };

      recs.forEach(function(rec) {
        var card = document.createElement('a');
        card.className = 'duo-recommend-card';
        card.href = rec.url || '#';
        card.setAttribute('role', 'listitem');

        var icon = document.createElement('div');
        icon.className = 'duo-recommend-card-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = typeIcons[rec.activity_type] || '📚';

        var body = document.createElement('div');
        body.className = 'duo-recommend-card-body';

        var title = document.createElement('div');
        title.className = 'duo-recommend-card-title';
        title.textContent = rec.lesson_title + ' · ' + t('activity_' + rec.activity_type, rec.activity_type);

        var reason = document.createElement('div');
        reason.className = 'duo-recommend-card-reason';
        reason.textContent = '💡 ' + rec.reason;

        var arrow = document.createElement('span');
        arrow.className = 'duo-recommend-card-arrow';
        arrow.setAttribute('aria-hidden', 'true');
        arrow.textContent = '→';

        body.appendChild(title);
        body.appendChild(reason);
        card.appendChild(icon);
        card.appendChild(body);
        card.appendChild(arrow);
        list.appendChild(card);
      });

      section.style.display = '';
    })
    .catch(function() {
      // Silent: recommendation fetch is optional
      section.style.display = 'none';
    });
  }

  initRecommendations();

  // ═══════════════════════════════════════════════════════
  // ADAPTIVE TEST — Real-time per-question difficulty adjustment (F5-3)
  // ═══════════════════════════════════════════════════════

  function initAdaptiveTest() {
    var containers = document.querySelectorAll('.duo-adaptest');
    containers.forEach(function(container) {
      if (container.dataset.bound) return;
      container.dataset.bound = 'true';

      var nonce = container.getAttribute('data-nonce');
      if (!nonce || !window.wpAmsawalAjax || !window.wpAmsawalAjax.ajaxUrl) return;

      var ajaxUrl = window.wpAmsawalAjax.ajaxUrl;
      var card = container.querySelector('.duo-adaptest-card');
      var questionEl = container.querySelector('.duo-adaptest-question');
      var optionsEl = container.querySelector('.duo-adaptest-options');
      var feedbackEl = container.querySelector('.duo-adaptest-feedback');
      var nextBtn = container.querySelector('.duo-adaptest-next');
      var cancelBtn = container.querySelector('.duo-adaptest-cancel');
      var progressFill = container.querySelector('.duo-adaptest-progress-fill');
      var progressText = container.querySelector('.duo-adaptest-progress-text');
      var diffValue = container.querySelector('.duo-adaptest-diff-value');
      var resultEl = container.querySelector('.duo-adaptest-result');
      var startBtn = container.querySelector('.duo-adaptest-start');

      var state = {
        totalQuestions: 10,
        currentNum: 0,
        selectedOption: -1,
        answered: false
      };

      // Difficulty dots helper
      function renderDifficulty(d) {
        var dots = '';
        for (var i = 1; i <= 5; i++) {
          dots += i <= d ? '●' : '○';
        }
        return dots;
      }

      // Start test: fetch session from server
      function startTest() {
        if (!card) return;
        card.style.opacity = '0.5';
        questionEl.textContent = 'Iniciando test adaptativo...';
        optionsEl.textContent = '';
        nextBtn.disabled = true;

        var data = new URLSearchParams();
        data.append('action', 'wp_amsawal_start_adaptive_test');
        data.append('_ajax_nonce', nonce);

        fetch(ajaxUrl, {
          method: 'POST', body: data,
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          card.style.opacity = '1';
          if (res.success && res.data) {
            state.totalQuestions = res.data.total_questions || 10;
            fetchNextQuestion();
          } else {
            questionEl.textContent = 'No se pudo iniciar el test.';
          }
        })
        .catch(function() {
          card.style.opacity = '1';
          questionEl.textContent = 'Error de conexión. Inténtalo de nuevo.';
        });
      }

      // Fetch next question
      function fetchNextQuestion(answerIndex) {
        if (!card || !optionsEl) return;
        card.style.opacity = '0.6';
        nextBtn.disabled = true;
        feedbackEl.style.display = 'none';
        state.answered = false;
        state.selectedOption = -1;

        var data = new URLSearchParams();
        data.append('action', 'wp_amsawal_next_adaptive_question');
        data.append('answer', typeof answerIndex === 'number' ? String(answerIndex) : '-1');
        data.append('_ajax_nonce', nonce);

        fetch(ajaxUrl, {
          method: 'POST', body: data,
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          card.style.opacity = '1';
          if (!res.success || !res.data) {
            questionEl.textContent = 'Error al cargar pregunta.';
            return;
          }

          if (res.data.complete) {
            showResult(res.data.result, res.data.answers);
            return;
          }

          var q = res.data.question;
          var prog = res.data.progress;

          // Update progress
          state.currentNum = q.num;
          var pct = Math.round((prog.current / prog.total) * 100);
          progressFill.style.width = pct + '%';
          progressFill.parentElement.setAttribute('aria-valuenow', String(prog.current));
          progressText.textContent = prog.current + ' / ' + prog.total;

          // Update difficulty
          diffValue.textContent = renderDifficulty(prog.current_difficulty);

          // Render question (announce to screen readers via aria-live)
          questionEl.setAttribute('aria-live', 'polite');
          questionEl.textContent = q.question;
          optionsEl.textContent = '';
          optionsEl.setAttribute('role', 'radiogroup');
          optionsEl.setAttribute('aria-label', 'Opciones para pregunta ' + q.num);

          q.options.forEach(function(opt, idx) {
            var label = document.createElement('label');
            label.className = 'duo-adaptest-option';
            label.setAttribute('tabindex', '-1');

            var radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'duo-adaptest-answer';
            radio.value = String(idx);
            radio.setAttribute('aria-label', opt);

            label.appendChild(radio);
            label.appendChild(document.createTextNode(' ' + opt));

            label.addEventListener('click', function() {
              state.selectedOption = idx;
              state.answered = true;
              nextBtn.disabled = false;
              // Highlight selected + ARIA
              optionsEl.querySelectorAll('.duo-adaptest-option').forEach(function(l) {
                l.classList.remove('duo-adaptest-option--selected');
                l.removeAttribute('aria-selected');
              });
              label.classList.add('duo-adaptest-option--selected');
              label.setAttribute('aria-selected', 'true');
              DuoAudio.playClick();
            });

            label.addEventListener('keydown', function(e) {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                label.click();
              }
            });

            optionsEl.appendChild(label);
          });

          // Focus first option
          var firstOption = optionsEl.querySelector('.duo-adaptest-option');
          if (firstOption) firstOption.focus();
        })
        .catch(function() {
          card.style.opacity = '1';
          questionEl.textContent = 'Error de conexión. Inténtalo de nuevo.';
        });
      }

      // Show result
      function showResult(result, answers) {
        if (!card || !resultEl) return;
        card.style.display = 'none';
        container.querySelector('.duo-adaptest-progress').style.display = 'none';
        container.querySelector('.duo-adaptest-difficulty').style.display = 'none';
        container.querySelector('.duo-adaptest-actions').style.display = 'none';
        feedbackEl.style.display = 'none';
        resultEl.style.display = '';

        var levelEl = resultEl.querySelector('.duo-adaptest-result-level-value');
        var accuracyEl = resultEl.querySelector('.duo-adaptest-result-accuracy strong');
        var coinsWrap = resultEl.querySelector('.duo-adaptest-result-coins');
        var coinsStrong = coinsWrap ? coinsWrap.querySelector('strong') : null;

        if (levelEl) levelEl.textContent = String(result.level || 3);
        if (accuracyEl) accuracyEl.textContent = (result.accuracy || 0) + '%';

        // Highlight the level bar
        resultEl.querySelectorAll('.duo-adaptest-result-level-bar').forEach(function(bar) {
          var barLevel = parseInt(bar.getAttribute('data-level'), 10);
          bar.classList.toggle('is-active', barLevel === (result.level || 3));
          bar.classList.toggle('is-passed', barLevel <= (result.level || 3));
        });

        // Coins
        if (result.coins && result.coins > 0 && coinsWrap) {
          coinsWrap.style.display = '';
          if (coinsStrong) coinsStrong.textContent = '+' + result.coins;
        }

        // Confetti for good results
        if (result.accuracy >= 70) {
          setTimeout(function() { fireConfetti(); }, 500);
        }

        if (result.accuracy >= 50) DuoAudio.playSuccess();
        else DuoAudio.playError();

        // Restart button (once: true to prevent listener leak on multiple completions)
        var restartBtn = resultEl.querySelector('.duo-adaptest-restart');
        if (restartBtn && !restartBtn.dataset.restartBound) {
          restartBtn.dataset.restartBound = 'true';
          restartBtn.addEventListener('click', function() {
            restartTest();
          });
        }
      }

      // Restart test
      function restartTest() {
        if (!card || !resultEl) return;
        resultEl.style.display = 'none';
        container.querySelector('.duo-adaptest-progress').style.display = '';
        container.querySelector('.duo-adaptest-difficulty').style.display = '';
        container.querySelector('.duo-adaptest-actions').style.display = '';
        card.style.display = '';
        state.currentNum = 0;
        state.answered = false;
        state.selectedOption = -1;
        progressFill.style.width = '0%';
        progressFill.parentElement.setAttribute('aria-valuenow', '0');
        progressText.textContent = '0 / 10';
        diffValue.textContent = renderDifficulty(3);
        startTest();
      }

      // Next button handler
      if (nextBtn) {
        nextBtn.addEventListener('click', function() {
          if (!state.answered || state.selectedOption < 0) return;
          DuoAudio.playClick();
          fetchNextQuestion(state.selectedOption);
        });
      }

      // Cancel button handler
      if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
          var data = new URLSearchParams();
          data.append('action', 'wp_amsawal_cancel_adaptive_test');
          data.append('_ajax_nonce', nonce);
          fetch(ajaxUrl, {
            method: 'POST', body: data,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
          });
          // Navigate to home
          navigate('/');
        });
      }

      // Keyboard: 1-5 to select option (once per container)
      if (!container.dataset.keyboardBound) {
        container.dataset.keyboardBound = 'true';
        document.addEventListener('keydown', function(e) {
          if (!card || card.style.display === 'none') return;
          var num = parseInt(e.key, 10);
          if (num >= 1 && num <= 5 && !state.answered) {
            var opts = optionsEl.querySelectorAll('.duo-adaptest-option');
            if (opts[num - 1]) {
              opts[num - 1].click();
            }
          }
        });
      }

      // Start the test
      startTest();
    });
  }

  initAdaptiveTest();

});

// F8-9: Registrar service worker
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
}

// F13-3: PWA Install prompt
let deferredPrompt = null;
const installBanner = document.createElement('div');
installBanner.className = 'duo-install-banner';

(function populateInstallBanner(banner) {
  const content = document.createElement('div');
  content.className = 'duo-install-banner__content';

  const icon = document.createElement('span');
  icon.className = 'duo-install-banner__icon';
  icon.textContent = '📱';

  const text = document.createElement('div');
  text.className = 'duo-install-banner__text';

  const strong = document.createElement('strong');
  strong.textContent = 'Instala Amsawal';

  const span = document.createElement('span');
  span.textContent = 'Accede más rápido desde tu pantalla de inicio';

  text.appendChild(strong);
  text.appendChild(span);

  const installBtn = document.createElement('button');
  installBtn.className = 'duo-install-banner__install';
  installBtn.textContent = 'Instalar';

  const closeBtn = document.createElement('button');
  closeBtn.className = 'duo-install-banner__close';
  closeBtn.setAttribute('aria-label', 'Cerrar');
  closeBtn.textContent = '✕';

  content.appendChild(icon);
  content.appendChild(text);
  content.appendChild(installBtn);
  content.appendChild(closeBtn);
  banner.appendChild(content);
})(installBanner);

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show banner after 30 seconds if not installed
    setTimeout(() => {
        if (!window.matchMedia('(display-mode: standalone)').matches) {
            document.body.appendChild(installBanner);
        }
    }, 30000);
});

installBanner.querySelector('.duo-install-banner__install').addEventListener('click', async () => {
    if (!deferredPrompt) return;
    
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    
    if (outcome === 'accepted') {
        // PWA installed successfully
    }

    deferredPrompt = null;
    installBanner.remove();
});

installBanner.querySelector('.duo-install-banner__close').addEventListener('click', () => {
    installBanner.remove();
    localStorage.setItem('amsawal-install-dismissed', 'true');
});

// Hide banner if already dismissed
if (localStorage.getItem('amsawal-install-dismissed') === 'true') {
    installBanner.remove();
}

// F13-7: Push notification infrastructure
if ('serviceWorker' in navigator && 'PushManager' in window) {
    navigator.serviceWorker.ready.then(registration => {
        // Check if user has already subscribed
        registration.pushManager.getSubscription().then(subscription => {
            if (!subscription) {
                // Ask for permission after user interaction
                document.addEventListener('click', function requestPermission() {
                    if (Notification.permission === 'default') {
                        Notification.requestPermission();
                    }
                    document.removeEventListener('click', requestPermission);
                }, { once: true });
            }
        });
    });
}

// Handle push messages
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', event => {
        if (event.data && event.data.type === 'NOTIFICATION') {
            // Show notification
            if (Notification.permission === 'granted') {
                new Notification(event.data.title, {
                    body: event.data.body,
                    icon: '/wp-content/plugins/wp-amsawal/assets/icon-192.png',
                    badge: '/wp-content/plugins/wp-amsawal/assets/icon-192.png'
                });
            }
        }
    });
}
