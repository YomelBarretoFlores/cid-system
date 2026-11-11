import cabecera from './ciln/cabecera';
import fecha from './ciln/fecha';
import { contador, revelar, titular } from './ciln/movimiento';
import selector from './ciln/selector';

const registrar = () => {
    window.Alpine.data('selectorCiln', selector);
    window.Alpine.data('fechaCiln', fecha);
    window.Alpine.data('cabeceraCiln', cabecera);
    window.Alpine.data('revelarCiln', revelar);
    window.Alpine.data('titularCiln', titular);
    window.Alpine.data('contadorCiln', contador);
};

if (window.Alpine) {
    registrar();
} else {
    document.addEventListener('alpine:init', registrar);
}
