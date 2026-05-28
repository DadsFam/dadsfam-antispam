/* DadsFam Anti-Spam — Frontend honeypot + timestamp injector
 * Injects hidden fields into ALL <form> elements on the page.
 * This ensures generic forms not handled by a specific plugin hook
 * are still protected by the honeypot and time check.
 */
(function () {
    'use strict';

    var vars = window.dfsasVars;
    if (!vars) return;

    function inject(form) {
        // Skip admin forms, login forms, WP internal forms
        var id     = form.getAttribute('id') || '';
        var action = form.getAttribute('action') || '';
        var skip   = ['loginform','registerform','lostpasswordform','dfsas'];
        for (var i = 0; i < skip.length; i++) {
            if (id.indexOf(skip[i]) !== -1 || action.indexOf('wp-login') !== -1) return;
        }

        // Already injected?
        if (form.querySelector('[name="' + vars.hp_name + '"]')) return;

        // ── Honeypot field (positioned off-screen) ─────────────────────────
        var wrapper = document.createElement('div');
        wrapper.setAttribute('aria-hidden', 'true');
        wrapper.style.cssText = 'position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;';

        var hp = document.createElement('input');
        hp.type          = 'text';
        hp.name          = vars.hp_name;
        hp.value         = '';
        hp.tabIndex      = -1;
        hp.autocomplete  = 'off';
        wrapper.appendChild(hp);
        form.appendChild(wrapper);

        // ── Timestamp field ────────────────────────────────────────────────
        var ts = document.createElement('input');
        ts.type  = 'hidden';
        ts.name  = vars.ts_name;
        ts.value = vars.ts_value;
        form.appendChild(ts);
    }

    function injectAll() {
        var forms = document.querySelectorAll('form');
        for (var i = 0; i < forms.length; i++) {
            inject(forms[i]);
        }
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectAll);
    } else {
        injectAll();
    }

    // Also watch for dynamically inserted forms (e.g. popup plugins)
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var nodes = mutations[i].addedNodes;
                for (var j = 0; j < nodes.length; j++) {
                    var node = nodes[j];
                    if (node.nodeType !== 1) continue;
                    if (node.tagName === 'FORM') { inject(node); }
                    var nested = node.querySelectorAll ? node.querySelectorAll('form') : [];
                    for (var k = 0; k < nested.length; k++) { inject(nested[k]); }
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

})();
