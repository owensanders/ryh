import React from 'react';
import { AbsoluteFill, Easing, random, useCurrentFrame } from 'remotion';
import { lerp, prog, range, spr, springs } from '../anim';
import { Avatar, Card, Cursor, Eyebrow, Headline, Sfx, Sub, WarmBg } from '../components/ui';
import { brand, family, fonts, light, Person, tints } from '../theme';

type Meal = { name: string; tag: string; tint: number; cook: Person };

const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

// Planner geometry, in scene pixels, so the cursor can hit real targets.
const PL = { x: 690, y: 250, w: 1150, h: 560, pad: 26, header: 84, gap: 12 };
const colW = (PL.w - PL.pad * 2 - PL.gap * 6) / 7;
const colX = (i: number) => PL.x + PL.pad + i * (colW + PL.gap);
const cardTop = PL.y + PL.pad + PL.header + 52;
const cardH = 330;

const DRAG = { grab: 72, drop: 104 };
const AI = { click: 138, fills: [150, 160, 170] };

const initial: (Meal | null)[] = [
    { name: 'Lemon chicken traybake', tag: 'Healthy', tint: 0, cook: family.james },
    { name: 'Halloumi wraps', tag: 'Quick', tint: 1, cook: family.sarah },
    { name: 'Salmon & greens', tag: 'Healthy', tint: 2, cook: family.sarah },
    { name: 'Veg lasagne', tag: 'Vegetarian', tint: 3, cook: family.james },
    null,
    null,
    null,
];
const aiMeals: Record<number, Meal> = {
    2: { name: 'Chickpea curry', tag: 'Vegetarian', tint: 4, cook: family.mia },
    5: { name: 'BBQ burgers', tag: 'Kid-friendly', tint: 0, cook: family.james },
    6: { name: 'Roast chicken', tag: 'Comfort food', tint: 1, cook: family.sarah },
};

const MealCard: React.FC<{ meal: Meal; style?: React.CSSProperties; glow?: number }> = ({ meal, style, glow = 0 }) => (
    <div
        style={{
            height: cardH,
            borderRadius: 22,
            background: tints[meal.tint],
            padding: 16,
            display: 'flex',
            flexDirection: 'column',
            color: light.ink,
            boxShadow: glow ? `0 0 0 ${3 * glow}px ${brand.lilac}, 0 0 ${40 * glow}px ${brand.lilac}` : undefined,
            ...style,
        }}
    >
        <span
            style={{
                alignSelf: 'flex-start',
                fontSize: 13,
                fontWeight: 800,
                padding: '4px 9px',
                borderRadius: 8,
                background: 'rgba(255,255,255,0.6)',
            }}
        >
            {meal.tag}
        </span>
        <div style={{ marginTop: 'auto', fontSize: 23, fontWeight: 900, lineHeight: 1.08, letterSpacing: '-0.02em' }}>{meal.name}</div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginTop: 14 }}>
            <Avatar person={meal.cook} size={30} radius={9} />
            <span style={{ fontSize: 14, fontWeight: 600, color: light.ink2 }}>{meal.cook.name} cooks</span>
        </div>
    </div>
);

// 240 frames.
export const MealPlanner: React.FC = () => {
    const f = useCurrentFrame();
    const exit = prog(f, 222, 240, Easing.in(Easing.cubic));

    const card = spr(f, 4, { damping: 18, stiffness: 100 });
    const lifted = f >= DRAG.grab && f < DRAG.drop + 6;
    const lift = range(f, [DRAG.grab, DRAG.grab + 6, DRAG.drop, DRAG.drop + 6], [0, 1, 1, 0]);
    const move = prog(f, DRAG.grab + 2, DRAG.drop, Easing.bezier(0.65, 0, 0.35, 1));
    const dragX = lerp(move, colX(2), colX(4));
    const dragY = cardTop - Math.sin(move * Math.PI) * 40;

    const shimmer = prog(f, AI.click + 2, AI.click + 30, Easing.inOut(Easing.cubic));
    const aiGlow = range(f, [AI.click, AI.click + 10, 200, 220], [0, 1, 1, 0]);

    const slotMeal = (i: number): { meal: Meal | null; appear: number } => {
        if (i === 4) return f >= DRAG.drop ? { meal: initial[2], appear: 1 } : { meal: null, appear: 1 };
        if (i === 2 && f >= DRAG.grab) {
            const k = AI.fills[0];
            return f >= k ? { meal: aiMeals[2], appear: spr(f, k, springs.bouncy) } : { meal: null, appear: 1 };
        }
        if (aiMeals[i] && i !== 2) {
            const k = AI.fills[i === 5 ? 1 : 2];
            return f >= k ? { meal: aiMeals[i], appear: spr(f, k, springs.bouncy) } : { meal: null, appear: 1 };
        }
        return { meal: initial[i], appear: 1 };
    };

    const btnX = PL.x + PL.w - PL.pad - 130;
    const btnY = PL.y + PL.pad + 30;

    return (
        <AbsoluteFill>
            <WarmBg />
            <Sfx at={4} name="swish" volume={0.4} />
            {days.map((_, i) => (
                <Sfx key={i} at={16 + i * 4} name="tick" volume={0.25} rate={0.8 + i * 0.07} />
            ))}
            <Sfx at={DRAG.grab} name="click" volume={0.6} />
            <Sfx at={DRAG.grab + 4} name="swish" volume={0.3} rate={0.8} />
            <Sfx at={DRAG.drop} name="pop" volume={0.65} />
            <Sfx at={AI.click} name="click" volume={0.6} />
            <Sfx at={AI.click + 2} name="sparkle" volume={0.5} />
            {AI.fills.map((k, i) => (
                <Sfx key={k} at={k} name="pop" volume={0.5} rate={1.1 + i * 0.12} />
            ))}
            <Sfx at={222} name="whoosh" volume={0.45} />

            <AbsoluteFill style={{ transform: `translateY(${exit * -140}px)`, opacity: 1 - exit }}>
                <div style={{ position: 'absolute', left: 100, top: 250, width: 540 }}>
                    <Eyebrow icon="🍽" label="Meal planner" delay={6} />
                    <Headline segments={['Dinner,', { accent: 'sorted' }, 'for the week.']} delay={12} size={96} style={{ marginTop: 30 }} />
                    <Sub delay={34} style={{ marginTop: 26 }}>
                        Assign who's cooking and drag meals between nights. Or let AI plan the week around your family.
                    </Sub>
                </div>

                <div
                    style={{
                        position: 'absolute',
                        left: PL.x,
                        top: PL.y,
                        transform: `translateX(${(1 - card) * 900}px) rotate(${(1 - card) * 6}deg)`,
                        transformOrigin: 'left center',
                    }}
                >
                    <Card style={{ width: PL.w, height: PL.h, padding: PL.pad, position: 'relative', overflow: 'hidden' }}>
                        <div style={{ height: PL.header, display: 'flex', alignItems: 'flex-start' }}>
                            <div>
                                <div style={{ fontFamily: fonts.sans, fontSize: 30, fontWeight: 900, letterSpacing: '-0.03em' }}>This week</div>
                                <div style={{ fontFamily: fonts.sans, fontSize: 16, color: light.ink3 }}>4 – 10 August · 7 dinners</div>
                            </div>
                            <div
                                style={{
                                    marginLeft: 'auto',
                                    height: 56,
                                    padding: '0 24px',
                                    borderRadius: 17,
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 10,
                                    fontFamily: fonts.sans,
                                    fontWeight: 800,
                                    fontSize: 18,
                                    color: 'white',
                                    background: `linear-gradient(120deg, ${brand.lilac}, ${brand.coral})`,
                                    boxShadow: `0 ${10 + aiGlow * 10}px ${30 + aiGlow * 30}px -12px ${brand.lilac}`,
                                    transform: `scale(${1 - range(f, [AI.click - 2, AI.click, AI.click + 6], [0, 0.08, 0]) + Math.sin(f / 6) * 0.01})`,
                                }}
                            >
                                ✨ Plan with AI
                            </div>
                        </div>

                        <div style={{ display: 'flex', gap: PL.gap }}>
                            {days.map((d, i) => {
                                const p = spr(f, 14 + i * 4, springs.snappy);
                                const { meal, appear } = slotMeal(i);
                                const isDropTarget = i === 4 && lifted;
                                return (
                                    <div key={d} style={{ width: colW, opacity: p, transform: `translateY(${(1 - p) * 80}px)` }}>
                                        <div style={{ height: 52, fontFamily: fonts.sans }}>
                                            <div
                                                style={{
                                                    fontSize: 14,
                                                    fontWeight: 800,
                                                    letterSpacing: '0.08em',
                                                    textTransform: 'uppercase',
                                                    color: light.ink3,
                                                }}
                                            >
                                                {d}
                                            </div>
                                            <div style={{ fontSize: 22, fontWeight: 900 }}>{4 + i}</div>
                                        </div>
                                        {meal && !(i === 2 && lifted && f < AI.fills[0]) ? (
                                            <div style={{ transform: `scale(${appear})`, opacity: Math.min(1, appear * 2) }}>
                                                <MealCard meal={meal} glow={aiMeals[i] && f >= AI.fills[0] ? aiGlow : 0} />
                                            </div>
                                        ) : (
                                            <div
                                                style={{
                                                    height: cardH,
                                                    borderRadius: 22,
                                                    border: `2.5px dashed ${isDropTarget ? brand.coral : light.line}`,
                                                    background: isDropTarget ? 'rgba(255,79,64,0.06)' : light.panel,
                                                    display: 'grid',
                                                    placeItems: 'center',
                                                    color: light.ink3,
                                                    fontFamily: fonts.sans,
                                                    fontWeight: 700,
                                                    fontSize: 16,
                                                }}
                                            >
                                                + Add dinner
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        {/* AI shimmer sweep */}
                        {shimmer > 0 && shimmer < 1 && (
                            <div
                                style={{
                                    position: 'absolute',
                                    top: 0,
                                    bottom: 0,
                                    left: -400 + shimmer * (PL.w + 800),
                                    width: 300,
                                    background:
                                        'linear-gradient(90deg, transparent, rgba(139,124,246,0.35), rgba(255,255,255,0.7), rgba(255,79,64,0.25), transparent)',
                                    transform: 'skewX(-18deg)',
                                }}
                            />
                        )}
                    </Card>
                </div>

                {/* The card being dragged, lifted above the grid */}
                {lifted && (
                    <div
                        style={{
                            position: 'absolute',
                            left: dragX,
                            top: dragY,
                            width: colW,
                            transform: `rotate(${lift * 4 * Math.cos(move * Math.PI)}deg) scale(${1 + lift * 0.08})`,
                            filter: `drop-shadow(0 ${30 * lift}px ${30 * lift}px rgba(14,26,43,${0.35 * lift}))`,
                            zIndex: 5,
                        }}
                    >
                        <MealCard meal={initial[2]!} />
                    </div>
                )}

                {/* Sparkles when the AI fills a night */}
                {AI.fills.map((k, n) => {
                    const i = [2, 5, 6][n];
                    const t = f - k;
                    if (t < 0 || t > 30) return null;
                    return new Array(10).fill(0).map((_, s) => {
                        const a = random(`s${n}${s}`) * Math.PI * 2;
                        const d = t * (4 + random(`d${n}${s}`) * 5);
                        return (
                            <div
                                key={`${n}-${s}`}
                                style={{
                                    position: 'absolute',
                                    left: colX(i) + colW / 2 + Math.cos(a) * d,
                                    top: cardTop + cardH / 2 + Math.sin(a) * d,
                                    fontSize: 20 + random(`z${n}${s}`) * 16,
                                    color: [brand.lilac, brand.coral, brand.sun][s % 3],
                                    opacity: 1 - t / 30,
                                    transform: `rotate(${t * 8}deg)`,
                                }}
                            >
                                ✦
                            </div>
                        );
                    });
                })}

                <Cursor
                    show={[46, 176]}
                    clicks={[DRAG.grab, AI.click]}
                    keys={[
                        { f: 46, x: 1500, y: 1000 },
                        { f: 66, x: colX(2) + colW / 2, y: cardTop + cardH / 2 },
                        { f: DRAG.grab + 2, x: colX(2) + colW / 2, y: cardTop + cardH / 2 },
                        { f: DRAG.drop, x: colX(4) + colW / 2, y: cardTop + cardH / 2 },
                        { f: 116, x: colX(4) + colW / 2 + 20, y: cardTop + cardH / 2 + 10 },
                        { f: AI.click - 4, x: btnX, y: btnY },
                        { f: 176, x: btnX + 60, y: btnY + 160 },
                    ]}
                />
            </AbsoluteFill>
        </AbsoluteFill>
    );
};
