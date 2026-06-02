import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
                heading: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                pm: {
                    shell: '#060B18',
                    page: '#0d1117',
                    card: '#161b22',
                    surface: '#0f1319',
                    sidebar: '#0A1628',
                    border: '#21262d',
                    'border-hover': '#30363d',
                },
                navy: {
                    base: '#0B1120',
                    surface: 'rgba(255, 255, 255, 0.03)',
                    border: 'rgba(255, 255, 255, 0.08)',
                    muted: '#94a3b8',
                },
            },
            borderWidth: {
                3: '3px',
            },
            boxShadow: {
                'emerald-glow': '0 0 20px rgba(16,185,129,0.15)',
                'emerald-glow-lg': '0 0 40px rgba(16,185,129,0.3)',
            },
            animation: {
                'fade-up': 'fadeUp 0.6s ease forwards',
                'orb-float': 'pm-orb-float 20s ease-in-out infinite',
                'pulse-dot': 'pm-pulse-dot 2s infinite',
            },
            keyframes: {
                fadeUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'pm-orb-float': {
                    '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                    '33%': { transform: 'translate(30px, -40px) scale(1.05)' },
                    '66%': { transform: 'translate(-20px, 20px) scale(0.95)' },
                },
                'pm-pulse-dot': {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.4' },
                },
            },
        },
    },

    plugins: [forms],
};
