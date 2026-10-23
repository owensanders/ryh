import React from 'react';
import { AbsoluteFill, Easing, useCurrentFrame } from 'remotion';
import { prog, range, spr, springs } from '../anim';
import { Confetti, Cursor, HeroBg, RoofMark, Sfx, Wordmark } from '../components/ui';
import { brand, fonts } from '../theme';

// The price holds for six seconds so it can be read properly, then the logo
// lands on the track's final chord (three bars into this scene).
const HIT = 180;
const CTA = HIT + 44;
const CLICK = HIT + 90;

// 360 frames: price, then the logo on the last big chord, then the CTA, which
// the video ends and holds on (the landing page plays it once, no loop).
export const Outro: React.FC = () => {
    const f = useCurrentFrame();

    const priceIn = spr(f, 2, springs.pop);
    const priceOut = prog(f, HIT - 8, HIT + 2, Easing.in(Easing.cubic));
    const price = range(f, [6, 36], [0, 3.99], Easing.out(Easing.cubic));

    const logo = spr(f, HIT, springs.bouncy);
    const word = spr(f, HIT + 6, springs.snappy);
    const cta = spr(f, CTA, springs.pop);
    const press = range(f, [CLICK - 2, CLICK, CLICK + 8], [0, 1, 0]);
    // A slow push-in while the price is held, so it never looks frozen.
    const hold = prog(f, 40, HIT, (t) => t);

    return (
        <AbsoluteFill style={{ fontFamily: fonts.sans }}>
            <HeroBg glow={1 + logo * 0.4} />
            <Sfx at={0} name="riser" volume={0.45} />
            <Sfx at={4} name="swish" volume={0.4} />
            <Sfx at={36} name="coin" volume={0.6} />
            {[40, 45, 50].map((a, i) => (
                <Sfx key={a} at={a} name="pop" volume={0.4} rate={1 + i * 0.12} />
            ))}
            <Sfx at={HIT} name="impact" volume={0.8} />
            <Sfx at={HIT + 4} name="sparkle" volume={0.4} />
            <Sfx at={CTA} name="pop" volume={0.5} rate={0.85} />
            <Sfx at={CLICK} name="click" volume={0.7} />
            <Sfx at={CLICK + 1} name="sparkle" volume={0.55} />

            {/* Pricing */}
            {f < HIT + 4 && (
                <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center' }}>
                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            transform: `scale(${(0.6 + priceIn * 0.4) * (1 + hold * 0.04) * (1 - priceOut * 0.6)}) translateY(${priceOut * -200}px)`,
                            opacity: Math.min(1, priceIn * 2) * (1 - priceOut),
                            filter: `blur(${priceOut * 20}px)`,
                        }}
                    >
                        <span
                            style={{
                                padding: '10px 18px',
                                borderRadius: 14,
                                background: brand.coral,
                                color: 'white',
                                fontSize: 24,
                                fontWeight: 900,
                                letterSpacing: '0.06em',
                                textTransform: 'uppercase',
                            }}
                        >
                            First month free
                        </span>
                        <div style={{ display: 'flex', alignItems: 'baseline', gap: 16, marginTop: 30, color: brand.onhero }}>
                            <span style={{ fontSize: 240, fontWeight: 900, letterSpacing: '-0.06em', lineHeight: 1, fontVariantNumeric: 'tabular-nums' }}>£{price.toFixed(2)}</span>
                            <span style={{ fontSize: 44, fontWeight: 700, color: brand.onhero2 }}>/month</span>
                        </div>
                        <div style={{ fontSize: 34, fontWeight: 600, color: brand.onhero2, marginTop: 10, opacity: prog(f, 26, 38) }}>
                            or £39.99 a year · one subscription for the whole house
                        </div>
                        <div style={{ display: 'flex', gap: 14, marginTop: 36 }}>
                            {['No card needed', 'Up to 8 people', 'Cancel anytime'].map((c, i) => {
                                const p = spr(f, 40 + i * 5, springs.pop);
                                return (
                                    <span
                                        key={c}
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 10,
                                            height: 58,
                                            padding: '0 22px',
                                            borderRadius: 18,
                                            border: `1.5px solid ${brand.heroline}`,
                                            background: brand.herofilm,
                                            color: brand.onhero,
                                            fontSize: 24,
                                            fontWeight: 700,
                                            transform: `scale(${p})`,
                                        }}
                                    >
                                        <span style={{ width: 28, height: 28, borderRadius: 9, background: brand.mint, color: brand.onDark, display: 'grid', placeItems: 'center', fontSize: 16, fontWeight: 900 }}>
                                            ✓
                                        </span>
                                        {c}
                                    </span>
                                );
                            })}
                        </div>
                    </div>
                </AbsoluteFill>
            )}

            {/* Logo + CTA */}
            {f >= HIT && (
                <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center' }}>
                    {[0, 6, 12].map((d) => {
                        const p = prog(f, HIT + d, HIT + d + 40);
                        return (
                            <div
                                key={d}
                                style={{
                                    position: 'absolute',
                                    top: 250,
                                    width: 260,
                                    height: 260,
                                    borderRadius: '50%',
                                    border: `4px solid ${brand.coral}`,
                                    transform: `scale(${1 + p * 6})`,
                                    opacity: (1 - p) * 0.6,
                                }}
                            />
                        );
                    })}
                    <div style={{ position: 'absolute', top: 250, display: 'flex', alignItems: 'center', gap: 40 }}>
                        <div style={{ transform: `scale(${logo}) rotate(${(1 - logo) * 40}deg)` }}>
                            <RoofMark width={532} color={brand.onhero} />
                        </div>
                        <Wordmark height={190} color={brand.onhero} lines={[word, word]} />
                    </div>
                    <div
                        style={{
                            position: 'absolute',
                            top: 520,
                            fontFamily: fonts.serif,
                            fontStyle: 'italic',
                            fontSize: 70,
                            color: brand.onhero2,
                            opacity: prog(f, HIT + 16, HIT + 34),
                            transform: `translateY(${(1 - prog(f, HIT + 16, HIT + 34)) * 20}px)`,
                        }}
                    >
                        Your home, <span style={{ color: brand.coral }}>organised.</span>
                    </div>
                    <div
                        style={{
                            position: 'absolute',
                            top: 700,
                            height: 104,
                            padding: '0 52px',
                            borderRadius: 30,
                            background: brand.coral,
                            color: 'white',
                            fontSize: 40,
                            fontWeight: 800,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 16,
                            transform: `scale(${cta * (1 - press * 0.08) * (1 + Math.sin(f / 7) * 0.012)})`,
                            boxShadow: `0 30px 70px -20px rgba(255,79,64,${0.6 + press * 0.4})`,
                        }}
                    >
                        Start your free month <span style={{ transform: `translateX(${Math.sin(f / 6) * 6}px)`, display: 'inline-block' }}>→</span>
                    </div>
                    <div style={{ position: 'absolute', top: 850, fontSize: 26, fontWeight: 600, color: brand.onhero2, opacity: prog(f, CTA + 14, CTA + 30) }}>
                        Set it up before the kettle boils.
                    </div>
                </AbsoluteFill>
            )}

            <Confetti at={CLICK} x={960} y={752} count={120} />
            <Cursor
                show={[CTA + 14, CLICK + 50]}
                clicks={[CLICK]}
                keys={[
                    { f: CTA + 14, x: 1500, y: 1040 },
                    { f: CLICK - 4, x: 1150, y: 760 },
                    { f: CLICK + 50, x: 1300, y: 900 },
                ]}
            />
        </AbsoluteFill>
    );
};
