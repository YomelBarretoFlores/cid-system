const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

const iso = (anio, mes, dia) => `${anio}-${String(mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;

const leer = (valor) => {
    const partes = /^(\d{4})-(\d{2})-(\d{2})$/.exec(valor ?? '');

    return partes ? { anio: +partes[1], mes: +partes[2] - 1, dia: +partes[3] } : null;
};

// Monthly calendar (Monday first) with a year view; dates travel as YYYY-MM-DD like a native date input.
export default ({ hoy, min = '', max = '' }) => ({
    valor: '',
    abierto: false,
    alDerecha: false,
    vista: 'dias',
    anio: 0,
    mes: 0,
    hoy,
    min,
    max,

    init() {
        this.irA(leer(this.valor) ?? leer(this.hoy));
    },

    irA({ anio, mes }) {
        this.anio = anio;
        this.mes = mes;
    },

    get etiqueta() {
        const fecha = leer(this.valor);

        return fecha ? `${fecha.dia} ${MESES[fecha.mes].slice(0, 3)} ${fecha.anio}` : '';
    },

    get tituloMes() {
        return `${MESES[this.mes][0].toUpperCase()}${MESES[this.mes].slice(1)} ${this.anio}`;
    },

    get dias() {
        const primero = (new Date(this.anio, this.mes, 1).getDay() + 6) % 7;

        return Array.from({ length: 42 }, (_, indice) => {
            const fecha = new Date(this.anio, this.mes, indice - primero + 1);
            const valor = iso(fecha.getFullYear(), fecha.getMonth(), fecha.getDate());

            return { valor, numero: fecha.getDate(), fuera: fecha.getMonth() !== this.mes, bloqueado: this.bloqueado(valor) };
        });
    },

    get anios() {
        const inicio = this.anio - (this.anio % 12);

        return Array.from({ length: 12 }, (_, indice) => inicio + indice);
    },

    bloqueado(valor) {
        return (this.min !== '' && valor < this.min) || (this.max !== '' && valor > this.max);
    },

    abrir() {
        this.irA(leer(this.valor) ?? leer(this.hoy));
        this.vista = 'dias';
        this.alDerecha = this.$refs.boton.getBoundingClientRect().left + 300 > window.innerWidth;
        this.abierto = true;
    },

    cerrar() {
        this.abierto = false;
        this.$refs.boton.focus();
    },

    desplazar(paso) {
        if (this.vista === 'anios') {
            this.anio += paso * 12;

            return;
        }

        const fecha = new Date(this.anio, this.mes + paso, 1);
        this.irA({ anio: fecha.getFullYear(), mes: fecha.getMonth() });
    },

    elegirAnio(anio) {
        this.anio = anio;
        this.vista = 'dias';
    },

    elegir(valor) {
        if (valor !== '' && this.bloqueado(valor)) {
            return;
        }

        this.valor = valor;
        this.cerrar();
    },
});
