#!/usr/bin/env python3
"""Fase 8: Performance & Optimización"""

def apply_f8_1_lazy_load_images():
    """F8-1: Lazy loading nativo para imágenes"""
    with open('wp-amsawal-view.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    # Añadir loading="lazy" a imágenes de cursos
    php = php.replace(
        '<img src="\' . $course_img . \'">',
        '<img src="\' . $course_img . \'" loading="lazy" decoding="async">'
    )
    
    # Añadir lazy load a avatares de leaderboard
    php = php.replace(
        '<img src="\' . $avatar_url . \'">',
        '<img src="\' . $avatar_url . \'" loading="lazy" decoding="async">'
    )
    
    with open('wp-amsawal-view.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F8-1: Lazy loading nativo añadido a imágenes")
    return True

def apply_f8_2_critical_css():
    """F8-2: Critical CSS inline para above-the-fold"""
    with open('wp-amsawal-view.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    # Añadir critical CSS inline antes del primer render
    critical_css = """
    <!-- F8-2: Critical CSS inline -->
    <style>
    .duo-topbar{display:flex;justify-content:space-between;align-items:center;padding:12px 20px;background:linear-gradient(135deg,#2c5f8d 0%,#1e4364 100%);border-bottom:3px solid #d4af37;position:fixed;top:0;left:0;right:0;z-index:1000;height:60px;box-shadow:0 8px 32px rgba(44,95,141,0.18)}
    .duo-container{max-width:640px;margin:0 auto;padding:20px 24px 100px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#2c3e50;min-height:100vh;min-height:100dvh}
    .duo-path{display:flex;flex-direction:column;align-items:center;gap:0}
    .duo-node{display:flex;align-items:center;gap:12px;width:100%;padding:8px 16px;border-radius:12px}
    .duo-node-circle{width:70px;height:70px;min-width:70px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;position:relative;flex-shrink:0;box-shadow:0 8px 0 rgba(0,0,0,0.15),inset 0 -4px 0 rgba(0,0,0,0.1)}
    </style>
"""
    
    # Insertar después de <head>
    if '<head>' in php and 'F8-2: Critical CSS' not in php:
        php = php.replace('<head>', '<head>\n' + critical_css)
    
    with open('wp-amsawal-view.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F8-2: Critical CSS inline añadido")
    return True

def apply_f8_3_defer_non_critical_js():
    """F8-3: Defer JS no crítico"""
    with open('wp-amsawal-ai.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    # Añadir defer attribute
    if "add_filter('script_loader_tag'" not in php:
        defer_code = """
// F8-3: Defer non-critical JS
add_filter('script_loader_tag', function($tag, $handle) {
    $defer_handles = ['amsawal-pure-js', 'amsawal-h5p'];
    if (in_array($handle, $defer_handles)) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}, 10, 2);
"""
        php += defer_code
    
    with open('wp-amsawal-ai.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F8-3: Defer JS no crítico añadido")
    return True

def apply_f8_4_resource_hints():
    """F8-4: Resource hints (preconnect, dns-prefetch)"""
    with open('wp-amsawal-ai.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    resource_hints = """
// F8-4: Resource hints para performance
add_action('wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="dns-prefetch" href="//localhost">';
});
"""
    
    if 'F8-4: Resource hints' not in php:
        php += resource_hints
    
    with open('wp-amsawal-ai.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F8-4: Resource hints añadidos")
    return True

def apply_f8_5_image_optimization():
    """F8-5: Optimización de imágenes con srcset"""
    with open('wp-amsawal-courses.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    # Mejorar imágenes de cursos con sizes attribute
    if "sizes='(max-width: 600px) 100vw, 50vw'" not in php:
        php = php.replace(
            "esc_url($course_img)",
            "esc_url($course_img) . \"' sizes='(max-width: 600px) 100vw, 50vw'\""
        )
    
    with open('wp-amsawal-courses.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F8-5: Optimización de imágenes con sizes attribute")
    return True

def apply_f8_6_cache_headers():
    """F8-6: Cache headers para assets estáticos"""
    htaccess_content = """# F8-6: Cache headers para assets estáticos
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType image/x-icon "access plus 1 year"
</IfModule>

<IfModule mod_headers.c>
  <FilesMatch "\\.(css|js|jpg|jpeg|png|gif|svg|ico)$">
    Header set Cache-Control "public, max-age=31536000"
  </FilesMatch>
</IfModule>
"""
    
    with open('.htaccess', 'w', encoding='utf-8') as f:
        f.write(htaccess_content)
    print("✅ F8-6: Cache headers configurados en .htaccess")
    return True

def apply_f8_7_reduce_motion():
    """F8-7: Respetar prefers-reduced-motion en animaciones JS"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    # Añadir check de reduced motion al inicio
    reduced_motion_check = """
// F8-7: Check prefers-reduced-motion
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
"""
    
    if 'prefersReducedMotion' not in js:
        js = js.replace(
            "document.addEventListener('DOMContentLoaded', function() {",
            "document.addEventListener('DOMContentLoaded', function() {\n" + reduced_motion_check
        )
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F8-7: prefers-reduced-motion check añadido")
    return True

def apply_f8_8_service_worker():
    """F8-8: Service worker básico para offline"""
    sw_content = """// F8-8: Service Worker básico para caching
const CACHE_NAME = 'amsawal-v1';
const urlsToCache = [
  '/',
  '/wp-content/plugins/wp-amsawal/css/wp-amsawal-style-h5p.css',
  '/wp-content/plugins/wp-amsawal/js/pure-js-script.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});
"""
    
    with open('sw.js', 'w', encoding='utf-8') as f:
        f.write(sw_content)
    print("✅ F8-8: Service worker básico creado")
    return True

def apply_f8_9_register_sw():
    """F8-9: Registrar service worker en JS"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    sw_registration = """
// F8-9: Registrar service worker
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js')
    .then(reg => console.log('SW registered'))
    .catch(err => console.log('SW registration failed'));
}
"""
    
    if 'serviceWorker' not in js:
        js += sw_registration
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F8-9: Service worker registration añadido")
    return True

def apply_f8_10_preload_fonts():
    """F8-10: Preload de fuentes críticas"""
    with open('wp-amsawal-ai.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    preload_fonts = """
// F8-10: Preload de fuentes críticas
add_action('wp_head', function() {
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Quicksand:wght@600;700;800&display=swap" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
    echo '<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Quicksand:wght@600;700;800&display=swap"></noscript>';
});
"""
    
    if 'F8-10: Preload de fuentes' not in php:
        php += preload_fonts
    
    with open('wp-amsawal-ai.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F8-10: Preload de fuentes críticas añadido")
    return True

# Ejecutar todas las mejoras de performance
if __name__ == '__main__':
    print("🚀 Aplicando mejoras Fase 8 - Performance & Optimización...\n")
    
    apply_f8_1_lazy_load_images()
    apply_f8_2_critical_css()
    apply_f8_3_defer_non_critical_js()
    apply_f8_4_resource_hints()
    apply_f8_5_image_optimization()
    apply_f8_6_cache_headers()
    apply_f8_7_reduce_motion()
    apply_f8_8_service_worker()
    apply_f8_9_register_sw()
    apply_f8_10_preload_fonts()
    
    print("\n✨ Mejoras de performance completadas")
