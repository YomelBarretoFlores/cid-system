import fecha from './ciln/fecha';
import selector from './ciln/selector';

const registrar = () => {
    window.Alpine.data('selectorCiln', selector);
    window.Alpine.data('fechaCiln', fecha);
};

if (window.Alpine) {
    registrar();
} else {
    document.addEventListener('alpine:init', registrar);
}
