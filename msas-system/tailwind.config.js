import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans:    ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
                heading: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy:     '#1e3a5f',
                teal:     '#14b8a6',
                amber:    '#f59e0b',
                graytext: '#94a3b8',
                carddark: '#162d4a',
                cardlight:'#2a4a6e',
            },
        },
    },

    plugins: [forms],
};
