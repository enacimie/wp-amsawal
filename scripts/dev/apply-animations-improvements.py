#!/usr/bin/env python3
"""Fase 12: Advanced Animations - Transiciones y efectos visuales"""

def apply_f12_1_page_transitions():
    """F12-1: Page transition animations"""
    with open('css/modules/_layout.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    page_transitions = """
/* F12-1: Page transition animations */
.amsawal-page {
  animation: duo-page-enter 400ms var(--amsawal-ease-out);
}

@keyframes duo-page-enter {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Stagger animations for child elements */
.amsawal-page > * {
  animation: duo-stagger-enter 400ms var(--amsawal-ease-out) backwards;
}

.amsawal-page > *:nth-child(1) { animation-delay: 0ms; }
.amsawal-page > *:nth-child(2) { animation-delay: 50ms; }
.amsawal-page > *:nth-child(3) { animation-delay: 100ms; }
.amsawal-page > *:nth-child(4) { animation-delay: 150ms; }
.amsawal-page > *:nth-child(5) { animation-delay: 200ms; }

@keyframes duo-stagger-enter {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce) {
  .amsawal-page,
  .amsawal-page > * {
    animation: none;
  }
}
"""
    
    if 'duo-page-enter' not in css:
        css += page_transitions
    
    with open('css/modules/_layout.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F12-1: Page transition animations added")
    return True

def apply_f12_2_skeleton_loaders():
    """F12-2: Enhanced skeleton loaders"""
    with open('css/modules/_feedback-toast.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    skeleton_css = """
/* F12-2: Enhanced skeleton loaders */
.duo-skeleton {
  position: relative;
  overflow: hidden;
  background: var(--amsawal-border);
  border-radius: var(--amsawal-radius);
}

.duo-skeleton::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(255,255,255,0.4) 50%,
    transparent 100%);
  animation: duo-skeleton-shimmer 1.5s ease-in-out infinite;
}

@keyframes duo-skeleton-shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

/* Skeleton variants */
.duo-skeleton--circle {
  border-radius: 50%;
}

.duo-skeleton--text {
  height: 16px;
  margin-bottom: 8px;
}

.duo-skeleton--text:last-child {
  width: 60%;
}

.duo-skeleton--card {
  height: 200px;
}

.duo-skeleton--avatar {
  width: 48px;
  height: 48px;
}

/* Dark mode skeleton */
[data-theme="dark"] .duo-skeleton {
  background: var(--duo-border);
}

[data-theme="dark"] .duo-skeleton::after {
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(255,255,255,0.1) 50%,
    transparent 100%);
}

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce) {
  .duo-skeleton::after {
    animation: none;
  }
}
"""
    
    if 'duo-skeleton-shimmer' not in css:
        css += skeleton_css
    
    with open('css/modules/_feedback-toast.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F12-2: Enhanced skeleton loaders added")
    return True

def apply_f12_3_ripple_effect():
    """F12-3: Ripple effect on buttons"""
    with open('css/modules/_activities.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    ripple_css = """
/* F12-3: Ripple effect on buttons */
.duo-activity,
.duo-test-btn,
.duo-info-btn,
.duo-course-btn,
.duo-unit-guide-btn {
  position: relative;
  overflow: hidden;
}

.duo-ripple {
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,0.6);
  transform: scale(0);
  animation: duo-ripple-animation 600ms linear;
  pointer-events: none;
}

@keyframes duo-ripple-animation {
  to {
    transform: scale(4);
    opacity: 0;
  }
}

/* Dark mode ripple */
[data-theme="dark"] .duo-ripple {
  background: rgba(255,255,255,0.3);
}

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce) {
  .duo-ripple {
    animation: none;
  }
}
"""
    
    if 'duo-ripple' not in css:
        css += ripple_css
    
    with open('css/modules/_activities.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F12-3: Ripple effect on buttons added")
    return True

def apply_f12_4_ripple_js():
    """F12-4: Ripple effect JavaScript"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    ripple_js = """
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
"""
    
    if 'createRipple' not in js:
        js = js.replace(
            "document.addEventListener('DOMContentLoaded', function() {",
            "document.addEventListener('DOMContentLoaded', function() {\n" + ripple_js
        )
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F12-4: Ripple effect JavaScript added")
    return True

def apply_f12_5_scroll_animations():
    """F12-5: Scroll-triggered animations"""
    with open('css/modules/_learning-path.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    scroll_css = """
/* F12-5: Scroll-triggered animations for nodes */
.duo-node {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 500ms var(--amsawal-ease-out),
              transform 500ms var(--amsawal-ease-out);
}

.duo-node.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* Stagger delay for nodes */
.duo-node:nth-child(1) { transition-delay: 0ms; }
.duo-node:nth-child(2) { transition-delay: 100ms; }
.duo-node:nth-child(3) { transition-delay: 200ms; }
.duo-node:nth-child(4) { transition-delay: 300ms; }
.duo-node:nth-child(5) { transition-delay: 400ms; }

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce) {
  .duo-node {
    opacity: 1;
    transform: none;
    transition: none;
  }
}
"""
    
    if '.duo-node.is-visible' not in css:
        css += scroll_css
    
    with open('css/modules/_learning-path.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F12-5: Scroll-triggered animations added")
    return True

def apply_f12_6_scroll_observer():
    """F12-6: Intersection Observer for scroll animations"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    observer_js = """
// F12-6: Intersection Observer for scroll animations
function initScrollAnimations() {
    if (prefersReducedMotion) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    document.querySelectorAll('.duo-node').forEach(node => {
        observer.observe(node);
    });
}

// Initialize on load
if (typeof initScrollAnimations !== 'undefined') {
    initScrollAnimations();
}
"""
    
    if 'initScrollAnimations' not in js:
        js = js.replace(
            "document.addEventListener('DOMContentLoaded', function() {",
            "document.addEventListener('DOMContentLoaded', function() {\n" + observer_js
        )
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F12-6: Intersection Observer for scroll animations added")
    return True

def apply_f12_7_haptic_feedback():
    """F12-7: Enhanced haptic feedback patterns"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    # Enhance existing DuoHaptics with more patterns
    haptic_enhancement = """
// F12-7: Enhanced haptic feedback patterns
if (typeof DuoHaptics !== 'undefined') {
    DuoHaptics.patterns = {
        light: [10],
        medium: [20],
        heavy: [30],
        success: [10, 50, 10],
        error: [30, 50, 30],
        warning: [20, 50, 20],
        levelUp: [10, 50, 20, 50, 30],
        achievement: [10, 30, 10, 30, 10, 30, 10],
        streak: [15, 30, 15],
        coin: [10, 20, 10],
        perfect: [10, 30, 10, 30, 10, 30, 10, 30, 10]
    };
    
    DuoHaptics.play = function(pattern) {
        if (!navigator.vibrate || prefersReducedMotion) return;
        navigator.vibrate(this.patterns[pattern] || this.patterns.light);
    };
}
"""
    
    if 'DuoHaptics.patterns' not in js:
        js += haptic_enhancement
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F12-7: Enhanced haptic feedback patterns added")
    return True

def apply_f12_8_sound_improvements():
    """F12-8: Sound effect improvements"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    # Add volume control and mute persistence
    sound_improvement = """
// F12-8: Sound effect improvements - volume control
if (typeof DuoAudio !== 'undefined') {
    DuoAudio.volume = parseFloat(localStorage.getItem('amsawal-volume') || '0.5');
    DuoAudio.muted = localStorage.getItem('amsawal-muted') === 'true';
    
    DuoAudio.setVolume = function(vol) {
        this.volume = Math.max(0, Math.min(1, vol));
        localStorage.setItem('amsawal-volume', this.volume);
    };
    
    DuoAudio.toggleMute = function() {
        this.muted = !this.muted;
        localStorage.setItem('amsawal-muted', this.muted);
    };
    
    // Override play to respect volume and mute
    const originalPlay = DuoAudio.play;
    DuoAudio.play = function(sound) {
        if (this.muted) return;
        originalPlay.call(this, sound);
    };
}
"""
    
    if 'DuoAudio.volume' not in js:
        js += sound_improvement
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F12-8: Sound effect improvements added")
    return True

def apply_f12_9_loading_animations():
    """F12-9: Loading animations for various states"""
    with open('css/modules/_feedback-toast.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    loading_css = """
/* F12-9: Loading animations */
.duo-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--amsawal-border);
  border-top-color: var(--amsawal-primary);
  border-radius: 50%;
  animation: duo-spin 800ms linear infinite;
}

@keyframes duo-spin {
  to { transform: rotate(360deg); }
}

.duo-spinner--sm {
  width: 24px;
  height: 24px;
  border-width: 3px;
}

.duo-spinner--lg {
  width: 60px;
  height: 60px;
  border-width: 5px;
}

/* Pulse animation */
.duo-pulse {
  animation: duo-pulse 1.5s ease-in-out infinite;
}

@keyframes duo-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Bounce animation */
.duo-bounce {
  animation: duo-bounce 1s ease-in-out infinite;
}

@keyframes duo-bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce) {
  .duo-spinner,
  .duo-pulse,
  .duo-bounce {
    animation: none;
  }
}
"""
    
    if 'duo-spinner' not in css:
        css += loading_css
    
    with open('css/modules/_feedback-toast.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F12-9: Loading animations added")
    return True

def apply_f12_10_celebration_improvements():
    """F12-10: Enhanced celebration effects"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    celebration_improvement = """
// F12-10: Enhanced celebration effects
if (typeof celebrate !== 'undefined') {
    const originalCelebrate = celebrate;
    celebrate = function(type = 'default') {
        // Play appropriate haptic pattern
        if (typeof DuoHaptics !== 'undefined') {
            const patterns = {
                'default': 'success',
                'levelup': 'levelUp',
                'achievement': 'achievement',
                'perfect': 'perfect'
            };
            DuoHaptics.play(patterns[type] || 'success');
        }
        
        // Call original celebration
        originalCelebrate(type);
    };
}
"""
    
    if 'F12-10: Enhanced celebration' not in js:
        js += celebration_improvement
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F12-10: Enhanced celebration effects added")
    return True

# Ejecutar todas las mejoras de animaciones
if __name__ == '__main__':
    print(" Aplicando mejoras Fase 12 - Advanced Animations...\n")
    
    apply_f12_1_page_transitions()
    apply_f12_2_skeleton_loaders()
    apply_f12_3_ripple_effect()
    apply_f12_4_ripple_js()
    apply_f12_5_scroll_animations()
    apply_f12_6_scroll_observer()
    apply_f12_7_haptic_feedback()
    apply_f12_8_sound_improvements()
    apply_f12_9_loading_animations()
    apply_f12_10_celebration_improvements()
    
    print("\n✨ Mejoras de animaciones completadas")
