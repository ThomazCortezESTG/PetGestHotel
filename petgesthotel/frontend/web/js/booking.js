/*
 * Formulário da reserva (frontend/views/booking/_form.php): noites, resumo e preço em direto.
 * O preço que conta é o que o servidor calcula ao guardar (Booking::calcularPreco); este é a estimativa:
 *   (preço do quarto de cada animal × noites) + (serviços escolhidos × animais que ficam)
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

    function noites() {
        var a = data(inicio.value);
        var b = data(fim.value);
        if (!a || !b) { return 0; }
        return Math.round((b - a) / 86400000);
    }

    function linha(texto, valor, classe) {
        var li = document.createElement('li');
        if (classe) { li.className = classe; }
        var t = document.createElement('span');
        t.textContent = texto;
        var v = document.createElement('span');
        v.textContent = valor;
        li.appendChild(t);
        li.appendChild(v);
        return li;
    }

    function atualizar() {
        // a saída nunca pode ser antes da entrada
        fim.min = inicio.value || fim.min;

        var n = noites();
        var datasOk = n >= 1;
        if (!inicio.value || !fim.value) {
            noitesEl.textContent = '';
            resumoDatas.textContent = 'Escolha as datas.';
        } else if (!datasOk) {
            noitesEl.textContent = 'A saída tem de ser depois da entrada.';
            noitesEl.classList.add('is-error');
            resumoDatas.textContent = 'Datas por corrigir.';
        } else {
            noitesEl.classList.remove('is-error');
            noitesEl.textContent = n + (n === 1 ? ' noite' : ' noites');
            resumoDatas.textContent = dia.format(data(inicio.value)) + ' a ' + dia.format(data(fim.value)) + ' · ' + n + (n === 1 ? ' noite' : ' noites');
        }

        // animais que ficam (e o seu cartão fica destacado)
        var ficam = [];
        form.querySelectorAll('[data-bk-animal]').forEach(function (fs) {
            var r = fs.querySelector('input[type="radio"]:checked');
            var on = r && r.value !== '';
            fs.classList.toggle('is-on', !!on);
            if (on) {
                ficam.push({ nome: fs.getAttribute('data-nome'), quarto: r.getAttribute('data-quarto'), preco: parseFloat(r.getAttribute('data-preco')) || 0 });
            }
        });
        var servicos = [];
        form.querySelectorAll('input[name="servicos[]"]:checked').forEach(function (c) {
            servicos.push({ nome: c.getAttribute('data-nome'), preco: parseFloat(c.getAttribute('data-preco')) || 0 });
        });

        linhas.innerHTML = '';
        var total = 0;
        if (ficam.length === 0) {
            linhas.appendChild(linha('Escolha o quarto de pelo menos um animal.', '', 'pg-bk-summary__empty'));
        } else {
            var nn = datasOk ? n : 0;
            ficam.forEach(function (a) {
                var v = a.preco * nn;
                total += v;
                linhas.appendChild(linha(a.nome + ' · ' + a.quarto + (nn ? ' × ' + nn : ''), euro.format(v)));
            });
            servicos.forEach(function (s) {
                var v = s.preco * ficam.length;
                total += v;
                linhas.appendChild(linha(s.nome + (ficam.length > 1 ? ' × ' + ficam.length : ''), euro.format(v), 'pg-bk-summary__extra'));
            });
        }
        totais.forEach(function (el) { el.textContent = euro.format(datasOk ? total : 0); });
    }

    form.addEventListener('change', atualizar);
    inicio.addEventListener('input', atualizar);
    fim.addEventListener('input', atualizar);
    atualizar();
}());
