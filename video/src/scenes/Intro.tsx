import React from 'react';
import { AbsoluteFill, Easing, useCurrentFrame } from 'remotion';
import { prog, range, spr, springs } from '../anim';
import { HeroBg, RoofMark, Sfx, Wordmark } from '../components/ui';
import { brand, fonts } from '../theme';

// 120 frames: roof slam, wordmark, tagline, zoom-through exit.
export const Intro: React.FC = () => {
    const f = useCurrentFrame();

    const mark = spr(f, 2, springs.bouncy);
    const draw = prog(f, 8, 34, Easing.inOut(Easing.cubic));
    const ring = prog(f, 4, 40);
    const slide = spr(f, 30, springs.smooth);
    const exit = prog(f, 104, 120, Easing.in(Easing.cubic));

    return (
        <AbsoluteFill>
            <HeroBg glow={0.6 + ring * 0.6} />
            <Sfx at={0} name="impact" volume={0.7} />
            <Sfx at={26} name="sparkle" volume={0.35} />
            <Sfx at={104} name="whoosh" volume={0.55} />

            <AbsoluteFill
                style={{
                    alignItems: 'center',
                    justifyContent: 'center',
                    transform: `scale(${1 + exit * 2.4})`,
                    opacity: 1 - exit,
                    filter: `blur(${exit * 18}px)`,
                }}
            >
                {/* Shockwave rings behind the mark */}
                {[0, 8].map((d) => {
                    const p = prog(f, 4 + d, 44 + d);
                    return (
                        <div
                            key={d}
                            style={{
                                position: 'absolute',
                                width: 200,
                                height: 200,
                                borderRadius: '50%',
                                border: `3px solid ${brand.coral}`,
                                transform: `translateX(${-slide * 247}px) scale(${1 + p * 5})`,
                                opacity: (1 - p) * 0.7,
                            }}
                        />
                    );
                })}

                <div style={{ display: 'flex', alignItems: 'center', gap: 48 }}>
                    <div
                        style={{
                            transform: `scale(${mark}) rotate(${(1 - mark) * -35}deg)`,
                            marginRight: (1 - slide) * -494,
                        }}
                    >
                        <RoofMark width={532} color={brand.onhero} draw={draw} />
                    </div>
                    <Wordmark height={190} color={brand.onhero} lines={[spr(f, 34, springs.snappy), spr(f, 39, springs.snappy)]} />
                </div>

                <div
                    style={{
                        position: 'absolute',
                        top: 700,
                        fontFamily: fonts.serif,
                        fontStyle: 'italic',
                        fontSize: 64,
                        color: brand.onhero2,
                        opacity: prog(f, 58, 80),
                        transform: `translateY(${range(f, [58, 80], [20, 0])}px)`,
                        filter: `blur(${range(f, [58, 80], [10, 0])}px)`,
                        letterSpacing: '0.01em',
                    }}
                >
                    The home, <span style={{ color: brand.coral }}>organised.</span>
                </div>
            </AbsoluteFill>
        </AbsoluteFill>
    );
};
