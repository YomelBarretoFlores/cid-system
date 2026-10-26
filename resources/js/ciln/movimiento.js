const quieto = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window);

const alVerse = (elemento, accion, margen = '0px 0px -12% 0px') => {
    const observador = new IntersectionObserver(
        (entradas) => {
            entradas.forEach((entrada) => {
                if (entrada.isIntersecting) {
                    observador.unobserve(entrada.target);
                    accion(entrada.target);
                }
            });
        },
        { rootMargin: margen },
    );

    [].concat(elemento).forEach((cada) => observador.observe(cada));
};

// Scroll-triggered reveal of every [data-revelar] inside; without this component the content stays visible.
export const revelar = () => ({
    init() {
        if (quieto()) {
            return;
        }

        this.$el.classList.add('js-movimiento');
        alVerse([...this.$el.querySelectorAll('[data-revelar]')], (elemento) => elemento.classList.add('revelado'));
    },
});

// Counts up to the number the server rendered once it scrolls into view.
export const contador = () => ({
    init() {
        const meta = Number.parseInt(this.$el.textContent, 10);

        if (quieto() || Number.isNaN(meta)) {
            return;
        }

        this.$el.textContent = '0';
        alVerse(this.$el, (elemento) => {
            const inicio = performance.now();
            const paso = (ahora) => {
                const avance = Math.min((ahora - inicio) / 1400, 1);
                elemento.textContent = String(Math.round(meta * (1 - (1 - avance) ** 4)));

                if (avance < 1) {
                    requestAnimationFrame(paso);
                }
            };

            requestAnimationFrame(paso);
        });
    },
});

// Cycles the words stacked inside, the outgoing one sliding up through the mask.
export const rotador = (intervalo = 2400) => ({
    actual: 0,
    saliente: null,

    init() {
        const total = this.$el.children.length;

        if (quieto() || total < 2) {
            return;
        }

        setInterval(() => {
            this.saliente = this.actual;
            this.actual = (this.actual + 1) % total;
        }, intervalo);
    },

    clase(indice) {
        return { actual: indice === this.actual, saliente: indice === this.saliente };
    },
});

// Sliding pill behind the pressed button of a segmented control, like a shared layout animation.
export const indicador = () => ({
    estilo: 'opacity: 0',

    init() {
        this.$nextTick(() => this.mover());
        new ResizeObserver(() => this.mover()).observe(this.$el);
        new MutationObserver(() => this.mover()).observe(this.$el, { attributes: true, attributeFilter: ['aria-pressed'], subtree: true });
    },

    mover() {
        const activo = this.$el.querySelector('[aria-pressed="true"]');

        if (activo) {
            this.estilo = `width: ${activo.offsetWidth}px; height: ${activo.offsetHeight}px; transform: translate(${activo.offsetLeft}px, ${activo.offsetTop}px)`;
        }
    },
});

// Hides the sticky header while scrolling down and brings it back on the way up.
export const cabecera = () => ({
    oculta: false,
    desplazada: false,
    ultimo: 0,

    init() {
        window.addEventListener('scroll', () => this.revisar(), { passive: true });
    },

    revisar() {
        const y = window.scrollY;
        this.desplazada = y > 8;
        this.oculta = !quieto() && y > 160 && y > this.ultimo;
        this.ultimo = y;
    },
});
