import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                // Colores del logo de Cantera Lajitas S.A.S.
                navy: { DEFAULT: '#162342', 700: '#1f2f57', 50: '#e6e9f1' },
                naranja: { DEFAULT: '#da7d15', 600: '#c06c0f', 50: '#fbeedd' },
                cafe: { DEFAULT: '#955515', 50: '#f3e6d6' },
                pizarra: '#55534b',
                // Neutros cálidos del boceto
                piedra: '#ece8de',
                tarjeta: '#faf8f2',
                linea: { DEFAULT: '#dbd5c6', 2: '#c8c1ae' },
                tinta: '#1f1d19',
                apagado: '#7a7466',
                // Estados
                exito: { DEFAULT: '#3f5c3a', 50: '#e3ebdc' },
                peligro: { DEFAULT: '#a8553a', 50: '#f1dcd4' },
            },
            fontFamily: {
                sans: ['"Public Sans"', ...defaultTheme.fontFamily.sans],
                serif: ['Fraunces', 'Georgia', ...defaultTheme.fontFamily.serif],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
        },
    },

    plugins: [forms],
};
