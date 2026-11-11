const CABECERA_CON_SOMBRA = 8;

// Gives the sticky header a shadow once the page scrolls, and holds the state of its mobile menu.
export default () => ({
    menu: false,
    desplazada: false,

    init() {
        this.alDesplazar = () => this.revisar();
        window.addEventListener('scroll', this.alDesplazar, { passive: true });
    },

    destroy() {
        window.removeEventListener('scroll', this.alDesplazar);
    },

    revisar() {
        this.desplazada = window.scrollY > CABECERA_CON_SOMBRA;
    },
});
