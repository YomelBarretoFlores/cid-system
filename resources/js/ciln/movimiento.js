const MARGEN_DE_REVELADO = '0px 0px -12% 0px';
const DURACION_DEL_CONTADOR = 1400;
const TRANSICION_DEL_ROTADOR = 700;
const CABECERA_CON_SOMBRA = 8;
const CABECERA_SE_OCULTA = 160;
const VELOCIDAD_DE_LA_CINTA = 0.045;

const quieto = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window);

const alVerse = (elemento, accion) => {
    const observador = new IntersectionObserver(
        (entradas) => {
            entradas.forEach((entrada) => {
                if (entrada.isIntersecting) {
                    observador.unobserve(entrada.target);
                    accion(entrada.target);
                }
            });
        },
        { rootMargin: MARGEN_DE_REVELADO },
    );

    [].concat(elemento).forEach((cada) => observador.observe(cada));

    return observador;
};

// Scroll-triggered reveal of every [data-revelar] inside; without this component the content stays visible.
export const revelar = () => ({
    init() {
        if (quieto()) {
            return;
        }

        this.$el.classList.add('js-movimiento');
        this.observador = alVerse([...this.$el.querySelectorAll('[data-revelar]')], (elemento) => elemento.classList.add('revelado'));
    },

    destroy() {
        this.observador?.disconnect();
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
        this.observador = alVerse(this.$el, (elemento) => {
            const inicio = performance.now();
            const paso = (ahora) => {
                const avance = Math.min((ahora - inicio) / DURACION_DEL_CONTADOR, 1);
                elemento.textContent = String(Math.round(meta * (1 - (1 - avance) ** 4)));

                if (avance < 1) {
                    requestAnimationFrame(paso);
                }
            };

            requestAnimationFrame(paso);
        });
    },

    destroy() {
        this.observador?.disconnect();
    },
});

// Cycles the words stacked inside: the outgoing one slides up through the mask, then drops back below unseen.
export const rotador = (intervalo = 2400) => ({
    actual: 0,
    saliente: null,

    init() {
        const total = this.$el.children.length;

        if (quieto() || total < 2) {
            return;
        }

        this.reloj = setInterval(() => {
            this.saliente = this.actual;
            this.actual = (this.actual + 1) % total;
            setTimeout(() => (this.saliente = null), TRANSICION_DEL_ROTADOR);
        }, intervalo);
    },

    destroy() {
        clearInterval(this.reloj);
    },

    clase(indice) {
        return { actual: indice === this.actual, saliente: indice === this.saliente };
    },
});

// Sliding pill behind the pressed button of a segmented control, like a shared layout animation; data-listo hands the pressed background over to it.
export const indicador = () => ({
    estilo: 'opacity: 0',

    init() {
        this.$nextTick(() => this.mover());
        this.tamano = new ResizeObserver(() => this.mover());
        this.tamano.observe(this.$el);
        this.cambios = new MutationObserver(() => this.mover());
        this.cambios.observe(this.$el, { attributes: true, attributeFilter: ['aria-pressed'], subtree: true });
    },

    destroy() {
        this.tamano?.disconnect();
        this.cambios?.disconnect();
    },

    mover() {
        const activo = this.$el.querySelector('[aria-pressed="true"]');

        if (activo) {
            this.estilo = `width: ${activo.offsetWidth}px; height: ${activo.offsetHeight}px; transform: translate(${activo.offsetLeft}px, ${activo.offsetTop}px)`;
            this.$el.dataset.listo = '';
        }
    },
});

// Hides the sticky header while scrolling down and brings it back on the way up or when it holds the keyboard focus.
export const cabecera = () => ({
    menu: false,
    oculta: false,
    desplazada: false,
    ultimo: 0,

    init() {
        this.alDesplazar = () => this.revisar();
        window.addEventListener('scroll', this.alDesplazar, { passive: true });
    },

    destroy() {
        window.removeEventListener('scroll', this.alDesplazar);
    },

    revisar() {
        const y = window.scrollY;
        this.desplazada = y > CABECERA_CON_SOMBRA;
        this.oculta = !quieto() && y > CABECERA_SE_OCULTA && y > this.ultimo && !this.$el.contains(document.activeElement);
        this.ultimo = y;
    },
});

// Slides the words of the curved ribbon along its path, looping by the length of one copy so the seam never shows.
export const cinta = () => ({
    init() {
        if (quieto()) {
            return;
        }

        const ruta = this.$el.querySelector('textPath');
        const copia = this.$el.querySelector('tspan');
        const inicio = performance.now();
        let largo = copia.getComputedTextLength();
        document.fonts?.ready.then(() => (largo = copia.getComputedTextLength()));

        // Before the font loads the text can measure 0, so the ribbon waits instead of dividing by it.
        const paso = (ahora) => {
            if (largo > 0) {
                ruta.setAttribute('startOffset', String(-(((ahora - inicio) * VELOCIDAD_DE_LA_CINTA) % largo)));
            }
            this.cuadro = requestAnimationFrame(paso);
        };
        this.cuadro = requestAnimationFrame(paso);
    },

    destroy() {
        cancelAnimationFrame(this.cuadro);
    },
});
