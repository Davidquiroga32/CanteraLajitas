/**
 * Filtra mientras se escribe: en cada cambio del formulario (GET) pide la misma página
 * con los filtros y reemplaza las zonas marcadas con data-vivo="nombre", sin recargar
 * ni perder el foco del buscador.
 */
export default () => ({
    cargando: false,
    temporizador: null,
    controlador: null,

    init() {
        const esTexto = (el) => el.matches('input:not([type]), input[type=text], input[type=search]');

        this.$el.addEventListener('input', (e) => {
            if (esTexto(e.target)) this.programar(350);
        });
        this.$el.addEventListener('change', (e) => {
            if (!esTexto(e.target)) this.programar(150);
        });
        this.$el.addEventListener('submit', (e) => {
            e.preventDefault();
            this.programar(0);
        });
    },

    programar(espera) {
        clearTimeout(this.temporizador);
        this.temporizador = setTimeout(() => this.buscar(), espera);
    },

    async buscar() {
        const parametros = new URLSearchParams();
        for (const [clave, valor] of new FormData(this.$el)) {
            if (String(valor).trim() !== '') parametros.append(clave, valor);
        }
        const url = this.$el.action + (parametros.size ? '?' + parametros : '');

        this.controlador?.abort();
        this.controlador = new AbortController();
        this.cargando = true;

        try {
            const respuesta = await fetch(url, { headers: { Accept: 'text/html' }, signal: this.controlador.signal });
            if (!respuesta.ok) throw new Error(respuesta.status);
            const pagina = new DOMParser().parseFromString(await respuesta.text(), 'text/html');

            document.querySelectorAll('[data-vivo]').forEach((zona) => {
                const nueva = pagina.querySelector(`[data-vivo="${zona.dataset.vivo}"]`);
                if (nueva) zona.replaceWith(document.importNode(nueva, true));
            });
            history.replaceState(null, '', url);
            this.cargando = false;
        } catch (error) {
            if (error.name !== 'AbortError') window.location = url;
        }
    },
});
