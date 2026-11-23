/* PetGestHotel — animais: medalhas penduradas (pêndulo), e no formulário: medalha em direto,
   fotografia com recorte, documentos de vacinas e barra de guardar */
(function () {
    'use strict';

    /* ======================================================================
       Medalhas penduradas: pêndulo com física (pendulum.js), como a etiqueta do perfil.
       Lista: balançam em cascata. Ficha: balança o fio + medalha. Formulário: a medalha de pré-visualização.
       ====================================================================== */
    var pendulums = [];
    if (window.PgPendulum && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var visible = function (el) { return el.offsetParent !== null; };

        document.querySelectorAll('.pg-pets__hang').forEach(function (el) {
            if (visible(el)) {
                pendulums.push(new window.PgPendulum(el, { handle: el.querySelector('.pg-medal__disc') }));
            }
        });
        document.querySelectorAll('.pg-pet .pg-medal').forEach(function (el, i) {
            if (visible(el)) {
                pendulums.push(new window.PgPendulum(el, {
                    handle: el.querySelector('.pg-medal__disc'),
                    angle: -0.38,
                    delay: 200 + Math.min(i, 8) * 80   // em cascata, de cima para baixo
                }));
            }
        });
        document.querySelectorAll('.pg-pets__preview .pg-medal, .pg-acct__photo > .pg-medal').forEach(function (el) {
            if (visible(el)) {
                pendulums.push(new window.PgPendulum(el, { handle: el.querySelector('.pg-medal__disc') }));
            }
        });
    }

    var form = document.getElementById('form-pet');
    if (!form) {
        return;
    }
    form.classList.add('has-js');

    var MAX_BYTES = parseInt(form.dataset.maxBytes, 10) || 5 * 1024 * 1024;
    var MAX_POST = parseInt(form.dataset.maxPost, 10) || Infinity;
    var MAX_DOCS = parseInt(form.dataset.maxDocs, 10) || 5;
    var IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    var medals = Array.prototype.slice.call(document.querySelectorAll('.pg-acct--edit [data-medal]'));
    var bar = form.querySelector('[data-savebar]');
    var barText = form.querySelector('[data-savebar-text]');
    var idleText = barText ? barText.textContent : '';
    var pending = 0; // imagens a ser reduzidas

    function humanSize(bytes) {
        return bytes >= 1048576
            ? (Math.round(bytes / 104857.6) / 10).toString().replace('.', ',') + ' MB'
            : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    // cada alteração dá um toque leve nas medalhas, alternando o lado (no máximo ~8 por segundo)
    var nudgeSide = 1;
    var lastNudge = 0;
    function nudge(strength) {
        var now = performance.now();
        if (now - lastNudge < 120) {
            return;
        }
        lastNudge = now;
        nudgeSide = -nudgeSide;
        pendulums.forEach(function (p) { p.impulse((strength || 0.55) * nudgeSide); });
    }

    /* Reduz uma imagem no browser antes de enviar (cabe nos limites do servidor e envia mais depressa).
       O servidor volta a desenhá-la de qualquer forma; aqui é só para poupar o envio. */
    function shrinkImage(file, maxSide, quality) {
        if (!window.createImageBitmap || IMAGE_TYPES.indexOf(file.type) === -1) {
            return Promise.resolve(file);
        }
        return createImageBitmap(file).then(function (bmp) {
            var scale = Math.min(1, maxSide / Math.max(bmp.width, bmp.height));
            if (scale === 1 && file.size <= 1.5 * 1048576) {
                bmp.close && bmp.close();
                return file;
            }
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(bmp.width * scale);
            canvas.height = Math.round(bmp.height * scale);
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(bmp, 0, 0, canvas.width, canvas.height);
            bmp.close && bmp.close();
            return new Promise(function (resolve) {
                canvas.toBlob(function (blob) {
                    if (!blob || blob.size >= file.size) {
                        resolve(file);
                        return;
                    }
                    var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', quality);
            });
        }).catch(function () {
            return file;
        });
    }

    function setFiles(input, files) {
        var dt = new DataTransfer();
        files.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
    }

    /* ======================================================================
       Medalha: nome e espécie em direto
       ====================================================================== */
    function updateMedal(input) {
        if (!input.dataset || !input.dataset.medalIn) {
            return;
        }
        if (input.dataset.medalIn === 'nome') {
            var nome = input.value.trim();
            medals.forEach(function (m) {
                var ini = m.querySelector('[data-medal-out="inicial"]');
                var out = m.querySelector('[data-medal-out="nome"]');
                if (ini) { ini.textContent = nome ? nome.charAt(0).toUpperCase() : '?'; }
                if (out) { out.textContent = nome || 'Nome'; }
            });
        } else if (input.dataset.medalIn === 'especie') {
            // a espécie é o nome do grupo da raça escolhida (Cão, Gato, Outros)
            var opt = input.options[input.selectedIndex];
            var grupo = opt && opt.parentNode.tagName === 'OPTGROUP' ? opt.parentNode.label : '';
            var tom = grupo === 'Gato' ? 'pg-medal--gato' : (grupo === 'Outros' ? 'pg-medal--outro' : 'pg-medal--cao');
            medals.forEach(function (m) {
                m.classList.remove('pg-medal--cao', 'pg-medal--gato', 'pg-medal--outro');
                m.classList.add(tom);
            });
        }
        nudge();
    }

    /* ======================================================================
       Fotografia
       ====================================================================== */
    var fotoInput = form.querySelector('[data-foto-input]');
    var fotoLabel = form.querySelector('[data-foto-label]');
    var fotoRemove = form.querySelector('[data-foto-remove]');
    var fotoFlag = form.querySelector('[data-foto-remove-flag]');
    var fotoError = null;
    var fotoUrl = null;

    function showFotoError(msg) {
        if (!fotoError) {
            fotoError = document.createElement('p');
            fotoError.className = 'pg-pets__field-error';
            fotoError.setAttribute('role', 'alert');
            fotoInput.closest('.pg-acct__photo-actions').appendChild(fotoError);
        }
        fotoError.textContent = msg;
        fotoError.hidden = !msg;
    }

    function setPhoto(src) {
        medals.forEach(function (m) {
            var img = m.querySelector('[data-medal-photo]');
            if (src) {
                img.src = src;
                img.hidden = false;
            } else {
                img.hidden = true;
                img.removeAttribute('src');
            }
            m.classList.toggle('has-photo', !!src);
        });
        fotoLabel.textContent = src ? 'Alterar fotografia' : 'Carregar fotografia';
        fotoRemove.hidden = !src;
        lastNudge = 0;
        nudge(2.2); // a medalha "recebe" a fotografia com um balanço maior
    }

    /* Com a janela de recorte (cropper.js): escolher → ajustar → "Usar fotografia".
       A imagem recortada (640×640) vai para o campo "foto" e é gravada com o resto do formulário. */
    var cropDialog = document.querySelector('[data-cropper]');
    var cropInput = document.querySelector('[data-crop-input]');
    var fotoPick = form.querySelector('[data-foto-pick]');
    if (fotoInput && cropDialog && cropInput && window.PgCropper) {
        var crop = window.PgCropper(cropDialog, cropInput, {
            size: 640,
            busyLabel: 'A preparar…',
            onSave: function (blob) {
                if (!blob) {
                    return Promise.reject(new Error('Não foi possível recortar esta imagem.'));
                }
                var file = new File([blob], 'foto.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                if (file.size > MAX_BYTES) {
                    return Promise.reject(new Error('A fotografia é maior do que ' + humanSize(MAX_BYTES) + '.'));
                }
                setFiles(fotoInput, [file]);
                if (fotoUrl) { URL.revokeObjectURL(fotoUrl); }
                fotoUrl = URL.createObjectURL(file);
                fotoFlag.value = '';
                showFotoError('');
                // espera a janela fechar para o balanço se ver
                setTimeout(function () { setPhoto(fotoUrl); refreshDirty(); }, 250);
                return Promise.resolve();
            }
        });
        fotoPick.addEventListener('click', crop.pick);
    }

    if (fotoInput) {
        // sem a janela de recorte (sem JS do recorte): o campo de ficheiro funciona como antes
        fotoInput.addEventListener('change', function () {
            if (form.classList.contains('has-js') && cropDialog) {
                return; // o ficheiro foi lá posto pelo recorte
            }
            var file = fotoInput.files[0];
            showFotoError('');
            if (!file) {
                return;
            }
            if (IMAGE_TYPES.indexOf(file.type) === -1) {
                fotoInput.value = '';
                showFotoError('Use uma imagem JPG, PNG ou WebP.');
                return;
            }
            pending++;
            shrinkImage(file, 1280, 0.88).then(function (small) {
                pending--;
                if (small.size > MAX_BYTES) {
                    fotoInput.value = '';
                    showFotoError('A fotografia é maior do que ' + humanSize(MAX_BYTES) + '.');
                    return;
                }
                if (small !== file) {
                    setFiles(fotoInput, [small]);
                }
                if (fotoUrl) { URL.revokeObjectURL(fotoUrl); }
                fotoUrl = URL.createObjectURL(small);
                fotoFlag.value = '';
                setPhoto(fotoUrl);
                refreshDirty();
            });
        });

        fotoRemove.addEventListener('click', function () {
            fotoInput.value = '';
            fotoFlag.value = '1';
            showFotoError('');
            setPhoto(null);
            refreshDirty();
        });
    }

    /* ======================================================================
       Documentos de vacinas (até MAX_DOCS, PDF ou imagem)
       ====================================================================== */
    var docsBox = form.querySelector('[data-docs]');
    var docsInput = form.querySelector('[data-docs-input]');
    var docsList = form.querySelector('[data-docs-list]');
    var docsDrop = form.querySelector('[data-docs-drop]');
    var docsCount = form.querySelector('[data-docs-count]');
    var docsError = form.querySelector('[data-docs-error]');
    var icons = form.querySelector('[data-docs-icons]');
    var newDocs = [];

    function keptExisting() {
        return docsList.querySelectorAll('[data-doc-existing]:not(.is-removed)').length;
    }

    function docsTotal() {
        return keptExisting() + newDocs.length;
    }

    function showDocsError(msg) {
        docsError.textContent = msg;
        docsError.hidden = !msg;
    }

    function icon(kind) {
        var el = icons.content.querySelector('[data-icon="' + kind + '"]');
        return el ? el.innerHTML : '';
    }

    function renderDocs() {
        docsList.querySelectorAll('.is-new').forEach(function (li) { li.remove(); });
        newDocs.forEach(function (file, i) {
            var li = document.createElement('li');
            li.className = 'pg-docs__item is-new';
            li.innerHTML = '<span class="pg-docs__icon">' + icon(file.type === 'application/pdf' ? 'pdf' : 'img') + '</span>'
                + '<span class="pg-docs__name"></span>'
                + '<span class="pg-docs__size"></span>'
                + '<button type="button" class="pg-docs__drop-x">Tirar</button>';
            li.querySelector('.pg-docs__name').textContent = file.name;
            li.querySelector('.pg-docs__size').textContent = humanSize(file.size) + ' · novo';
            li.querySelector('button').setAttribute('aria-label', 'Tirar ' + file.name);
            li.querySelector('button').addEventListener('click', function () {
                newDocs.splice(i, 1);
                syncDocs();
                showDocsError('');
            });
            docsList.appendChild(li);
        });

        var total = docsTotal();
        docsCount.textContent = total + ' de ' + MAX_DOCS;
        docsCount.classList.toggle('is-full', total >= MAX_DOCS);
        docsDrop.classList.toggle('is-full', total >= MAX_DOCS);
        docsInput.disabled = total >= MAX_DOCS;
    }

    function syncDocs() {
        setFiles(docsInput, newDocs);
        renderDocs();
        refreshDirty();
    }

    if (docsBox) {
        renderDocs();

        docsInput.addEventListener('change', function () {
            var chosen = Array.prototype.slice.call(docsInput.files);
            var errors = [];
            var jobs = chosen.map(function (file) {
                var isPdf = file.type === 'application/pdf';
                if (!isPdf && IMAGE_TYPES.indexOf(file.type) === -1) {
                    errors.push('«' + file.name + '» não é PDF nem imagem.');
                    return Promise.resolve(null);
                }
                return isPdf ? Promise.resolve(file) : shrinkImage(file, 2400, 0.85);
            });
            pending++;
            Promise.all(jobs).then(function (files) {
                pending--;
                files.forEach(function (file) {
                    if (!file) {
                        return;
                    }
                    if (file.size > MAX_BYTES) {
                        errors.push('«' + file.name + '» é maior do que ' + humanSize(MAX_BYTES) + '.');
                    } else if (docsTotal() >= MAX_DOCS) {
                        errors.push('Só pode ter ' + MAX_DOCS + ' documentos: «' + file.name + '» ficou de fora.');
                    } else {
                        newDocs.push(file);
                    }
                });
                showDocsError(errors.join(' '));
                syncDocs();
            });
        });

        docsList.addEventListener('change', function (e) {
            if (e.target.matches('[data-doc-remove]')) {
                var li = e.target.closest('.pg-docs__item');
                if (!e.target.checked && docsTotal() >= MAX_DOCS) {
                    e.target.checked = true; // anular a remoção passaria do limite
                    showDocsError('Para manter este documento, tire primeiro um dos novos.');
                    return;
                }
                li.classList.toggle('is-removed', e.target.checked);
                showDocsError('');
                renderDocs();
                refreshDirty();
            }
        });

        ['dragenter', 'dragover'].forEach(function (t) {
            docsDrop.addEventListener(t, function () { docsDrop.classList.add('is-over'); });
        });
        ['dragleave', 'drop'].forEach(function (t) {
            docsDrop.addEventListener(t, function () { docsDrop.classList.remove('is-over'); });
        });
    }

    /* ======================================================================
       Barra de guardar
       ====================================================================== */
    function snapshot() {
        var out = [];
        new FormData(form).forEach(function (value, key) {
            if (key.indexOf('_csrf') !== -1) {
                return;
            }
            out.push(key + '=' + (value instanceof File ? value.name + ':' + value.size : value));
        });
        return out.join('&');
    }
    var initial = snapshot();

    function refreshDirty() {
        if (!bar) {
            return;
        }
        var dirty = snapshot() !== initial;
        bar.classList.toggle('is-dirty', dirty);
        barText.textContent = dirty ? 'Tem alterações por guardar.' : idleText;
    }

    form.addEventListener('input', function (e) {
        if (e.target.type !== 'file') {
            updateMedal(e.target);
            refreshDirty();
        }
    });
    form.addEventListener('change', function (e) {
        if (e.target.tagName === 'SELECT') {
            updateMedal(e.target);
            refreshDirty();
        }
    });

    // avisa antes de sair com alterações por guardar; não deixa enviar a meio de reduzir imagens
    var submitting = false;
    form.addEventListener('submit', function (e) {
        if (pending > 0) {
            e.preventDefault();
            barText.textContent = 'Ainda a preparar as imagens. Tente daqui a um segundo.';
            return;
        }
        var bytes = 0;
        form.querySelectorAll('input[type="file"]').forEach(function (inp) {
            Array.prototype.forEach.call(inp.files, function (f) { bytes += f.size; });
        });
        if (bytes > MAX_POST * 0.95) {
            e.preventDefault();
            showDocsError('Os ficheiros juntos passam de ' + humanSize(MAX_POST) + '. Guarde menos documentos de cada vez.');
            return;
        }
        submitting = true;
    });
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && bar && bar.classList.contains('is-dirty')) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
