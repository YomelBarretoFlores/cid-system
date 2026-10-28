{{-- Filament 5.10–5.11 closes an open dropdown on any window resize when its panel was anchored to the trigger wrapper; a phone keyboard only changes the height, so the filters closed as soon as a searchable select took the focus. --}}
<script data-navigate-once>
    document.addEventListener('alpine:init', () => {
        const registrar = window.Alpine.data.bind(window.Alpine);

        window.Alpine.data = (nombre, fabrica) => registrar(nombre, nombre !== 'filamentDropdown' ? fabrica : (...argumentos) => {
            const componente = fabrica(...argumentos);
            const alRedimensionar = componente.handleResize;
            let ancho = window.innerWidth;

            componente.handleResize = function (anterior) {
                if (window.innerWidth === ancho) {
                    return;
                }

                ancho = window.innerWidth;

                return alRedimensionar.call(this, anterior);
            };

            return componente;
        });
    });
</script>
