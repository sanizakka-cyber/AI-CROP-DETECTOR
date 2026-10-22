import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import colors from 'tailwindcss/colors';

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
                // A bare colour here replaced the whole amber palette, so every
                // amber-50..900 class (warning boxes, inconclusive results) had no CSS.
                amber:    { ...colors.amber, DEFAULT: '#f59e0b' },
                graytext: '#94a3b8',
                carddark: '#162d4a',
                cardlight:'#2a4a6e',
            },
        },
    },

    plugins: [forms],
};
