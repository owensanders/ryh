import { loadFont as loadFigtree } from '@remotion/google-fonts/Figtree';
import { loadFont as loadSerif } from '@remotion/google-fonts/InstrumentSerif';
import { loadFont as loadMono } from '@remotion/google-fonts/JetBrainsMono';

// Same families the app loads from bunny.net.
export const fonts = {
    sans: loadFigtree('normal', { weights: ['400', '500', '600', '700', '800', '900'], subsets: ['latin'] }).fontFamily,
    serif: loadSerif('italic', { weights: ['400'], subsets: ['latin'] }).fontFamily,
    mono: loadMono('normal', { weights: ['400', '500'], subsets: ['latin'] }).fontFamily,
};

// Mirrors the --ryh-* tokens in resources/css/app.css.
export const light = {
    bg: '#eeeae3',
    shell: '#fcfaf7',
    panel: '#f4f1eb',
    card: '#ffffff',
    soft: '#f2eee7',
    sunk: '#ede8e0',
    ink: '#0e1a2b',
    ink2: 'rgba(14, 26, 43, 0.56)',
    ink3: 'rgba(14, 26, 43, 0.4)',
    line: 'rgba(14, 26, 43, 0.09)',
    shadow: '0 30px 70px -30px rgba(14, 26, 43, 0.45)',
};

export const dark = {
    bg: '#07090f',
    shell: '#0e1119',
    panel: '#0b0e15',
    card: '#151a25',
    soft: '#1a2130',
    sunk: '#11161f',
    ink: '#f2f5f9',
    ink2: 'rgba(242, 245, 249, 0.62)',
    ink3: 'rgba(242, 245, 249, 0.42)',
    line: 'rgba(255, 255, 255, 0.09)',
    shadow: '0 30px 70px -26px rgba(0, 0, 0, 0.8)',
};

export type Palette = typeof light;

export const brand = {
    coral: '#ff4f40',
    mint: '#3ddc97',
    lilac: '#8b7cf6',
    sun: '#ffc94d',
    sky: '#6bc5f8',
    hero1: '#101c2e',
    hero2: '#1b2f4a',
    onhero: '#fbfaf8',
    onhero2: 'rgba(251, 250, 248, 0.66)',
    herofilm: 'rgba(255, 255, 255, 0.09)',
    heroline: 'rgba(255, 255, 255, 0.14)',
    onDark: '#0e1a2b',
};

export const heroGradient = `linear-gradient(118deg, ${brand.hero1} 0%, ${brand.hero2} 100%)`;

export const tints = [
    'linear-gradient(150deg, #ffd9d4, #ffb3a8)',
    'linear-gradient(150deg, #d8f6e7, #a9e9cc)',
    'linear-gradient(150deg, #dde6ff, #bcc9fa)',
    'linear-gradient(150deg, #ffefc9, #ffdc97)',
    'linear-gradient(150deg, #e9e1ff, #cfc2fa)',
];

export const darkTints = [
    'linear-gradient(150deg, #4a2a2c, #6b3a38)',
    'linear-gradient(150deg, #173a2e, #22553f)',
    'linear-gradient(150deg, #1e2a4e, #2c3c6b)',
    'linear-gradient(150deg, #463618, #63501f)',
    'linear-gradient(150deg, #2e2450, #42356e)',
];

// The seeded Parkers household.
export const family = {
    sarah: { name: 'Sarah', initials: 'SP', colour: brand.mint, role: 'Owner' },
    james: { name: 'James', initials: 'JP', colour: brand.lilac, role: 'Adult' },
    mia: { name: 'Mia', initials: 'MP', colour: brand.sun, role: 'Teen' },
    noah: { name: 'Noah', initials: 'NP', colour: brand.sky, role: 'Child' },
};

export type Person = (typeof family)[keyof typeof family];
export const people: Person[] = [family.sarah, family.james, family.mia, family.noah];
