import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                charcoal: {
                    DEFAULT: '#0b1220',
                    900: '#060d1b',
                    800: '#0b1220',
                    700: '#1a2438',
                },
                electric: {
                    DEFAULT: '#0070f3',
                    hover: '#0060d9',
                },
                'muted-green': {
                    DEFAULT: '#4ade80',
                    low: '#14532d',
                },
            },
        },
    },

    plugins: [forms],
};
