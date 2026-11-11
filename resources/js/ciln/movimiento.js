const MARGEN_DE_REVELADO = '0px 0px -10% 0px';
const MARGEN_DEL_CONTADOR = '0px';
const DURACION_DEL_CONTADOR = 1400;

const quieto = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window);

const alVerse = (elementos, accion, margen = MARGEN_DE_REVELADO) => {
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

    elementos.forEach((elemento) => observador.observe(elemento));

    return observador;
};

// Fades up every [data-revelar] inside as it scrolls into view; without this component the content stays visible.
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

// Splits the headline into words that come into focus one after another; the server text stays as is for crawlers and screen readers.
export const titular = () => ({
    init() {
        if (quieto()) {
            return;
        }

        const palabras = this.$el.textContent.trim().split(/\s+/);
        this.$el.replaceChildren(
            ...palabras.flatMap((palabra, orden) => {
                const envoltura = document.createElement('span');
                envoltura.className = 'palabra';
                envoltura.textContent = palabra;
                envoltura.style.setProperty('--orden', String(orden));

                return orden === 0 ? [envoltura] : [document.createTextNode(' '), envoltura];
            }),
        );
        this.$el.classList.add('titular-listo');
    },
});

// Counts up to the integer the server rendered once it scrolls into view.
export const contador = () => ({
    init() {
        const meta = Number.parseInt(this.$el.textContent, 10);

        if (quieto() || Number.isNaN(meta) || meta === 0) {
            return;
        }

        this.$el.textContent = '0';
        this.observador = alVerse([this.$el], (elemento) => {
            const inicio = performance.now();
            const paso = (ahora) => {
                const avance = Math.min((ahora - inicio) / DURACION_DEL_CONTADOR, 1);
                elemento.textContent = String(Math.round(meta * (1 - (1 - avance) ** 4)));

                if (avance < 1) {
                    requestAnimationFrame(paso);
                }
            };

            requestAnimationFrame(paso);
        }, MARGEN_DEL_CONTADOR);
    },

    destroy() {
        this.observador?.disconnect();
    },
});
