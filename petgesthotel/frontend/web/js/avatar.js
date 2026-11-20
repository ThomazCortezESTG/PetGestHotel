/* PetGestHotel — fotografia de perfil: escolher ficheiro → recortar (arrastar + zoom) → enviar.
   O recorte é feito aqui num <canvas>; o servidor recebe só a imagem final 512×512 (PerfilController::actionAvatar). */
(function () {
    'use strict';

    var dialog = document.querySelector('[data-cropper]');
    var input = document.querySelector('[data-avatar-input]');
    if (!dialog || !input) {
        return;
    }

    var stage = dialog.querySelector('[data-crop-stage]');
    var img = dialog.querySelector('[data-crop-img]');
    var range = dialog.querySelector('[data-crop-range]');
    var saveBtn = dialog.querySelector('[data-crop-save]');
    var OUT = 512;
    var MIN_ZOOM = 1;
    var MAX_ZOOM = 4;
    var st = { nw: 0, nh: 0, size: 0, base: 1, zoom: 1, x: 0, y: 0 };
    var objectUrl = null;

    function toast(msg, type) {
        if (window.pgToast) {
            window.pgToast(msg, type);
        } else {
            window.alert(msg);
        }
    }

    /* ---------- geometria ---------- */
    function maxOffset() {
        var s = st.base * st.zoom;
        return { x: Math.max(0, (st.nw * s - st.size) / 2), y: Math.max(0, (st.nh * s - st.size) / 2) };
    }

    // a imagem tem sempre de cobrir a área toda (sem buracos no recorte)
    function clamp() {
        var m = maxOffset();
        st.x = Math.max(-m.x, Math.min(m.x, st.x));
        st.y = Math.max(-m.y, Math.min(m.y, st.y));
    }

    function render() {
        img.style.width = (st.nw * st.base) + 'px';
        img.style.height = (st.nh * st.base) + 'px';
        img.style.transform = 'translate(-50%, -50%) translate(' + st.x + 'px, ' + st.y + 'px) scale(' + st.zoom + ')';
        range.value = String(st.zoom);
        range.style.setProperty('--fill', ((st.zoom - MIN_ZOOM) / (MAX_ZOOM - MIN_ZOOM) * 100) + '%');
    }

    // zoom à volta de um ponto (px, py relativos ao centro): o que está debaixo do cursor fica no sítio
    function setZoom(z, px, py) {
        z = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, z));
        px = px || 0;
        py = py || 0;
        var k = z / st.zoom;
        st.x = px - (px - st.x) * k;
        st.y = py - (py - st.y) * k;
        st.zoom = z;
        clamp();
        render();
    }

    function measure() {
        var old = st.size;
        st.size = stage.clientWidth;
        st.base = Math.max(st.size / st.nw, st.size / st.nh);
        if (old && st.size !== old) {
            st.x *= st.size / old;
            st.y *= st.size / old;
        }
        clamp();
        render();
    }

    /* ---------- abrir / fechar com animação ---------- */
    function open() {
        dialog.classList.remove('is-closing');
        dialog.showModal();
        st.size = 0;
        measure();
        stage.focus();
    }

    function close() {
        if (!dialog.open || dialog.classList.contains('is-closing')) {
            return;
        }
        dialog.classList.add('is-closing');
        var finished = false;
        var done = function () {
            if (finished) {
                return;
            }
            finished = true;
            dialog.classList.remove('is-closing');
            dialog.close();
        };
        dialog.addEventListener('animationend', function onEnd(e) {
            if (e.target === dialog) {
                dialog.removeEventListener('animationend', onEnd);
                done();
            }
        });
        setTimeout(done, 400); // se a animação estiver desligada
    }

    dialog.addEventListener('cancel', function (e) { // tecla Esc
        e.preventDefault();
        close();
    });
    dialog.addEventListener('click', function (e) { // clique no fundo escuro
        if (e.target === dialog) {
            close();
        }
    });
    dialog.querySelectorAll('[data-crop-cancel]').forEach(function (b) { b.addEventListener('click', close); });
    dialog.querySelector('[data-crop-other]').addEventListener('click', function () { input.click(); });

    /* ---------- escolher ficheiro ---------- */
    document.querySelectorAll('[data-avatar-pick]').forEach(function (btn) {
        btn.addEventListener('click', function () { input.click(); });
    });

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        input.value = ''; // permite escolher o mesmo ficheiro outra vez
        if (!file) {
            return;
        }
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
            toast('Use uma imagem JPG, PNG ou WebP.', 'error');
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            toast('Essa imagem é muito grande. Escolha uma com menos de 20 MB.', 'error');
            return;
        }
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
        objectUrl = URL.createObjectURL(file);
        img.onload = function () {
            if (img.naturalWidth < 64 || img.naturalHeight < 64) {
                toast('A imagem é demasiado pequena (mínimo 64×64).', 'error');
                return;
            }
            st.nw = img.naturalWidth;
            st.nh = img.naturalHeight;
            st.zoom = 1;
            st.x = 0;
            st.y = 0;
            if (dialog.open) {
                measure();
            } else {
                open();
            }
        };
        img.onerror = function () { toast('Não foi possível abrir essa imagem.', 'error'); };
        img.src = objectUrl;
    });

    /* ---------- arrastar e pinçar ---------- */
    var pointers = new Map();
    var pinch = null;

    stage.addEventListener('pointerdown', function (e) {
        stage.setPointerCapture(e.pointerId);
        pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
        stage.classList.add('is-dragging');
        if (pointers.size === 2) {
            var p = Array.from(pointers.values());
            pinch = { dist: Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y), zoom: st.zoom };
        }
    });

    stage.addEventListener('pointermove', function (e) {
        var prev = pointers.get(e.pointerId);
        if (!prev) {
            return;
        }
        var cur = { x: e.clientX, y: e.clientY };
        pointers.set(e.pointerId, cur);
        if (pointers.size === 2 && pinch) {
            var p = Array.from(pointers.values());
            var r = stage.getBoundingClientRect();
            var mx = (p[0].x + p[1].x) / 2 - r.left - r.width / 2;
            var my = (p[0].y + p[1].y) / 2 - r.top - r.height / 2;
            setZoom(pinch.zoom * Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y) / pinch.dist, mx, my);
        } else if (pointers.size === 1) {
            st.x += cur.x - prev.x;
            st.y += cur.y - prev.y;
            clamp();
            render();
        }
    });

    function endPointer(e) {
        pointers.delete(e.pointerId);
        if (pointers.size < 2) {
            pinch = null;
        }
        if (!pointers.size) {
            stage.classList.remove('is-dragging');
        }
    }
    stage.addEventListener('pointerup', endPointer);
    stage.addEventListener('pointercancel', endPointer);

    stage.addEventListener('wheel', function (e) {
        e.preventDefault();
        var r = stage.getBoundingClientRect();
        setZoom(st.zoom * Math.exp(-e.deltaY * 0.0015), e.clientX - r.left - r.width / 2, e.clientY - r.top - r.height / 2);
    }, { passive: false });

    stage.addEventListener('keydown', function (e) {
        var step = e.shiftKey ? 40 : 10;
        var moves = { ArrowLeft: [step, 0], ArrowRight: [-step, 0], ArrowUp: [0, step], ArrowDown: [0, -step] };
        if (moves[e.key]) {
            e.preventDefault();
            st.x += moves[e.key][0];
            st.y += moves[e.key][1];
            clamp();
            render();
        } else if (e.key === '+' || e.key === '=') {
            e.preventDefault();
            setZoom(st.zoom * 1.1);
        } else if (e.key === '-') {
            e.preventDefault();
            setZoom(st.zoom / 1.1);
        }
    });

    range.addEventListener('input', function () { setZoom(parseFloat(range.value)); });
    dialog.querySelectorAll('[data-crop-zoom]').forEach(function (b) {
        b.addEventListener('click', function () {
            setZoom(st.zoom * (b.dataset.cropZoom === '1' ? 1.2 : 1 / 1.2));
        });
    });
    window.addEventListener('resize', function () {
        if (dialog.open) {
            measure();
        }
    });

    /* ---------- guardar / remover ---------- */
    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function cropToBlob() {
        var s = st.base * st.zoom;   // px no ecrã por px da imagem original
        var side = st.size / s;      // lado do recorte, em px da imagem original
        var sx = st.nw / 2 - st.x / s - side / 2;
        var sy = st.nh / 2 - st.y / s - side / 2;
        var canvas = document.createElement('canvas');
        canvas.width = OUT;
        canvas.height = OUT;
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, OUT, OUT);
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(img, sx, sy, side, side, 0, 0, OUT, OUT);
        return new Promise(function (resolve) { canvas.toBlob(resolve, 'image/jpeg', 0.92); });
    }

    // atualiza todos os avatares da página (perfil, formulário e botão do cabeçalho)
    function setAvatar(url) {
        document.querySelectorAll('[data-avatar]').forEach(function (el) {
            el.textContent = '';
            if (url) {
                var im = document.createElement('img');
                im.src = url;
                im.alt = '';
                el.appendChild(im);
            } else {
                el.textContent = el.dataset.initials || '?';
            }
            el.classList.remove('is-updated');
            void el.offsetWidth;
            el.classList.add('is-updated');
        });
        document.querySelectorAll('[data-avatar-remove]').forEach(function (b) { b.hidden = !url; });
        document.querySelectorAll('button[data-avatar-pick]:not(.pg-avatar__edit)').forEach(function (b) {
            b.textContent = url ? 'Alterar fotografia' : 'Carregar fotografia';
        });
        document.querySelectorAll('.pg-avatar__edit').forEach(function (b) {
            b.setAttribute('aria-label', url ? 'Alterar fotografia de perfil' : 'Adicionar fotografia de perfil');
        });
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': csrf(), 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json().catch(function () { return { ok: false }; }).then(function (data) {
                if (!res.ok || !data.ok) {
                    if (data.message) {
                        throw new Error(data.message);
                    }
                    // resposta que não é JSON: erro do servidor web/PHP antes de chegar ao controlador
                    var why = {
                        400: 'a sessão expirou, recarregue a página',
                        403: 'sem permissão',
                        404: 'endereço não encontrado',
                        413: 'a imagem excede o limite do servidor',
                        500: 'erro no servidor'
                    }[res.status];
                    throw new Error('Não foi possível guardar (erro ' + res.status + (why ? ': ' + why : '') + ').');
                }
                return data;
            });
        });
    }

    saveBtn.addEventListener('click', function () {
        if (saveBtn.disabled) {
            return;
        }
        var label = saveBtn.textContent;
        saveBtn.disabled = true;
        saveBtn.classList.add('is-loading');
        saveBtn.textContent = 'A guardar…';
        cropToBlob()
            .then(function (blob) {
                var fd = new FormData();
                fd.append('avatar', blob, 'avatar.jpg');
                return post(dialog.dataset.uploadUrl, fd);
            })
            .then(function (data) {
                close();
                setAvatar(data.url);
                toast(data.message, 'success');
            })
            .catch(function (err) { toast(err.message, 'error'); })
            .finally(function () {
                saveBtn.disabled = false;
                saveBtn.classList.remove('is-loading');
                saveBtn.textContent = label;
            });
    });

    document.querySelectorAll('[data-avatar-remove]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.disabled = true;
            post(dialog.dataset.removeUrl, new FormData())
                .then(function (data) {
                    setAvatar(null);
                    toast(data.message, 'info');
                })
                .catch(function (err) { toast(err.message, 'error'); })
                .finally(function () { btn.disabled = false; });
        });
    });
})();
