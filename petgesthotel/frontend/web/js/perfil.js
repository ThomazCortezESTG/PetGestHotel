/* PetGestHotel — área de cliente: etiqueta com física de pêndulo, etiqueta em direto e barra de guardar */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ======================================================================
       Etiqueta pendurada: pêndulo com mola e amortecimento.
       - balança ao abrir a página e vai assentando
       - com o botão esquerdo do rato carregado pode ser agarrada e largada
       - no telemóvel, um toque dá um abanão
       ====================================================================== */
    function Pendulum(el) {
        this.el = el;
        this.angle = -0.30;      // rad (≈ -17°): posição inicial ao abrir a página
        this.vel = 0;            // rad/s
        this.stiffness = 22;     // força que a traz de volta ao centro (período ≈ 1,3 s)
        this.damping = 1.7;      // atrito do ar: baixo = balança mais tempo
        this.maxAngle = 1.2;     // ~70°
        this.dragging = false;
        this.dragTarget = 0;
        this.grabOffset = 0;
        this.running = false;
        this.last = 0;

        // ponto fixo (topo do fio): é a origem da rotação, por isso não se mexe ao rodar
        this.pivot = document.createElement('span');
        this.pivot.className = 'pg-tag__pivot';
        el.appendChild(this.pivot);

        el.classList.add('is-physics');
        this.render();
        this.bind();
        // pequena pausa para a página assentar antes de largar a etiqueta
        var self = this;
        setTimeout(function () { self.wake(); }, 150);
    }

    Pendulum.prototype.pivotPoint = function () {
        var r = this.pivot.getBoundingClientRect();
        return { x: r.left, y: r.top };
    };

    // ângulo da etiqueta se o ponto (x, y) estivesse por baixo dela (0 = vertical)
    Pendulum.prototype.angleTo = function (x, y) {
        var p = this.pivotPoint();
        return -Math.atan2(x - p.x, Math.max(y - p.y, 1));
    };

    Pendulum.prototype.impulse = function (v) {
        this.vel = Math.max(-7, Math.min(7, this.vel + v));
        this.wake();
    };

    Pendulum.prototype.wake = function () {
        if (this.running) {
            return;
        }
        this.running = true;
        this.last = performance.now();
        requestAnimationFrame(this.tick.bind(this));
    };

    Pendulum.prototype.step = function (dt) {
        var force;
        if (this.dragging) {
            // segue o cursor com uma mola forte: suave, mas sem atraso visível
            force = 260 * (this.dragTarget - this.angle) - 22 * this.vel;
        } else {
            force = -this.stiffness * Math.sin(this.angle) - this.damping * this.vel;
        }
        // Euler semi-implícito: estável e barato
        this.vel += force * dt;
        this.angle += this.vel * dt;
        if (Math.abs(this.angle) > this.maxAngle) {
            this.angle = Math.sign(this.angle) * this.maxAngle;
            this.vel *= -0.3;
        }
    };

    Pendulum.prototype.tick = function (now) {
        var elapsed = Math.min((now - this.last) / 1000, 1 / 20); // separador em segundo plano: não dar um salto
        this.last = now;

        // passos fixos de 1/240 s: o movimento é igual a 60 Hz ou 144 Hz
        var h = 1 / 240;
        while (elapsed > 0) {
            this.step(Math.min(h, elapsed));
            elapsed -= h;
        }
        this.render();

        if (!this.dragging && Math.abs(this.angle) < 0.0008 && Math.abs(this.vel) < 0.003) {
            this.angle = 0;
            this.vel = 0;
            this.render();
            this.running = false;
            return;
        }
        requestAnimationFrame(this.tick.bind(this));
    };

    Pendulum.prototype.render = function () {
        // pequena inclinação em Y com a velocidade: dá a sensação de cartão com volume
        var tilt = Math.max(-12, Math.min(12, this.vel * 4));
        this.el.style.transform = 'rotate(' + this.angle.toFixed(4) + 'rad) rotateY(' + tilt.toFixed(2) + 'deg)';
    };

    Pendulum.prototype.bind = function () {
        var self = this;
        var el = this.el;
        var body = el.querySelector('.pg-tag__body') || el;
        // o rato só mexe na etiqueta enquanto o botão esquerdo está carregado (arrastar)
        body.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'touch') {
                // no telemóvel não prendemos o dedo (para não bloquear o scroll): só um abanão
                var p = self.pivotPoint();
                self.impulse(e.clientX > p.x ? -2.6 : 2.6);
                return;
            }
            if (e.button !== 0) {
                return;
            }
            e.preventDefault();
            self.dragging = true;
            self.grabOffset = self.angle - self.angleTo(e.clientX, e.clientY);
            self.dragTarget = self.angle;
            el.classList.add('is-dragging');
            body.setPointerCapture(e.pointerId);
            self.wake();
        });

        body.addEventListener('pointermove', function (e) {
            if (!self.dragging) {
                return;
            }
            var target = self.angleTo(e.clientX, e.clientY) + self.grabOffset;
            self.dragTarget = Math.max(-self.maxAngle, Math.min(self.maxAngle, target));
        });

        function release() {
            if (!self.dragging) {
                return;
            }
            self.dragging = false; // a velocidade que tinha fica: é isso que a faz balançar ao largar
            el.classList.remove('is-dragging');
            self.wake();
        }
        body.addEventListener('pointerup', release);
        body.addEventListener('pointercancel', release);
        body.addEventListener('lostpointercapture', release);
    };

    var pendulums = [];
    if (!reduceMotion) {
        document.querySelectorAll('[data-perfil] .pg-tag').forEach(function (el) {
            if (el.offsetParent !== null) { // a pré-visualização fica escondida em ecrãs pequenos
                pendulums.push(new Pendulum(el));
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
