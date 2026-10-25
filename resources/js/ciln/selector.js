// Listbox with optional search; options are rendered by Blade so flags and texts come from the server.
export default ({ buscable = false } = {}) => ({
    valor: '',
    abierto: false,
    consulta: '',
    activo: -1,
    buscable,

    get opciones() {
        return [...this.$root.querySelectorAll('[role="option"]')];
    },

    get visibles() {
        return this.opciones.filter((opcion) => this.coincide(opcion.dataset.texto));
    },

    get elegida() {
        return this.opciones.find((opcion) => opcion.dataset.valor === String(this.valor ?? ''));
    },

    coincide(texto) {
        const consulta = this.consulta.trim().toLocaleLowerCase('es');

        return consulta === '' || texto.toLocaleLowerCase('es').includes(consulta);
    },

    abrir() {
        if (this.$refs.boton.disabled) {
            return;
        }

        this.abierto = true;
        this.consulta = '';
        this.activo = Math.max(0, this.visibles.indexOf(this.elegida));
        this.$nextTick(() => (this.buscable ? this.$refs.busqueda.focus() : this.enfocarActivo()));
    },

    cerrar(devolverFoco = true) {
        this.abierto = false;
        this.consulta = '';

        if (devolverFoco) {
            this.$refs.boton.focus();
        }
    },

    elegir(opcion) {
        if (!opcion) {
            return;
        }

        this.valor = opcion.dataset.valor;
        this.cerrar();
    },

    mover(paso) {
        if (!this.abierto) {
            return this.abrir();
        }

        const total = this.visibles.length;
        this.activo = total === 0 ? -1 : (this.activo + paso + total) % total;
        this.enfocarActivo();
    },

    enfocarActivo() {
        this.visibles[this.activo]?.scrollIntoView({ block: 'nearest' });
    },

    esActiva(opcion) {
        return this.visibles[this.activo] === opcion;
    },
});
