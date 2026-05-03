/**
 * Listing detail pages: arrow uses browser history when there is prior same-tab navigation,
 * else follows href fallback (products.php / services.php) or explicit ?return= from the prior page.
 */
(function () {
  function bind() {
    document.querySelectorAll('a[data-wvsu-smart-back="1"]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        if (window.history.length > 1) {
          e.preventDefault();
          window.history.back();
        }
      });
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
