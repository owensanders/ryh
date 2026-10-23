import React from 'react';
import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { prog, spr, springs } from '../anim';
import { Avatar, CircleWipe, Headline, HeroBg, Sfx } from '../components/ui';
import { brand, family, fonts, light, Person } from '../theme';

type Toast = { at: number; who: Person; title: string; body: string; x: number; y: number; side: 1 | -1 };

// Each title is the exact text the app sends (app/Notifications/*); the line
// underneath is its area, as on Settings → Notifications.
const toasts: Toast[] = [
    { at: 8, who: family.sarah, title: 'You have 2 outstanding chores', body: 'Chores · reminder from Sarah', x: 110, y: 380, side: -1 },
    { at: 18, who: family.mia, title: 'Mia Parker completed “Feed the cat”', body: 'Chores', x: 1290, y: 360, side: 1 },
    { at: 28, who: family.sarah, title: 'Sarah Parker assigned you to cook “Lemon chicken traybake”', body: 'Meals', x: 150, y: 580, side: -1 },
    { at: 38, who: family.james, title: 'James Parker updated “Swim club pickup”', body: 'Calendar', x: 1250, y: 560, side: 1 },
    { at: 48, who: family.sarah, title: 'Sarah Parker assigned you “Hoover upstairs”', body: 'Chores', x: 190, y: 780, side: -1 },
    { at: 58, who: family.james, title: 'James Parker added you to “Parents’ evening”', body: 'Calendar', x: 1210, y: 760, side: 1 },
];

// 120 frames.
export const Notifications: React.FC = () => {
    const f = useCurrentFrame();
    const bellIn = spr(f, 0, springs.bouncy);
    const unread = toasts.filter((t) => f >= t.at + 4).length;
    // The bell rings again each time a toast lands.
    const lastAt = toasts.filter((t) => f >= t.at).slice(-1)[0]?.at ?? 0;
    const ring = Math.max(0, 1 - (f - lastAt) / 16);

    return (
        <AbsoluteFill style={{ fontFamily: fonts.sans }}>
            <HeroBg />
            <Sfx at={2} name="chime" volume={0.55} />
            {toasts.map((t, i) => (
                <Sfx key={t.at} at={t.at} name="pop" volume={0.45} rate={1 + (i % 3) * 0.12} />
            ))}
            <Sfx at={104} name="whoosh" volume={0.5} />

            <div
                style={{ position: 'absolute', top: 80, left: 0, right: 0, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 18 }}
            >
                <Headline segments={['Everyone in the loop,', { accent: 'live.' }]} delay={4} size={100} colour={brand.onhero} align="center" />
                <div style={{ fontSize: 28, fontWeight: 500, color: brand.onhero2, opacity: prog(f, 20, 36) }}>
                    Assignments, edits and reminders land the moment they happen.
                </div>
            </div>

            {/* Bell */}
            <div style={{ position: 'absolute', left: 960 - 120, top: 560 - 60, width: 240, height: 240 }}>
                {[0, 1, 2].map((k) => {
                    const r = ((f + k * 14) % 42) / 42;
                    return (
                        <div
                            key={k}
                            style={{
                                position: 'absolute',
                                inset: 0,
                                borderRadius: 72,
                                border: `3px solid ${brand.coral}`,
                                transform: `scale(${1 + r * 1.2})`,
                                opacity: (1 - r) * 0.5 * bellIn,
                            }}
                        />
                    );
                })}
                <div
                    style={{
                        position: 'absolute',
                        inset: 0,
                        borderRadius: 72,
                        background: brand.coral,
                        display: 'grid',
                        placeItems: 'center',
                        fontSize: 130,
                        boxShadow: '0 40px 90px -20px rgba(255,79,64,0.8)',
                        transform: `scale(${bellIn}) rotate(${Math.sin(f * 1.3) * 18 * ring}deg)`,
                        transformOrigin: '50% 10%',
                    }}
                >
                    🔔
                </div>
                {unread > 0 && (
                    <div
                        style={{
                            position: 'absolute',
                            top: -18,
                            right: -18,
                            width: 76,
                            height: 76,
                            borderRadius: 38,
                            background: 'white',
                            color: brand.coral,
                            fontSize: 40,
                            fontWeight: 900,
                            display: 'grid',
                            placeItems: 'center',
                            transform: `scale(${1 + ring * 0.25})`,
                            boxShadow: '0 10px 30px -10px rgba(0,0,0,0.5)',
                        }}
                    >
                        {unread}
                    </div>
                )}
            </div>

            {/* Toasts */}
            <div style={{ position: 'absolute', inset: 0, perspective: 1600 }}>
                {toasts.map((t) => {
                    const p = spr(f, t.at, springs.snappy);
                    const float = Math.sin((f + t.at) / 18) * 6;
                    return (
                        <div
                            key={t.at}
                            style={{
                                position: 'absolute',
                                left: t.x,
                                top: t.y + float,
                                width: 520,
                                padding: '18px 22px',
                                borderRadius: 24,
                                background: light.card,
                                color: light.ink,
                                display: 'flex',
                                alignItems: 'center',
                                gap: 16,
                                boxShadow: '0 30px 60px -20px rgba(0,0,0,0.6)',
                                opacity: Math.min(1, p * 1.5),
                                transform: `translateX(${(1 - p) * 500 * t.side}px) rotateY(${(1 - p) * 60 * t.side}deg) scale(${0.8 + p * 0.2})`,
                            }}
                        >
                            <Avatar person={t.who} size={52} radius={16} />
                            <div style={{ flex: 1, minWidth: 0 }}>
                                <div style={{ fontSize: 19, fontWeight: 800, lineHeight: 1.25 }}>{t.title}</div>
                                <div style={{ fontSize: 17, color: light.ink2, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                    {t.body}
                                </div>
                            </div>
                            <span style={{ fontSize: 14, fontWeight: 700, color: brand.coral, alignSelf: 'flex-start' }}>now</span>
                        </div>
                    );
                })}
            </div>

            <CircleWipe start={104} len={16} colour={light.bg} x={960} y={620} />
        </AbsoluteFill>
    );
};
