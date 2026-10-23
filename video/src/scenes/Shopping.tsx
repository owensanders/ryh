import React from 'react';
import { AbsoluteFill, Easing, useCurrentFrame } from 'remotion';
import { lerp, prog, range, spr, springs } from '../anim';
import { Card, CircleWipe, Cursor, Eyebrow, Headline, Sfx, Sub, Tick, WarmBg } from '../components/ui';
import { brand, dark, fonts, heroGradient, light, tints } from '../theme';

const R = { x: 100, y: 190, w: 480 };
const L = { x: 630, y: 150, w: 540, h: 790 };
const ingRow = (i: number) => R.y + 262 + i * 50 + 25;
const listRow = (i: number) => L.y + 160 + i * 56 + 28;

const ingredients = [
    { name: 'Chicken thighs', qty: '1kg' },
    { name: 'New potatoes', qty: '750g' },
    { name: 'Lemons', qty: 'x3' },
    { name: 'Garlic', qty: '1 bulb' },
    { name: 'Rosemary', qty: '1 bunch' },
];
const existing = [
    { name: 'Oat milk', qty: 'x2' },
    { name: 'Sourdough', qty: '1' },
    { name: 'Bananas', qty: '6' },
];

const CLICK = 46;
const launch = (i: number) => CLICK + 6 + i * 6;
const FLIGHT = 18;
const ticks: Record<string, number> = { 'Oat milk': 124, 'Chicken thighs': 134, Lemons: 144 };

// 180 frames.
export const Shopping: React.FC = () => {
    const f = useCurrentFrame();

    const recipeIn = spr(f, 2, springs.snappy);
    const listIn = spr(f, 8, springs.snappy);
    const toast = spr(f, 96, springs.pop) * (1 - prog(f, 150, 160));

    const items = [...existing, ...ingredients];
    const remaining = items.filter((it, i) => (i < 3 || f >= launch(i - 3) + FLIGHT) && !(ticks[it.name] && f >= ticks[it.name] + 4)).length;

    return (
        <AbsoluteFill>
            <WarmBg />
            <Sfx at={2} name="swish" volume={0.4} />
            <Sfx at={CLICK} name="click" volume={0.6} />
            {ingredients.map((_, i) => (
                <React.Fragment key={i}>
                    <Sfx at={launch(i)} name="swish" volume={0.18} rate={1.3} />
                    <Sfx at={launch(i) + FLIGHT} name="pop" volume={0.5} rate={1 + i * 0.1} />
                </React.Fragment>
            ))}
            <Sfx at={96} name="chime" volume={0.25} />
            {Object.values(ticks).map((t, i) => (
                <Sfx key={t} at={t} name="tick" volume={0.7} rate={1 + i * 0.1} />
            ))}
            <Sfx at={164} name="whoosh" volume={0.5} />

            {/* Recipe card */}
            <div style={{ position: 'absolute', left: R.x, top: R.y, transform: `translateY(${(1 - recipeIn) * 700}px) rotate(${(1 - recipeIn) * -10}deg)` }}>
                <Card style={{ width: R.w, padding: 24, fontFamily: fonts.sans }}>
                    <div style={{ height: 180, borderRadius: 20, background: tints[0], padding: 20, display: 'flex', flexDirection: 'column' }}>
                        <span style={{ alignSelf: 'flex-start', fontSize: 14, fontWeight: 800, padding: '5px 10px', borderRadius: 9, background: 'rgba(255,255,255,0.6)' }}>
                            ♥ Favourite · 45 min
                        </span>
                        <div style={{ marginTop: 'auto', fontSize: 34, fontWeight: 900, letterSpacing: '-0.03em', lineHeight: 1.05 }}>Lemon chicken traybake</div>
                    </div>
                    <div style={{ fontSize: 14, fontWeight: 800, letterSpacing: '0.08em', textTransform: 'uppercase', color: light.ink3, margin: '22px 0 8px' }}>Ingredients</div>
                    {ingredients.map((ing, i) => {
                        const gone = prog(f, launch(i), launch(i) + 4);
                        return (
                            <div key={ing.name} style={{ height: 50, display: 'flex', alignItems: 'center', fontSize: 19, borderTop: `1.5px solid ${light.line}` }}>
                                <span style={{ flex: 1 }}>{ing.name}</span>
                                <span style={{ fontFamily: fonts.mono, fontSize: 15, color: light.ink3 }}>{ing.qty}</span>
                                <span style={{ marginLeft: 12, width: 22, color: brand.mint, fontWeight: 900, opacity: gone }}>✓</span>
                            </div>
                        );
                    })}
                    <div
                        style={{
                            marginTop: 18,
                            height: 60,
                            borderRadius: 18,
                            background: brand.coral,
                            color: 'white',
                            fontWeight: 800,
                            fontSize: 19,
                            display: 'grid',
                            placeItems: 'center',
                            transform: `scale(${1 - range(f, [CLICK - 2, CLICK, CLICK + 6], [0, 0.07, 0])})`,
                            boxShadow: '0 16px 30px -14px rgba(255,79,64,0.8)',
                        }}
                    >
                        🛒 Add ingredients to Tesco
                    </div>
                </Card>
            </div>

            {/* Shopping list card */}
            <div style={{ position: 'absolute', left: L.x, top: L.y, transform: `translateY(${(1 - listIn) * 800}px) rotate(${(1 - listIn) * 8}deg)` }}>
                <Card style={{ width: L.w, height: L.h, padding: 26, fontFamily: fonts.sans, position: 'relative', overflow: 'hidden' }}>
                    <div style={{ display: 'flex', alignItems: 'baseline', height: 50 }}>
                        <span style={{ fontSize: 32, fontWeight: 900, letterSpacing: '-0.03em' }}>Tesco</span>
                        <span style={{ marginLeft: 'auto', fontFamily: fonts.mono, fontSize: 17, color: light.ink3 }}>{remaining} left</span>
                    </div>
                    <div style={{ display: 'flex', gap: 8, height: 52, marginTop: 6 }}>
                        {[
                            { n: 'Tesco', c: brand.mint },
                            { n: 'Aldi', c: brand.sky },
                            { n: 'Costco', c: brand.sun },
                            { n: 'Christmas', c: brand.coral },
                        ].map((t, i) => {
                            const p = spr(f, 14 + i * 3, springs.pop);
                            const on = i === 0;
                            return (
                                <span
                                    key={t.n}
                                    style={{
                                        height: 40,
                                        padding: '0 14px',
                                        borderRadius: 12,
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 7,
                                        fontSize: 16,
                                        fontWeight: 700,
                                        background: on ? light.ink : light.soft,
                                        color: on ? light.shell : light.ink2,
                                        transform: `scale(${p})`,
                                    }}
                                >
                                    <span style={{ width: 9, height: 9, borderRadius: '50%', background: t.c }} />
                                    {t.n}
                                </span>
                            );
                        })}
                    </div>
                    <div style={{ height: 30 }} />
                    {items.map((it, i) => {
                        const arrived = i < 3 ? spr(f, 20 + i * 4, springs.snappy) : f >= launch(i - 3) + FLIGHT ? spr(f, launch(i - 3) + FLIGHT, springs.bouncy) : 0;
                        const d = ticks[it.name] ? prog(f, ticks[it.name], ticks[it.name] + 8) : 0;
                        return (
                            <div
                                key={it.name}
                                style={{
                                    height: 56,
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 16,
                                    opacity: Math.min(1, arrived * 1.5),
                                    transform: `scale(${0.6 + arrived * 0.4})`,
                                    transformOrigin: 'left center',
                                    background: i >= 3 ? `rgba(61,220,151,${0.14 * (1 - prog(f, launch(i - 3) + FLIGHT, launch(i - 3) + FLIGHT + 30))})` : undefined,
                                    borderRadius: 12,
                                    margin: '0 -10px',
                                    padding: '0 10px',
                                }}
                            >
                                <Tick done={d} size={28} />
                                <span style={{ flex: 1, fontSize: 20, color: d > 0.5 ? light.ink3 : light.ink }}>
                                    <span style={{ position: 'relative' }}>
                                        {it.name}
                                        <span style={{ position: 'absolute', left: 0, top: '52%', height: 2, width: `${d * 100}%`, background: light.ink3 }} />
                                    </span>
                                </span>
                                <span style={{ fontFamily: fonts.mono, fontSize: 15, color: light.ink3 }}>{it.qty}</span>
                            </div>
                        );
                    })}

                    <div
                        style={{
                            position: 'absolute',
                            left: 26,
                            right: 26,
                            bottom: 26,
                            height: 64,
                            borderRadius: 18,
                            background: heroGradient,
                            color: 'white',
                            display: 'flex',
                            alignItems: 'center',
                            gap: 12,
                            padding: '0 20px',
                            fontSize: 18,
                            fontWeight: 700,
                            transform: `translateY(${(1 - toast) * 120}px)`,
                            opacity: toast,
                        }}
                    >
                        <span style={{ width: 34, height: 34, borderRadius: 10, background: brand.mint, display: 'grid', placeItems: 'center', color: dark.bg }}>✓</span>
                        5 ingredients added to Tesco
                    </div>
                </Card>
            </div>

            {/* Flying ingredient chips */}
            {ingredients.map((ing, i) => {
                const t0 = launch(i);
                const p = prog(f, t0, t0 + FLIGHT, Easing.inOut(Easing.cubic));
                if (f < t0 || f > t0 + FLIGHT) return null;
                const x = lerp(p, R.x + 60, L.x + 70);
                const y = lerp(p, ingRow(i), listRow(3 + i)) - Math.sin(p * Math.PI) * 180;
                return (
                    <div
                        key={ing.name}
                        style={{
                            position: 'absolute',
                            left: x,
                            top: y - 24,
                            height: 48,
                            padding: '0 18px',
                            borderRadius: 14,
                            background: light.ink,
                            color: 'white',
                            fontFamily: fonts.sans,
                            fontWeight: 700,
                            fontSize: 18,
                            display: 'flex',
                            alignItems: 'center',
                            transform: `rotate(${Math.sin(p * Math.PI) * -10}deg) scale(${1 + Math.sin(p * Math.PI) * 0.15})`,
                            boxShadow: '0 20px 30px -12px rgba(14,26,43,0.5)',
                            whiteSpace: 'nowrap',
                        }}
                    >
                        {ing.name}
                    </div>
                );
            })}

            <div style={{ position: 'absolute', left: 1250, top: 300, width: 590 }}>
                <Eyebrow icon="🛒" label="Shopping lists" delay={10} />
                <Headline segments={['Every shop,', { accent: 'one list', colour: brand.mint }, 'away.']} delay={16} size={92} style={{ marginTop: 30 }} />
                <Sub delay={36} style={{ marginTop: 26 }}>
                    Tesco, Aldi, DIY, Christmas — as many lists as you need. Send a recipe's ingredients across in one tap.
                </Sub>
            </div>

            <Cursor
                show={[26, 170]}
                clicks={[CLICK, ...Object.values(ticks)]}
                keys={[
                    { f: 26, x: 900, y: 1040 },
                    { f: CLICK - 4, x: R.x + R.w / 2 + 40, y: 925 },
                    { f: 110, x: R.x + R.w / 2 + 60, y: 935 },
                    { f: ticks['Oat milk'] - 4, x: L.x + 40, y: listRow(0) },
                    { f: ticks['Chicken thighs'] - 4, x: L.x + 40, y: listRow(3) },
                    { f: ticks.Lemons - 4, x: L.x + 40, y: listRow(5) },
                    { f: 170, x: L.x + 300, y: 900 },
                ]}
            />
            <CircleWipe start={164} len={16} colour={dark.bg} x={L.x + 300} y={900} />
        </AbsoluteFill>
    );
};
