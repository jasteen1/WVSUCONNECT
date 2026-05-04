/**
 * Blocks form submit unless the user confirms. Set data-wvsu-confirm="Your message" on the form.
 */
(function () {
    function bind() {
        document.querySelectorAll('form[data-wvsu-confirm]').forEach(function (form) {
            if (form.getAttribute('data-wvsu-confirm-bound') === '1') {
                return;
            }
            form.setAttribute('data-wvsu-confirm-bound', '1');
            form.addEventListener(
                'submit',
                function (e) {
                    var msg = form.getAttribute('data-wvsu-confirm');
                    if (!msg) {
                        return;
                    }
                    if (!window.confirm(msg)) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                },
                true
            );
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
