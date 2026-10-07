import React from 'react';
import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { prog, range, spr, springs } from '../anim';
import { Avatar, CircleWipe, Eyebrow, Headline, LogoMark, Sfx, Sub, Tick, WarmBg } from '../components/ui';
import { brand, family, fonts, heroGradient, light, Person } from '../theme';

// Beats, in frames.
const ADD = 26; // HouseHub lands on the home screen
const BANNER = 80; // a push notification arrives
const TAP = 126; // it's tapped…
const OPEN = 130; // …and the app opens on Chores
const TICK = 178; // the chore gets done
const WIPE = 222;

const PHONE = { x: 1180, y: 124, w: 420, h: 868 };
const SCREEN_INSET = 14;

// The notification is the app's real wording (ChoreAssignedNotification),
// titled with the household's name as every HouseHub push is.
const PUSH = { household: 'The Parkers', body: 'Sarah Parker assigned you “Take the bins out”' };

const homeIcons = ['#6bc5f8', '#3ddc97', '#ffc94d', '#8b7cf6', '#f2eee7', '#ff8a7a', null, '#a9e9cc', '#bcc9fa', '#ffdc97', '#cfc2fa', '#ffb3a8'];

const chores: { name: string; who: Person; done?: boolean }[] = [
    { name: 'Take the bins out', who: family.james },
    { name: 'Hoover upstairs', who: family.james },
    { name: 'Feed the cat', who: family.mia, done: true },
    { name: 'Water the plants', who: family.noah },
    { name: 'Empty the dishwasher', who: family.mia, done: true },
];

const tabs = [
    { icon: '🏠', label: 'Home' },
    { icon: '📅', label: 'Calendar' },
    { icon: '🍽', label: 'Meals' },
    { icon: '🛒', label: 'Lists' },
    { icon: '✅', label: 'Chores' },
    { icon: '☰', label: 'More' },
];

const perks = ['Installs from your browser', 'Notifications you choose', 'iPhone, iPad and Android'];

const StatusBar: React.FC<{ dark?: boolean }> = ({ dark }) => (
    <div
        style={{
            height: 54,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            padding: '0 30px',
            fontSize: 17,
            fontWeight: 700,
            color: dark ? light.ink : 'white',
        }}
    >
        <span>07:42</span>
        <span style={{ width: 120, height: 34, borderRadius: 17, background: '#000' }} />
        <span>5G ▮</span>
    </div>
);

const HomeScreen: React.FC<{ f: number }> = ({ f }) => {
    const add = spr(f, ADD, springs.bouncy);
    return (
        <div style={{ position: 'absolute', inset: 0, background: heroGradient }}>
            <StatusBar />
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '30px 0', padding: '40px 22px 0', justifyItems: 'center' }}>
                {homeIcons.map((colour, i) =>
                    colour ? (
                        <div key={i} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8 }}>
                            <div style={{ width: 70, height: 70, borderRadius: 18, background: colour, opacity: 0.85 }} />
                            <div style={{ width: 46, height: 8, borderRadius: 4, background: 'rgba(255,255,255,0.35)' }} />
                        </div>
                    ) : (
                        <div key={i} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 6, transform: `scale(${add})` }}>
                            <LogoMark size={70} radius={18} />
                            <div style={{ fontSize: 14, fontWeight: 700, color: 'white' }}>HouseHub</div>
                        </div>
                    ),
                )}
            </div>
        </div>
    );
};

const AppScreen: React.FC<{ f: number }> = ({ f }) => {
    const done = prog(f, TICK, TICK + 14);
    return (
        <div style={{ position: 'absolute', inset: 0, background: light.shell, color: light.ink, display: 'flex', flexDirection: 'column' }}>
            <StatusBar dark />
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '6px 22px 18px', borderBottom: `1.5px solid ${light.line}` }}>
                <LogoMark size={40} radius={12} />
                <div style={{ fontSize: 26, fontWeight: 900, letterSpacing: '-0.03em' }}>Chores</div>
                <div style={{ flex: 1 }} />
                <div style={{ width: 42, height: 42, borderRadius: 13, background: light.soft, display: 'grid', placeItems: 'center', fontSize: 19 }}>🔔</div>
            </div>

            <div style={{ flex: 1, padding: '18px 18px 0', display: 'flex', flexDirection: 'column', gap: 12 }}>
                <div style={{ fontSize: 14, fontWeight: 800, letterSpacing: '0.08em', textTransform: 'uppercase', color: light.ink3 }}>Today's tasks</div>
                {chores.map((chore, i) => {
                    const isTarget = i === 0;
                    const ticked = chore.done ? 1 : isTarget ? done : 0;
                    const glow = isTarget ? range(f, [OPEN + 10, OPEN + 24, TICK, TICK + 12], [0, 1, 1, 0]) : 0;
                    return (
                        <div
                            key={chore.name}
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 14,
                                padding: '16px 16px',
                                borderRadius: 20,
                                background: light.card,
                                border: `2px solid ${glow > 0 ? `rgba(255,79,64,${glow})` : light.line}`,
                                boxShadow: glow > 0 ? `0 14px 30px -16px rgba(255,79,64,${glow * 0.8})` : undefined,
                            }}
                        >
                            <Tick done={ticked} size={30} />
                            <div
                                style={{
                                    flex: 1,
                                    fontSize: 19,
                                    fontWeight: 700,
                                    color: ticked > 0.5 ? light.ink3 : light.ink,
                                    textDecoration: ticked > 0.5 ? 'line-through' : undefined,
                                }}
                            >
                                {chore.name}
                            </div>
                            <Avatar person={chore.who} size={34} radius={11} />
                        </div>
                    );
                })}
            </div>

            <div style={{ display: 'flex', borderTop: `1.5px solid ${light.line}`, padding: '8px 6px 22px' }}>
                {tabs.map((tab) => {
                    const on = tab.label === 'Chores';
                    return (
                        <div key={tab.label} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 4 }}>
                            <div style={{ width: 52, height: 32, borderRadius: 16, background: on ? light.soft : 'transparent', display: 'grid', placeItems: 'center', fontSize: 18 }}>
                                {tab.icon}
                            </div>
                            <div style={{ fontSize: 12, fontWeight: on ? 800 : 600, color: on ? light.ink : light.ink3 }}>{tab.label}</div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

// 240 frames: install → push → tap → the app, on the chore it was about.
export const Phone: React.FC = () => {
    const f = useCurrentFrame();
    const phoneIn = spr(f, 0, springs.smooth);
    const banner = spr(f, BANNER, springs.snappy);
    const bannerGone = prog(f, OPEN, OPEN + 8);
    const open = spr(f, OPEN, springs.snappy);
    const pill = range(f, [ADD - 14, ADD, BANNER - 12, BANNER], [0, 1, 1, 0]);
    const tapRipple = prog(f, TAP, TAP + 18);

    return (
        <AbsoluteFill style={{ fontFamily: fonts.sans }}>
            <WarmBg />
            <Sfx at={2} name="swish" volume={0.4} />
            <Sfx at={ADD} name="sparkle" volume={0.5} />
            <Sfx at={BANNER} name="chime" volume={0.6} />
            <Sfx at={TAP} name="click" volume={0.6} />
            <Sfx at={OPEN + 2} name="whoosh" volume={0.35} />
            <Sfx at={TICK + 2} name="pop" volume={0.55} />
            <Sfx at={WIPE} name="whoosh" volume={0.5} />

            {/* Copy */}
            <div style={{ position: 'absolute', left: 150, top: 250, width: 900 }}>
                <Eyebrow icon="📱" label="On your phone" delay={4} />
                <Headline segments={['In your', { accent: 'pocket,' }, 'too.']} delay={10} size={104} style={{ marginTop: 30 }} />
                <Sub delay={30} style={{ marginTop: 26, maxWidth: 760 }}>
                    Add HouseHub to your home screen — no app store — and get a nudge the moment something needs you.
                </Sub>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 14, marginTop: 34 }}>
                    {perks.map((perk, i) => {
                        const p = spr(f, 54 + i * 8, springs.pop);
                        return (
                            <div key={perk} style={{ display: 'flex', alignItems: 'center', gap: 14, fontSize: 26, fontWeight: 700, color: light.ink, opacity: Math.min(1, p * 1.5), transform: `translateX(${(1 - p) * -30}px)` }}>
                                <span style={{ width: 34, height: 34, borderRadius: 11, background: brand.mint, color: brand.onDark, display: 'grid', placeItems: 'center', fontSize: 18, fontWeight: 900 }}>
                                    ✓
                                </span>
                                {perk}
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* "Add to Home Screen" label over the phone while the icon lands */}
            <div
                style={{
                    position: 'absolute',
                    left: PHONE.x + PHONE.w / 2 - 170,
                    top: PHONE.y - 70,
                    width: 340,
                    height: 52,
                    borderRadius: 16,
                    background: light.ink,
                    color: light.shell,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: 10,
                    fontSize: 21,
                    fontWeight: 800,
                    opacity: pill * phoneIn,
                    transform: `translateY(${(1 - pill) * 12}px)`,
                }}
            >
                <span style={{ fontSize: 22 }}>＋</span> Add to Home Screen
            </div>

            {/* Phone */}
            <div
                style={{
                    position: 'absolute',
                    left: PHONE.x,
                    top: PHONE.y,
                    width: PHONE.w,
                    height: PHONE.h,
                    borderRadius: 70,
                    background: '#0b0e15',
                    padding: SCREEN_INSET,
                    boxShadow: '0 60px 120px -40px rgba(14,26,43,0.65)',
                    transform: `translateY(${(1 - phoneIn) * 500}px) rotate(${(1 - phoneIn) * 8}deg)`,
                }}
            >
                <div style={{ position: 'relative', width: '100%', height: '100%', borderRadius: 56, overflow: 'hidden' }}>
                    <HomeScreen f={f} />

                    {/* Push notification */}
                    {f >= BANNER && f < OPEN + 10 && (
                        <div
                            style={{
                                position: 'absolute',
                                left: 14,
                                right: 14,
                                top: 60,
                                padding: 16,
                                borderRadius: 26,
                                background: 'rgba(255,255,255,0.9)',
                                color: light.ink,
                                display: 'flex',
                                gap: 14,
                                boxShadow: '0 20px 40px -18px rgba(0,0,0,0.5)',
                                transform: `translateY(${(1 - banner) * -170}px) scale(${1 - bannerGone * 0.1})`,
                                opacity: 1 - bannerGone,
                            }}
                        >
                            <LogoMark size={46} radius={13} />
                            <div style={{ flex: 1, minWidth: 0 }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 17, fontWeight: 800 }}>
                                    {PUSH.household}
                                    <span style={{ fontSize: 14, fontWeight: 600, color: light.ink3 }}>now</span>
                                </div>
                                <div style={{ fontSize: 16, lineHeight: 1.3, color: light.ink2, marginTop: 3 }}>{PUSH.body}</div>
                            </div>
                            {f >= TAP && tapRipple < 1 && (
                                <div
                                    style={{
                                        position: 'absolute',
                                        left: '50%',
                                        top: '50%',
                                        width: 90,
                                        height: 90,
                                        marginLeft: -45,
                                        marginTop: -45,
                                        borderRadius: '50%',
                                        background: 'rgba(14,26,43,0.18)',
                                        transform: `scale(${0.4 + tapRipple * 1.6})`,
                                        opacity: 1 - tapRipple,
                                    }}
                                />
                            )}
                        </div>
                    )}

                    {/* The app, opening from the notification */}
                    {f >= OPEN && (
                        <div
                            style={{
                                position: 'absolute',
                                inset: 0,
                                transform: `scale(${0.35 + open * 0.65})`,
                                transformOrigin: '50% 12%',
                                opacity: Math.min(1, open * 1.6),
                                borderRadius: (1 - open) * 40,
                                overflow: 'hidden',
                            }}
                        >
                            <AppScreen f={f} />
                        </div>
                    )}
                </div>
            </div>

            <CircleWipe start={WIPE} len={18} colour={light.bg} x={PHONE.x + PHONE.w / 2} y={PHONE.y + PHONE.h / 2} />
        </AbsoluteFill>
    );
};
