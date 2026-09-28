{{-- Seintje voor de bezoekersteller (App\Models\PageView::confirm): pas na een teken van leven
     — scrollen, tikken, muis, toets — of tien seconden kijken telt een bezoek als mens.
     Geen cookie en geen gegevens: het verzoek is leeg. --}}
@guest
<script>
(function () {
  var sent = false, timer = null;
  var signs = ['pointerdown', 'pointermove', 'keydown', 'scroll', 'touchstart'];
  function send() {
    if (sent) return;
    sent = true;
    clearTimeout(timer);
    signs.forEach(function (name) { window.removeEventListener(name, send, true); });
    try {
      if (navigator.sendBeacon) navigator.sendBeacon('/m/gezien');
      else fetch('/m/gezien', { method: 'POST', keepalive: true });
    } catch (e) {}
  }
  signs.forEach(function (name) { window.addEventListener(name, send, { capture: true, passive: true }); });
  timer = setTimeout(function () { if (document.visibilityState === 'visible') send(); }, 10000);
})();
</script>
@endguest
