import fecha from './ciln/fecha';
import { cabecera, contador, indicador, revelar, rotador } from './ciln/movimiento';
import selector from './ciln/selector';

const registrar = () => {
    window.Alpine.data('selectorCiln', selector);
    window.Alpine.data('fechaCiln', fecha);
    window.Alpine.data('revelarCiln', revelar);
    window.Alpine.data('contadorCiln', contador);
    window.Alpine.data('rotadorCiln', rotador);
    window.Alpine.data('indicadorCiln', indicador);
    window.Alpine.data('cabeceraCiln', cabecera);
};

if (window.Alpine) {
    registrar();
} else {
    document.addEventListener('alpine:init', registrar);
}
