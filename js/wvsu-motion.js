/*!
 * WVSU CONNECT — subtle motion helpers
 */
(function () {
  "use strict";

  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  onReady(function () {
    document.documentElement.classList.add("wvsu-loaded");
    document.body.classList.add("wvsu-app");

    var nav = document.querySelector(".navbar.navbar-wvsu");
    if (nav) {
      nav.classList.add("navbar-wvsu--shown");
      var ticking = false;
      function updateNav() {
        if (window.scrollY > 10) nav.classList.add("navbar-wvsu--scrolled");
        else nav.classList.remove("navbar-wvsu--scrolled");
        ticking = false;
      }
      window.addEventListener(
        "scroll",
        function () {
          if (!ticking) {
            requestAnimationFrame(updateNav);
            ticking = true;
          }
        },
        { passive: true },
      );
      updateNav();
    }

    document.querySelectorAll("[data-animate]").forEach(function (el, i) {
      el.style.animationDelay = Math.min(i, 20) * 0.045 + "s";
    });

    try {
      if (
        !window.matchMedia("(prefers-reduced-motion: reduce)").matches &&
        "IntersectionObserver" in window
      ) {
        var obs = new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                entry.target.classList.add("wvsu-io-visible");
                obs.unobserve(entry.target);
              }
            });
          },
          { rootMargin: "0px 0px 8% 0px", threshold: 0.03 },
        );
        document.querySelectorAll("[data-io-animate]").forEach(function (el) {
          obs.observe(el);
        });
      } else {
        document.querySelectorAll("[data-io-animate]").forEach(function (el) {
          el.classList.add("wvsu-io-visible");
        });
      }
    } catch (e) {
      document.querySelectorAll("[data-io-animate]").forEach(function (el) {
        el.classList.add("wvsu-io-visible");
      });
    }

    setTimeout(function () {
      document.querySelectorAll("[data-io-animate]").forEach(function (el) {
        el.classList.add("wvsu-io-visible");
      });
    }, 800);
  });
})();
