// Renders the PWA icons in public/icons/*.png and public/favicon.ico from the
// SVG sources beside them, using Playwright's Chromium (already installed for
// the e2e suite). Re-run after changing an SVG:
//
//   node scripts/generate-pwa-icons.mjs
import { chromium } from '@playwright/test';
import { readFile, rm, writeFile } from 'node:fs/promises';

const dir = new URL('../public/icons/', import.meta.url);

// [source svg, output png, size]
const targets = [
    ['icon.svg', 'icon-192.png', 192],
    ['icon.svg', 'icon-512.png', 512],
    ['icon-maskable.svg', 'icon-maskable-512.png', 512],
    // iOS masks the corners itself, so the touch icon is full-bleed.
    ['icon-full.svg', 'apple-touch-icon.png', 180],
    ['icon.svg', 'favicon-32.png', 32],
];

const browser = await chromium.launch();
const page = await browser.newPage();

for (const [source, output, size] of targets) {
    const svg = await readFile(new URL(source, dir), 'utf8');
    await page.setViewportSize({ width: size, height: size });
    await page.setContent(`<style>html,body{margin:0;background:transparent}svg{display:block;width:${size}px;height:${size}px}</style>${svg}`);
    await writeFile(new URL(output, dir), await page.locator('svg').screenshot({ omitBackground: true }));
}

await browser.close();

// An .ico that wraps the 32px PNG (supported by every current browser).
const png = await readFile(new URL('favicon-32.png', dir));
const header = Buffer.alloc(22);
header.writeUInt16LE(0, 0); // reserved
header.writeUInt16LE(1, 2); // type: icon
header.writeUInt16LE(1, 4); // image count
header.writeUInt8(32, 6); // width
header.writeUInt8(32, 7); // height
header.writeUInt16LE(1, 10); // colour planes
header.writeUInt16LE(32, 12); // bits per pixel
header.writeUInt32LE(png.length, 14);
header.writeUInt32LE(22, 18); // image data offset
await writeFile(new URL('../favicon.ico', dir), Buffer.concat([header, png]));
await rm(new URL('favicon-32.png', dir));
