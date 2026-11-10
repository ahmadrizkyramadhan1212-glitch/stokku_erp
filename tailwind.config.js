import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                // Soft Lavender & Royal Indigo — STOKKU ERP Design System
                primary: '#4F46E5',                 // Royal Indigo
                'on-primary': '#ffffff',
                'primary-container': '#4338CA',
                'on-primary-container': '#EEF2FF',
                'primary-fixed': '#E0E7FF',
                'primary-fixed-dim': '#C7D2FE',
                'on-primary-fixed': '#1E1B4B',
                'on-primary-fixed-variant': '#3730A3',
                'inverse-primary': '#A5B4FC',

                secondary: '#8B5CF6',               // Soft Violet
                'on-secondary': '#ffffff',
                'secondary-container': '#7C3AED',
                'on-secondary-container': '#F5F3FF',
                'secondary-fixed': '#EDE9FE',
                'secondary-fixed-dim': '#DDD6FE',
                'on-secondary-fixed': '#2E1065',
                'on-secondary-fixed-variant': '#5B21B6',

                tertiary: '#10B981',                // Emerald Green
                'on-tertiary': '#ffffff',
                'tertiary-container': '#059669',
                'on-tertiary-container': '#ecfdf5',
                'tertiary-fixed': '#a7f3d0',
                'tertiary-fixed-dim': '#6ee7b7',
                'on-tertiary-fixed': '#022c22',
                'on-tertiary-fixed-variant': '#065f46',

                error: '#EF4444',                   // Coral Red
                'on-error': '#ffffff',
                'error-container': '#fee2e2',
                'on-error-container': '#991b1b',

                surface: '#F8F7FF',
                'surface-bright': '#ffffff',
                'surface-dim': '#EDE9FE',
                'surface-container-lowest': '#ffffff',
                'surface-container-low': '#F5F3FF',
                'surface-container': '#EDE9FE',
                'surface-container-high': '#DDD6FE',
                'surface-container-highest': '#C4B5FD',
                'surface-variant': '#EDE9FE',
                'surface-tint': '#4F46E5',

                'on-surface': '#1E1B4B',            // Deep Indigo Slate
                'on-surface-variant': '#475569',
                'inverse-surface': '#181625',
                'inverse-on-surface': '#F8FAFC',

                background: '#F8F7FF',
                'on-background': '#1E1B4B',
                outline: '#64748B',
                'outline-variant': '#DDD6FE',
            },
            spacing: {
                'space-xs': '0.25rem',
                'space-sm': '0.5rem',
                'space-md': '1rem',
                'space-lg': '1.5rem',
                'space-xl': '2rem',
                gutter: '1rem',
                margin: '1rem',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Inter', ...defaultTheme.fontFamily.sans],
                'headline-lg': ['Inter', ...defaultTheme.fontFamily.sans],
                'headline-md': ['Inter', ...defaultTheme.fontFamily.sans],
                'headline-sm': ['Inter', ...defaultTheme.fontFamily.sans],
                'body-lg': ['Inter', ...defaultTheme.fontFamily.sans],
                'body-md': ['Inter', ...defaultTheme.fontFamily.sans],
                'body-sm': ['Inter', ...defaultTheme.fontFamily.sans],
                'label-lg': ['Inter', ...defaultTheme.fontFamily.sans],
                'label-md': ['Inter', ...defaultTheme.fontFamily.sans],
                'label-sm': ['Inter', ...defaultTheme.fontFamily.sans],
                'currency-display': ['Inter', ...defaultTheme.fontFamily.sans],
                'currency-md': ['Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                display: ['32px', { lineHeight: '40px', letterSpacing: '-0.02em', fontWeight: '700' }],
                'headline-lg': ['24px', { lineHeight: '32px', letterSpacing: '-0.015em', fontWeight: '700' }],
                'headline-md': ['20px', { lineHeight: '28px', letterSpacing: '-0.01em', fontWeight: '600' }],
                'headline-sm': ['18px', { lineHeight: '24px', letterSpacing: '600' }],
                'body-lg': ['16px', { lineHeight: '24px', fontWeight: '400' }],
                'body-md': ['14px', { lineHeight: '20px', fontWeight: '400' }],
                'body-sm': ['12px', { lineHeight: '16px', fontWeight: '400' }],
                'label-lg': ['14px', { lineHeight: '20px', letterSpacing: '0.01em', fontWeight: '600' }],
                'label-md': ['12px', { lineHeight: '16px', letterSpacing: '0.02em', fontWeight: '600' }],
                'label-sm': ['10px', { lineHeight: '14px', letterSpacing: '0.04em', fontWeight: '700' }],
                'currency-display': ['28px', { lineHeight: '36px', letterSpacing: '-0.02em', fontWeight: '700' }],
                'currency-md': ['16px', { lineHeight: '22px', fontWeight: '700' }],
            },
        },
    },

    plugins: [forms],
};
