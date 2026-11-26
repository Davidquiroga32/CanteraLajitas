/**
 * Formulario de nueva remisión: comprador, líneas de material, despacho y pago,
 * con vista previa en vivo y aviso grande de lo que falta antes de enviar.
 */
const dinero = (n) => '$ ' + Math.round(n || 0).toLocaleString('es-CO');
const m3 = (n) => (Number(n) || 0).toLocaleString('es-CO', { maximumFractionDigits: 2 });

export default (config) => ({
    materiales: config.materiales,
    urlBuscar: config.urlBuscar,
    comprador: config.comprador ?? { id: null, nombre: '', tipo_documento: 'NIT', documento: '', ciudad: '', deuda: 0, anticipo: 0 },
    lineas: config.lineas?.length ? config.lineas : [{ material_id: '', cantidad_m3: '' }],
    conductor: config.valores?.conductor ?? '',
    placa: config.valores?.placa ?? '',
    maquina: config.valores?.maquina ?? '',
    destino: config.valores?.destino ?? '',
    forma_pago: config.valores?.forma_pago ?? '',
    observaciones: config.valores?.observaciones ?? '',
    busqueda: '',
    sugerencias: [],
    buscando: false,
    faltantes: [],
    enviando: false,
    verPrevia: false,

    dinero,
    m3,

    get compradorElegido() {
        return this.comprador.id !== null;
    },

    material(id) {
        return this.materiales.find((m) => m.id === Number(id));
    },

    precio(linea) {
        return this.material(linea.material_id)?.precio_m3 ?? 0;
    },

    subtotal(linea) {
        return Math.round((Number(linea.cantidad_m3) || 0) * this.precio(linea));
    },

    get total() {
        return this.lineas.reduce((suma, l) => suma + this.subtotal(l), 0);
    },

    get lineasValidas() {
        return this.lineas.filter((l) => l.material_id && Number(l.cantidad_m3) > 0);
    },

    disponiblesPara(indice) {
        const usados = this.lineas.filter((_, i) => i !== indice).map((l) => Number(l.material_id));
        return this.materiales.filter((m) => !usados.includes(m.id));
    },

    agregarLinea() {
        if (this.lineas.length < Math.min(10, this.materiales.length)) {
            this.lineas.push({ material_id: '', cantidad_m3: '' });
        }
    },

    quitarLinea(indice) {
        this.lineas.splice(indice, 1);
        if (this.lineas.length === 0) {
            this.agregarLinea();
        }
    },

    excedeStock(linea) {
        const material = this.material(linea.material_id);
        return material && Number(linea.cantidad_m3) > material.stock_m3;
    },

    async buscar() {
        const termino = this.busqueda.trim();
        if (termino.length < 2) {
            this.sugerencias = [];
            return;
        }
        this.buscando = true;
        try {
            const respuesta = await fetch(this.urlBuscar + '?q=' + encodeURIComponent(termino), { headers: { Accept: 'application/json' } });
            this.sugerencias = respuesta.ok ? await respuesta.json() : [];
        } finally {
            this.buscando = false;
        }
    },

    elegir(c) {
        this.comprador = { ...c };
        this.busqueda = '';
        this.sugerencias = [];
        this.quitarFaltante('comprador');
    },

    cambiarComprador() {
        this.comprador = { id: null, nombre: '', tipo_documento: 'NIT', documento: '', ciudad: '', deuda: 0, anticipo: 0 };
        this.$nextTick(() => document.getElementById('buscar-comprador')?.focus());
    },

    get diferenciaAnticipo() {
        if (this.forma_pago !== 'anticipo') {
            return 0;
        }
        return Math.max(0, this.total - (this.comprador.anticipo || 0));
    },

    quitarFaltante(clave) {
        this.faltantes = this.faltantes.filter((f) => f.clave !== clave);
    },

    falta(clave) {
        return this.faltantes.some((f) => f.clave === clave);
    },

    /**
     * Revisa en el navegador lo mismo que exige el servidor, para avisar
     * de una vez todo lo que falta (el servidor vuelve a validar).
     */
    revisar() {
        const f = [];
        if (!this.compradorElegido) {
            if (!this.comprador.nombre.trim()) f.push({ clave: 'comprador', texto: 'El nombre del comprador' });
            if (!this.comprador.documento.trim()) f.push({ clave: 'documento', texto: 'El NIT o cédula del comprador' });
            if (!this.comprador.ciudad.trim()) f.push({ clave: 'ciudad', texto: 'La ciudad del comprador' });
        }
        this.lineas.forEach((l, i) => {
            if (!l.material_id) f.push({ clave: 'material-' + i, texto: 'El material de la línea ' + (i + 1) });
            if (!(Number(l.cantidad_m3) > 0)) f.push({ clave: 'cantidad-' + i, texto: 'La cantidad (m³) de la línea ' + (i + 1) });
            else if (this.excedeStock(l)) f.push({ clave: 'cantidad-' + i, texto: 'La línea ' + (i + 1) + ' pide más de lo que hay en patio (' + m3(this.material(l.material_id).stock_m3) + ' m³)' });
        });
        if (!this.conductor.trim()) f.push({ clave: 'conductor', texto: 'El nombre del conductor' });
        if (!this.placa.trim()) f.push({ clave: 'placa', texto: 'La placa del vehículo' });
        if (!this.maquina.trim()) f.push({ clave: 'maquina', texto: 'La máquina que carga el material' });
        if (!this.destino.trim()) f.push({ clave: 'destino', texto: 'El destino del material (si no aplica, toca N/A)' });
        if (!this.forma_pago) f.push({ clave: 'forma_pago', texto: 'La forma de pago' });
        if (this.forma_pago === 'anticipo' && this.compradorElegido && !(this.comprador.anticipo > 0)) {
            f.push({ clave: 'forma_pago', texto: 'Este comprador no tiene anticipo. Elige otra forma de pago' });
        }
        if (this.forma_pago === 'anticipo' && !this.compradorElegido) {
            f.push({ clave: 'forma_pago', texto: 'Un comprador nuevo no tiene anticipo. Elige otra forma de pago' });
        }
        this.faltantes = f;
        return f.length === 0;
    },

    enviar(evento) {
        if (this.enviando) {
            evento.preventDefault();
            return;
        }
        if (!this.revisar()) {
            evento.preventDefault();
            this.$nextTick(() => {
                const aviso = document.getElementById('aviso-faltantes');
                aviso?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                aviso?.focus();
            });
            return;
        }
        this.enviando = true;
    },
});
