import React from 'react';
import { AbsoluteFill, Easing, useCurrentFrame } from 'remotion';
import { prog, range, spr, springs } from '../anim';
import { AppWindow } from '../components/AppWindow';
import { Avatar, Card, Chip, Dial, Headline, Sfx, Tick, WarmBg } from '../components/ui';
import { brand, family, fonts, heroGradient, light, people } from '../theme';

const list = [
    { name: 'Chicken thighs', qty: '1kg', tick: 112 },
    { name: 'New potatoes', qty: '750g', tick: 126 },
    { name: 'Lemons', qty: 'x3', tick: 140 },
    { name: 'Oat milk', qty: 'x2', tick: 0 },
    { name: 'Sourdough', qty: '1', tick: 0 },
];

const agenda = [
    { time: '09:30', title: 'Dentist — Noah', colour: brand.sky },
    { time: '16:00', title: 'Swim club pickup', colour: brand.sun },
    { time: '19:00', title: 'Council tax £186', colour: brand.coral },
];

const statuses = ['Planning dinner', 'Cooking tonight', 'Swim club 16:00', '2 chores left'];

// 240 frames: the app window flies in and the dashboard assembles itself.
export const Dashboard: React.FC = () => {
    const f = useCurrentFrame();

    const enter = spr(f, 0, { damping: 20, stiffness: 90, mass: 1 });
    const exit = prog(f, 222, 240, Easing.in(Easing.cubic));
    const push = range(f, [30, 220], [1, 1.07], Easing.inOut(Easing.sin));

    const rise = (d: number) => {
        const p = spr(f, d, springs.snappy);
        return { opacity: Math.min(1, p * 1.6), transform: `translateY(${(1 - p) * 70}px) scale(${0.94 + p * 0.06})` };
    };

    const streak = Math.round(range(f, [22, 52], [0, 12], Easing.out(Easing.cubic)));
    const greeting = 'Good morning, Sarah';
    const typed = greeting.slice(0, Math.floor(range(f, [22, 46], [0, greeting.length])));
    const ticked = list.filter((i) => i.tick && f >= i.tick + 4).length;
    const dial = range(f, [72, 130], [0, 50], Easing.out(Easing.cubic));

    const caption = spr(f, 150, springs.snappy);

    return (
        <AbsoluteFill>
            <WarmBg />
            <Sfx at={0} name="whoosh" volume={0.5} />
            <Sfx at={48} name="swish" volume={0.4} />
            <Sfx at={60} name="swish" volume={0.35} rate={1.2} />
            {list.filter((i) => i.tick).map((i, k) => (
                <Sfx key={i.name} at={i.tick} name="tick" volume={0.7} rate={1 + k * 0.1} />
            ))}
            <Sfx at={150} name="pop" volume={0.5} />
            <Sfx at={220} name="whoosh" volume={0.5} />

            <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center', perspective: 2200 }}>
                <div
                    style={{
                        transform: `translateY(${(1 - enter) * 500 + exit * -80}px) translateX(${exit * -1400}px) rotateX(${(1 - enter) * 38}deg) rotateY(${exit * 35 + Math.sin(f / 60) * 1.5}deg) scale(${(0.8 + enter * 0.2) * push * 0.94})`,
                        opacity: Math.min(1, enter * 2),
                    }}
                >
                    <AppWindow width={1720} height={960} active="Dashboard" title="Dashboard" subtitle="Tuesday 5 August" navDelay={10}>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 18, height: '100%' }}>
                            {/* Hero */}
                            <div
                                style={{
                                    ...rise(16),
                                    position: 'relative',
                                    overflow: 'hidden',
                                    borderRadius: 26,
                                    background: heroGradient,
                                    color: brand.onhero,
                                    padding: '26px 30px',
                                    flex: 'none',
                                }}
                            >
                                <div style={{ position: 'absolute', top: -120, right: 200, width: 360, height: 360, borderRadius: '50%', background: brand.coral, opacity: 0.25, filter: 'blur(60px)' }} />
                                <div
                                    style={{
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        gap: 8,
                                        height: 34,
                                        padding: '0 14px',
                                        borderRadius: 11,
                                        border: `1.5px solid ${brand.heroline}`,
                                        background: brand.herofilm,
                                        fontSize: 15,
                                        fontWeight: 800,
                                        letterSpacing: '0.05em',
                                        textTransform: 'uppercase',
                                    }}
                                >
                                    <span style={{ transform: `scale(${1 + Math.sin(f / 5) * 0.12})`, display: 'inline-block' }}>🔥</span> {streak} day streak
                                </div>
                                <div style={{ position: 'relative', marginTop: 14, fontSize: 52, fontWeight: 900, letterSpacing: '-0.035em', lineHeight: 1.05 }}>
                                    {typed}
                                    <span style={{ opacity: f < 60 && Math.floor(f / 8) % 2 === 0 ? 1 : 0, color: brand.coral }}>|</span>
                                </div>
                                <div style={{ marginTop: 6, fontSize: 18, color: brand.onhero2, opacity: prog(f, 38, 52) }}>
                                    Tuesday 5 August · 3 events today · dinner sorted
                                </div>
                                <div style={{ display: 'flex', gap: 12, marginTop: 20 }}>
                                    {people.map((p, i) => {
                                        const s = spr(f, 40 + i * 5, springs.pop);
                                        return (
                                            <div
                                                key={p.initials}
                                                style={{
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    gap: 11,
                                                    height: 54,
                                                    padding: '0 18px 0 6px',
                                                    borderRadius: 16,
                                                    border: `1.5px solid ${brand.heroline}`,
                                                    background: brand.herofilm,
                                                    transform: `scale(${s})`,
                                                    opacity: s,
                                                }}
                                            >
                                                <Avatar person={p} size={42} radius={12} />
                                                <div style={{ lineHeight: 1.2 }}>
                                                    <div style={{ fontWeight: 800, fontSize: 16 }}>{p.name}</div>
                                                    <div style={{ fontSize: 13, color: brand.onhero2 }}>{statuses[i]}</div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Dinner + list */}
                            <div style={{ display: 'grid', gridTemplateColumns: '1.45fr 1fr', gap: 18, flex: 'none' }}>
                                <Card style={{ ...rise(48), padding: 24 }}>
                                    <div style={{ display: 'flex', alignItems: 'baseline', gap: 12, marginBottom: 16 }}>
                                        <span style={{ fontSize: 19, fontWeight: 800 }}>Tonight's dinner</span>
                                        <span style={{ fontSize: 14, color: light.ink3 }}>Meal plan</span>
                                        <span style={{ marginLeft: 'auto', fontSize: 16, fontWeight: 700, color: brand.coral }}>Meal planner →</span>
                                    </div>
                                    <div style={{ display: 'flex', gap: 20 }}>
                                        <div
                                            style={{
                                                width: 190,
                                                height: 190,
                                                flex: 'none',
                                                borderRadius: 22,
                                                background: brand.coral,
                                                padding: 18,
                                                display: 'flex',
                                                alignItems: 'flex-end',
                                                color: 'white',
                                                fontWeight: 900,
                                                fontSize: 29,
                                                lineHeight: 1.05,
                                                letterSpacing: '-0.03em',
                                                transform: `rotate(${(1 - spr(f, 56, springs.bouncy)) * -8}deg)`,
                                            }}
                                        >
                                            Lemon chicken traybake
                                        </div>
                                        <div style={{ display: 'flex', flexDirection: 'column', flex: 1 }}>
                                            <div style={{ display: 'flex', gap: 8 }}>
                                                {['Healthy', '45 min', 'Easy'].map((c, i) => (
                                                    <Chip key={c} style={{ transform: `scale(${spr(f, 60 + i * 3, springs.pop)})` }}>
                                                        {c}
                                                    </Chip>
                                                ))}
                                            </div>
                                            <div style={{ marginTop: 14, fontSize: 18, lineHeight: 1.5, color: light.ink2 }}>
                                                One tray, crispy skin, zero washing up. On the plan since Sunday.
                                            </div>
                                            <div style={{ marginTop: 'auto', display: 'flex', alignItems: 'center', gap: 10 }}>
                                                <Avatar person={family.james} size={36} radius={11} />
                                                <span style={{ fontSize: 17, color: light.ink2 }}>James is cooking</span>
                                                <span
                                                    style={{
                                                        marginLeft: 'auto',
                                                        height: 48,
                                                        padding: '0 22px',
                                                        borderRadius: 15,
                                                        background: brand.coral,
                                                        color: 'white',
                                                        fontWeight: 800,
                                                        fontSize: 16,
                                                        display: 'grid',
                                                        placeItems: 'center',
                                                    }}
                                                >
                                                    View recipe
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </Card>

                                <Card style={{ ...rise(58), padding: 24 }}>
                                    <div style={{ display: 'flex', alignItems: 'baseline', marginBottom: 8 }}>
                                        <span style={{ fontSize: 19, fontWeight: 800 }}>Tesco list</span>
                                        <span style={{ marginLeft: 'auto', fontFamily: fonts.mono, fontSize: 15, color: light.ink3 }}>{7 - ticked} left</span>
                                    </div>
                                    {list.map((item, i) => {
                                        const d = item.tick ? prog(f, item.tick, item.tick + 8) : 0;
                                        const inP = spr(f, 64 + i * 3, springs.snappy);
                                        return (
                                            <div
                                                key={item.name}
                                                style={{
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    gap: 14,
                                                    height: 42,
                                                    opacity: inP,
                                                    transform: `translateX(${(1 - inP) * 40}px)`,
                                                }}
                                            >
                                                <Tick done={d} />
                                                <span style={{ flex: 1, fontSize: 18, color: d > 0.5 ? light.ink3 : light.ink }}>
                                                    <span style={{ position: 'relative' }}>
                                                    {item.name}
                                                    <span
                                                        style={{
                                                            position: 'absolute',
                                                            left: 0,
                                                            top: '52%',
                                                            height: 2,
                                                            width: `${d * 100}%`,
                                                            background: light.ink3,
                                                        }}
                                                    />
                                                    </span>
                                                </span>
                                                <span style={{ fontFamily: fonts.mono, fontSize: 14, color: light.ink3 }}>{item.qty}</span>
                                            </div>
                                        );
                                    })}
                                </Card>
                            </div>

                            {/* Chores + agenda */}
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1.45fr', gap: 18, flex: 1, minHeight: 0 }}>
                                <Card style={{ ...rise(68), padding: 24, display: 'flex', alignItems: 'center', gap: 22 }}>
                                    <Dial pct={dial} size={112} colour={brand.mint} track={light.soft} inner={light.card} thickness={12}>
                                        <span style={{ fontSize: 26, fontWeight: 900 }}>{Math.round(dial)}%</span>
                                    </Dial>
                                    <div>
                                        <div style={{ fontSize: 22, fontWeight: 900 }}>Today's chores</div>
                                        <div style={{ fontSize: 17, color: light.ink3, marginTop: 4 }}>3 of 6 done · Noah's on the bins</div>
                                    </div>
                                </Card>
                                <Card style={{ ...rise(76), padding: '20px 24px' }}>
                                    <div style={{ fontSize: 15, fontWeight: 800, letterSpacing: '0.08em', textTransform: 'uppercase', color: light.ink3, marginBottom: 10 }}>
                                        Coming up
                                    </div>
                                    <div style={{ display: 'flex', gap: 10 }}>
                                        {agenda.map((a, i) => {
                                            const p = spr(f, 84 + i * 5, springs.pop);
                                            return (
                                                <div
                                                    key={a.title}
                                                    style={{
                                                        flex: 1,
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: 12,
                                                        padding: '14px 14px',
                                                        borderRadius: 15,
                                                        background: light.sunk,
                                                        transform: `scale(${p})`,
                                                        opacity: p,
                                                    }}
                                                >
                                                    <span style={{ width: 4, height: 40, borderRadius: 3, background: a.colour }} />
                                                    <div>
                                                        <div style={{ fontFamily: fonts.mono, fontSize: 14, color: light.ink2 }}>{a.time}</div>
                                                        <div style={{ fontSize: 16, fontWeight: 700 }}>{a.title}</div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </Card>
                            </div>
                        </div>
                    </AppWindow>
                </div>
            </AbsoluteFill>

            {/* Floating caption */}
            <div
                style={{
                    position: 'absolute',
                    left: 90,
                    bottom: 70,
                    padding: '26px 40px 30px',
                    borderRadius: 32,
                    background: heroGradient,
                    boxShadow: '0 40px 80px -30px rgba(14,26,43,0.7)',
                    transform: `translateY(${(1 - caption) * 260 + exit * 300}px) rotate(${(1 - caption) * -4}deg)`,
                }}
            >
                <Headline segments={['Your whole day,', { accent: 'at a glance.' }]} delay={152} size={74} colour={brand.onhero} />
            </div>
        </AbsoluteFill>
    );
};
