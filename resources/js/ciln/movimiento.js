const MARGEN_DE_REVELADO = '0px 0px -10% 0px';

const quieto = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window);

const alVerse = (elementos, accion) => {
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

    elementos.forEach((elemento) => observador.observe(elemento));

    return observador;
};

// Fades up every [data-revelar] and opens every [data-cortina] photograph inside as it scrolls into view; without this component the content stays visible.
export const revelar = () => ({
    init() {
        if (quieto()) {
            return;
        }

        this.$el.classList.add('js-movimiento');
        this.observador = alVerse([...this.$el.querySelectorAll('[data-revelar], [data-cortina]')], (elemento) => elemento.classList.add('revelado'));
    },

    destroy() {
        this.observador?.disconnect();
    },
});

// Splits the headline into words that rise one after another from behind their own line; the server text stays as is for crawlers and screen readers.
export const titular = () => ({
    init() {
        if (quieto()) {
            return;
        }

        const palabras = this.$el.textContent.trim().split(/\s+/);
        this.$el.replaceChildren(
            ...palabras.flatMap((palabra, orden) => {
                const marco = document.createElement('span');
                const envoltura = document.createElement('span');
                marco.className = 'palabra-marco';
                envoltura.className = 'palabra';
                envoltura.textContent = palabra;
                envoltura.style.setProperty('--orden', String(orden));
                marco.append(envoltura);

                return orden === 0 ? [marco] : [document.createTextNode(' '), marco];
            }),
        );
        this.$el.classList.add('titular-listo');
    },
});
