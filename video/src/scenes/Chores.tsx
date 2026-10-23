import React from 'react';
import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { lerp, prog, range, spr, springs } from '../anim';
import { Avatar, Card, CircleWipe, Cursor, Dial, Eyebrow, Headline, Sfx, Sub, Tick, WarmBg } from '../components/ui';
import { brand, family, fonts, light, people, Person } from '../theme';

type Chore = { id: string; name: string; who: Person; moveAt?: number };

const today: Chore[] = [
    { id: 'dish', name: 'Empty dishwasher', who: family.mia, moveAt: 94 },
    { id: 'recy', name: 'Put the recycling out', who: family.noah },
    { id: 'hoov', name: 'Hoover upstairs', who: family.james, moveAt: 118 },
    { id: 'cat', name: 'Feed the cat', who: family.mia, moveAt: 70 },
    { id: 'bed', name: 'Tidy bedroom', who: family.noah },
];
const done: Chore[] = [
    { id: 'plant', name: 'Water the plants', who: family.sarah },
    { id: 'home', name: 'Homework check', who: family.sarah },
];
const moves = today.filter((c) => c.moveAt).sort((a, b) => a.moveAt! - b.moveAt!);
const all = [...today, ...done];

const B = { x: 690, y: 140, w: 1140, h: 790, pad: 28 };
const colW = (B.w - B.pad * 2 - 20) / 2;
const colTop = B.y + B.pad + 74 + 160 + 50;
const rowH = 76,
    rowGap = 10;
const NOTIFY = 176;
const btn = { x: B.x + B.w - B.pad - 120, y: B.y + B.pad + 28 };

const weekly: Record<string, [number, number]> = { SP: [8, 10], JP: [5, 8], MP: [3, 7], NP: [2, 6] };

/** Row position of a chore after `s` of the moves have happened. */
function slot(c: Chore, s: number) {
    const moved = moves.slice(0, s);
    const todayList = today.filter((t) => !moved.includes(t));
    const doneList = [...moved.slice().reverse(), ...done];
    const inDone = doneList.indexOf(c);
    const col = inDone >= 0 ? 1 : 0;
    const idx = inDone >= 0 ? inDone : todayList.indexOf(c);
    return { x: B.x + B.pad + col * (colW + 20), y: colTop + idx * (rowH + rowGap), col };
}

// 240 frames.
export const Chores: React.FC = () => {
    const f = useCurrentFrame();
    const boardIn = spr(f, 2, { damping: 20, stiffness: 100 });

    const step = moves.filter((m) => f >= m.moveAt! + 6).length;
    const lastMove = moves[step - 1];
    const reflow = lastMove ? spr(f, lastMove.moveAt! + 6, springs.snappy) : 1;

    const sent = f >= NOTIFY;

    return (
        <AbsoluteFill style={{ fontFamily: fonts.sans }}>
            <WarmBg />
            <Sfx at={2} name="swish" volume={0.4} />
            {moves.map((m, i) => (
                <React.Fragment key={m.id}>
                    <Sfx at={m.moveAt!} name="tick" volume={0.7} rate={1 + i * 0.1} />
                    <Sfx at={m.moveAt! + 6} name="swish" volume={0.25} rate={1.2} />
                </React.Fragment>
            ))}
            <Sfx at={NOTIFY} name="click" volume={0.6} />
            <Sfx at={NOTIFY + 2} name="chime" volume={0.5} />
            <Sfx at={222} name="whoosh" volume={0.5} />

            <div style={{ position: 'absolute', left: 100, top: 250, width: 540 }}>
                <Eyebrow icon="✅" label="Chores" delay={6} />
                <Headline segments={['Jobs done.', { accent: 'No nagging.', colour: brand.mint }]} delay={12} size={96} style={{ marginTop: 30 }} />
                <Sub delay={32} style={{ marginTop: 26 }}>
                    Assign chores, tick them off, and see who's pulling their weight. Need a nudge? One tap reminds everyone.
                </Sub>
            </div>

            <div
                style={{
                    position: 'absolute',
                    left: B.x,
                    top: B.y,
                    transform: `translateX(${(1 - boardIn) * 1000}px) rotate(${(1 - boardIn) * 5}deg)`,
                }}
            >
                <Card style={{ width: B.w, height: B.h, padding: B.pad }}>
                    <div style={{ height: 74, display: 'flex', alignItems: 'flex-start' }}>
                        <div>
                            <div style={{ fontSize: 32, fontWeight: 900, letterSpacing: '-0.03em' }}>Chores</div>
                            <div style={{ fontSize: 16, color: light.ink3 }}>This week</div>
                        </div>
                        <div
                            style={{
                                marginLeft: 'auto',
                                height: 56,
                                padding: '0 22px',
                                borderRadius: 17,
                                display: 'flex',
                                alignItems: 'center',
                                gap: 10,
                                fontWeight: 800,
                                fontSize: 18,
                                background: sent ? brand.mint : light.ink,
                                color: sent ? light.ink : light.shell,
                                transform: `scale(${1 - range(f, [NOTIFY - 2, NOTIFY, NOTIFY + 6], [0, 0.08, 0])})`,
                            }}
                        >
                            {sent ? '✓ Reminders sent' : '🔔 Notify Chores'}
                        </div>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 14, height: 136 }}>
                        {people.map((p, i) => {
                            const [d0, total] = weekly[p.initials];
                            const finished = moves.filter((m) => m.who === p && f >= m.moveAt! + 6);
                            const extra = finished.length;
                            const last = finished[extra - 1];
                            // The newest completion grows in rather than jumping.
                            const grow = last ? spr(f, last.moveAt! + 6, springs.smooth) : 0;
                            const intro = spr(f, 16 + i * 4, springs.smooth);
                            const shown = intro * (((d0 + Math.max(0, extra - 1) + grow) / total) * 100);
                            const bump = last ? range(f, [last.moveAt! + 6, last.moveAt! + 12, last.moveAt! + 24], [0, 1, 0]) : 0;
                            return (
                                <div
                                    key={p.initials}
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 14,
                                        padding: 16,
                                        borderRadius: 20,
                                        background: light.panel,
                                        transform: `scale(${1 + bump * 0.05})`,
                                    }}
                                >
                                    <Dial pct={shown} size={84} colour={p.colour} track={light.sunk} inner={light.panel} thickness={9}>
                                        <Avatar person={p} size={46} radius={14} />
                                    </Dial>
                                    <div>
                                        <div style={{ fontSize: 20, fontWeight: 900 }}>{p.name}</div>
                                        <div style={{ fontSize: 15, color: light.ink3, fontFamily: fonts.mono }}>
                                            {d0 + extra}/{total} done
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, marginTop: 24 }}>
                        {[
                            { t: 'Today', n: today.length - step },
                            { t: 'Completed', n: done.length + step },
                        ].map((c) => (
                            <div key={c.t} style={{ display: 'flex', alignItems: 'baseline', gap: 10, fontSize: 20, fontWeight: 900 }}>
                                {c.t}
                                <span style={{ fontFamily: fonts.mono, fontSize: 15, color: light.ink3 }}>{c.n}</span>
                            </div>
                        ))}
                    </div>
                </Card>
            </div>

            {/* Chore cards, positioned absolutely so they can travel between columns */}
            {all.map((c, i) => {
                const from = slot(c, Math.max(0, step - 1));
                const to = slot(c, step);
                const p = step === 0 ? 1 : reflow;
                const x = lerp(p, from.x, to.x);
                const isMover = lastMove === c && p < 1;
                const y = lerp(p, from.y, to.y) - (isMover ? Math.sin(p * Math.PI) * 60 : 0);
                const intro = spr(f, 24 + i * 3, springs.snappy);
                const ticked = to.col === 1 || (c.moveAt !== undefined && f >= c.moveAt);
                const tickP = c.moveAt !== undefined ? prog(f, c.moveAt, c.moveAt + 6) : ticked ? 1 : 0;
                return (
                    <div
                        key={c.id}
                        style={{
                            position: 'absolute',
                            left: x + (1 - boardIn) * 1000,
                            top: y,
                            width: colW,
                            height: rowH,
                            borderRadius: 18,
                            background: light.card,
                            border: `1.5px solid ${light.line}`,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 14,
                            padding: '0 18px',
                            zIndex: isMover ? 10 : 1,
                            opacity: intro,
                            transform: `translateY(${(1 - intro) * 40}px) scale(${1 + (isMover ? Math.sin(p * Math.PI) * 0.06 : 0)}) rotate(${isMover ? Math.sin(p * Math.PI) * 3 : 0}deg)`,
                            boxShadow: isMover ? '0 30px 50px -20px rgba(14,26,43,0.45)' : '0 8px 20px -16px rgba(14,26,43,0.4)',
                        }}
                    >
                        <Tick done={tickP} size={30} />
                        <span
                            style={{
                                flex: 1,
                                fontSize: 19,
                                fontWeight: 700,
                                color: ticked ? light.ink3 : light.ink,
                                textDecoration: ticked ? 'line-through' : undefined,
                            }}
                        >
                            {c.name}
                        </span>
                        <Avatar person={c.who} size={38} radius={11} />
                    </div>
                );
            })}

            {/* "Reminder sent" ripple from the button */}
            {[0, 6, 12].map((d) => {
                const r = prog(f, NOTIFY + d, NOTIFY + d + 30);
                if (f < NOTIFY + d || r >= 1) return null;
                return (
                    <div
                        key={d}
                        style={{
                            position: 'absolute',
                            left: btn.x - 60,
                            top: btn.y - 60,
                            width: 120,
                            height: 120,
                            borderRadius: '50%',
                            border: `4px solid ${brand.mint}`,
                            transform: `scale(${1 + r * 4})`,
                            opacity: 1 - r,
                        }}
                    />
                );
            })}

            <Cursor
                show={[50, 230]}
                clicks={[...moves.map((m) => m.moveAt!), NOTIFY]}
                keys={[
                    { f: 50, x: 700, y: 1060 },
                    ...moves.flatMap((m) => {
                        const s = slot(m, moves.indexOf(m));
                        return [
                            { f: m.moveAt! - 8, x: s.x + 34, y: s.y + rowH / 2 },
                            { f: m.moveAt! + 4, x: s.x + 34, y: s.y + rowH / 2 },
                        ];
                    }),
                    { f: NOTIFY - 6, x: btn.x, y: btn.y },
                    { f: 230, x: btn.x - 30, y: btn.y + 40 },
                ]}
            />
            <CircleWipe start={220} len={20} colour={brand.hero1} x={btn.x} y={btn.y} />
        </AbsoluteFill>
    );
};
