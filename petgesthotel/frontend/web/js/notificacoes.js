/*
 * Sino de notificações do cliente (frontend/views/layouts/_notificacoes.php).
 *
 * - O número vem já da página; a lista só é pedida quando se abre o painel.
 * - Tempo real: subscreve <prefixo>/utilizador/<id> no Mosquitto. Cada aviso faz pedir a lista outra vez
 *   (o aviso só traz ids) e, se for uma notificação nova, mostra um toast, exceto se a conversa
 *   dessa notificação já estiver aberta na página.
 * - Sem Mosquitto, atualiza de minuto a minuto.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-notificacoes]');
    if (!root) { return; }

    var cfg = JSON.parse(root.getAttribute('data-notificacoes'));
    var btn = root.querySelector('[data-bell-btn]');
    var panel = root.querySelector('[data-bell-panel]');
    var list = root.querySelector('[data-bell-list]');
    var count = root.querySelector('[data-bell-count]');
    var allBtn = root.querySelector('[data-bell-all]');
    var toggle = document.querySelector('.pg-nav-toggle');
    var Paho = window.Paho && (window.Paho.Client ? window.Paho : window.Paho.MQTT);
    var vistas = {}; // id -> updated_at, para saber o que é novo

    var relFmt = window.Intl && Intl.RelativeTimeFormat ? new Intl.RelativeTimeFormat('pt-PT', { numeric: 'auto' }) : null;
    function quando(iso) {
        var s = Math.round((new Date(iso) - Date.now()) / 1000);
        if (!relFmt) { return new Date(iso).toLocaleString('pt-PT'); }
        var a = Math.abs(s);
        if (a < 60) { return 'agora mesmo'; }
        if (a < 3600) { return relFmt.format(Math.round(s / 60), 'minute'); }
        if (a < 86400) { return relFmt.format(Math.round(s / 3600), 'hour'); }
        return relFmt.format(Math.round(s / 86400), 'day');
    }

    function setCount(n) {
        count.textContent = n > 9 ? '9+' : String(n);
        count.hidden = n === 0;
        allBtn.hidden = n === 0;
        btn.setAttribute('aria-label', 'Notificações' + (n > 0 ? ', ' + n + ' por ler' : ''));
        if (toggle) { toggle.classList.toggle('has-unread', n > 0); }
    }

    function render(itens) {
        list.innerHTML = '';
        if (itens.length === 0) {
            var li = document.createElement('li');
            li.className = 'pg-bell__empty';
            li.textContent = 'Não tem notificações.';
            list.appendChild(li);
            return;
        }
        itens.forEach(function (n) {
            var li = document.createElement('li');
            var a = document.createElement('a');
            a.className = 'pg-bell__item' + (n.lida ? '' : ' is-unread');
            a.href = n.url;
            var t = document.createElement('strong');
            t.textContent = n.titulo;
            var p = document.createElement('span');
            p.className = 'pg-bell__text';
            p.textContent = n.texto;
            var time = document.createElement('time');
            time.dateTime = n.updated_at;
            time.textContent = quando(n.updated_at);
            a.appendChild(t);
            a.appendChild(p);
            a.appendChild(time);
            li.appendChild(a);
            list.appendChild(li);
        });
    }

    function conversaAberta(id) {
        return !!document.querySelector('[data-chat][data-conversa-id="' + id + '"]');
    }

    function load(avisar) {
        return fetch(cfg.lista, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
            .then(function (data) {
                setCount(data.nao_lidas);
                render(data.itens);
                data.itens.forEach(function (n) {
                    var nova = vistas[n.id] !== n.updated_at;
                    if (avisar && nova && !n.lida && window.pgToast
                        && !(n.tipo === 'mensagem' && conversaAberta(n.ref_id))) {
                        window.pgToast(n.titulo + (n.texto ? ': ' + n.texto : ''), 'info');
                    }
                    vistas[n.id] = n.updated_at;
                });
            })
            .catch(function () { /* tenta outra vez no próximo aviso */ });
    }

    // ---------- painel ----------

    function open() {
        panel.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        load(false);
    }
    function close() {
        panel.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
    }
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.hidden ? open() : close();
    });
    document.addEventListener('click', function (e) {
        if (!panel.hidden && !root.contains(e.target)) { close(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) { close(); btn.focus(); }
    });

    allBtn.addEventListener('click', function () {
        var body = new FormData();
        var param = document.querySelector('meta[name="csrf-param"]');
        var token = document.querySelector('meta[name="csrf-token"]');
        if (param && token) { body.append(param.content, token.content); }
        fetch(cfg.lidas, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function () { return load(false); });
    });

    // ---------- tempo real ----------

    var timer = setInterval(function () { load(true); }, 60000);
    load(false); // memoriza o que já existe, para só avisar do que for novo

    if (Paho && cfg.topico) {
        var url = cfg.wsUrl || ((location.protocol === 'https:' ? 'wss://' : 'ws://') + location.hostname + ':9001/');
        var client;
        try {
            client = new Paho.Client(url, 'petgest-bell-' + Math.random().toString(16).slice(2, 10));
        } catch (err) {
            client = null;
        }
        if (client) {
            var connect = function () {
                client.connect({
                    timeout: 5,
                    keepAliveInterval: 30,
                    useSSL: url.indexOf('wss://') === 0,
                    onSuccess: function () {
                        client.subscribe(cfg.topico);
                        clearInterval(timer);
                        timer = setInterval(function () { load(true); }, 300000);
                    },
                    onFailure: function () { setTimeout(connect, 30000); }
                });
            };
            client.onConnectionLost = function () { setTimeout(connect, 5000); };
            client.onMessageArrived = function () { load(true); };
            connect();
        }
    }
}());
