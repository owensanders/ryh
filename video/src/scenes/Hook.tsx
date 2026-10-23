import React from 'react';
import { AbsoluteFill, Easing, useCurrentFrame } from 'remotion';
import { prog, range, spr, springs } from '../anim';
import { CircleWipe, Headline, HeroBg, Sfx } from '../components/ui';
import { brand, fonts, light, tints } from '../theme';

const questions = [
    { icon: '🍽', text: "What's for dinner?", tint: tints[0], at: 22 },
    { icon: '📅', text: "What's on today?", tint: tints[2], at: 52 },
    { icon: '🛒', text: 'What do we need?', tint: tints[1], at: 82 },
];

// 180 frames: the landing page's "three questions" hook as kinetic type.
export const Hook: React.FC = () => {
    const f = useCurrentFrame();
    const answer = 124;
    const collapse = prog(f, answer - 8, answer + 8, Easing.inOut(Easing.cubic));

    return (
        <AbsoluteFill>
            <HeroBg />
            {questions.map((q) => (
                <Sfx key={q.at} at={q.at} name="pop" volume={0.6} rate={1 + questions.indexOf(q) * 0.12} />
            ))}
            <Sfx at={answer - 60} name="riser" volume={0.4} />
            <Sfx at={answer} name="impact" volume={0.45} />
            <Sfx at={164} name="whoosh" volume={0.5} />

            <AbsoluteFill style={{ padding: '0 160px', justifyContent: 'center' }}>
                <div
                    style={{
                        fontFamily: fonts.sans,
                        fontSize: 34,
                        fontWeight: 700,
                        letterSpacing: '0.12em',
                        textTransform: 'uppercase',
                        color: brand.onhero2,
                        opacity: prog(f, 0, 14) * (1 - collapse),
                        transform: `translateY(${(1 - prog(f, 0, 14)) * 20}px)`,
                        marginBottom: 36,
                    }}
                >
                    Every morning starts with
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: 18 }}>
                    {questions.map((q, i) => {
                        const p = spr(f, q.at, springs.snappy);
                        const next = questions[i + 1];
                        const dim = next ? prog(f, next.at, next.at + 10) : 0;
                        const out = prog(f, answer - 10 + i * 2, answer + 4 + i * 2, Easing.in(Easing.cubic));
                        return (
                            <div
                                key={q.text}
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 34,
                                    opacity: Math.min(1, p * 2) * (1 - dim * 0.55) * (1 - out),
                                    transform: `translateY(${(1 - p) * 120 - out * 80}px) scale(${1 - dim * 0.05})`,
                                    transformOrigin: 'left center',
                                    filter: `blur(${out * 12}px)`,
                                }}
                            >
                                <div
                                    style={{
                                        width: 128,
                                        height: 128,
                                        borderRadius: 38,
                                        background: q.tint,
                                        display: 'grid',
                                        placeItems: 'center',
                                        fontSize: 68,
                                        transform: `scale(${spr(f, q.at + 4, springs.bouncy)}) rotate(${(1 - spr(f, q.at + 4, springs.bouncy)) * -40}deg)`,
                                    }}
                                >
                                    {q.icon}
                                </div>
                                <div
                                    style={{ fontFamily: fonts.sans, fontWeight: 900, fontSize: 132, letterSpacing: '-0.045em', color: brand.onhero }}
                                >
                                    {q.text}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </AbsoluteFill>

            {f >= answer - 2 && (
                <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center', transform: `scale(${range(f, [answer, 170], [1.08, 1])})` }}>
                    <Headline
                        segments={['RYH answers', { accent: 'all three.' }]}
                        delay={answer}
                        size={150}
                        stagger={4}
                        colour={brand.onhero}
                        align="center"
                        style={{ maxWidth: 1400 }}
                    />
                    <div
                        style={{
                            position: 'absolute',
                            top: 760,
                            fontFamily: fonts.sans,
                            fontWeight: 600,
                            fontSize: 36,
                            color: brand.onhero2,
                            opacity: prog(f, answer + 16, answer + 32),
                        }}
                    >
                        …and quietly keeps hold of everything else.
                    </div>
                </AbsoluteFill>
            )}

            <CircleWipe start={164} len={16} colour={light.bg} x={1580} y={900} />
        </AbsoluteFill>
    );
};
