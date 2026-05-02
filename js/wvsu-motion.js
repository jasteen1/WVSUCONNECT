/*!
 * WVSU CONNECT — subtle motion helpers (waits for entry splash when present).
 */
(function () {
  "use strict";

  var motionBooted = false;

  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  function bootstrapMotion() {
    if (motionBooted) return;
    motionBooted = true;

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
        { passive: true }
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
          { rootMargin: "0px 0px 8% 0px", threshold: 0.03 }
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

    window.setTimeout(function () {
      document.querySelectorAll("[data-io-animate]").forEach(function (el) {
        el.classList.add("wvsu-io-visible");
      });
    }, 800);
  }

  onReady(function () {
    var splash = document.getElementById("wvsu-entry-splash");
    if (!splash) {
      bootstrapMotion();
      return;
    }
    var fb = window.setTimeout(bootstrapMotion, 3400);
    window.addEventListener(
      "wvsu-entry-done",
      function onEntryDone() {
        window.clearTimeout(fb);
        bootstrapMotion();
      },
      { once: true }
    );
  });
})();
