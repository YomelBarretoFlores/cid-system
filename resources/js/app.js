import cabecera from './ciln/cabecera';
import fecha from './ciln/fecha';
import selector from './ciln/selector';

const registrar = () => {
    window.Alpine.data('selectorCiln', selector);
    window.Alpine.data('fechaCiln', fecha);
    window.Alpine.data('cabeceraCiln', cabecera);
};

if (window.Alpine) {
    registrar();
} else {
    document.addEventListener('alpine:init', registrar);
}
