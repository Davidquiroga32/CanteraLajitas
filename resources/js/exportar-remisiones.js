/**
 * Ventana para confirmar qué se exporta del historial de remisiones:
 * formato, fechas y si se aplican los demás filtros de la lista.
 */
const iso = (fecha) => {
    const d = new Date(fecha.getTime() - fecha.getTimezoneOffset() * 60000);
    return d.toISOString().slice(0, 10);
};

const legible = (valor) => valor.split('-').reverse().join('/');

const rangos = () => {
    const hoy = new Date();
    const atras = (dias) => {
        const d = new Date(hoy);
        d.setDate(d.getDate() - (dias - 1));
        return iso(d);
    };

    return {
        hoy: [iso(hoy), iso(hoy)],
        7: [atras(7), iso(hoy)],
        15: [atras(15), iso(hoy)],
        30: [atras(30), iso(hoy)],
        mes: [iso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)), iso(hoy)],
    };
};

const ETIQUETAS = { material: 'Material', pago: 'Pago', vendedor: 'Vendedor' };

export default () => ({
    formato: 'excel',
    desde: '',
    hasta: '',
    rango: null,
    hoy: iso(new Date()),
    otros: [],
    conFiltros: true,
    intentado: false,

    abrir(formato) {
        const filtro = document.getElementById('filtro-remisiones');
        this.formato = formato;
        this.intentado = false;
        this.desde = filtro?.elements.desde.value ?? '';
        this.hasta = filtro?.elements.hasta.value ?? '';
        this.rango = Object.entries(rangos()).find(([, [d, h]]) => d === this.desde && h === this.hasta)?.[0] ?? null;
        this.otros = this.leerOtrosFiltros(filtro);
        this.conFiltros = true;
        this.$dispatch('open-modal', 'exportar');
    },

    leerOtrosFiltros(filtro) {
        if (!filtro) return [];
        const otros = [];

        for (const campo of filtro.elements) {
            if (!campo.name || ['desde', 'hasta'].includes(campo.name)) continue;
            if (campo.type === 'checkbox') {
                if (campo.checked) otros.push({ nombre: campo.name, valor: campo.value, texto: 'Incluye anuladas' });
            } else if (campo.tagName === 'SELECT' && campo.value) {
                otros.push({ nombre: campo.name, valor: campo.value, texto: `${ETIQUETAS[campo.name] ?? campo.name}: ${campo.selectedOptions[0].text}` });
            } else if (campo.name === 'q' && campo.value.trim()) {
                otros.push({ nombre: 'q', valor: campo.value.trim(), texto: `Búsqueda "${campo.value.trim()}"` });
            }
        }

        return otros;
    },

    usarRango(clave) {
        [this.desde, this.hasta] = rangos()[clave];
        this.rango = clave;
    },

    valido() {
        return Boolean(this.desde && this.hasta && this.desde <= this.hasta);
    },

    resumen() {
        if (this.desde === this.hasta) return `del día ${legible(this.desde)}`;
        return `del ${legible(this.desde)} al ${legible(this.hasta)}`;
    },

    enviar(evento) {
        if (!this.valido()) {
            evento.preventDefault();
            this.intentado = true;
            return;
        }
        this.$dispatch('close-modal', 'exportar');
    },
});
