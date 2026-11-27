/**
 * Formulario de ingreso: si el usuario marca "Recordar mis datos", el correo
 * queda guardado en este equipo y se escribe solo la próxima vez.
 * La contraseña nunca se guarda aquí: la recuerda el navegador si el usuario acepta.
 */
const CLAVE = 'cantera.correo';

const leer = () => {
    try {
        return localStorage.getItem(CLAVE) || '';
    } catch {
        return '';
    }
};

const escribir = (valor) => {
    try {
        if (valor) {
            localStorage.setItem(CLAVE, valor);
        } else {
            localStorage.removeItem(CLAVE);
        }
    } catch {
        // Navegación privada o almacenamiento bloqueado: se ignora.
    }
};

export default () => ({
    init() {
        const correo = this.$refs.correo;
        const guardado = leer();

        if (correo && ! correo.value && guardado) {
            correo.value = guardado;
            this.$nextTick(() => document.getElementById('password')?.focus());
        }
    },

    guardar() {
        escribir(this.$refs.recordar?.checked ? this.$refs.correo.value.trim() : '');
    },
});
