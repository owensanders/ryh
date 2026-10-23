import React from 'react';
import { AbsoluteFill, Audio, Easing, interpolate, random, Sequence, staticFile, useCurrentFrame } from 'remotion';
import { lerp, prog, range, spr, springs } from '../anim';
import { brand, fonts, heroGradient, light, Palette, Person } from '../theme';

// ---------------------------------------------------------------- audio

export type SoundName = 'whoosh' | 'swish' | 'pop' | 'tick' | 'click' | 'chime' | 'sparkle' | 'coin' | 'riser' | 'impact';

export const Sfx: React.FC<{ at: number; name: SoundName; volume?: number; rate?: number }> = ({ at, name, volume = 0.6, rate = 1 }) => (
    <Sequence from={Math.round(at)} layout="none" name={`sfx:${name}`}>
        <Audio src={staticFile(`audio/${name}.wav`)} volume={volume} playbackRate={rate} />
    </Sequence>
);

// ---------------------------------------------------------------- backgrounds

/** The warm light app background with slow-drifting colour blobs. */
export const WarmBg: React.FC<{ palette?: Palette }> = ({ palette = light }) => {
    const f = useCurrentFrame();
    const blobs = [
        { c: brand.coral, x: 12, y: 18, s: 620, o: 0.16 },
        { c: brand.mint, x: 86, y: 78, s: 700, o: 0.16 },
        { c: brand.lilac, x: 78, y: 8, s: 520, o: 0.12 },
        { c: brand.sun, x: 20, y: 92, s: 480, o: 0.12 },
    ];
    return (
        <AbsoluteFill style={{ background: palette.bg, overflow: 'hidden' }}>
            {blobs.map((b, i) => (
                <div
                    key={i}
                    style={{
                        position: 'absolute',
                        left: `${b.x + Math.sin(f / 50 + i * 2) * 3}%`,
                        top: `${b.y + Math.cos(f / 60 + i) * 4}%`,
                        width: b.s,
                        height: b.s,
                        marginLeft: -b.s / 2,
                        marginTop: -b.s / 2,
                        borderRadius: '50%',
                        background: b.c,
                        opacity: b.o,
                        filter: 'blur(90px)',
                    }}
                />
            ))}
            <AbsoluteFill
                style={{
                    backgroundImage: `radial-gradient(${palette.line} 1.6px, transparent 1.6px)`,
                    backgroundSize: '34px 34px',
                    backgroundPosition: `${(f * 0.3) % 34}px ${(f * 0.15) % 34}px`,
                    maskImage: 'radial-gradient(ellipse at center, black 30%, transparent 85%)',
                }}
            />
        </AbsoluteFill>
    );
};

/** The navy hero gradient, with the coral glow the app uses behind heroes. */
export const HeroBg: React.FC<{ glow?: number; gradient?: string }> = ({ glow = 1, gradient = heroGradient }) => {
    const f = useCurrentFrame();
    return (
        <AbsoluteFill style={{ background: gradient, overflow: 'hidden' }}>
            <div
                style={{
                    position: 'absolute',
                    left: 1150 + Math.sin(f / 40) * 120,
                    top: -220 + Math.cos(f / 55) * 60,
                    width: 820,
                    height: 820,
                    borderRadius: '50%',
                    background: brand.coral,
                    opacity: 0.24 * glow,
                    filter: 'blur(120px)',
                }}
            />
            <div
                style={{
                    position: 'absolute',
                    left: -200 + Math.cos(f / 45) * 80,
                    top: 620 + Math.sin(f / 50) * 60,
                    width: 700,
                    height: 700,
                    borderRadius: '50%',
                    background: brand.lilac,
                    opacity: 0.18 * glow,
                    filter: 'blur(120px)',
                }}
            />
            <Particles />
        </AbsoluteFill>
    );
};

const Particles: React.FC = () => {
    const f = useCurrentFrame();
    return (
        <AbsoluteFill>
            {new Array(40).fill(0).map((_, i) => {
                const x = random(`px${i}`) * 1920;
                const speed = 0.3 + random(`ps${i}`) * 0.8;
                const y = (((random(`py${i}`) * 1200 - f * speed) % 1200) + 1200) % 1200 - 60;
                const s = 2 + random(`pz${i}`) * 3;
                return (
                    <div
                        key={i}
                        style={{
                            position: 'absolute',
                            left: x + Math.sin(f / 30 + i) * 10,
                            top: y,
                            width: s,
                            height: s,
                            borderRadius: '50%',
                            background: 'white',
                            opacity: 0.08 + random(`po${i}`) * 0.18,
                        }}
                    />
                );
            })}
        </AbsoluteFill>
    );
};

// ---------------------------------------------------------------- brand

// The Run Your Home logo, drawn as in resources/js/components/ryh/RyhLogo.vue:
// the roof and "RUN YOUR" in the ink colour, the window and "HOME" in coral.
const ROOF = 'M4 275 L395 6 L786 275 L731 275 L395 46 L59 275 Z M560 46 H644 V195 L560 138 Z';
const PANES = [
    [328, 144],
    [406, 144],
    [328, 222],
    [406, 222],
];

/** The roof alone. `draw` wipes the roof in from the left, then pops the window panes in. */
export const RoofMark: React.FC<{ width: number; color: string; draw?: number }> = ({ width, color, draw = 1 }) => {
    const id = React.useId();
    return (
        <svg width={width} height={(width * 282) / 790} viewBox="0 0 790 282" style={{ display: 'block', flex: 'none', overflow: 'visible' }}>
            <clipPath id={id}>
                <rect x={-10} y={-10} width={810 * Math.min(1, draw / 0.75)} height={300} />
            </clipPath>
            <path d={ROOF} fill={color} stroke={color} strokeWidth={6} strokeLinejoin="round" clipPath={`url(#${id})`} />
            {PANES.map(([x, y], i) => {
                const p = Math.max(0, Math.min(1, (draw - 0.55 - i * 0.08) / 0.2));
                return (
                    <rect
                        key={i}
                        x={x}
                        y={y}
                        width={56}
                        height={56}
                        rx={7}
                        fill={brand.coral}
                        style={{ transformBox: 'fill-box', transformOrigin: 'center', transform: `scale(${p})` }}
                    />
                );
            })}
        </svg>
    );
};

/** "RUN YOUR" over "HOME", `height` tall. `lines` animates each line in (0 hidden, 1 in place). */
export const Wordmark: React.FC<{ height: number; color: string; lines?: [number, number] }> = ({ height, color, lines = [1, 1] }) => (
    <svg width={(height * 662) / 282} height={height} viewBox="860 0 662 282" style={{ display: 'block', flex: 'none', overflow: 'visible' }}>
        {(
            [
                ['RUN YOUR', 84, 117, color],
                ['HOME', 276, 226, brand.coral],
            ] as const
        ).map(([text, y, size, fill], i) => (
            <text
                key={text}
                x={860}
                y={y}
                fontFamily={fonts.sans}
                fontWeight={800}
                fontSize={size}
                textLength={662}
                lengthAdjust="spacingAndGlyphs"
                fill={fill}
                opacity={lines[i]}
                transform={`translate(0 ${(1 - lines[i]) * 90})`}
            >
                {text}
            </text>
        ))}
    </svg>
);

/** The roof beside the wordmark, `height` tall — the app's header logo. */
export const Lockup: React.FC<{ height: number; color: string }> = ({ height, color }) => (
    <div style={{ display: 'flex', alignItems: 'center', gap: (height * 70) / 282 }}>
        <RoofMark width={(height * 790) / 282} color={color} />
        <Wordmark height={height} color={color} />
    </div>
);

/** The installed app's icon (public/icons/icon.svg): the roof on a white tile. */
export const LogoMark: React.FC<{ size: number; radius?: number }> = ({ size, radius }) => (
    <div
        style={{
            width: size,
            height: size,
            flex: 'none',
            borderRadius: radius ?? size * 0.22,
            background: 'white',
            display: 'grid',
            placeItems: 'center',
            boxShadow: `0 ${size * 0.12}px ${size * 0.4}px -${size * 0.16}px rgba(0, 0, 0, 0.35)`,
        }}
    >
        <RoofMark width={size * 0.78} color={light.ink} />
    </div>
);

// ---------------------------------------------------------------- primitives

export const Avatar: React.FC<{ person: Person; size?: number; radius?: number; style?: React.CSSProperties }> = ({
    person,
    size = 34,
    radius,
    style,
}) => (
    <div
        style={{
            width: size,
            height: size,
            borderRadius: radius ?? size * 0.3,
            background: person.colour,
            color: brand.onDark,
            display: 'grid',
            placeItems: 'center',
            fontWeight: 800,
            fontSize: size * 0.36,
            flex: 'none',
            ...style,
        }}
    >
        {person.initials}
    </div>
);

export const Card: React.FC<{ palette?: Palette; style?: React.CSSProperties; children?: React.ReactNode }> = ({ palette = light, style, children }) => (
    <div
        style={{
            background: palette.card,
            border: `1.5px solid ${palette.line}`,
            borderRadius: 26,
            boxShadow: palette.shadow,
            color: palette.ink,
            ...style,
        }}
    >
        {children}
    </div>
);

export const Chip: React.FC<{ children: React.ReactNode; palette?: Palette; style?: React.CSSProperties }> = ({ children, palette = light, style }) => (
    <span
        style={{
            background: palette.soft,
            color: palette.ink2,
            borderRadius: 11,
            padding: '6px 13px',
            fontSize: 17,
            fontWeight: 600,
            whiteSpace: 'nowrap',
            ...style,
        }}
    >
        {children}
    </span>
);

/** The ticked/unticked checkbox from the shopping list. */
export const Tick: React.FC<{ done: number; size?: number; palette?: Palette }> = ({ done, size = 26, palette = light }) => (
    <div
        style={{
            width: size,
            height: size,
            borderRadius: size * 0.3,
            border: `2px solid ${done > 0.5 ? brand.mint : palette.line}`,
            background: done > 0.02 ? brand.mint : 'transparent',
            display: 'grid',
            placeItems: 'center',
            flex: 'none',
            transform: `scale(${1 + Math.sin(Math.min(done, 1) * Math.PI) * 0.35})`,
        }}
    >
        <svg width={size * 0.62} height={size * 0.62} viewBox="0 0 24 24" fill="none" stroke={brand.onDark} strokeWidth={3.5} strokeLinecap="round" strokeLinejoin="round">
            <path d="M4 12.5l5 5L20 6.5" pathLength={1} strokeDasharray={1} strokeDashoffset={1 - Math.min(1, done)} />
        </svg>
    </div>
);

/** A circular progress dial, like the chores score rings. */
export const Dial: React.FC<{ pct: number; size: number; colour: string; track: string; thickness?: number; children?: React.ReactNode; inner: string }> = ({
    pct,
    size,
    colour,
    track,
    thickness = 9,
    children,
    inner,
}) => (
    <div
        style={{
            width: size,
            height: size,
            borderRadius: '50%',
            background: `conic-gradient(${colour} ${pct * 3.6}deg, ${track} 0deg)`,
            display: 'grid',
            placeItems: 'center',
            flex: 'none',
        }}
    >
        <div
            style={{
                width: size - thickness * 2,
                height: size - thickness * 2,
                borderRadius: '50%',
                background: inner,
                display: 'grid',
                placeItems: 'center',
            }}
        >
            {children}
        </div>
    </div>
);

// ---------------------------------------------------------------- captions

export type Segment = string | { accent: string; colour?: string };

/**
 * Big kinetic headline. Words spring up one by one; `{ accent }` segments are
 * set in Instrument Serif italic, like an editorial highlight.
 */
export const Headline: React.FC<{
    segments: Segment[];
    delay?: number;
    size?: number;
    colour?: string;
    stagger?: number;
    align?: 'left' | 'center';
    style?: React.CSSProperties;
}> = ({ segments, delay = 0, size = 92, colour = light.ink, stagger = 3, align = 'left', style }) => {
    const f = useCurrentFrame();
    const words: { text: string; accent: boolean; colour?: string }[] = [];
    for (const seg of segments) {
        if (typeof seg === 'string') seg.split(' ').filter(Boolean).forEach((w) => words.push({ text: w, accent: false }));
        else seg.accent.split(' ').forEach((w) => words.push({ text: w, accent: true, colour: seg.colour }));
    }
    return (
        <div
            style={{
                fontFamily: fonts.sans,
                fontWeight: 900,
                fontSize: size,
                lineHeight: 1.02,
                letterSpacing: '-0.04em',
                color: colour,
                textAlign: align,
                display: 'flex',
                flexWrap: 'wrap',
                justifyContent: align === 'center' ? 'center' : 'flex-start',
                columnGap: size * 0.24,
                ...style,
            }}
        >
            {words.map((w, i) => {
                const p = spr(f, delay + i * stagger, springs.snappy);
                return (
                    <span key={i} style={{ display: 'inline-block', overflow: 'hidden', padding: `0 ${size * 0.04}px ${size * 0.12}px`, margin: `0 -${size * 0.04}px -${size * 0.12}px` }}>
                        <span
                            style={{
                                display: 'inline-block',
                                transform: `translateY(${(1 - p) * 110}%) rotate(${(1 - p) * 6}deg)`,
                                opacity: Math.min(1, p * 2),
                                fontFamily: w.accent ? fonts.serif : undefined,
                                fontStyle: w.accent ? 'italic' : undefined,
                                fontWeight: w.accent ? 400 : undefined,
                                letterSpacing: w.accent ? '-0.02em' : undefined,
                                fontSize: w.accent ? size * 1.12 : undefined,
                                color: w.accent ? (w.colour ?? brand.coral) : undefined,
                            }}
                        >
                            {w.text}
                        </span>
                    </span>
                );
            })}
        </div>
    );
};

export const Eyebrow: React.FC<{ icon: string; label: string; delay?: number; dark?: boolean }> = ({ icon, label, delay = 0, dark }) => {
    const f = useCurrentFrame();
    const p = spr(f, delay, springs.pop);
    return (
        <div
            style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: 12,
                height: 50,
                padding: '0 20px 0 8px',
                borderRadius: 16,
                background: dark ? brand.herofilm : light.card,
                border: `1.5px solid ${dark ? brand.heroline : light.line}`,
                color: dark ? brand.onhero : light.ink,
                fontFamily: fonts.sans,
                fontWeight: 800,
                fontSize: 19,
                letterSpacing: '0.06em',
                textTransform: 'uppercase',
                transform: `scale(${p}) translateX(${(1 - p) * -40}px)`,
                transformOrigin: 'left center',
                opacity: Math.min(1, p * 1.5),
                boxShadow: dark ? undefined : '0 12px 30px -18px rgba(14,26,43,0.4)',
            }}
        >
            <span style={{ width: 36, height: 36, borderRadius: 11, background: dark ? brand.heroline : light.soft, display: 'grid', placeItems: 'center', fontSize: 20 }}>
                {icon}
            </span>
            {label}
        </div>
    );
};

export const Sub: React.FC<{ children: React.ReactNode; delay?: number; dark?: boolean; size?: number; style?: React.CSSProperties }> = ({
    children,
    delay = 0,
    dark,
    size = 30,
    style,
}) => {
    const f = useCurrentFrame();
    const p = prog(f, delay, delay + 20);
    return (
        <div
            style={{
                fontFamily: fonts.sans,
                fontSize: size,
                lineHeight: 1.45,
                fontWeight: 500,
                color: dark ? brand.onhero2 : light.ink2,
                opacity: p,
                transform: `translateY(${(1 - p) * 24}px)`,
                filter: `blur(${(1 - p) * 8}px)`,
                ...style,
            }}
        >
            {children}
        </div>
    );
};

// ---------------------------------------------------------------- cursor

export type CursorKey = { f: number; x: number; y: number };

/** A macOS-style pointer that glides between keyframes and ripples on clicks. */
export const Cursor: React.FC<{ keys: CursorKey[]; clicks?: number[]; show?: [number, number] }> = ({ keys, clicks = [], show = [0, 99999] }) => {
    const f = useCurrentFrame();
    const ease = Easing.bezier(0.65, 0, 0.35, 1);
    let x = keys[0].x, y = keys[0].y;
    for (let i = 0; i < keys.length - 1; i++) {
        const a = keys[i], b = keys[i + 1];
        if (f >= a.f && f <= b.f) {
            const t = ease((f - a.f) / Math.max(1, b.f - a.f));
            x = lerp(t, a.x, b.x);
            y = lerp(t, a.y, b.y) - Math.sin(t * Math.PI) * 30; // slight arc
        } else if (f > b.f) {
            x = b.x;
            y = b.y;
        }
    }
    const vis = range(f, [show[0], show[0] + 8, show[1] - 8, show[1]], [0, 1, 1, 0]);
    const press = clicks.reduce((acc, c) => Math.max(acc, range(f, [c - 3, c, c + 5], [0, 1, 0])), 0);
    return (
        <>
            {clicks.map((c) => {
                const p = prog(f, c, c + 18);
                if (f < c || p >= 1) return null;
                return (
                    <div
                        key={c}
                        style={{
                            position: 'absolute',
                            left: x - 40,
                            top: y - 40,
                            width: 80,
                            height: 80,
                            borderRadius: '50%',
                            border: `4px solid ${brand.coral}`,
                            transform: `scale(${0.3 + p * 1.2})`,
                            opacity: 1 - p,
                        }}
                    />
                );
            })}
            <svg
                width={44}
                height={44}
                viewBox="0 0 28 28"
                style={{
                    position: 'absolute',
                    left: x - 6,
                    top: y - 3,
                    opacity: vis,
                    transform: `scale(${1 - press * 0.18})`,
                    transformOrigin: '6px 3px',
                    filter: 'drop-shadow(0 6px 10px rgba(0,0,0,0.35))',
                }}
            >
                <path d="M5 3l16 10.5-7 1.2 4.2 8.3-3.2 1.6-4.2-8.4L5 21z" fill="#111" stroke="white" strokeWidth={1.6} strokeLinejoin="round" />
            </svg>
        </>
    );
};

// ---------------------------------------------------------------- transitions

/** A circle that grows from a point to cover the frame — used as a wipe. */
export const CircleWipe: React.FC<{ start: number; len?: number; colour: string; x?: number; y?: number }> = ({ start, len = 14, colour, x = 960, y = 540 }) => {
    const f = useCurrentFrame();
    const p = prog(f, start, start + len, Easing.in(Easing.cubic));
    if (p <= 0) return null;
    const r = p * 2300;
    return <div style={{ position: 'absolute', left: x - r, top: y - r, width: r * 2, height: r * 2, borderRadius: '50%', background: colour }} />;
};

/** Confetti burst in brand colours. */
export const Confetti: React.FC<{ at: number; x: number; y: number; count?: number }> = ({ at, x, y, count = 90 }) => {
    const f = useCurrentFrame();
    const t = f - at;
    if (t < 0 || t > 90) return null;
    const colours = [brand.coral, brand.mint, brand.lilac, brand.sun, brand.sky];
    return (
        <>
            {new Array(count).fill(0).map((_, i) => {
                const ang = random(`ca${i}`) * Math.PI * 2;
                const speed = 14 + random(`cs${i}`) * 26;
                const px = x + Math.cos(ang) * speed * t * 0.9;
                const py = y + Math.sin(ang) * speed * t * 0.9 + 0.55 * t * t;
                const rot = t * (random(`cr${i}`) * 20 - 10);
                const w = 10 + random(`cw${i}`) * 10;
                return (
                    <div
                        key={i}
                        style={{
                            position: 'absolute',
                            left: px,
                            top: py,
                            width: w,
                            height: w * 0.55,
                            borderRadius: 3,
                            background: colours[i % colours.length],
                            transform: `rotate(${rot}deg) scaleX(${Math.cos(t / 4 + i)})`,
                            opacity: interpolate(t, [60, 90], [1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }),
                        }}
                    />
                );
            })}
        </>
    );
};
