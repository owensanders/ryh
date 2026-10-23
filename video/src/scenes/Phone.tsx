import React from 'react';
import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { prog, range, spr, springs } from '../anim';
import { PhoneShell, Ripple, StatusBar } from '../components/PhoneFrame';
import { Avatar, CircleWipe, Eyebrow, Headline, LogoMark, RoofMark, Sfx, Sub, Tick, WarmBg } from '../components/ui';
import { brand, family, fonts, heroGradient, light, Person } from '../theme';

// Beats, in frames.
const ADD = 26; // RYH lands on the home screen
const BANNER = 80; // a push notification arrives
const TAP = 126; // it's tapped…
const OPEN = 130; // …and the app opens on Chores
const TICK = 178; // the chore gets done
const MORE_TAP = 214; // More opens the sheet (Budget lives there on phones)…
const BUDGET_TAP = 250; // …Budget is tapped…
const SWITCH = 256; // …and only the content swaps; the header and tab bar stay
const WIPE = 342;

const PHONE = { x: 1180, y: 124, w: 420, h: 868 };

// The notification is the app's real wording (ChoreAssignedNotification),
// titled with the household's name as every RYH push is.
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

// What an Adult sees in the More sheet (House is Owner-only).
const moreItems = [
    { icon: '💰', label: 'Budget' },
    { icon: '📂', label: 'Documents' },
    { icon: '⚙', label: 'Settings' },
];

// The seeded Parkers budget (RyhSeeder): £3,390 in, £2,150 assigned.
const budget = {
    leftToSpend: 1240,
    moneyIn: '£3,390',
    moneyOut: '£2,150',
    perDay: '£41',
    categories: [
        { icon: '🛒', label: 'Food & shopping', amount: '£900', share: 0.42, colour: brand.coral },
        { icon: '💡', label: 'Utilities', amount: '£600', share: 0.28, colour: brand.mint },
        { icon: '⛽', label: 'Fuel', amount: '£500', share: 0.23, colour: brand.sky },
        { icon: '📺', label: 'Subscriptions', amount: '£150', share: 0.07, colour: brand.sun },
    ],
};

// The last one lands as the Budget screen opens.
const perks = [
    { text: 'Installs from your browser', at: 54 },
    { text: 'Notifications you choose', at: 62 },
    { text: 'iPhone, iPad and Android', at: 70 },
    { text: 'Every screen, sized for your phone', at: SWITCH + 10 },
];

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
                            <div style={{ fontSize: 14, fontWeight: 700, color: 'white' }}>RYH</div>
                        </div>
                    ),
                )}
            </div>
        </div>
    );
};

const ChoresContent: React.FC<{ f: number }> = ({ f }) => {
    const done = prog(f, TICK, TICK + 14);
    return (
        <div style={{ padding: '18px 18px 0', display: 'flex', flexDirection: 'column', gap: 12 }}>
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
    );
};

const BudgetContent: React.FC<{ f: number }> = ({ f }) => {
    const count = range(f, [SWITCH + 6, SWITCH + 36], [0, budget.leftToSpend], (t) => 1 - (1 - t) ** 3);
    return (
        <div style={{ padding: '16px 16px 0', display: 'flex', flexDirection: 'column', gap: 12 }}>
            <div style={{ borderRadius: 24, padding: '20px 20px 18px', background: heroGradient, color: brand.onhero }}>
                <div style={{ fontSize: 13, fontWeight: 800, letterSpacing: '0.08em', textTransform: 'uppercase', color: brand.onhero2 }}>
                    October
                </div>
                <div style={{ fontSize: 15, color: brand.onhero2, marginTop: 12 }}>Left to spend this month</div>
                <div style={{ fontSize: 50, fontWeight: 900, letterSpacing: '-0.04em', lineHeight: 1.05, fontVariantNumeric: 'tabular-nums' }}>
                    £{Math.round(count).toLocaleString('en-GB')}
                </div>
                <div style={{ marginTop: 12, height: 10, borderRadius: 6, background: brand.herofilm, overflow: 'hidden' }}>
                    <div style={{ height: '100%', width: `${prog(f, SWITCH + 10, SWITCH + 40) * 63}%`, background: brand.coral, borderRadius: 6 }} />
                </div>
                <div style={{ marginTop: 10, fontSize: 13.5, color: brand.onhero2 }}>
                    <b style={{ color: brand.onhero }}>{budget.moneyOut}</b> assigned · <b style={{ color: brand.onhero }}>{budget.perDay}</b> a day
                    to stay on track
                </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
                {[
                    ['Money in', budget.moneyIn],
                    ['Money out', budget.moneyOut],
                ].map(([label, value]) => (
                    <div key={label} style={{ borderRadius: 18, padding: '12px 14px', background: light.card, border: `1.5px solid ${light.line}` }}>
                        <div style={{ fontSize: 11.5, fontWeight: 800, letterSpacing: '0.08em', textTransform: 'uppercase', color: light.ink3 }}>
                            {label}
                        </div>
                        <div style={{ fontSize: 22, fontWeight: 900, letterSpacing: '-0.03em', marginTop: 2 }}>{value}</div>
                    </div>
                ))}
            </div>

            <div style={{ borderRadius: 22, padding: '14px 14px 6px', background: light.card, border: `1.5px solid ${light.line}` }}>
                <div style={{ fontSize: 16, fontWeight: 800, marginBottom: 6 }}>Where it goes</div>
                {budget.categories.map((c, i) => {
                    const grow = spr(f, SWITCH + 18 + i * 6, springs.smooth);
                    return (
                        <div key={c.label} style={{ display: 'flex', alignItems: 'center', gap: 11, padding: '8px 0' }}>
                            <span
                                style={{
                                    width: 32,
                                    height: 32,
                                    borderRadius: 10,
                                    background: c.colour,
                                    display: 'grid',
                                    placeItems: 'center',
                                    fontSize: 15,
                                }}
                            >
                                {c.icon}
                            </span>
                            <div style={{ flex: 1, minWidth: 0 }}>
                                <div style={{ display: 'flex', fontSize: 15, fontWeight: 700 }}>
                                    {c.label}
                                    <span style={{ marginLeft: 'auto', fontFamily: fonts.mono, fontSize: 13.5, fontWeight: 500 }}>{c.amount}</span>
                                </div>
                                <div style={{ marginTop: 6, height: 7, borderRadius: 4, background: light.sunk, overflow: 'hidden' }}>
                                    <div style={{ height: '100%', width: `${c.share * grow * 100}%`, background: c.colour, borderRadius: 4 }} />
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

const AppScreen: React.FC<{ f: number }> = ({ f }) => {
    const onBudget = f >= SWITCH;
    const swap = spr(f, SWITCH, springs.snappy);
    const sheet = spr(f, MORE_TAP + 4, springs.snappy) * (1 - prog(f, SWITCH - 4, SWITCH + 6));
    const sheetOpen = f >= MORE_TAP + 4 && f < SWITCH + 6;
    const activeTab = f >= MORE_TAP ? 'More' : 'Chores';

    return (
        <div style={{ position: 'absolute', inset: 0, background: light.shell, color: light.ink, display: 'flex', flexDirection: 'column' }}>
            <StatusBar dark />
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '6px 22px 18px', borderBottom: `1.5px solid ${light.line}` }}>
                <RoofMark width={52} color={light.ink} />
                <div style={{ fontSize: 26, fontWeight: 900, letterSpacing: '-0.03em' }}>{onBudget ? 'Budget' : 'Chores'}</div>
                <div style={{ flex: 1 }} />
                <div style={{ width: 42, height: 42, borderRadius: 13, background: light.soft, display: 'grid', placeItems: 'center', fontSize: 19 }}>
                    🔔
                </div>
            </div>

            {/* Only the content changes between screens: the frame is a persistent layout. */}
            <div style={{ flex: 1, minHeight: 0, position: 'relative', overflow: 'hidden' }}>
                {onBudget ? (
                    <div style={{ position: 'absolute', inset: 0, opacity: Math.min(1, swap * 1.4), transform: `translateY(${(1 - swap) * 30}px)` }}>
                        <BudgetContent f={f} />
                    </div>
                ) : (
                    <ChoresContent f={f} />
                )}
            </div>

            <div
                style={{
                    position: 'relative',
                    display: 'flex',
                    borderTop: `1.5px solid ${light.line}`,
                    padding: '8px 6px 22px',
                    background: light.shell,
                }}
            >
                {tabs.map((tab) => {
                    const on = tab.label === activeTab;
                    return (
                        <div key={tab.label} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 4 }}>
                            <div
                                style={{
                                    width: 52,
                                    height: 32,
                                    borderRadius: 16,
                                    background: on ? light.soft : 'transparent',
                                    display: 'grid',
                                    placeItems: 'center',
                                    fontSize: 18,
                                }}
                            >
                                {tab.icon}
                            </div>
                            <div style={{ fontSize: 12, fontWeight: on ? 800 : 600, color: on ? light.ink : light.ink3 }}>{tab.label}</div>
                        </div>
                    );
                })}
                <Ripple f={f} at={MORE_TAP} x="91.5%" y="40%" />
            </div>

            {/* The More sheet */}
            {sheetOpen && (
                <>
                    <div style={{ position: 'absolute', inset: 0, bottom: 86, background: `rgba(0,0,0,${0.3 * sheet})` }} />
                    <div
                        style={{
                            position: 'absolute',
                            left: 12,
                            right: 12,
                            bottom: 96,
                            borderRadius: 26,
                            padding: 8,
                            background: light.card,
                            border: `1.5px solid ${light.line}`,
                            boxShadow: '0 20px 40px -18px rgba(14,26,43,0.45)',
                            opacity: sheet,
                            transform: `translateY(${(1 - sheet) * 40}px)`,
                        }}
                    >
                        {moreItems.map((item) => {
                            const on = item.label === 'Budget' && f >= BUDGET_TAP;
                            return (
                                <div
                                    key={item.label}
                                    style={{
                                        position: 'relative',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 14,
                                        height: 54,
                                        padding: '0 14px',
                                        borderRadius: 18,
                                        fontSize: 18,
                                        fontWeight: on ? 800 : 600,
                                        color: on ? light.ink : light.ink2,
                                        background: on ? light.soft : 'transparent',
                                    }}
                                >
                                    <span style={{ width: 26, textAlign: 'center' }}>{item.icon}</span>
                                    {item.label}
                                    {item.label === 'Budget' && <Ripple f={f} at={BUDGET_TAP} x="30%" y="50%" />}
                                </div>
                            );
                        })}
                        <div style={{ height: 1.5, background: light.line, margin: '4px 8px' }} />
                        {[
                            ['🌙', 'Dark mode'],
                            ['↪', 'Log out'],
                        ].map(([icon, label]) => (
                            <div
                                key={label}
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 14,
                                    height: 50,
                                    padding: '0 14px',
                                    fontSize: 18,
                                    fontWeight: 600,
                                    color: light.ink2,
                                }}
                            >
                                <span style={{ width: 26, textAlign: 'center' }}>{icon}</span>
                                {label}
                            </div>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
};

// 360 frames: install → push → tap → the app on the chore it was about →
// More → Budget, with the frame staying put between screens.
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
            <Sfx at={MORE_TAP} name="click" volume={0.5} />
            <Sfx at={MORE_TAP + 4} name="swish" volume={0.35} />
            <Sfx at={BUDGET_TAP} name="click" volume={0.5} />
            <Sfx at={SWITCH + 30} name="coin" volume={0.45} />
            <Sfx at={WIPE} name="whoosh" volume={0.5} />

            {/* Copy */}
            <div style={{ position: 'absolute', left: 150, top: 250, width: 900 }}>
                <Eyebrow icon="📱" label="On your phone" delay={4} />
                <Headline segments={['In your', { accent: 'pocket,' }, 'too.']} delay={10} size={104} style={{ marginTop: 30 }} />
                <Sub delay={30} style={{ marginTop: 26, maxWidth: 760 }}>
                    Add RYH to your home screen — no app store — and get a nudge the moment something needs you.
                </Sub>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 14, marginTop: 34 }}>
                    {perks.map(({ text: perk, at }) => {
                        const p = spr(f, at, springs.pop);
                        return (
                            <div
                                key={perk}
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 14,
                                    fontSize: 26,
                                    fontWeight: 700,
                                    color: light.ink,
                                    opacity: Math.min(1, p * 1.5),
                                    transform: `translateX(${(1 - p) * -30}px)`,
                                }}
                            >
                                <span
                                    style={{
                                        width: 34,
                                        height: 34,
                                        borderRadius: 11,
                                        background: brand.mint,
                                        color: brand.onDark,
                                        display: 'grid',
                                        placeItems: 'center',
                                        fontSize: 18,
                                        fontWeight: 900,
                                    }}
                                >
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
            <PhoneShell
                style={{
                    left: PHONE.x,
                    top: PHONE.y,
                    transform: `translateY(${(1 - phoneIn) * 500}px) rotate(${(1 - phoneIn) * 8}deg)`,
                }}
            >
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
            </PhoneShell>

            <CircleWipe start={WIPE} len={18} colour={light.bg} x={PHONE.x + PHONE.w / 2} y={PHONE.y + PHONE.h / 2} />
        </AbsoluteFill>
    );
};
