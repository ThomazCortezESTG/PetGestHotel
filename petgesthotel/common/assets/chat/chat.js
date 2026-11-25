/*
 * Chat PetGestHotel (front-office e back-office).
 *
 * Funciona sobre um elemento [data-chat] com a configuração em JSON (ver common/views/chat/_chat.php):
 *   { mensagens, enviar, lidas: URLs;  topico, wsUrl: MQTT;  csrfParam, csrfToken;  podeEscrever }
 *
 * 1. Carrega o histórico (GET mensagens).
 * 2. Liga-se ao Mosquitto por WebSockets e subscreve o tópico da conversa. Cada aviso
 *    ({"tipo":"mensagem",...}) faz pedir ao servidor as mensagens novas: o texto nunca vem pelo MQTT.
 * 3. Sem ligação ao Mosquitto, pergunta ao servidor de 15 em 15 segundos (e de minuto a minuto com ligação, por segurança).
 */
(function () {
    'use strict';

    var Paho = window.Paho && (window.Paho.Client ? window.Paho : window.Paho.MQTT);

    function init(root) {
        var cfg = JSON.parse(root.getAttribute('data-chat'));
        var log = root.querySelector('[data-chat-log]');
        var form = root.querySelector('[data-chat-form]');
        var input = form ? form.querySelector('textarea') : null;
        var button = form ? form.querySelector('button[type="submit"]') : null;
        var errorBox = root.querySelector('[data-chat-error]');
        var status = root.querySelector('[data-chat-status]');

        var lastId = 0;
        var lastDay = null;
        var lastAuthor = null;
        var loading = false;
        var again = false;
        var mqttOk = false;
        var timer = null;

        // ---------- desenhar ----------

        var dayFmt = new Intl.DateTimeFormat('pt-PT', { weekday: 'long', day: 'numeric', month: 'long' });
        var timeFmt = new Intl.DateTimeFormat('pt-PT', { hour: '2-digit', minute: '2-digit' });

        function dayLabel(d) {
            var today = new Date();
            var y = new Date(); y.setDate(today.getDate() - 1);
            if (d.toDateString() === today.toDateString()) { return 'Hoje'; }
            if (d.toDateString() === y.toDateString()) { return 'Ontem'; }
            var s = dayFmt.format(d);
            return s.charAt(0).toUpperCase() + s.slice(1);
        }

        function el(tag, cls, text) {
            var e = document.createElement(tag);
            if (cls) { e.className = cls; }
            if (text !== undefined) { e.textContent = text; }
            return e;
        }

        function avatar(p) {
            var a = el('span', 'pg-chat__avatar');
            a.setAttribute('aria-hidden', 'true');
            a.textContent = p.iniciais || '?';
            if (p.foto_url) {
                var img = new Image();
                img.alt = '';
                img.onload = function () { a.textContent = ''; a.appendChild(img); };
                img.src = p.foto_url;
            }
            return a;
        }

        function render(m) {
            if (m.id <= lastId) { return; } // já está no ecrã
            lastId = m.id;

            var empty = log.querySelector('[data-chat-empty]');
            if (empty) { empty.remove(); }

            var when = new Date(m.created_at);
            var day = when.toDateString();
            if (day !== lastDay) {
                log.appendChild(el('p', 'pg-chat__day', dayLabel(when)));
                lastDay = day;
                lastAuthor = null;
            }

            if (m.tipo === 'sistema') {
                log.appendChild(el('p', 'pg-chat__system', m.texto));
                lastAuthor = null;
                return;
            }

            var authorKey = m.autor ? m.autor.id : 0;
            var row = el('div', 'pg-chat__msg' + (m.minha ? ' pg-chat__msg--mine' : ' pg-chat__msg--other'));
            var first = authorKey !== lastAuthor;
            if (first) { row.classList.add('pg-chat__msg--first'); }

            if (!m.minha) {
                row.appendChild(first && m.autor ? avatar(m.autor) : el('span', 'pg-chat__avatar pg-chat__avatar--gap'));
            }
            var bubble = el('div', 'pg-chat__bubble');
            if (first && !m.minha && m.autor) {
                bubble.appendChild(el('span', 'pg-chat__author', m.autor.nome));
            }
            if (m.anexo) {
                bubble.classList.add('pg-chat__bubble--media');
                bubble.appendChild(media(m.anexo));
            }
            if (m.texto) {
                bubble.appendChild(el('p', 'pg-chat__text', m.texto));
            }
            var t = el('time', 'pg-chat__time', timeFmt.format(when));
            t.dateTime = m.created_at;
            bubble.appendChild(t);
            row.appendChild(bubble);

            // leitores de ecrã: dizer quem escreveu, mesmo quando o nome não aparece
            var who = m.minha ? 'Eu' : (m.autor ? m.autor.nome : '');
            var what = m.anexo ? (m.anexo.tipo === 'video' ? 'vídeo' : 'fotografia') + (m.texto ? ', ' + m.texto : '') : m.texto;
            bubble.setAttribute('aria-label', who + ', ' + timeFmt.format(when) + ': ' + what);

            log.appendChild(row);
            lastAuthor = authorKey;
        }

        // Fotografia (abre em tamanho real noutro separador) ou vídeo com controlos
        function media(anexo) {
            // quando a imagem/vídeo acaba de carregar a conversa cresce: continua no fundo se lá estava
            var keep = function () { if (autoScroll) { scrollDown(); } };
            if (anexo.tipo === 'video') {
                var v = document.createElement('video');
                v.className = 'pg-chat__media';
                v.controls = true;
                v.preload = 'metadata';
                v.playsInline = true;
                v.src = anexo.url;
                v.addEventListener('loadedmetadata', keep);
                return v;
            }
            var a = el('a', 'pg-chat__media-link');
            a.href = anexo.url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.setAttribute('aria-label', 'Abrir fotografia em tamanho real');
            var img = new Image();
            img.className = 'pg-chat__media';
            img.alt = 'Fotografia';
            img.loading = 'lazy';
            img.addEventListener('load', keep);
            img.src = anexo.url;
            a.appendChild(img);
            return a;
        }

        function nearBottom(margin) {
            return log.scrollHeight - log.scrollTop - log.clientHeight < (margin || 120);
        }

        var autoScroll = true;
        log.addEventListener('scroll', function () { autoScroll = nearBottom(); });

        function scrollDown() {
            log.scrollTop = log.scrollHeight;
            autoScroll = true;
        }

        function setStatus(text) {
            if (status) { status.textContent = text; }
        }

        function showError(text) {
            if (!errorBox) { return; }
            errorBox.textContent = text || '';
            errorBox.hidden = !text;
        }

        // ---------- servidor ----------

        function post(url, data) {
            var body = new FormData();
            body.append(cfg.csrfParam, cfg.csrfToken);
            Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
            return fetch(url, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
        }

        function markRead() {
            if (document.visibilityState === 'visible' && lastId > 0) {
                post(cfg.lidas, {}).catch(function () {});
            }
        }

        var gone = false;

        function load() {
            if (gone) { return; }
            if (loading) { again = true; return; }
            loading = true;
            var stick = lastId === 0 || nearBottom();
            var url = cfg.mensagens + (cfg.mensagens.indexOf('?') >= 0 ? '&' : '?') + 'depois=' + lastId;

            fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (res) {
                    if (res.status === 404 || res.status === 403) {
                        // a reserva foi apagada ou a conversa passou para outra pessoa: parar de perguntar
                        gone = true;
                        clearInterval(timer);
                        if (form) { form.hidden = true; }
                        return [];
                    }
                    if (!res.ok) { throw new Error(res.status); }
                    return res.json();
                })
                .then(function (lista) {
                    var before = lastId;
                    var empty = log.querySelector('[data-chat-empty]');
                    if (empty && lista.length === 0) { empty.textContent = 'Ainda não há mensagens.'; }
                    lista.forEach(render);
                    setStatus(gone ? 'Já não tem acesso a esta conversa. Recarregue a página.' : '');
                    if (stick) { scrollDown(); }
                    if (lastId > before) { markRead(); }
                })
                .catch(function () {
                    setStatus('Sem ligação ao servidor. A tentar de novo…');
                })
                .finally(function () {
                    loading = false;
                    if (again) { again = false; load(); }
                });
        }

        // ---------- anexo (fotografia ou vídeo, só o colaborador responsável) ----------

        var fileInput = root.querySelector('[data-chat-file-input]');
        var fileBox = root.querySelector('[data-chat-file]');
        var file = null;
        var fileUrl = null;

        function humanSize(b) {
            return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
        }

        function clearFile() {
            file = null;
            if (fileUrl) { URL.revokeObjectURL(fileUrl); fileUrl = null; }
            if (fileInput) { fileInput.value = ''; }
            if (fileBox) {
                fileBox.hidden = true;
                fileBox.querySelector('[data-chat-file-thumb]').innerHTML = '';
                fileBox.querySelector('[data-chat-file-progress]').hidden = true;
            }
        }

        function pickFile(f) {
            showError('');
            var video = /^video\//.test(f.type);
            var image = /^image\//.test(f.type);
            if (!video && !image) {
                showError('Só pode enviar fotografias ou vídeos.');
                return;
            }
            var max = video ? cfg.maxVideo : cfg.maxImagem;
            if (f.size > max) {
                showError((video ? 'O vídeo' : 'A fotografia') + ' tem ' + humanSize(f.size) + '. O máximo é ' + (video ? cfg.maxVideoTexto : cfg.maxImagemTexto) + '.');
                return;
            }
            clearFile();
            file = f;
            var thumb = fileBox.querySelector('[data-chat-file-thumb]');
            if (image) {
                fileUrl = URL.createObjectURL(f);
                var img = new Image();
                img.alt = '';
                img.src = fileUrl;
                thumb.appendChild(img);
            } else {
                thumb.textContent = '🎬';
            }
            fileBox.querySelector('[data-chat-file-name]').textContent = f.name + ' · ' + humanSize(f.size);
            fileBox.hidden = false;
            input.placeholder = 'Legenda (opcional)…';
            input.focus();
        }

        if (fileInput) {
            root.querySelector('[data-chat-attach]').addEventListener('click', function () { fileInput.click(); });
            fileInput.addEventListener('change', function () { if (fileInput.files[0]) { pickFile(fileInput.files[0]); } });
            fileBox.querySelector('[data-chat-file-remove]').addEventListener('click', function () {
                clearFile();
                input.placeholder = 'Escreva uma mensagem…';
                input.focus();
            });
            // também dá para arrastar o ficheiro para cima da conversa
            root.addEventListener('dragover', function (e) { e.preventDefault(); root.classList.add('is-dragging'); });
            root.addEventListener('dragleave', function (e) { if (!root.contains(e.relatedTarget)) { root.classList.remove('is-dragging'); } });
            root.addEventListener('drop', function (e) {
                e.preventDefault();
                root.classList.remove('is-dragging');
                if (e.dataTransfer.files[0]) { pickFile(e.dataTransfer.files[0]); }
            });
        }

        // Com anexo usa XMLHttpRequest, que mostra o progresso do envio (o fetch não mostra)
        function upload(texto) {
            return new Promise(function (resolve, reject) {
                var body = new FormData();
                body.append(cfg.csrfParam, cfg.csrfToken);
                body.append('texto', texto);
                body.append('anexo', file);

                var bar = fileBox.querySelector('[data-chat-file-progress]');
                bar.hidden = false;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', cfg.enviar);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) { bar.firstChild.style.width = Math.round(e.loaded / e.total * 100) + '%'; }
                });
                xhr.onload = function () {
                    var data = {};
                    try { data = JSON.parse(xhr.responseText); } catch (err) { /* resposta que não é JSON */ }
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve(data);
                    } else {
                        reject(new Error(data.erro || data.message || 'Não foi possível enviar (erro ' + xhr.status + ').'));
                    }
                };
                xhr.onerror = function () { reject(new Error('Sem ligação ao servidor.')); };
                xhr.send(body);
            });
        }

        function sendText(texto) {
            return post(cfg.enviar, { texto: texto }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (data) {
                    if (!res.ok) {
                        throw new Error(data.erro || data.message || 'Não foi possível enviar (erro ' + res.status + ').');
                    }
                    return data;
                });
            });
        }

        function send(e) {
            e.preventDefault();
            var texto = input.value.trim();
            if (!texto && !file) { input.focus(); return; }

            button.disabled = true;
            showError('');
            (file ? upload(texto) : sendText(texto))
                .then(function (m) {
                    input.value = '';
                    input.placeholder = 'Escreva uma mensagem…';
                    clearFile();
                    resize();
                    render(m);
                    scrollDown();
                })
                .catch(function (err) {
                    showError(err.message);
                    if (fileBox) { fileBox.querySelector('[data-chat-file-progress]').hidden = true; }
                })
                .finally(function () {
                    button.disabled = false;
                    input.focus();
                });
        }

        // ---------- caixa de texto ----------

        function resize() {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 160) + 'px';
        }

        if (form) {
            form.addEventListener('submit', send);
            input.addEventListener('input', resize);
            // Enter envia; Shift+Enter muda de linha
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                    e.preventDefault();
                    form.requestSubmit ? form.requestSubmit() : send(e);
                }
            });
        }

        // ---------- tempo real ----------

        function schedule() {
            clearInterval(timer);
            timer = setInterval(load, mqttOk ? 60000 : 15000);
        }

        function connectMqtt() {
            if (!Paho || !cfg.topico) { schedule(); return; }

            var url = cfg.wsUrl || ((location.protocol === 'https:' ? 'wss://' : 'ws://') + location.hostname + ':9001/');
            var client;
            try {
                client = new Paho.Client(url, 'petgest-web-' + Math.random().toString(16).slice(2, 10));
            } catch (err) {
                schedule();
                return;
            }

            client.onConnectionLost = function () {
                mqttOk = false;
                setStatus('');
                schedule();
                setTimeout(connect, 5000);
            };
            client.onMessageArrived = function () {
                load();
            };

            function connect() {
                client.connect({
                    timeout: 5,
                    keepAliveInterval: 30,
                    useSSL: url.indexOf('wss://') === 0,
                    onSuccess: function () {
                        mqttOk = true;
                        setStatus('');
                        client.subscribe(cfg.topico);
                        schedule();
                        load(); // pode ter chegado alguma coisa enquanto estava desligado
                    },
                    onFailure: function () {
                        mqttOk = false;
                        schedule();
                        setTimeout(connect, 15000);
                    }
                });
            }

            connect();
        }

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') { load(); markRead(); }
        });

        load();
        schedule();
        connectMqtt();
    }

    document.querySelectorAll('[data-chat]').forEach(init);
}());
