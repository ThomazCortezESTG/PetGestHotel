/*
 * Formulário da reserva (frontend/views/booking/_form.php): noites, resumo e preço em direto.
 * O preço que conta é o que o servidor calcula ao guardar (Booking::calcularPreco); este é a estimativa,
 * calculada da mesma forma, animal a animal:
 *   (preço do quarto × noites desse animal) + serviços escolhidos para esse animal
 * As noites de um animal são as das datas próprias (se marcou "Datas diferentes") ou as da reserva.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-booking-form]');
    if (!form) { return; }

    var inicio = form.querySelector('[data-bk-inicio]');
    var fim = form.querySelector('[data-bk-fim]');
    var noitesEl = form.querySelector('[data-bk-noites]');
    var linhas = document.querySelector('[data-bk-linhas]');
    var resumoDatas = document.querySelector('[data-bk-resumo-datas]');
    var totais = document.querySelectorAll('[data-bk-total], [data-bk-total-bar]');

    var euro = new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' });
    var dia = new Intl.DateTimeFormat('pt-PT', { day: 'numeric', month: 'short' });

    function data(v) {
        if (!v) { return null; }
        var p = v.split('-');
        return new Date(+p[0], +p[1] - 1, +p[2]);
    }

    function noitesEntre(a, b) {
        var da = data(a);
        var db = data(b);
        if (!da || !db) { return 0; }
        return Math.round((db - da) / 86400000);
    }

    function plural(n) { return n + (n === 1 ? ' noite' : ' noites'); }

    function linha(texto, valor, classe, sub) {
        var li = document.createElement('li');
        if (classe) { li.className = classe; }
        var t = document.createElement('span');
        t.textContent = texto;
        if (sub) {
            var s = document.createElement('small');
            s.textContent = sub;
            t.appendChild(s);
        }
        var v = document.createElement('span');
        v.textContent = valor;
        li.appendChild(t);
        li.appendChild(v);
        return li;
    }

    function atualizar() {
        // a saída nunca pode ser antes da entrada
        if (inicio.value) { fim.min = inicio.value; }

        var n = noitesEntre(inicio.value, fim.value);
        var datasOk = n >= 1;
        noitesEl.classList.toggle('is-error', !!(inicio.value && fim.value && !datasOk));
        if (!inicio.value || !fim.value) {
            noitesEl.textContent = '';
            resumoDatas.textContent = 'Escolha as datas.';
        } else if (!datasOk) {
            noitesEl.textContent = 'A saída tem de ser depois da entrada.';
            resumoDatas.textContent = 'Datas por corrigir.';
        } else {
            noitesEl.textContent = plural(n);
            resumoDatas.textContent = dia.format(data(inicio.value)) + ' a ' + dia.format(data(fim.value)) + ' · ' + plural(n);
        }

        linhas.innerHTML = '';
        var total = 0;
        var algum = false;

        form.querySelectorAll('[data-bk-animal]').forEach(function (fs) {
            var r = fs.querySelector('input[type="radio"]:checked');
            var vem = !!(r && r.value !== '');
            fs.classList.toggle('is-on', vem);

            // datas próprias: limitadas às da reserva e, por omissão, iguais a elas
            var proprias = fs.querySelector('[data-bk-proprias]');
            var ai = fs.querySelector('[data-bk-a-inicio]');
            var af = fs.querySelector('[data-bk-a-fim]');
            ai.min = af.min = inicio.value || '';
            ai.max = af.max = fim.value || '';
            if (proprias.checked) {
                if (!ai.value) { ai.value = inicio.value; }
                if (!af.value) { af.value = fim.value; }
                if (ai.value) { af.min = ai.value; }
            }
            ai.disabled = af.disabled = !proprias.checked || !vem;

            if (!vem) { return; }
            algum = true;

            var entrada = proprias.checked && ai.value ? ai.value : inicio.value;
            var saida = proprias.checked && af.value ? af.value : fim.value;
            var na = Math.max(datasOk ? 1 : 0, noitesEntre(entrada, saida));
            var preco = parseFloat(r.getAttribute('data-preco')) || 0;
            var sub = preco > 0 && na ? euro.format(preco) + ' × ' + plural(na) : '';
            if (proprias.checked && entrada && saida) {
                sub += (sub ? ' · ' : '') + dia.format(data(entrada)) + ' a ' + dia.format(data(saida));
            }
            total += preco * na;
            linhas.appendChild(linha(fs.getAttribute('data-nome') + ' · ' + r.getAttribute('data-quarto'), euro.format(preco * na), 'pg-bk-summary__animal', sub));

            fs.querySelectorAll('input[type="checkbox"][data-nome]:checked').forEach(function (c) {
                var p = parseFloat(c.getAttribute('data-preco')) || 0;
                total += p;
                linhas.appendChild(linha('+ ' + c.getAttribute('data-nome'), euro.format(p), 'pg-bk-summary__extra'));
            });
        });

        if (!algum) {
            linhas.appendChild(linha('Escolha o quarto de pelo menos um animal.', '', 'pg-bk-summary__empty'));
        }
        totais.forEach(function (el) { el.textContent = euro.format(datasOk ? total : 0); });
    }

    form.addEventListener('change', atualizar);
    form.addEventListener('input', function (e) {
        if (e.target.type === 'date') { atualizar(); }
    });
    atualizar();
}());
