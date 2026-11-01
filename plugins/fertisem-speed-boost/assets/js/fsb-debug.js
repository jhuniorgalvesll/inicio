/**
 * Fertisem Speed Boost – diagnóstico (solo administradores con "Modo diagnóstico" activo).
 * Muestra en consola qué scripts inline usan wp / _ / moment antes de que existan,
 * y un resumen de métricas (LCP, CLS, TTFB) para comparar con ?fsb_off=1.
 */
(function (w, d) {
  'use strict';
  var TAG = '[fertisem-speed-boost]';

  function label(s) {
    if (s.src) return s.src;
    var t = (s.text || '').trim().replace(/\s+/g, ' ');
    return t ? '[inline] ' + t.slice(0, 80) + (t.length > 80 ? '…' : '') : '[inline vacío]';
  }

  function scan(re, msg) {
    [].forEach.call(d.scripts, function (s) {
      if (!s.src && re.test(s.text || '')) console.warn(TAG, msg, '->', label(s));
    });
  }

  d.addEventListener('DOMContentLoaded', function () {
    if (!w.wp) scan(/\bwp\./, 'Uso inline de "wp." sin wp cargado:');
    if (!w._ && !w.lodash) scan(/\b_\.\w/, 'Uso inline de "_" sin lodash/underscore:');
    if (!w.moment) scan(/\bmoment\(/, 'Uso inline de "moment(" sin moment:');

    var deferred = [].filter.call(d.scripts, function (s) { return s.defer; }).length;
    var delayed = d.querySelectorAll('script[type="fsb/delay"]').length;
    console.info(TAG, 'scripts con defer:', deferred, '| retrasados hasta interacción:', delayed);
  });

  if ('PerformanceObserver' in w) {
    try {
      var cls = 0;
      new PerformanceObserver(function (l) {
        var e = l.getEntries(); var last = e[e.length - 1];
        console.info(TAG, 'LCP:', Math.round(last.startTime) + 'ms', last.element || '');
      }).observe({ type: 'largest-contentful-paint', buffered: true });
      new PerformanceObserver(function (l) {
        l.getEntries().forEach(function (e) { if (!e.hadRecentInput) cls += e.value; });
        console.info(TAG, 'CLS acumulado:', cls.toFixed(3));
      }).observe({ type: 'layout-shift', buffered: true });
      var nav = performance.getEntriesByType('navigation')[0];
      if (nav) console.info(TAG, 'TTFB:', Math.round(nav.responseStart) + 'ms');
    } catch (e) {}
  }

  w.addEventListener('error', function (e) {
    console.error(TAG, 'Error JS:', e.message, '@', e.filename + ':' + e.lineno,
      '→ prueba la misma URL con ?fsb_off=1 para ver si lo causa la optimización.');
  });
})(window, document);
