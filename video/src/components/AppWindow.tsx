import React from 'react';
import { useCurrentFrame } from 'remotion';
import { spr, springs } from '../anim';
import { family } from '../theme';
import { brand, fonts, light, Palette } from '../theme';
import { Avatar, LogoMark } from './ui';

const nav = [
    { icon: '🏠', label: 'Dashboard' },
    { icon: '📅', label: 'Calendar' },
    { icon: '🍽', label: 'Meal Planner' },
    { icon: '🛒', label: 'Shopping Lists' },
    { icon: '✅', label: 'Chores' },
    { icon: '💰', label: 'Budget' },
    { icon: '🏡', label: 'House' },
    { icon: '📂', label: 'Documents' },
    { icon: '⚙', label: 'Settings' },
];

/** Browser chrome + the HouseHubLayout sidebar and header. */
export const AppWindow: React.FC<{
    width: number;
    height: number;
    active: string;
    title: string;
    subtitle: string;
    palette?: Palette;
    navDelay?: number;
    bellRing?: number;
    unread?: number;
    children: React.ReactNode;
}> = ({ width, height, active, title, subtitle, palette = light, navDelay = 8, bellRing = 0, unread = 0, children }) => {
    const f = useCurrentFrame();
    return (
        <div
            style={{
                width,
                height,
                borderRadius: 30,
                overflow: 'hidden',
                background: palette.shell,
                border: `1.5px solid ${palette.line}`,
                boxShadow: '0 60px 120px -40px rgba(14, 26, 43, 0.55), 0 20px 40px -20px rgba(14,26,43,0.25)',
                display: 'flex',
                flexDirection: 'column',
                fontFamily: fonts.sans,
                color: palette.ink,
            }}
        >
            <div style={{ height: 46, flex: 'none', display: 'flex', alignItems: 'center', gap: 9, padding: '0 20px', borderBottom: `1.5px solid ${palette.line}`, background: palette.panel }}>
                {['#ff5f57', '#febc2e', '#28c840'].map((c) => (
                    <span key={c} style={{ width: 13, height: 13, borderRadius: '50%', background: c }} />
                ))}
                <div
                    style={{
                        margin: '0 auto',
                        width: 420,
                        height: 28,
                        borderRadius: 9,
                        background: palette.soft,
                        color: palette.ink3,
                        fontFamily: fonts.mono,
                        fontSize: 14,
                        display: 'grid',
                        placeItems: 'center',
                    }}
                >
                    househub.app/{active.toLowerCase().split(' ')[0]}
                </div>
            </div>

            <div style={{ flex: 1, display: 'flex', minHeight: 0 }}>
                <div style={{ width: 262, flex: 'none', padding: '26px 18px', borderRight: `1.5px solid ${palette.line}`, display: 'flex', flexDirection: 'column', gap: 5 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '0 8px 22px' }}>
                        <LogoMark size={40} radius={13} />
                        <span style={{ fontWeight: 800, fontSize: 21, letterSpacing: '-0.02em' }}>HouseHub</span>
                    </div>
                    {nav.map((item, i) => {
                        const p = spr(f, navDelay + i * 2, springs.snappy);
                        const on = item.label === active;
                        return (
                            <div
                                key={item.label}
                                style={{
                                    height: 46,
                                    borderRadius: 13,
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 12,
                                    padding: '0 13px',
                                    fontSize: 17,
                                    fontWeight: on ? 800 : 600,
                                    background: on ? palette.ink : 'transparent',
                                    color: on ? palette.shell : palette.ink2,
                                    opacity: p,
                                    transform: `translateX(${(1 - p) * -40}px)`,
                                }}
                            >
                                <span style={{ fontSize: 18 }}>{item.icon}</span>
                                {item.label}
                            </div>
                        );
                    })}
                </div>

                <div style={{ flex: 1, minWidth: 0, display: 'flex', flexDirection: 'column', padding: '22px 30px 28px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 14, marginBottom: 20, flex: 'none' }}>
                        <div>
                            <div style={{ fontSize: 30, fontWeight: 900, letterSpacing: '-0.03em' }}>{title}</div>
                            <div style={{ fontSize: 16, color: palette.ink3, marginTop: 2 }}>{subtitle}</div>
                        </div>
                        <div style={{ flex: 1 }} />
                        <div
                            style={{
                                position: 'relative',
                                width: 48,
                                height: 48,
                                borderRadius: 15,
                                background: palette.soft,
                                display: 'grid',
                                placeItems: 'center',
                                fontSize: 22,
                                transform: `rotate(${Math.sin(bellRing * Math.PI * 8) * 22 * (1 - bellRing)}deg)`,
                            }}
                        >
                            🔔
                            {unread > 0 && (
                                <span
                                    style={{
                                        position: 'absolute',
                                        top: -6,
                                        right: -6,
                                        minWidth: 24,
                                        height: 24,
                                        borderRadius: 12,
                                        background: brand.coral,
                                        color: 'white',
                                        fontSize: 13,
                                        fontWeight: 800,
                                        display: 'grid',
                                        placeItems: 'center',
                                        border: `3px solid ${palette.shell}`,
                                    }}
                                >
                                    {unread}
                                </span>
                            )}
                        </div>
                        <Avatar person={family.sarah} size={48} radius={15} />
                    </div>
                    <div style={{ flex: 1, minHeight: 0, position: 'relative' }}>{children}</div>
                </div>
            </div>
        </div>
    );
};
