/* PetGestHotel — fotografia de perfil: escolher ficheiro → recortar (cropper.js) → enviar.
   O servidor recebe só a imagem final 512×512 (PerfilController::actionAvatar). */
(function () {
    'use strict';

    var dialog = document.querySelector('[data-cropper]');
    var input = document.querySelector('[data-crop-input]');
    if (!dialog || !input || !dialog.dataset.uploadUrl) {
        return;
    }

    function toast(msg, type) {
        if (window.pgToast) {
            window.pgToast(msg, type);
        } else {
            window.alert(msg);
        }
    }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
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

    var crop = window.PgCropper(dialog, input, {
        size: 512,
        onSave: function (blob) {
            var fd = new FormData();
            fd.append('avatar', blob, 'avatar.jpg');
            return post(dialog.dataset.uploadUrl, fd).then(function (data) {
                setAvatar(data.url);
                toast(data.message, 'success');
            });
        }
    });

    document.querySelectorAll('[data-avatar-pick]').forEach(function (btn) {
        btn.addEventListener('click', crop.pick);
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
