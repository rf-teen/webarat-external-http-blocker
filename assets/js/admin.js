/**
 * Webarat External HTTP Blocker - Admin Scripts
 */
document.addEventListener('DOMContentLoaded', function () {
    // Quick Presets Handler
    var presetButtons = document.querySelectorAll('.preset-btn');
    presetButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var domain = this.getAttribute('data-domain');
            if (!domain) return;
            var textarea = document.getElementById('wb-domains');
            if (!textarea) return;

            var domainsToAdd = domain.split(',').map(function (d) { return d.trim(); }).filter(Boolean);
            var currentLines = textarea.value.split('\n').map(function (l) { return l.trim(); }).filter(Boolean);

            domainsToAdd.forEach(function (d) {
                if (currentLines.indexOf(d) === -1) {
                    currentLines.push(d);
                }
            });

            textarea.value = currentLines.join('\n');
        });
    });

    // Clear Logs Handler
    var clearBtn = document.getElementById('wb-clear');
    if (clearBtn && typeof webaratBlocker !== 'undefined') {
        clearBtn.addEventListener('click', function () {
            if (!confirm(webaratBlocker.confirmClear)) return;
            var btn = this;
            btn.disabled = true;
            fetch(webaratBlocker.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'webarat_clear_blocked_logs',
                    nonce: webaratBlocker.nonce
                })
            }).then(function () {
                window.location.reload();
            });
        });
    }
});
