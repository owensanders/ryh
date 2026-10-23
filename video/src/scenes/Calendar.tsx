import React from 'react';
import { AbsoluteFill, Easing, useCurrentFrame } from 'remotion';
import { prog, spr, springs } from '../anim';
import { Avatar, Card, CircleWipe, Eyebrow, Headline, Sfx, Sub, WarmBg } from '../components/ui';
import { brand, dark, family, fonts, light, people } from '../theme';

type Ev = { day: number; title: string; colour: string; time?: string };

const events: Ev[] = [
    { day: 5, time: '09:30', title: 'Dentist — Noah', colour: brand.sky },
    { day: 5, time: '16:00', title: 'Swim club', colour: brand.sun },
    { day: 5, time: '19:00', title: 'Council tax £186', colour: brand.coral },
    { day: 7, time: '18:00', title: "Parents' evening", colour: brand.lilac },
    { day: 9, title: "🎂 Mia's birthday", colour: brand.mint },
    { day: 12, title: 'Bin day', colour: brand.mint },
    { day: 14, time: '16:00', title: 'Swim club', colour: brand.sun },
    { day: 16, time: '13:00', title: "BBQ at Gran's", colour: brand.coral },
    { day: 20, time: '08:30', title: 'MOT — Golf', colour: brand.sky },
    { day: 22, time: '19:30', title: 'Date night', colour: brand.lilac },
    { day: 26, title: 'Back to school', colour: brand.sun },
    { day: 29, title: 'Energy bill', colour: brand.coral },
];

// August 2025 starts on a Friday; the grid starts Monday 28 July.
const cells = new Array(35).fill(0).map((_, i) => {
    const d = i - 3;
    return d < 1 ? { n: 28 + i, out: true } : { n: d, out: false };
});

const C = { x: 100, y: 330, w: 1720, h: 700, pad: 26, head: 74, dow: 38 };
const cellW = (C.w - C.pad * 2) / 7;
const cellH = (C.h - C.pad * 2 - C.head - C.dow) / 5;
const POPOVER = 112;

// 180 frames, in the app's dark theme.
export const Calendar: React.FC = () => {
    const f = useCurrentFrame();
    const exit = prog(f, 160, 176, Easing.in(Easing.cubic));
    const cardIn = spr(f, 0, { damping: 20, stiffness: 110 });
    const pop = spr(f, POPOVER, springs.pop);

    const evAt = (i: number) => 34 + i * 5;

    // Where the highlighted "Swim club" on the 5th sits.
    const fifthIndex = cells.findIndex((c) => !c.out && c.n === 5);
    const col = fifthIndex % 7,
        row = Math.floor(fifthIndex / 7);
    const anchorX = C.x + C.pad + col * cellW;
    const anchorY = C.y + C.pad + C.head + C.dow + row * cellH;

    return (
        <AbsoluteFill style={{ fontFamily: fonts.sans }}>
            <WarmBg palette={dark} />
            <Sfx at={0} name="swish" volume={0.4} />
            {events.map((_, i) => (i % 2 === 0 ? <Sfx key={i} at={evAt(i)} name="pop" volume={0.35} rate={0.9 + i * 0.05} /> : null))}
            <Sfx at={POPOVER - 4} name="swish" volume={0.35} />
            <Sfx at={POPOVER} name="pop" volume={0.6} rate={0.8} />
            <Sfx at={160} name="whoosh" volume={0.5} />

            <AbsoluteFill style={{ transform: `scale(${1 - exit * 0.1})`, opacity: 1 - exit * 0.5 }}>
                <div style={{ position: 'absolute', left: 100, top: 70 }}>
                    <Eyebrow icon="📅" label="Shared calendar" delay={4} dark />
                    <Headline
                        segments={['One calendar.', { accent: 'Everyone', colour: brand.sky }, 'on it.']}
                        delay={10}
                        size={104}
                        colour={dark.ink}
                        style={{ marginTop: 26 }}
                    />
                </div>
                <div style={{ position: 'absolute', left: 100, top: 262, width: 1720 }}>
                    <Sub delay={30} dark size={27}>
                        Appointments, club runs and bill dates for the whole house, with who's going on each. Teens can add their own.
                    </Sub>
                </div>

                <div
                    style={{
                        position: 'absolute',
                        left: C.x,
                        top: C.y,
                        transform: `translateY(${(1 - cardIn) * 600}px) rotateX(${(1 - cardIn) * 30}deg)`,
                        perspective: 1800,
                    }}
                >
                    <Card palette={dark} style={{ width: C.w, height: C.h, padding: C.pad }}>
                        <div style={{ height: C.head, display: 'flex', alignItems: 'flex-start', gap: 14 }}>
                            <span style={{ fontSize: 34, fontWeight: 900, letterSpacing: '-0.03em' }}>August</span>
                            <span style={{ fontSize: 16, color: dark.ink3, marginTop: 14 }}>12 events</span>
                            <div style={{ marginLeft: 'auto', display: 'flex', gap: 8 }}>
                                {people.map((p, i) => (
                                    <div
                                        key={p.initials}
                                        style={{
                                            height: 44,
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 9,
                                            padding: '0 14px 0 5px',
                                            borderRadius: 13,
                                            background: dark.soft,
                                            fontWeight: 700,
                                            fontSize: 16,
                                            transform: `scale(${spr(f, 14 + i * 3, springs.pop)})`,
                                        }}
                                    >
                                        <Avatar person={p} size={34} radius={10} />
                                        {p.name}
                                    </div>
                                ))}
                                <div
                                    style={{
                                        height: 44,
                                        padding: '0 18px',
                                        borderRadius: 13,
                                        background: brand.coral,
                                        color: 'white',
                                        fontWeight: 800,
                                        fontSize: 16,
                                        display: 'grid',
                                        placeItems: 'center',
                                    }}
                                >
                                    + New event
                                </div>
                            </div>
                        </div>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', height: C.dow }}>
                            {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((d) => (
                                <span
                                    key={d}
                                    style={{
                                        fontSize: 14,
                                        fontWeight: 800,
                                        letterSpacing: '0.08em',
                                        textTransform: 'uppercase',
                                        color: dark.ink3,
                                        paddingLeft: 10,
                                    }}
                                >
                                    {d}
                                </span>
                            ))}
                        </div>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', gridAutoRows: cellH }}>
                            {cells.map((c, i) => {
                                const p = spr(f, 6 + ((i % 7) + Math.floor(i / 7)) * 2, springs.snappy);
                                const today = !c.out && c.n === 5;
                                const dayEvents = c.out ? [] : events.map((e, k) => ({ ...e, k })).filter((e) => e.day === c.n);
                                return (
                                    <div
                                        key={i}
                                        style={{
                                            borderTop: `1.5px solid ${dark.line}`,
                                            borderLeft: i % 7 ? `1.5px solid ${dark.line}` : undefined,
                                            padding: '8px 8px',
                                            opacity: p * (c.out ? 0.35 : 1),
                                            transform: `scale(${0.7 + p * 0.3})`,
                                            display: 'flex',
                                            flexDirection: 'column',
                                            gap: 4,
                                            overflow: 'hidden',
                                        }}
                                    >
                                        <span
                                            style={{
                                                alignSelf: 'flex-start',
                                                minWidth: 30,
                                                height: 30,
                                                borderRadius: 10,
                                                display: 'grid',
                                                placeItems: 'center',
                                                fontSize: 16,
                                                fontWeight: 800,
                                                background: today ? brand.coral : 'transparent',
                                                color: today ? 'white' : dark.ink2,
                                            }}
                                        >
                                            {c.n}
                                        </span>
                                        {dayEvents.map((e) => {
                                            const ep = spr(f, evAt(e.k), springs.pop);
                                            return (
                                                <div
                                                    key={e.k}
                                                    style={{
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: 6,
                                                        height: 22,
                                                        padding: '0 6px',
                                                        borderRadius: 7,
                                                        background: `${e.colour}26`,
                                                        fontSize: 13,
                                                        fontWeight: 700,
                                                        whiteSpace: 'nowrap',
                                                        transform: `scale(${ep})`,
                                                        transformOrigin: 'left center',
                                                        opacity: ep,
                                                    }}
                                                >
                                                    <span style={{ width: 3, height: 14, borderRadius: 2, background: e.colour, flex: 'none' }} />
                                                    {e.time && (
                                                        <span style={{ fontFamily: fonts.mono, fontSize: 11, color: dark.ink2 }}>{e.time}</span>
                                                    )}
                                                    <span style={{ overflow: 'hidden', textOverflow: 'ellipsis' }}>{e.title}</span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                );
                            })}
                        </div>
                    </Card>
                </div>

                {/* Event popover */}
                {f >= POPOVER && (
                    <div
                        style={{
                            position: 'absolute',
                            left: anchorX + cellW - 10,
                            top: anchorY - 120,
                            transform: `scale(${pop})`,
                            transformOrigin: 'left center',
                            opacity: Math.min(1, pop * 2),
                        }}
                    >
                        <Card
                            palette={light}
                            style={{ width: 430, padding: 26, fontFamily: fonts.sans, boxShadow: '0 40px 80px -20px rgba(0,0,0,0.7)' }}
                        >
                            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                <span style={{ width: 6, height: 44, borderRadius: 3, background: brand.sun }} />
                                <div>
                                    <div style={{ fontSize: 26, fontWeight: 900, letterSpacing: '-0.02em' }}>Swim club pickup</div>
                                    <div style={{ fontSize: 16, color: light.ink2 }}>Tue 5 Aug · 16:00 – 17:00</div>
                                </div>
                            </div>
                            <div style={{ marginTop: 16, fontSize: 17, color: light.ink2 }}>📍 Leisure centre, lane 3</div>
                            <div style={{ marginTop: 16, display: 'flex', alignItems: 'center', gap: 10 }}>
                                {[family.james, family.mia].map((p, i) => (
                                    <Avatar
                                        key={p.initials}
                                        person={p}
                                        size={40}
                                        radius={12}
                                        style={{ transform: `scale(${spr(f, POPOVER + 8 + i * 4, springs.bouncy)})` }}
                                    />
                                ))}
                                <span style={{ fontSize: 16, fontWeight: 700 }}>James & Mia going</span>
                            </div>
                        </Card>
                    </div>
                )}
            </AbsoluteFill>
            <CircleWipe start={162} len={16} colour={light.bg} x={anchorX + 300} y={anchorY} />
        </AbsoluteFill>
    );
};
