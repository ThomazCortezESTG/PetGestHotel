/* PetGestHotel — comportamento do frontend (sem dependências) */
(function () {
    'use strict';

    /* ---------- Menu móvel ---------- */
    var toggle = document.querySelector('.pg-nav-toggle');
    var nav = document.getElementById('pg-nav');

    function setMenu(open) {
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
        nav.classList.toggle('is-open', open);
    }

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            setMenu(toggle.getAttribute('aria-expanded') !== 'true');
        });
        // fecha ao escolher um link (ex.: âncoras da landing)
        nav.addEventListener('click', function (e) {
            if (e.target.closest('a')) {
                setMenu(false);
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                setMenu(false);
                toggle.focus();
            }
        });
    }

    /* ---------- Sombra no header ao fazer scroll ---------- */
    var header = document.getElementById('pg-header');
    if (header) {
        var onScroll = function () {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------- Animações ao fazer scroll ---------- */
    // O layout só põe .pg-reveal-on no <html> quando há IntersectionObserver
    // e o utilizador não pediu movimento reduzido.
    if (document.documentElement.classList.contains('pg-reveal-on')) {
        // filhos de um grupo entram em cascata
        document.querySelectorAll('[data-reveal-group]').forEach(function (group) {
            var items = group.querySelectorAll('[data-reveal]');
            items.forEach(function (el, i) {
                el.style.setProperty('--pg-delay', (i * 110) + 'ms');
            });
        });

        // Um elemento com clip-path fechado conta como invisível para o observer,
        // por isso nesses casos observa-se o pai.
        var revealTargets = new Map();

        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    revealTargets.get(entry.target).forEach(function (el) {
                        el.classList.add('is-visible');
                    });
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -12% 0px', threshold: 0.12 });

        document.querySelectorAll('[data-reveal]').forEach(function (el) {
            var watched = el.dataset.reveal === 'clip' ? el.parentElement : el;
            if (!revealTargets.has(watched)) {
                revealTargets.set(watched, []);
                revealObserver.observe(watched);
            }
            revealTargets.get(watched).push(el);
        });
    }

    /* ---------- Serviços: cada um mostra a sua foto ---------- */
    var serviceButtons = document.querySelectorAll('.pg-service[data-service]');
    var servicePhotos = document.querySelectorAll('.pg-services__img[data-service]');

    function showService(id) {
        serviceButtons.forEach(function (btn) {
            btn.setAttribute('aria-pressed', String(btn.dataset.service === id));
        });
        servicePhotos.forEach(function (photo) {
            photo.classList.toggle('is-active', photo.dataset.service === id);
        });
    }

    serviceButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { showService(btn.dataset.service); });
        btn.addEventListener('mouseenter', function () { showService(btn.dataset.service); });
        btn.addEventListener('focus', function () { showService(btn.dataset.service); });
    });

    /* ---------- Mostrar/esconder password (páginas de autenticação) ---------- */
    // Só é criado com JS: sem JS o campo continua a ser uma password normal.
    var eyeOpen = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    var eyeClosed = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.8 3.7M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M2 2l20 20"/></svg>';

    document.querySelectorAll('.pg-auth input[type="password"]').forEach(function (input) {
        var wrap = document.createElement('div');
        wrap.className = 'pg-auth__pw';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pg-auth__pw-toggle';
        btn.setAttribute('aria-controls', input.id);

        function render(visible) {
            btn.innerHTML = visible ? eyeClosed : eyeOpen;
            btn.setAttribute('aria-label', visible ? 'Esconder password' : 'Mostrar password');
            btn.setAttribute('aria-pressed', String(visible));
        }
        render(false);

        btn.addEventListener('click', function () {
            var visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            render(visible);
            input.focus();
        });
        wrap.appendChild(btn);
    });

    /* ---------- Datas da pesquisa de disponibilidade ---------- */
    function toISODate(d) {
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    var entrada = document.getElementById('pg-entrada');
    var saida = document.getElementById('pg-saida');

    if (entrada && saida) {
        var hoje = toISODate(new Date());
        entrada.min = hoje;
        saida.min = hoje;

        entrada.addEventListener('change', function () {
            if (!entrada.value) {
                return;
            }
            // a saída tem de ser pelo menos no dia seguinte à entrada
            var d = new Date(entrada.value + 'T00:00:00');
            d.setDate(d.getDate() + 1);
            var minSaida = toISODate(d);
            saida.min = minSaida;
            if (!saida.value || saida.value < minSaida) {
                saida.value = minSaida;
            }
        });
    }

    /* ---------- Toasts (mensagens flash) ----------
       Fecham quando a barra de tempo acaba (pausa com o rato por cima ou com foco),
       no botão X ou com Esc. Os erros (data-timeout="0") só fecham à mão. */
    function closeToast(toast) {
        if (toast.classList.contains('is-leaving')) {
            return;
        }
        toast.classList.add('is-leaving');

        var removed = false;
        var remove = function () {
            if (!removed) {
                removed = true;
                toast.remove();
            }
        };
        // 2.º tempo: depois de sair, encolhe o espaço que ocupava (os outros toasts sobem com suavidade)
        var collapse = function () {
            toast.style.height = toast.offsetHeight + 'px';
            void toast.offsetHeight;
            toast.classList.add('is-collapsing');
            toast.addEventListener('transitionend', function (e) {
                if (e.target === toast && e.propertyName === 'height') {
                    remove();
                }
            });
            setTimeout(remove, 400);
        };
        // Só reage ao fim da animação de saída do próprio toast: os animationend da barra
        // e do ícone também sobem até aqui e faziam-no desaparecer de repente.
        toast.addEventListener('animationend', function onOut(e) {
            if (e.target === toast && e.animationName === 'pg-toast-out') {
                toast.removeEventListener('animationend', onOut);
                collapse();
            }
        });
        setTimeout(function () { if (!toast.classList.contains('is-collapsing')) { collapse(); } }, 900); // se a animação estiver desligada
    }

    function initToast(toast) {
        var close = toast.querySelector('[data-toast-close]');
        if (close) {
            close.addEventListener('click', function () { closeToast(toast); });
        }
        var timer = toast.querySelector('.pg-toast__timer');
        if (timer) {
            // a barra é uma animação CSS: quando acaba, o toast fecha (e respeita a pausa no hover)
            timer.addEventListener('animationend', function () { closeToast(toast); });
        }
    }
    document.querySelectorAll('[data-toast]').forEach(initToast);

    /**
     * Mostra um toast a partir do JS (mesmo aspeto das mensagens flash do servidor).
     * pgToast('Fotografia atualizada.', 'success' | 'info' | 'warning' | 'error')
     */
    var toastIcons = {
        success: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.6v.1"/>',
        warning: '<path d="M12 4l9 16H3z"/><path d="M12 10v4.5M12 17.4v.1"/>',
        error: '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>'
    };
    var toastTimes = { success: 5, info: 6, warning: 8, error: 0 };
    window.pgToast = function (message, type) {
        type = toastIcons[type] ? type : 'info';
        var box = document.querySelector('.pg-toasts');
        if (!box) {
            box = document.createElement('div');
            box.className = 'pg-toasts';
            box.setAttribute('aria-live', 'polite');
            document.body.appendChild(box);
        }
        var secs = toastTimes[type];
        var toast = document.createElement('div');
        toast.className = 'pg-toast pg-toast--' + type;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.setAttribute('data-toast', '');
        toast.style.setProperty('--toast-time', secs + 's');
        toast.innerHTML =
            '<span class="pg-toast__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' + toastIcons[type] + '</svg></span>' +
            '<div class="pg-toast__msg"></div>' +
            '<button type="button" class="pg-toast__close" data-toast-close aria-label="Fechar mensagem"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>' +
            (secs > 0 ? '<span class="pg-toast__timer" aria-hidden="true"></span>' : '');
        toast.querySelector('.pg-toast__msg').textContent = message; // texto, nunca HTML
        box.appendChild(toast);
        initToast(toast);
        return toast;
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !document.querySelector('dialog[open]')) {
            var last = document.querySelectorAll('[data-toast]:not(.is-leaving)');
            if (last.length) {
                closeToast(last[last.length - 1]);
            }
        }
    });
})();
