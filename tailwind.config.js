import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js,ts,jsx,tsx}',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['Instrument Serif', ...defaultTheme.fontFamily.serif],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            animation: {
                'ryh-rise': 'ryh-rise 0.35s ease both',
                'ryh-pop': 'ryh-pop 0.4s ease both',
                'ryh-toast': 'ryh-toast 0.26s cubic-bezier(0.2, 0.9, 0.3, 1.3) both',
                'ryh-glow': 'ryh-glow 2.4s ease infinite',
            },
            boxShadow: {
                ryh: 'var(--ryh-shadow)',
            },
            borderRadius: {
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
            },
            colors: {
                // RYH design tokens — see the `--ryh-*` block in app.css.
                ryh: {
                    bg: 'var(--ryh-bg)',
                    shell: 'var(--ryh-shell)',
                    panel: 'var(--ryh-panel)',
                    card: 'var(--ryh-card)',
                    soft: 'var(--ryh-soft)',
                    sunk: 'var(--ryh-sunk)',
                    ink: 'var(--ryh-ink)',
                    ink2: 'var(--ryh-ink2)',
                    ink3: 'var(--ryh-ink3)',
                    line: 'var(--ryh-line)',
                    onhero: 'var(--ryh-onhero)',
                    onhero2: 'var(--ryh-onhero2)',
                    herofilm: 'var(--ryh-herofilm)',
                    heroline: 'var(--ryh-heroline)',
                    ontint: 'var(--ryh-ontint)',
                    coral: 'var(--ryh-coral)',
                    mint: 'var(--ryh-mint)',
                    lilac: 'var(--ryh-lilac)',
                    sun: 'var(--ryh-sun)',
                    sky: 'var(--ryh-sky)',
                },
                background: 'hsl(var(--background))',
                foreground: 'hsl(var(--foreground))',
                card: {
                    DEFAULT: 'hsl(var(--card))',
                    foreground: 'hsl(var(--card-foreground))',
                },
                popover: {
                    DEFAULT: 'hsl(var(--popover))',
                    foreground: 'hsl(var(--popover-foreground))',
                },
                primary: {
                    DEFAULT: 'hsl(var(--primary))',
                    foreground: 'hsl(var(--primary-foreground))',
                },
                secondary: {
                    DEFAULT: 'hsl(var(--secondary))',
                    foreground: 'hsl(var(--secondary-foreground))',
                },
                muted: {
                    DEFAULT: 'hsl(var(--muted))',
                    foreground: 'hsl(var(--muted-foreground))',
                },
                accent: {
                    DEFAULT: 'hsl(var(--accent))',
                    foreground: 'hsl(var(--accent-foreground))',
                },
                destructive: {
                    DEFAULT: 'hsl(var(--destructive))',
                    foreground: 'hsl(var(--destructive-foreground))',
                },
                border: 'hsl(var(--border))',
                input: 'hsl(var(--input))',
                ring: 'hsl(var(--ring))',
                chart: {
                    1: 'hsl(var(--chart-1))',
                    2: 'hsl(var(--chart-2))',
                    3: 'hsl(var(--chart-3))',
                    4: 'hsl(var(--chart-4))',
                    5: 'hsl(var(--chart-5))',
                },
                sidebar: {
                    DEFAULT: 'hsl(var(--sidebar-background))',
                    foreground: 'hsl(var(--sidebar-foreground))',
                    primary: 'hsl(var(--sidebar-primary))',
                    'primary-foreground': 'hsl(var(--sidebar-primary-foreground))',
                    accent: 'hsl(var(--sidebar-accent))',
                    'accent-foreground': 'hsl(var(--sidebar-accent-foreground))',
                    border: 'hsl(var(--sidebar-border))',
                    ring: 'hsl(var(--sidebar-ring))',
                },
            },
        },
    },
    plugins: [require('tailwindcss-animate')],
};
