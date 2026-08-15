/**
 * DIAGNÓSTICO DE FEEDBACK BAR - Paso a Paso
 *
 * Ejecuta esto en la consola del navegador (F12) después de completar una actividad H5P.
 * Te dirá exactamente qué está fallando.
 */

(function() {
  console.clear();
  console.log('=== DIAGNÓSTICO DE FEEDBACK BAR ===\n');

  // PASO 1: Verificar que el script está cargado
  console.log('PASO 1: Verificar pure-js-script.js');
  if (typeof showH5PFeedbackBar === 'undefined') {
    console.error('❌ showH5PFeedbackBar no está definido');
    console.log('💡 El script no se cargó. Haz hard refresh: Ctrl+Shift+R');
    return;
  }
  console.log('✅ showH5PFeedbackBar está definido\n');

  // PASO 2: Verificar configuración AJAX
  console.log('PASO 2: Verificar wpAmsawalAjax');
  if (!window.wpAmsawalAjax) {
    console.error('❌ wpAmsawalAjax no está definido');
    console.log('💡 WordPress no localizó el script. Recarga la página');
    return;
  }
  console.log('✅ wpAmsawalAjax encontrado:');
  console.log('   - ajaxUrl:', window.wpAmsawalAjax.ajaxUrl || '(defecto)');
  console.log('   - trackNonce:', window.wpAmsawalAjax.trackNonce ? '✓' : '❌');
  console.log('   - postId:', window.wpAmsawalAjax.postId || 0, '\n');

  // PASO 3: Probar el endpoint manualmente
  console.log('PASO 3: Probar endpoint wp_amsawal_track_item');
  console.log('Enviando petición de prueba...');

  const testData = new URLSearchParams();
  testData.append('action', 'wp_amsawal_track_item');
  testData.append('item_text', 'Prueba de diagnóstico');
  testData.append('success', '1');
  testData.append('score', '8');
  testData.append('max_score', '10');
  if (window.wpAmsawalAjax.trackNonce) {
    testData.append('_ajax_nonce', window.wpAmsawalAjax.trackNonce);
  }

  fetch(window.wpAmsawalAjax.ajaxUrl || '/wp-admin/admin-ajax.php', {
    method: 'POST',
    body: testData,
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
  })
  .then(r => r.json())
  .then(res => {
    console.log('\nRespuesta del servidor:');

    if (!res.success) {
      console.error('❌ El servidor devolvió error:', res.data || res);
      console.log('💡 Verifica logs: docker compose exec wordpress tail wp-content/debug.log');
      return;
    }

    console.log('✅ success:', res.success);
    console.log('   - pct:', res.data.pct);
    console.log('   - xp_earned:', res.data.xp_earned);
    console.log('   - next_lesson_url:', res.data.next_lesson_url || '(vacío)');
    console.log('   - rank_up:', res.data.rank_up);

    // Validar campos críticos
    if (typeof res.data.pct === 'undefined') {
      console.error('\n❌ CAMPO CRÍTICO FALTANTE: pct');
      console.log('💡 El backend no devolvió el porcentaje. El feedback bar no se mostrará.');
      console.log('💡 Fix: Commit 854e107 en wp-amsawal-router.php');
      return;
    }

    if (!res.data.next_lesson_url) {
      console.warn('\n⚠️  ADVERTENCIA: next_lesson_url está vacío');
      console.log('💡 El botón aparecerá pero no navegará a la siguiente lección.');
      console.log('💡 Fix: Commit a862453 en wp-amsawal-router.php');
    }

    // PASO 4: Crear feedback bar manualmente
    console.log('\nPASO 4: Crear feedback bar manualmente');
    console.log('Intentando crear feedback bar con datos de prueba...');

    try {
      showH5PFeedbackBar({
        pct: res.data.pct,
        xp_earned: res.data.xp_earned || 0,
        coins: 10,
        title: 'Prueba de diagnóstico',
        rank_up: false,
        new_level: 0,
        next_lesson_url: res.data.next_lesson_url || ''
      });
      console.log('✅ showH5PFeedbackBar() ejecutado sin errores');

      // Verificar en el DOM después de 500ms
      setTimeout(() => {
        console.log('\nPASO 5: Verificar en el DOM');
        const bar = document.querySelector('.duo-feedback-bar');
        const btn = document.querySelector('.duo-feedback-bar .duo-feedback-btn');
        const title = document.querySelector('.duo-feedback-bar .duo-feedback-title');

        if (bar) {
          console.log('✅ Feedback bar encontrado en el DOM');
          console.log('   - Visible:', window.getComputedStyle(bar).display !== 'none');
          console.log('   - Clases:', bar.className);
        } else {
          console.error('❌ Feedback bar NO encontrado en el DOM');
          console.log('💡 insertToDOM() no está funcionando');
          return;
        }

        if (title) {
          console.log('✅ Title encontrado:', title.textContent);
        } else {
          console.warn('⚠️  Title no encontrado');
        }

        if (btn) {
          console.log('✅ Botón encontrado:');
          console.log('   - Texto:', btn.textContent);
          console.log('   - Visible:', btn.offsetParent !== null);
          console.log('   - Has onclick:', typeof btn.onclick === 'function');
        } else {
          console.error('❌ Botón NO encontrado');
          console.log('💡 El feedback bar se creó pero sin botón');
        }

        // PASO 6: Verificar xAPI de H5P
        console.log('\nPASO 6: Verificar xAPI de H5P');
        if (window.H5P) {
          console.log('✅ H5P está cargado');
          if (window.H5P.externalDispatcher) {
            console.log('✅ H5P.externalDispatcher existe');
            console.log('💡 El xAPI debería estar disparándose automáticamente');
            console.log('💡 Completa una actividad H5P y revisa si el feedback bar aparece');
          } else {
            console.warn('⚠️  H5P.externalDispatcher no encontrado');
            console.log('💡 El xAPI no se disparará automáticamente');
          }
        } else {
          console.warn('⚠️  H5P no está cargado en esta página');
        }

        console.log('\n=== FIN DEL DIAGNÓSTICO ===');
        console.log('Si todo esto pasó correctamente, el problema es que:');
        console.log('1. El xAPI de H5P no está disparando el evento "completed"');
        console.log('2. O hay un error JavaScript en la consola (F12)');

      }, 500);

    } catch (e) {
      console.error('\n❌ Error al crear feedback bar:', e.message);
      console.log('Stack trace:');
      console.log(e.stack);
    }
  })
  .catch(err => {
    console.error('\n❌ Error de red:', err);
    console.log('💡 Posibles causas:');
    console.log('   - No estás logueado');
    console.log('   - El nonce es inválido (recarga la página)');
    console.log('   - WordPress está caído');
  });

})();
