// Synthesises every sound in the promo — no samples, no licensing.
// Run with `npm run audio`; writes WAVs into public/audio.
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const SR = 44100;
const OUT = join(dirname(fileURLToPath(import.meta.url)), '..', 'public', 'audio');
mkdirSync(OUT, { recursive: true });

// Deterministic noise so re-runs produce identical files.
let seed = 1234567;
const noise = () => {
    seed = (seed * 1664525 + 1013904223) >>> 0;
    return (seed / 4294967296) * 2 - 1;
};

const buf = (seconds) => new Float32Array(Math.ceil(seconds * SR));
const midi = (n) => 440 * 2 ** ((n - 69) / 12);

function writeWav(name, channels) {
    const frames = channels[0].length;
    const n = channels.length;
    const data = Buffer.alloc(44 + frames * n * 2);
    data.write('RIFF', 0);
    data.writeUInt32LE(36 + frames * n * 2, 4);
    data.write('WAVE', 8);
    data.write('fmt ', 12);
    data.writeUInt32LE(16, 16);
    data.writeUInt16LE(1, 20);
    data.writeUInt16LE(n, 22);
    data.writeUInt32LE(SR, 24);
    data.writeUInt32LE(SR * n * 2, 28);
    data.writeUInt16LE(n * 2, 32);
    data.writeUInt16LE(16, 34);
    data.write('data', 36);
    data.writeUInt32LE(frames * n * 2, 40);
    let o = 44;
    for (let i = 0; i < frames; i++) {
        for (let c = 0; c < n; c++) {
            const s = Math.max(-1, Math.min(1, channels[c][i]));
            data.writeInt16LE(Math.round(s * 32767), o);
            o += 2;
        }
    }
    writeFileSync(join(OUT, `${name}.wav`), data);
    console.log(`  ${name}.wav  ${(frames / SR).toFixed(2)}s`);
}

function normalise(b, peak = 0.9) {
    let max = 0;
    for (const s of b) max = Math.max(max, Math.abs(s));
    if (max > 0) for (let i = 0; i < b.length; i++) b[i] = (b[i] / max) * peak;
    return b;
}

// RBJ biquad band-pass with a per-sample centre frequency.
function bandpass(input, freqAt, q = 1.2) {
    const out = new Float32Array(input.length);
    let x1 = 0, x2 = 0, y1 = 0, y2 = 0;
    for (let i = 0; i < input.length; i++) {
        const w = (2 * Math.PI * Math.min(freqAt(i / SR), SR * 0.45)) / SR;
        const alpha = Math.sin(w) / (2 * q);
        const a0 = 1 + alpha;
        const b0 = alpha / a0, b2 = -alpha / a0;
        const a1 = (-2 * Math.cos(w)) / a0, a2 = (1 - alpha) / a0;
        const y = b0 * input[i] + b2 * x2 - a1 * y1 - a2 * y2;
        x2 = x1; x1 = input[i]; y2 = y1; y1 = y;
        out[i] = y;
    }
    return out;
}

const onePole = (input, cutoff) => {
    const out = new Float32Array(input.length);
    const a = Math.exp((-2 * Math.PI * cutoff) / SR);
    let y = 0;
    for (let i = 0; i < input.length; i++) out[i] = y = (1 - a) * input[i] + a * y;
    return out;
};

// Bell-ish partials, used by chime, sparkle, coin and the plucks.
function bell(b, start, freq, amp, decay, partials = [[1, 1], [2.76, 0.35], [5.4, 0.12]]) {
    const s0 = Math.floor(start * SR);
    const len = Math.floor(decay * 5 * SR);
    for (let i = 0; i < len && s0 + i < b.length; i++) {
        const t = i / SR;
        const env = Math.exp(-t / decay) * Math.min(1, t / 0.002);
        let v = 0;
        for (const [ratio, a] of partials) v += a * Math.sin(2 * Math.PI * freq * ratio * t) * Math.exp((-t * ratio) / (decay * 4));
        b[s0 + i] += v * env * amp;
    }
}

console.log('Sound effects');

{ // whoosh — swept band-passed noise, swelling then gone
    const d = 0.7, n = buf(d);
    for (let i = 0; i < n.length; i++) n[i] = noise();
    const f = bandpass(n, (t) => 250 * 14 ** Math.sin((Math.PI * t) / d), 0.9);
    for (let i = 0; i < f.length; i++) {
        const t = i / SR;
        f[i] *= Math.sin((Math.PI * t) / d) ** 2;
    }
    writeWav('whoosh', [normalise(f, 0.8)]);
}

{ // swish — shorter, brighter whoosh for card entrances
    const d = 0.32, n = buf(d);
    for (let i = 0; i < n.length; i++) n[i] = noise();
    const f = bandpass(n, (t) => 1200 + 5000 * (t / d), 1.4);
    for (let i = 0; i < f.length; i++) f[i] *= Math.sin((Math.PI * i) / f.length) ** 3;
    writeWav('swish', [normalise(f, 0.6)]);
}

{ // pop — pitch-dropping sine blip
    const b = buf(0.16);
    let ph = 0;
    for (let i = 0; i < b.length; i++) {
        const t = i / SR;
        ph += (2 * Math.PI * (320 + 700 * Math.exp(-t / 0.018))) / SR;
        b[i] = Math.sin(ph) * Math.exp(-t / 0.045) * Math.min(1, t / 0.001);
    }
    writeWav('pop', [normalise(b, 0.8)]);
}

{ // tick — tiny bright double-sine for checkboxes
    const b = buf(0.09);
    for (let i = 0; i < b.length; i++) {
        const t = i / SR;
        b[i] = (Math.sin(2 * Math.PI * 2400 * t) + 0.5 * Math.sin(2 * Math.PI * 3600 * t)) * Math.exp(-t / 0.014);
    }
    writeWav('tick', [normalise(b, 0.55)]);
}

{ // click — UI button press
    const b = buf(0.06);
    for (let i = 0; i < b.length; i++) {
        const t = i / SR;
        b[i] = (0.6 * noise() * Math.exp(-t / 0.002) + Math.sin(2 * Math.PI * 1500 * t) * Math.exp(-t / 0.008));
    }
    writeWav('click', [normalise(onePole(b, 7000), 0.6)]);
}

{ // chime — two-note notification bell (E6 → B6)
    const b = buf(2);
    bell(b, 0, midi(88), 0.7, 0.35);
    bell(b, 0.11, midi(95), 0.8, 0.45);
    writeWav('chime', [normalise(b, 0.7)]);
}

{ // sparkle — fast ascending pentatonic glitter
    const b = buf(1.6);
    [84, 86, 89, 91, 93, 96, 98, 101].forEach((n, k) => bell(b, k * 0.045, midi(n), 0.5 - k * 0.03, 0.18));
    writeWav('sparkle', [normalise(b, 0.55)]);
}

{ // coin — price reveal
    const b = buf(1);
    bell(b, 0, midi(91), 0.6, 0.08, [[1, 1], [2, 0.3]]);
    bell(b, 0.07, midi(96), 0.8, 0.3, [[1, 1], [2, 0.3], [3, 0.1]]);
    writeWav('coin', [normalise(b, 0.6)]);
}

{ // riser — 2s noise + sine sweep that cuts dead
    const d = 2, n = buf(d);
    for (let i = 0; i < n.length; i++) n[i] = noise();
    const f = bandpass(n, (t) => 200 * 30 ** (t / d), 2.5);
    let ph = 0;
    for (let i = 0; i < f.length; i++) {
        const t = i / SR, p = t / d;
        ph += (2 * Math.PI * (110 * 8 ** p)) / SR;
        f[i] = (f[i] * 1.4 + 0.25 * Math.sin(ph)) * p ** 2.2 * Math.min(1, (d - t) / 0.01);
    }
    writeWav('riser', [normalise(f, 0.7)]);
}

{ // impact — sub drop + crunchy transient + air tail
    const b = buf(2.2);
    let ph = 0;
    for (let i = 0; i < b.length; i++) {
        const t = i / SR;
        ph += (2 * Math.PI * (38 + 90 * Math.exp(-t / 0.05))) / SR;
        b[i] = Math.sin(ph) * Math.exp(-t / 0.6) + 0.5 * noise() * Math.exp(-t / 0.03);
    }
    const tail = buf(2.2);
    for (let i = 0; i < tail.length; i++) tail[i] = noise() * Math.exp(-(i / SR) / 0.5) * 0.15;
    const air = bandpass(tail, () => 4000, 0.7);
    for (let i = 0; i < b.length; i++) b[i] = Math.tanh(b[i] * 1.5) + air[i];
    writeWav('impact', [normalise(b, 0.9)]);
}

// ---------------------------------------------------------------------------
console.log('Music');
// 120 BPM, one bar = 2s = 60 video frames. F major: F – C – Dm – B♭.
// The video is 41 bars: the outro (pricing) starts at bar 35 and its logo
// lands on the final chord three bars later. Keep these in step with the
// scene lengths in src/HouseHubPromo.tsx.
const BPM = 120, BEAT = 60 / BPM, BAR = BEAT * 4, BARS = 41, OUTRO_BAR = 35, HIT_BAR = 38;
const LEN = BARS * BAR + 3;
const L = buf(LEN), R = buf(LEN);
const chords = [
    [53, 57, 60, 65], // F
    [48, 55, 60, 64], // C
    [50, 57, 62, 65], // Dm
    [46, 53, 58, 62], // Bb
];
const roots = [41, 36, 38, 34];

// Section map: what plays in each bar. Drop at bar 5 (dashboard reveal),
// breakdown through the pricing, final hit on the logo.
const has = (bar, part) => {
    if (bar >= HIT_BAR) return false;
    if (bar >= OUTRO_BAR) return ['pad', 'pluck'].includes(part);
    if (bar < 2) return part === 'pad';
    if (bar < 5) return ['pad', 'pluck', 'hat'].includes(part);
    return true;
};

const add = (ch, i, v) => { if (i >= 0 && i < ch.length) ch[i] += v; };
const addStereo = (i, v, pan = 0) => { add(L, i, v * (1 - pan) * 0.5 + v * 0.5); add(R, i, v * (1 + pan) * 0.5 + v * 0.5); };

function kick(t0, amp = 1) {
    const s0 = Math.floor(t0 * SR); let ph = 0;
    for (let i = 0; i < 0.45 * SR; i++) {
        const t = i / SR;
        ph += (2 * Math.PI * (45 + 110 * Math.exp(-t / 0.035))) / SR;
        addStereo(s0 + i, Math.tanh(1.6 * Math.sin(ph)) * Math.exp(-t / 0.16) * 0.55 * amp);
    }
}
function clap(t0) {
    const s0 = Math.floor(t0 * SR), len = Math.floor(0.25 * SR);
    const n = new Float32Array(len);
    for (let i = 0; i < len; i++) {
        const t = i / SR;
        const env = [0, 0.011, 0.022].reduce((a, off) => a + (t >= off ? Math.exp(-(t - off) / (off === 0.022 ? 0.07 : 0.006)) : 0), 0);
        n[i] = noise() * env;
    }
    const f = bandpass(n, () => 1500, 0.8);
    for (let i = 0; i < len; i++) addStereo(s0 + i, f[i] * 0.3, 0.1);
}
function hat(t0, amp, pan) {
    const s0 = Math.floor(t0 * SR); let prev = 0;
    for (let i = 0; i < 0.06 * SR; i++) {
        const x = noise(), hp = x - prev; prev = x;
        addStereo(s0 + i, hp * Math.exp(-(i / SR) / 0.018) * amp, pan);
    }
}
function bassNote(t0, note, dur) {
    const s0 = Math.floor(t0 * SR), len = Math.floor(dur * SR), f = midi(note);
    let y = 0;
    for (let i = 0; i < len; i++) {
        const t = i / SR;
        let v = 0;
        for (let h = 1; h <= 6; h++) v += Math.sin(2 * Math.PI * f * h * t) / h;
        const cutoff = 300 + 1400 * Math.exp(-t / 0.06);
        const a = Math.exp((-2 * Math.PI * cutoff) / SR);
        y = (1 - a) * v + a * y;
        const env = Math.min(1, t / 0.004) * Math.min(1, (dur - t) / 0.02) * (0.7 + 0.3 * Math.exp(-t / 0.1));
        addStereo(s0 + i, y * env * 0.28);
    }
}
function padChord(t0, notes, dur, amp) {
    const s0 = Math.floor(t0 * SR), len = Math.floor(dur * SR);
    for (const [k, note] of notes.entries()) {
        const f = midi(note + 12);
        for (const [det, pan] of [[-0.12, -0.7], [0.12, 0.7]]) {
            const fd = f * 2 ** (det / 12); let y = 0;
            const a = Math.exp((-2 * Math.PI * 1800) / SR);
            const phase0 = k * 1.3 + det * 10;
            for (let i = 0; i < len; i++) {
                const t = i / SR;
                let v = 0;
                for (let h = 1; h <= 7; h++) v += Math.sin(2 * Math.PI * fd * h * t + phase0 * h) / h;
                y = (1 - a) * v + a * y;
                const env = Math.min(1, t / 0.35) * Math.min(1, (dur - t) / 0.3);
                addStereo(s0 + i, y * env * amp * 0.035, pan);
            }
        }
    }
}
function pluck(t0, note, amp, pan) {
    const s0 = Math.floor(t0 * SR), f = midi(note);
    for (let i = 0; i < 0.5 * SR; i++) {
        const t = i / SR;
        const v = Math.sin(2 * Math.PI * f * t) + 0.3 * Math.sin(2 * Math.PI * f * 2 * t) * Math.exp(-t / 0.05);
        addStereo(s0 + i, v * Math.exp(-t / 0.12) * Math.min(1, t / 0.002) * amp, pan);
    }
}

for (let bar = 0; bar < BARS; bar++) {
    const t = bar * BAR, c = bar % 4;
    if (has(bar, 'pad')) {
        padChord(t, chords[c], BAR + 0.3, bar < 2 ? 0.5 + bar * 0.25 : 0.8);
    }
    if (bar === HIT_BAR) { kick(t, 1.3); bassNote(t, roots[0], BAR * 1.5); }
    if (has(bar, 'kick')) for (let b = 0; b < 4; b++) kick(t + b * BEAT);
    if (has(bar, 'clap')) { clap(t + BEAT); clap(t + 3 * BEAT); }
    if (has(bar, 'hat')) for (let e = 0; e < 8; e++) {
        const off = e % 2 === 1;
        if (bar < 5 && !off) continue;
        hat(t + e * (BEAT / 2), off ? 0.2 : 0.08, off ? 0.4 : -0.4);
    }
    if (has(bar, 'bass')) for (let e = 0; e < 8; e++) bassNote(t + e * (BEAT / 2), roots[c] + (e === 7 ? 12 : 0), BEAT / 2 - 0.02);
    if (has(bar, 'pluck')) {
        const tones = chords[c].map((n) => n + 24);
        const pattern = [0, 2, 1, 3, 2, 1, 3, 2];
        pattern.forEach((p, e) => pluck(t + e * (BEAT / 2), tones[p], bar < 5 ? 0.07 : 0.09, e % 2 ? 0.5 : -0.5));
    }
}
// Hold the final F chord on the logo and let it ring out.
padChord(HIT_BAR * BAR, chords[0], BAR * 3 - 0.2, 1);
[65, 69, 72, 77].forEach((n, k) => pluck(HIT_BAR * BAR + k * 0.06, n + 12, 0.1, k % 2 ? 0.4 : -0.4));

// Master: gentle glue + soft clip, then a fade on the last second.
const master = (ch) => {
    for (let i = 0; i < ch.length; i++) {
        const t = i / SR;
        const fade = Math.min(1, t / 0.5) * Math.min(1, Math.max(0, (LEN - t) / 2.5));
        ch[i] = Math.tanh(ch[i] * 1.2) * fade;
    }
};
master(L); master(R);
let peak = 0;
for (let i = 0; i < L.length; i++) peak = Math.max(peak, Math.abs(L[i]), Math.abs(R[i]));
for (let i = 0; i < L.length; i++) { L[i] = (L[i] / peak) * 0.85; R[i] = (R[i] / peak) * 0.85; }
writeWav('music', [L, R]);
