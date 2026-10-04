import React from 'react';
import { AbsoluteFill, Easing, useCurrentFrame } from 'remotion';
import { prog, range, spr, springs } from '../anim';
import { Avatar, Card, CircleWipe, Headline, Sfx, WarmBg } from '../components/ui';
import { brand, fonts, light, people, tints } from '../theme';

const TW = 540, TH = 340, GAP = 30;
const X0 = (1920 - (TW * 3 + GAP * 2)) / 2, Y0 = 290;

type TileProps = { d: number };

const Budget: React.FC<TileProps> = ({ d }) => {
    const f = useCurrentFrame() - d;
    const left = Math.round(range(f, [20, 60], [0, 1240], Easing.out(Easing.cubic)));
    const bars = [
        { m: 'Jun', v: 0.62 },
        { m: 'Jul', v: 0.78 },
        { m: 'Aug', v: 0.55 },
    ];
    return (
        <div style={{ display: 'flex', gap: 28, alignItems: 'flex-end', height: '100%' }}>
            <div style={{ flex: 1 }}>
                <div style={{ fontSize: 15, fontWeight: 800, letterSpacing: '0.08em', textTransform: 'uppercase', color: light.ink3 }}>Left to spend</div>
                <div style={{ fontSize: 58, fontWeight: 900, letterSpacing: '-0.04em' }}>£{left.toLocaleString('en-GB')}</div>
                <div style={{ display: 'flex', gap: 8, marginTop: 8, flexWrap: 'wrap' }}>
                    {['🛒 Food', '💡 Utilities', '⛽ Fuel'].map((c, i) => (
                        <span key={c} style={{ fontSize: 14, fontWeight: 700, padding: '6px 10px', borderRadius: 10, background: light.soft, transform: `scale(${spr(f, 30 + i * 4, springs.pop)})` }}>
                            {c}
                        </span>
                    ))}
                </div>
            </div>
            <div style={{ display: 'flex', gap: 12, alignItems: 'flex-end', height: 150 }}>
                {bars.map((b, i) => (
                    <div key={b.m} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 6 }}>
                        <div
                            style={{
                                width: 38,
                                height: 120 * b.v * spr(f, 16 + i * 6, springs.bouncy),
                                borderRadius: 10,
                                background: i === 2 ? brand.coral : light.sunk,
                            }}
                        />
                        <span style={{ fontSize: 13, fontWeight: 700, color: light.ink3 }}>{b.m}</span>
                    </div>
                ))}
            </div>
        </div>
    );
};

const Documents: React.FC<TileProps> = ({ d }) => {
    const f = useCurrentFrame() - d;
    const q = 'MOT'.slice(0, Math.floor(range(f, [34, 46], [0, 3])));
    const found = spr(f, 50, springs.pop);
    return (
        <div>
            <div style={{ height: 50, borderRadius: 14, background: light.soft, display: 'flex', alignItems: 'center', gap: 10, padding: '0 16px', fontSize: 18 }}>
                🔍 <span style={{ color: q ? light.ink : light.ink3 }}>{q || 'Search filenames and tags'}</span>
                {q && q.length < 3 && <span style={{ width: 2, height: 22, background: brand.coral }} />}
            </div>
            <div style={{ display: 'flex', gap: 10, marginTop: 14 }}>
                {[
                    ['🏠', 'Property'],
                    ['🛡', 'Insurance'],
                    ['🚗', 'Vehicles'],
                ].map(([icon, label], i) => (
                    <div
                        key={label}
                        style={{
                            flex: 1,
                            height: 62,
                            borderRadius: 14,
                            background: tints[i + 1],
                            display: 'flex',
                            alignItems: 'center',
                            gap: 8,
                            padding: '0 12px',
                            fontSize: 16,
                            fontWeight: 800,
                            transform: `scale(${spr(f, 14 + i * 4, springs.pop)})`,
                            outline: label === 'Vehicles' && found > 0.5 ? `3px solid ${brand.coral}` : undefined,
                        }}
                    >
                        <span style={{ fontSize: 22 }}>{icon}</span>
                        {label}
                    </div>
                ))}
            </div>
            <div
                style={{
                    marginTop: 14,
                    height: 58,
                    borderRadius: 14,
                    border: `1.5px solid ${light.line}`,
                    display: 'flex',
                    alignItems: 'center',
                    gap: 12,
                    padding: '0 14px',
                    fontSize: 17,
                    fontWeight: 700,
                    opacity: found,
                    transform: `translateY(${(1 - found) * 20}px)`,
                }}
            >
                <span style={{ fontFamily: fonts.mono, fontSize: 12, padding: '4px 7px', borderRadius: 7, background: brand.coral, color: 'white' }}>PDF</span>
                MOT certificate — Golf
                <span style={{ marginLeft: 'auto', fontSize: 13, color: light.ink3 }}>expires Mar</span>
            </div>
        </div>
    );
};

const roleColours = [brand.coral, brand.lilac, brand.sun, brand.sky];
const Roles: React.FC<TileProps> = ({ d }) => {
    const f = useCurrentFrame() - d;
    return (
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
            {people.map((p, i) => {
                const s = spr(f, 14 + i * 5, springs.pop);
                return (
                    <div key={p.initials} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: 12, borderRadius: 16, background: light.panel, transform: `scale(${s})` }}>
                        <Avatar person={p} size={46} radius={14} />
                        <div>
                            <div style={{ fontSize: 18, fontWeight: 800 }}>{p.name}</div>
                            <span
                                style={{
                                    fontSize: 13,
                                    fontWeight: 800,
                                    padding: '3px 9px',
                                    borderRadius: 8,
                                    background: `${roleColours[i]}33`,
                                    color: light.ink,
                                    display: 'inline-block',
                                    transform: `scale(${spr(f, 26 + i * 5, springs.bouncy)})`,
                                }}
                            >
                                {p.role}
                            </span>
                        </div>
                    </div>
                );
            })}
        </div>
    );
};

const Invites: React.FC<TileProps> = ({ d }) => {
    const f = useCurrentFrame() - d;
    const code = 'HX7-24Q';
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    const email = 'gran@parkers.family';
    const typed = email.slice(0, Math.floor(range(f, [44, 70], [0, email.length])));
    return (
        <div>
            <div style={{ fontSize: 14, fontWeight: 800, letterSpacing: '0.08em', textTransform: 'uppercase', color: light.ink3 }}>Join code</div>
            <div style={{ display: 'flex', gap: 8, marginTop: 8 }}>
                {code.split('').map((c, i) => {
                    const settle = 18 + i * 4;
                    const shown = c === '-' || f >= settle ? c : chars[Math.floor((f + i * 7) * 1.7) % chars.length];
                    return (
                        <span
                            key={i}
                            style={{
                                width: c === '-' ? 22 : 54,
                                height: 68,
                                borderRadius: 14,
                                background: c === '-' ? 'transparent' : f >= settle ? light.ink : light.soft,
                                color: f >= settle ? light.shell : light.ink3,
                                display: 'grid',
                                placeItems: 'center',
                                fontFamily: fonts.mono,
                                fontSize: 34,
                                fontWeight: 500,
                                transform: `scale(${f >= settle && c !== '-' ? 1 + (1 - spr(f, settle, springs.pop)) * 0.3 : 1})`,
                            }}
                        >
                            {c === '-' ? <span style={{ color: light.ink3 }}>–</span> : shown}
                        </span>
                    );
                })}
            </div>
            <div style={{ display: 'flex', gap: 10, marginTop: 18 }}>
                <div style={{ flex: 1, height: 52, borderRadius: 14, background: light.soft, display: 'flex', alignItems: 'center', padding: '0 14px', fontSize: 17 }}>
                    ✉️&nbsp;<span style={{ color: typed ? light.ink : light.ink3 }}>{typed || 'Invite by email'}</span>
                </div>
                <div style={{ height: 52, padding: '0 18px', borderRadius: 14, background: brand.coral, color: 'white', fontWeight: 800, fontSize: 16, display: 'grid', placeItems: 'center' }}>
                    Invite
                </div>
            </div>
        </div>
    );
};

const Homes: React.FC<TileProps> = ({ d }) => {
    const f = useCurrentFrame() - d;
    const homes = [
        { n: 'The Parkers', sub: '4 members', icon: '🏡' },
        { n: "Gran's house", sub: '2 members', icon: '🏠' },
        { n: 'Cornwall cottage', sub: '5 members', icon: '🌊' },
    ];
    const sel = range(f, [50, 62, 100, 112], [0, 1, 1, 2], Easing.inOut(Easing.cubic));
    return (
        <div style={{ position: 'relative' }}>
            <div style={{ position: 'absolute', left: 0, right: 0, top: sel * 70, height: 62, borderRadius: 16, background: light.ink }} />
            {homes.map((h, i) => {
                const on = Math.abs(sel - i) < 0.5;
                return (
                    <div
                        key={h.n}
                        style={{
                            position: 'relative',
                            height: 62,
                            marginBottom: 8,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 12,
                            padding: '0 16px',
                            color: on ? light.shell : light.ink,
                            transform: `translateX(${(1 - spr(f, 14 + i * 5, springs.snappy)) * 60}px)`,
                            opacity: spr(f, 14 + i * 5, springs.snappy),
                        }}
                    >
                        <span style={{ fontSize: 26 }}>{h.icon}</span>
                        <span style={{ fontSize: 19, fontWeight: 800 }}>{h.n}</span>
                        <span style={{ marginLeft: 'auto', fontSize: 14, opacity: 0.6 }}>{on ? '✓ Current' : h.sub}</span>
                    </div>
                );
            })}
        </div>
    );
};

const Recipes: React.FC<TileProps> = ({ d }) => {
    const f = useCurrentFrame() - d;
    const cards = [
        { n: 'Katsu curry', t: 2, tag: '30 min' },
        { n: 'Veg lasagne', t: 3, tag: 'Vegetarian' },
        { n: 'Sticky toffee pud', t: 0, tag: 'Desserts' },
    ];
    return (
        <div style={{ position: 'relative', height: 200 }}>
            {cards.map((c, i) => {
                const s = spr(f, 14 + i * 5, springs.bouncy);
                const rot = (i - 1) * 9 * s;
                return (
                    <div
                        key={c.n}
                        style={{
                            position: 'absolute',
                            left: 150 + (i - 1) * 140 * s,
                            top: 10 + Math.abs(i - 1) * 16 * s,
                            width: 170,
                            height: 190,
                            borderRadius: 20,
                            background: tints[c.t],
                            padding: 14,
                            display: 'flex',
                            flexDirection: 'column',
                            transform: `rotate(${rot}deg)`,
                            boxShadow: '0 16px 30px -18px rgba(14,26,43,0.5)',
                            zIndex: i === 1 ? 2 : 1,
                        }}
                    >
                        <span style={{ alignSelf: 'flex-end', fontSize: 22, transform: `scale(${1 + (i === 1 ? Math.max(0, Math.sin(f / 5)) * 0.25 : 0)})`, color: brand.coral }}>♥</span>
                        <div style={{ marginTop: 'auto', fontSize: 21, fontWeight: 900, lineHeight: 1.05 }}>{c.n}</div>
                        <span style={{ fontSize: 13, fontWeight: 700, color: light.ink2, marginTop: 6 }}>{c.tag}</span>
                    </div>
                );
            })}
        </div>
    );
};

const tiles: { icon: string; title: string; line: string; Body: React.FC<TileProps> }[] = [
    { icon: '📖', title: 'Recipe library', line: 'Favourites, tags and cook times', Body: Recipes },
    { icon: '💰', title: 'Budgeting', line: 'Income and planned spend, month to month', Body: Budget },
    { icon: '📂', title: 'Document vault', line: 'Folders, tags and search', Body: Documents },
    { icon: '👪', title: 'Family roles', line: 'Kids tick off, grown-ups manage', Body: Roles },
    { icon: '✉️', title: 'Easy invites', line: 'Email invites or a join code', Body: Invites },
    { icon: '🏘', title: 'Multiple homes', line: 'Switch households, one login', Body: Homes },
];

// 240 frames.
export const Montage: React.FC = () => {
    const f = useCurrentFrame();
    const burst = prog(f, 214, 236, Easing.in(Easing.cubic));
    return (
        <AbsoluteFill style={{ fontFamily: fonts.sans }}>
            <WarmBg />
            {tiles.map((_, i) => (
                <Sfx key={i} at={14 + i * 7} name="swish" volume={0.3} rate={0.9 + i * 0.06} />
            ))}
            <Sfx at={214} name="whoosh" volume={0.55} />

            <div style={{ position: 'absolute', top: 70, left: 0, right: 0, opacity: 1 - burst, transform: `translateY(${burst * -120}px)` }}>
                <Headline segments={['Then it holds', { accent: 'everything else.' }]} delay={2} size={100} align="center" />
            </div>

            <AbsoluteFill style={{ perspective: 2000 }}>
                {tiles.map((t, i) => {
                    const col = i % 3, row = Math.floor(i / 3);
                    const d = 14 + i * 7;
                    const p = spr(f, d, { damping: 14, stiffness: 120 });
                    const x = X0 + col * (TW + GAP);
                    const y = Y0 + row * (TH + GAP);
                    const dx = (x + TW / 2 - 960) * burst * 2.2;
                    const dy = (y + TH / 2 - 640) * burst * 2.2;
                    const hover = Math.sin((f + i * 20) / 25) * 5;
                    const { Body } = t;
                    return (
                        <div
                            key={t.title}
                            style={{
                                position: 'absolute',
                                left: x,
                                top: y,
                                transform: `translate(${dx}px, ${dy + hover}px) rotateY(${(1 - p) * -95}deg) rotateZ(${burst * (col - 1) * 20}deg) scale(${1 + burst * 0.4})`,
                                opacity: Math.min(1, p * 2) * (1 - burst),
                                transformOrigin: 'left center',
                            }}
                        >
                            <Card style={{ width: TW, height: TH, padding: 24, display: 'flex', flexDirection: 'column' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 14, marginBottom: 18 }}>
                                    <span style={{ width: 50, height: 50, borderRadius: 15, background: tints[i % 5], display: 'grid', placeItems: 'center', fontSize: 25 }}>{t.icon}</span>
                                    <div>
                                        <div style={{ fontSize: 23, fontWeight: 900, letterSpacing: '-0.02em' }}>{t.title}</div>
                                        <div style={{ fontSize: 15, color: light.ink3 }}>{t.line}</div>
                                    </div>
                                </div>
                                <div style={{ flex: 1, minHeight: 0 }}>
                                    <Body d={d} />
                                </div>
                            </Card>
                        </div>
                    );
                })}
            </AbsoluteFill>
            <CircleWipe start={222} len={18} colour={brand.hero1} />
        </AbsoluteFill>
    );
};
