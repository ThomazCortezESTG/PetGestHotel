/* PetGestHotel — área de cliente: etiqueta com física de pêndulo, etiqueta em direto e barra de guardar */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ======================================================================
       Etiqueta pendurada: pêndulo com mola e amortecimento (pendulum.js)
       ====================================================================== */
    var pendulums = [];
    if (!reduceMotion) {
        document.querySelectorAll('[data-perfil] .pg-tag').forEach(function (el) {
            if (el.offsetParent !== null) { // a pré-visualização fica escondida em ecrãs pequenos
                pendulums.push(new window.PgPendulum(el, { handle: el.querySelector('.pg-tag__body') || el }));
            }
        });
    }

    /* ======================================================================
       Formulário: etiqueta em direto e barra de guardar
       ====================================================================== */
    var form = document.getElementById('form-perfil');
    if (!form) {
        return;
    }

    var tag = document.querySelector('.pg-acct__preview .pg-tag');
    var bar = form.querySelector('[data-savebar]');
    var barText = form.querySelector('[data-savebar-text]');
    var idleText = barText ? barText.textContent : '';
    var initial = new FormData(form);
    var nudgeSide = 1;
    var lastNudge = 0;

    function format(field, value) {
        value = value.trim();
        if (field === 'telefone') {
            var digits = value.replace(/\s+/g, '');
            return digits.replace(/(\d{3})(?=\d)/g, '$1 ');
        }
        return value;
    }

    function updateTag(input) {
        if (!tag || !input.dataset.tag) {
            return;
        }
        var out = tag.querySelector('[data-tag-out="' + input.dataset.tag + '"]');
        if (!out) {
            return;
        }
        var value = format(input.dataset.tag, input.value);
        out.textContent = value || out.dataset.empty;

        // cada tecla dá um toque leve, alternando o lado (no máximo ~8 por segundo)
        var now = performance.now();
        if (now - lastNudge > 120) {
            lastNudge = now;
            nudgeSide = -nudgeSide;
            pendulums.forEach(function (p) {
                if (p.el === tag) {
                    p.impulse(0.55 * nudgeSide);
                }
            });
        }
    }

    function isDirty() {
        var now = new FormData(form);
        var dirty = false;
        initial.forEach(function (value, key) {
            if (key.indexOf('_csrf') === -1 && now.get(key) !== value) {
                dirty = true;
            }
        });
        return dirty;
    }

    form.addEventListener('input', function (e) {
        updateTag(e.target);
        if (bar && !bar.closest('.pg-acct').querySelector('.pg-acct__steps')) {
            var dirty = isDirty();
            bar.classList.toggle('is-dirty', dirty);
            barText.textContent = dirty ? 'Tem alterações por guardar.' : idleText;
        }
    });

    // avisa antes de sair com alterações por guardar
    var submitting = false;
    form.addEventListener('submit', function () { submitting = true; });
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && bar && bar.classList.contains('is-dirty')) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
