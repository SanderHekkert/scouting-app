import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'media',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    red: '#ED1C24',
                    'red-dark': '#C9141C',
                    blue: '#0068B7',
                    'blue-dark': '#004e8c',
                    'blue-light': '#52a8e8',
                    green: '#00A651',
                    yellow: '#FFF200',
                    'yellow-soft': '#fff6a3',
                },
                /** Merkgetinte UI-surfaces (licht = koel blauw-grijs, donker = diep marine) */
                app: {
                    canvas: '#e3edf7',
                    'canvas-dark': '#071422',
                    panel: '#ffffff',
                    'panel-dark': '#0c1a2e',
                    sidebar: '#eef6fc',
                    'sidebar-dark': '#081320',
                    border: '#a8c0da',
                    'border-dark': '#1a4070',
                    /** Secundaire tekst (licht modus): donkergrijs i.p.v. blauwgrijs */
                    muted: '#3f3f3f',
                    'muted-dark': '#8faabe',
                    /** Primaire tekst licht modus: zwart */
                    ink: '#000000',
                    'ink-dark': '#e8f1fb',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
