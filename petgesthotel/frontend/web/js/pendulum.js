/* PetGestHotel — pêndulo com mola e amortecimento, para coisas penduradas (etiqueta de hóspede, medalhas dos animais).
   - balança ao abrir a página e vai assentando
   - com o botão esquerdo do rato carregado pode ser agarrado e largado
   - no telemóvel, um toque dá um abanão (sem prender o dedo, para não bloquear o scroll)

   Uso: var p = new PgPendulum(el, { handle: el.querySelector('...'), angle: -0.3, delay: 150 });
        p.impulse(0.5);   // dá um toque
   O elemento roda à volta do ponto do meio em cima (transform-origin: 50% 0 no CSS). */
(function () {
    'use strict';

    function Pendulum(el, opts) {
        opts = opts || {};
        this.el = el;
        this.handle = opts.handle || el;
        this.angle = opts.angle !== undefined ? opts.angle : -0.30; // rad (≈ -17°): posição inicial
        this.vel = 0;            // rad/s
        this.stiffness = opts.stiffness || 22;  // força que o traz de volta ao centro (período ≈ 1,3 s)
        this.damping = opts.damping || 1.7;     // atrito do ar: baixo = balança mais tempo
        this.maxAngle = opts.maxAngle || 1.2;   // ~70°
        this.dragging = false;
        this.dragTarget = 0;
        this.grabOffset = 0;
        this.running = false;
        this.last = 0;
        this.moved = false;

        // ponto fixo (topo do fio): é a origem da rotação, por isso não se mexe ao rodar
        this.pivot = document.createElement('span');
        this.pivot.className = 'pg-tag__pivot';
        el.appendChild(this.pivot);

        el.classList.add('is-physics');
        this.render();
        this.bind();
        // pequena pausa para a página assentar antes de largar
        var self = this;
        setTimeout(function () { self.wake(); }, opts.delay !== undefined ? opts.delay : 150);
    }

    Pendulum.prototype.pivotPoint = function () {
        var r = this.pivot.getBoundingClientRect();
        return { x: r.left, y: r.top };
    };

    // ângulo se o ponto (x, y) estivesse por baixo (0 = vertical)
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
        // pequena inclinação em Y com a velocidade: dá a sensação de volume
        var tilt = Math.max(-12, Math.min(12, this.vel * 4));
        this.el.style.transform = 'rotate(' + this.angle.toFixed(4) + 'rad) rotateY(' + tilt.toFixed(2) + 'deg)';
    };

    Pendulum.prototype.bind = function () {
        var self = this;
        var el = this.el;
        var body = this.handle;
        var start = null;

        // o rato só mexe enquanto o botão esquerdo está carregado (arrastar)
        body.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'touch') {
                var p = self.pivotPoint();
                self.impulse(e.clientX > p.x ? -2.6 : 2.6);
                return;
            }
            if (e.button !== 0) {
                return;
            }
            e.preventDefault();
            start = { x: e.clientX, y: e.clientY };
            self.moved = false;
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
            if (start && Math.abs(e.clientX - start.x) + Math.abs(e.clientY - start.y) > 4) {
                self.moved = true;
            }
            var target = self.angleTo(e.clientX, e.clientY) + self.grabOffset;
            self.dragTarget = Math.max(-self.maxAngle, Math.min(self.maxAngle, target));
        });

        function release() {
            if (!self.dragging) {
                return;
            }
            self.dragging = false; // a velocidade que tinha fica: é isso que o faz balançar ao largar
            el.classList.remove('is-dragging');
            self.wake();
        }
        body.addEventListener('pointerup', release);
        body.addEventListener('pointercancel', release);
        body.addEventListener('lostpointercapture', release);

        // dentro de um link: arrastar não conta como clique (um clique sem arrastar abre o link)
        el.addEventListener('click', function (e) {
            if (self.moved) {
                e.preventDefault();
                e.stopPropagation();
                self.moved = false;
            }
        }, true);
    };

    window.PgPendulum = Pendulum;
})();
