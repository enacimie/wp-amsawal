#!/usr/bin/env python3
"""Aplica mejoras UI/UX y accesibilidad (Fase 7)"""
import re

def apply_f7_2_focus_trap():
    """F7-2: Focus trap en mobile drawer"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    # Buscar initDrawer
    if 'function initDrawer()' not in js:
        print("⚠️ F7-2: initDrawer() no encontrado")
        return False
    
    # Añadir variable de focus trap después del toggle
    js = js.replace(
        "var toggle = document.querySelector('.duo-drawer-toggle');",
        """var toggle = document.querySelector('.duo-drawer-toggle');
    // F7-2: Focus trap para accesibilidad
    var drawerFocusTrap = null;
    var drawer = document.querySelector('.duo-sidebar');"""
    )
    
    # Modificar open() para activar focus trap
    js = js.replace(
        "drawer.classList.add('is-open');",
        """drawer.classList.add('is-open');
        // F7-2: Activar focus trap
        if (typeof DuoFocusTrap !== 'undefined' && drawer) {
            drawerFocusTrap = DuoFocusTrap.create(drawer, { initialFocus: '.nav-item' });
        }"""
    )
    
    # Modificar close() para desactivar focus trap
    js = js.replace(
        "drawer.classList.remove('is-open');",
        """drawer.classList.remove('is-open');
        // F7-2: Desactivar focus trap
        if (drawerFocusTrap) {
            drawerFocusTrap.destroy();
            drawerFocusTrap = null;
        }"""
    )
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F7-2: Focus trap añadido al mobile drawer")
    return True

def apply_f7_3_aria_live():
    """F7-3: aria-live regions para adaptive test"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    # Buscar donde se renderiza la pregunta del adaptive test
    # Añadir aria-live region
    live_region_code = """
    // F7-3: Crear aria-live region para anunciar preguntas
    var liveRegion = document.getElementById('adaptest-live-region');
    if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.id = 'adaptest-live-region';
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'screen-reader-text';
        if (card.parentNode) {
            card.parentNode.insertBefore(liveRegion, card);
        }
    }
    if (liveRegion) {
        liveRegion.textContent = 'Pregunta ' + (state.currentQuestion + 1) + ' de ' + state.questions.length;
    }
"""
    
    # Insertar antes de card.innerHTML
    js = js.replace(
        "card.innerHTML = `",
        live_region_code + "\n    card.innerHTML = `"
    )
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F7-3: aria-live regions añadidas a adaptive test")
    return True

def apply_f7_7_streak_panel():
    """F7-7: Fix streak panel - usar tokens de marca"""
    with open('css/modules/_gamification.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    # Reemplazar colores hardcoded por tokens
    css = css.replace(
        'background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);',
        'background: linear-gradient(135deg, var(--amsawal-secondary) 0%, var(--amsawal-warning) 100%);'
    )
    
    # Mejorar contraste del texto blanco añadiendo text-shadow
    css = css.replace(
        '.duo-streak-panel {\n  background: linear-gradient(135deg, var(--amsawal-secondary) 0%, var(--amsawal-warning) 100%);\n  border-radius: var(--amsawal-radius-lg);\n  padding: 24px;\n  color: #fff;',
        '.duo-streak-panel {\n  background: linear-gradient(135deg, var(--amsawal-secondary) 0%, var(--amsawal-warning) 100%);\n  border-radius: var(--amsawal-radius-lg);\n  padding: 24px;\n  color: #fff;\n  text-shadow: 0 1px 3px rgba(0,0,0,0.3);'
    )
    
    with open('css/modules/_gamification.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F7-7: Streak panel actualizado con tokens de marca")
    return True

def apply_f7_10_confetti_colors():
    """F7-10: Mover colores de confetti a CSS tokens"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    # Reemplazar colores hardcoded en DuoConfetti
    js = js.replace(
        "const COLORS = ['#2c5f8d', '#3498db', '#e67e22', '#d4af37', '#27ae60', '#f39c12'];",
        """// F7-10: Colores desde CSS tokens
        const COLORS = [
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-primary').trim() || '#2c5f8d',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-accent').trim() || '#3498db',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-secondary').trim() || '#e67e22',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-tifinagh').trim() || '#d4af37',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-success').trim() || '#27ae60',
            getComputedStyle(document.documentElement).getPropertyValue('--amsawal-warning').trim() || '#f39c12'
        ];"""
    )
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F7-10: Colores de confetti movidos a CSS tokens")
    return True

def apply_f7_11_toast_position():
    """F7-11: Toast position en mobile"""
    with open('css/modules/_feedback-toast.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    # Añadir media query para móvil
    mobile_toast = """
/* F7-11: Toast position en móvil - debajo del topbar */
@media (max-width: 768px) {
  .duo-toast-stack {
    top: 70px;
    right: 16px;
    left: 16px;
    max-width: none;
  }
}
"""
    
    # Insertar antes del último media query
    if '@media (max-width: 480px)' in css:
        css = css.replace(
            '/* Small mobile */\n@media (max-width: 480px) {',
            mobile_toast + '\n/* Small mobile */\n@media (max-width: 480px) {'
        )
    else:
        css += mobile_toast
    
    with open('css/modules/_feedback-toast.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F7-11: Toast position ajustada para móvil")
    return True

def apply_f7_15_print_stylesheet():
    """F7-15: Crear print stylesheet"""
    print_css = """/*-----------------------------------*\\
  #PRINT STYLESHEET — F7-15
  Oculta elementos interactivos al imprimir
\\*-----------------------------------*/

@media print {
  /* Ocultar navegación y elementos interactivos */
  .duo-sidebar,
  .duo-topbar,
  .duo-mobile-nav,
  .duo-tutor,
  .duo-tutor-toggle,
  .duo-toast-stack,
  .duo-feedback-bar,
  .duo-modal-overlay,
  .duo-confetti-canvas,
  .duo-particle-container,
  .duo-scroll-top,
  .duo-drawer-toggle,
  .duo-drawer-scrim,
  button,
  .duo-unit-guide-btn,
  .duo-test-btn,
  .duo-info-btn,
  .duo-course-btn,
  .duo-activity,
  .duo-node-circle {
    display: none !important;
  }

  /* Ajustar layout para impresión */
  .amsawal-immersive {
    padding-left: 0 !important;
  }

  .duo-container {
    max-width: 100%;
    padding: 0;
  }

  /* Mostrar contenido principal */
  .duo-path,
  .duo-node,
  .duo-node-label {
    display: block !important;
    opacity: 1 !important;
  }

  /* Ajustar colores para impresión */
  body {
    background: #fff;
    color: #000;
  }

  .duo-node-circle {
    border: 2px solid #000;
    box-shadow: none;
  }

  .duo-connector svg path {
    stroke: #000 !important;
    opacity: 1 !important;
  }
}
"""
    
    with open('css/modules/_print.css', 'w', encoding='utf-8') as f:
        f.write(print_css)
    print("✅ F7-15: Print stylesheet creado")
    return True

def apply_f7_16_border_radius():
    """F7-16: Estandarizar border radius"""
    # Actualizar _variables.css para añadir alias
    with open('css/modules/_variables.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    # Añadir comentario de estandarización
    if '/* F7-16: Border radius aliases */' not in css:
        css = css.replace(
            '  --radius-pill: 9999px;',
            """  --radius-pill: 9999px;

  /* F7-16: Aliases para estandarización */
  --radius-xs: var(--amsawal-radius-sm);
  --radius-sm: var(--amsawal-radius-sm);
  --radius-md: var(--amsawal-radius);
  --radius-lg: var(--amsawal-radius-lg);
  --radius-xl: var(--amsawal-radius-lg);
  --radius-2xl: var(--amsawal-radius-lg);"""
        )
    
    with open('css/modules/_variables.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F7-16: Border radius estandarizado con aliases")
    return True

# Ejecutar todas las mejoras
if __name__ == '__main__':
    print("🚀 Aplicando mejoras Fase 7...\n")
    
    apply_f7_2_focus_trap()
    apply_f7_3_aria_live()
    apply_f7_7_streak_panel()
    apply_f7_10_confetti_colors()
    apply_f7_11_toast_position()
    apply_f7_15_print_stylesheet()
    apply_f7_16_border_radius()
    
    print("\n✨ Mejoras P0-P3 aplicadas")
