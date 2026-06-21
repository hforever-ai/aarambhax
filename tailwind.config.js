/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans:  ['Inter', 'Noto Sans Devanagari', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                serif: ['Fraunces', 'Noto Serif Devanagari', 'ui-serif', 'Georgia', 'serif'],
                mono:  ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'monospace'],
            },
            colors: {
                bg:        'var(--bg)',
                surface:   'var(--surface)',
                surface2:  'var(--surface-2)',
                fg:        'var(--fg)',
                'fg-muted':'var(--fg-muted)',
                border:    'var(--border)',
                primary:   'var(--primary)',
                accent:    'var(--accent)',
                link:      'var(--link)',
                success:   'var(--success)',
                warning:   'var(--warning)',
                danger:    'var(--danger)',
            },
            maxWidth: {
                prose: '70ch',
            },
        },
    },
    plugins: [],
};
