/**
 * x-miles: pone los puntos de miles mientras se escribe un valor en pesos
 * (5000000 → 5.000.000) sin mover el cursor de lugar. El servidor quita los
 * puntos antes de guardar.
 */
const formatear = (digitos) => (digitos ? Number(digitos).toLocaleString('es-CO', { maximumFractionDigits: 0 }) : '');

export default function miles(el) {
    const aplicar = () => {
        const cursor = el.selectionStart ?? el.value.length;
        const digitosAntesDelCursor = el.value.slice(0, cursor).replace(/\D/g, '').length;
        const digitos = el.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 12);
        const nuevo = formatear(digitos);

        if (nuevo === el.value) {
            return;
        }

        el.value = nuevo;

        let posicion = 0;
        for (let vistos = 0; posicion < nuevo.length && vistos < digitosAntesDelCursor; posicion++) {
            if (/\d/.test(nuevo[posicion])) {
                vistos++;
            }
        }

        if (document.activeElement === el) {
            el.setSelectionRange(posicion, posicion);
        }
    };

    el.addEventListener('input', aplicar);
    aplicar();
}
