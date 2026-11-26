{{-- The panel swaps pages in place, which redraws the sidebar and the cluster sub-navigation scrolled to the top on every click; keep where each one was, right after the swap and before it is painted. --}}
<script data-navigate-once>
    (() => {
        const zonas = ['.fi-main-sidebar .fi-sidebar-nav', '.fi-page-sub-navigation-sidebar-ctn'];

        document.addEventListener('livewire:navigating', (evento) => {
            const posiciones = zonas.map((selector) => document.querySelector(selector)?.scrollTop ?? 0);

            evento.detail.onSwap(() => {
                zonas.forEach((selector, indice) => {
                    const zona = document.querySelector(selector);

                    if (zona) {
                        zona.scrollTop = posiciones[indice];
                    }
                });
            });
        });
    })();
</script>
