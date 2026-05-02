/*!
 * Branded entry splash → dispatches window "wvsu-entry-done" when removed.
 */
(function () {
  "use strict";

  function prefersReducedMotion() {
    try {
      return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    } catch (e) {
      return false;
    }
  }

  function fireDone() {
    try {
      window.dispatchEvent(new CustomEvent("wvsu-entry-done", { bubbles: true }));
    } catch (e) {
      /* ignore */
    }
  }

  function unlock() {
    document.documentElement.classList.remove("wvsu-entry-lock");
    document.body.classList.remove("wvsu-entry-lock");
  }

  function teardown(splash, exitedRef) {
    if (!splash || exitedRef.v) return;
    exitedRef.v = true;
    try {
      splash.remove();
    } catch (e) {
      if (splash && splash.parentNode) splash.parentNode.removeChild(splash);
    }
    unlock();
    document.documentElement.classList.add("wvsu-entry-done");
    fireDone();
  }

  function boot() {
    var splash = document.getElementById("wvsu-entry-splash");
    if (!splash) {
      fireDone();
      return;
    }

    var exited = { v: false };
    var exitStarted = false;

    document.documentElement.classList.add("wvsu-entry-lock");
    document.body.classList.add("wvsu-entry-lock");

    if (prefersReducedMotion()) {
      teardown(splash, exited);
      return;
    }

    var home = document.body.classList.contains("wvsu-home-page");
    var minMs = home ? 1080 : 720;
    var maxMs = home ? 3200 : 2400;

    var loadP =
      document.readyState === "complete"
        ? Promise.resolve()
        : new Promise(function (resolve) {
            window.addEventListener(
              "load",
              function () {
                resolve();
              },
              { once: true }
            );
          });

    var minP = new Promise(function (resolve) {
      window.setTimeout(resolve, minMs);
    });

    function finishExit() {
      if (exitStarted || exited.v) return;
      exitStarted = true;
      splash.classList.add("wvsu-entry-splash--exiting");
      var finalized = false;
      function finalize() {
        if (finalized) return;
        finalized = true;
        teardown(splash, exited);
      }
      splash.addEventListener(
        "transitionend",
        function onEnd(ev) {
          if (
            ev.target !== splash ||
            (ev.propertyName !== "opacity" && ev.propertyName !== "filter")
          )
            return;
          splash.removeEventListener("transitionend", onEnd);
          finalize();
        }
      );
      window.setTimeout(finalize, 960);
    }

    var hardOut = window.setTimeout(function () {
      finishExit();
    }, maxMs);

    Promise.all([loadP, minP]).then(function () {
      window.clearTimeout(hardOut);
      finishExit();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
