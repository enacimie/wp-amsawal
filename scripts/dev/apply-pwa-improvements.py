#!/usr/bin/env python3
"""Fase 13: Mobile PWA - Progressive Web App"""

def apply_f13_1_manifest():
    """F13-1: Web App Manifest"""
    manifest = """{
  "name": "WP Amsawal - Aprende Tamazight",
  "short_name": "Amsawal",
  "description": "Plataforma educativa estilo Duolingo para aprender Tamazight (Tarifit/Rif)",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#2c5f8d",
  "orientation": "portrait-primary",
  "icons": [
    {
      "src": "/wp-content/plugins/wp-amsawal/assets/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/wp-content/plugins/wp-amsawal/assets/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    },
    {
      "src": "/wp-content/plugins/wp-amsawal/assets/icon-maskable.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "maskable"
    }
  ],
  "categories": ["education", "languages"],
  "lang": "es",
  "dir": "ltr",
  "scope": "/",
  "prefer_related_applications": false
}
"""
    
    with open('manifest.json', 'w', encoding='utf-8') as f:
        f.write(manifest)
    print("✅ F13-1: Web App Manifest created (manifest.json)")
    return True

def apply_f13_2_register_manifest():
    """F13-2: Register manifest in PHP"""
    with open('wp-amsawal-ai.php', 'r', encoding='utf-8') as f:
        php = f.read()
    
    manifest_code = """
// F13-2: Register PWA manifest
add_action('wp_head', function() {
    echo '<link rel="manifest" href="/manifest.json">';
    echo '<meta name="theme-color" content="#2c5f8d">';
    echo '<link rel="apple-touch-icon" href="/wp-content/plugins/wp-amsawal/assets/icon-192.png">';
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
    echo '<meta name="apple-mobile-web-app-title" content="Amsawal">';
});
"""
    
    if 'F13-2: Register PWA manifest' not in php:
        php += manifest_code
    
    with open('wp-amsawal-ai.php', 'w', encoding='utf-8') as f:
        f.write(php)
    print("✅ F13-2: PWA manifest registered in PHP")
    return True

def apply_f13_3_install_prompt():
    """F13-3: Install prompt logic"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    install_code = """
// F13-3: PWA Install prompt
let deferredPrompt = null;
const installBanner = document.createElement('div');
installBanner.className = 'duo-install-banner';
installBanner.innerHTML = `
    <div class="duo-install-banner__content">
        <span class="duo-install-banner__icon">📱</span>
        <div class="duo-install-banner__text">
            <strong>Instala Amsawal</strong>
            <span>Accede más rápido desde tu pantalla de inicio</span>
        </div>
        <button class="duo-install-banner__install">Instalar</button>
        <button class="duo-install-banner__close" aria-label="Cerrar">✕</button>
    </div>
`;

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
        console.log('User accepted the install prompt');
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
"""
    
    if 'F13-3: PWA Install prompt' not in js:
        js += install_code
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F13-3: Install prompt logic added")
    return True

def apply_f13_4_install_banner_css():
    """F13-4: Install banner styles"""
    with open('css/modules/_feedback-toast.css', 'r', encoding='utf-8') as f:
        css = f.read()
    
    banner_css = """
/* F13-4: Install banner styles */
.duo-install-banner {
  position: fixed;
  bottom: 80px;
  left: 16px;
  right: 16px;
  background: var(--amsawal-primary);
  color: #fff;
  border-radius: var(--amsawal-radius-lg);
  padding: 16px;
  box-shadow: var(--amsawal-shadow-lg);
  z-index: 999;
  animation: duo-slide-up 400ms var(--amsawal-ease-out);
}

@keyframes duo-slide-up {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.duo-install-banner__content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.duo-install-banner__icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.duo-install-banner__text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.duo-install-banner__text strong {
  font-size: 1rem;
  font-weight: 700;
}

.duo-install-banner__text span {
  font-size: 0.875rem;
  opacity: 0.9;
}

.duo-install-banner__install {
  background: #fff;
  color: var(--amsawal-primary);
  border: none;
  padding: 8px 16px;
  border-radius: var(--amsawal-radius);
  font-weight: 700;
  cursor: pointer;
  flex-shrink: 0;
  transition: transform var(--amsawal-duration-fast) var(--amsawal-ease-out);
}

.duo-install-banner__install:hover {
  transform: scale(1.05);
}

.duo-install-banner__install:active {
  transform: scale(0.95);
}

.duo-install-banner__close {
  background: transparent;
  border: none;
  color: #fff;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 4px 8px;
  opacity: 0.8;
  transition: opacity var(--amsawal-duration-fast);
}

.duo-install-banner__close:hover {
  opacity: 1;
}

/* Dark mode */
[data-theme="dark"] .duo-install-banner {
  background: var(--duo-card);
  border: 1px solid var(--duo-border);
}

[data-theme="dark"] .duo-install-banner__install {
  background: var(--amsawal-primary);
  color: #fff;
}

/* Mobile */
@media (max-width: 480px) {
  .duo-install-banner {
    bottom: 100px;
    left: 12px;
    right: 12px;
  }
  
  .duo-install-banner__text span {
    font-size: 0.75rem;
  }
}

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce) {
  .duo-install-banner {
    animation: none;
  }
}
"""
    
    if 'duo-install-banner' not in css:
        css += banner_css
    
    with open('css/modules/_feedback-toast.css', 'w', encoding='utf-8') as f:
        f.write(css)
    print("✅ F13-4: Install banner styles added")
    return True

def apply_f13_5_offline_page():
    """F13-5: Offline fallback page"""
    offline_html = """<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin conexión - Amsawal</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #2c5f8d 0%, #1e4364 100%);
            color: #fff;
            text-align: center;
            padding: 20px;
        }
        .offline-container {
            max-width: 400px;
        }
        .offline-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        p {
            opacity: 0.9;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .retry-btn {
            background: #fff;
            color: #2c5f8d;
            border: none;
            padding: 12px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .retry-btn:hover {
            transform: scale(1.05);
        }
        .retry-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📡</div>
        <h1>Sin conexión a internet</h1>
        <p>Parece que estás offline. Revisa tu conexión e intenta de nuevo.</p>
        <button class="retry-btn" onclick="window.location.reload()">Reintentar</button>
    </div>
</body>
</html>
"""
    
    with open('offline.html', 'w', encoding='utf-8') as f:
        f.write(offline_html)
    print("✅ F13-5: Offline fallback page created")
    return True

def apply_f13_6_sw_offline_fallback():
    """F13-6: Service worker offline fallback"""
    with open('sw.js', 'r', encoding='utf-8') as f:
        sw = f.read()
    
    # Enhance service worker with offline fallback
    sw_enhancement = """
// F13-6: Offline fallback
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        cache.addAll(urlsToCache);
        return cache.add(OFFLINE_URL);
      })
  );
  self.skipWaiting();
});

self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') return;
  
  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Clone and cache successful responses
        if (response.ok) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseClone);
          });
        }
        return response;
      })
      .catch(() => {
        // Return cached version or offline page
        return caches.match(event.request)
          .then(response => response || caches.match(OFFLINE_URL));
      })
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => name !== CACHE_NAME)
          .map(name => caches.delete(name))
      );
    })
  );
  self.clients.claim();
});
"""
    
    with open('sw.js', 'w', encoding='utf-8') as f:
        f.write(sw_enhancement)
    print("✅ F13-6: Service worker offline fallback added")
    return True

def apply_f13_7_push_notifications():
    """F13-7: Push notification infrastructure"""
    with open('js/pure-js-script.js', 'r', encoding='utf-8') as f:
        js = f.read()
    
    push_code = """
// F13-7: Push notification infrastructure
if ('serviceWorker' in navigator && 'PushManager' in window) {
    navigator.serviceWorker.ready.then(registration => {
        // Check if user has already subscribed
        registration.pushManager.getSubscription().then(subscription => {
            if (!subscription) {
                // Ask for permission after user interaction
                document.addEventListener('click', function requestPermission() {
                    if (Notification.permission === 'default') {
                        Notification.requestPermission().then(permission => {
                            if (permission === 'granted') {
                                console.log('Notification permission granted');
                                // Here you would send the subscription to your server
                            }
                        });
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
"""
    
    if 'F13-7: Push notification' not in js:
        js += push_code
    
    with open('js/pure-js-script.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("✅ F13-7: Push notification infrastructure added")
    return True

def apply_f13_8_app_shortcuts():
    """F13-8: App shortcuts in manifest"""
    with open('manifest.json', 'r', encoding='utf-8') as f:
        manifest = json.load(f)
    
    manifest['shortcuts'] = [
        {
            'name': 'Aprender',
            'short_name': 'Aprender',
            'description': 'Ir al mapa de aprendizaje',
            'url': '/',
            'icons': [
                {
                    'src': '/wp-content/plugins/wp-amsawal/assets/icon-192.png',
                    'sizes': '192x192'
                }
            ]
        },
        {
            'name': 'Ligas',
            'short_name': 'Ligas',
            'description': 'Ver clasificación de ligas',
            'url': '/liderazgos',
            'icons': [
                {
                    'src': '/wp-content/plugins/wp-amsawal/assets/icon-192.png',
                    'sizes': '192x192'
                }
            ]
        }
    ]
    
    with open('manifest.json', 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)
    print("✅ F13-8: App shortcuts added to manifest")
    return True

def apply_f13_9_share_target():
    """F13-9: Share target in manifest"""
    import json
    
    with open('manifest.json', 'r', encoding='utf-8') as f:
        manifest = json.load(f)
    
    manifest['share_target'] = {
        'action': '/wp-admin/admin-ajax.php?action=amsawal_share',
        'method': 'POST',
        'enctype': 'multipart/form-data',
        'params': {
            'title': 'title',
            'text': 'text',
            'url': 'url'
        }
    }
    
    with open('manifest.json', 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)
    print("✅ F13-9: Share target added to manifest")
    return True

def apply_f13_10_pwa_tests():
    """F13-10: PWA compliance tests"""
    test_code = """<?php
/**
 * F13-10: PWA Compliance Tests
 */

require_once dirname(__DIR__) . '/wp-load.php';

echo "📱 PWA Compliance Test\\n";
echo "========================\\n\\n";

$tests = [
    'manifest.json exists' => file_exists(dirname(__DIR__) . '/manifest.json'),
    'sw.js exists' => file_exists(dirname(__DIR__) . '/sw.js'),
    'offline.html exists' => file_exists(dirname(__DIR__) . '/offline.html'),
    'manifest is valid JSON' => json_decode(file_get_contents(dirname(__DIR__) . '/manifest.json')) !== null,
];

// Check manifest content
$manifest = json_decode(file_get_contents(dirname(__DIR__) . '/manifest.json'), true);
if ($manifest) {
    $tests['manifest has name'] = isset($manifest['name']);
    $tests['manifest has short_name'] = isset($manifest['short_name']);
    $tests['manifest has start_url'] = isset($manifest['start_url']);
    $tests['manifest has display'] = isset($manifest['display']);
    $tests['manifest has icons'] = isset($manifest['icons']) && count($manifest['icons']) > 0;
    $tests['manifest has theme_color'] = isset($manifest['theme_color']);
}

$passed = 0;
$failed = 0;

foreach ($tests as $name => $result) {
    if ($result) {
        echo "✅ $name\\n";
        $passed++;
    } else {
        echo "❌ $name\\n";
        $failed++;
    }
}

echo "\\n========================\\n";
echo "Results: $passed passed, $failed failed\\n";
echo "========================\\n";

exit($failed === 0 ? 0 : 1);
"""
    
    with open('tests/test-pwa.php', 'w', encoding='utf-8') as f:
        f.write(test_code)
    print("✅ F13-10: PWA compliance tests created")
    return True

# Ejecutar todas las mejoras PWA
if __name__ == '__main__':
    import json
    
    print("🚀 Aplicando mejoras Fase 13 - Mobile PWA...\n")
    
    apply_f13_1_manifest()
    apply_f13_2_register_manifest()
    apply_f13_3_install_prompt()
    apply_f13_4_install_banner_css()
    apply_f13_5_offline_page()
    apply_f13_6_sw_offline_fallback()
    apply_f13_7_push_notifications()
    apply_f13_8_app_shortcuts()
    apply_f13_9_share_target()
    apply_f13_10_pwa_tests()
    
    print("\n✨ Mejoras PWA completadas")
