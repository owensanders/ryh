import React from 'react';
import { Composition, Folder } from 'remotion';
import { FPS } from './anim';
import { RyhPromo, TOTAL_FRAMES } from './RyhPromo';
import { H, ttDefaults, W } from './tiktok/layout';
import { PROMO_VERTICAL_FRAMES, PromoVertical } from './tiktok/PromoVertical';
import { CALENDAR_FRAMES, TtCalendar } from './tiktok/TtCalendar';
import { CHORES_FRAMES, TtChores } from './tiktok/TtChores';
import { DASHBOARD_FRAMES, TtDashboard } from './tiktok/TtDashboard';
import { MEALS_AI_FRAMES, TtMealPlanner } from './tiktok/TtMealPlanner';
import { MEALS_FRAMES, TtMeals } from './tiktok/TtMeals';
import { SHOPPING_FRAMES, TtShopping } from './tiktok/TtShopping';

export const RemotionRoot: React.FC = () => (
    <>
        <Composition id="RyhPromo" component={RyhPromo} durationInFrames={TOTAL_FRAMES} fps={FPS} width={1920} height={1080} />
        <Composition
            id="RyhPromoComingSoon"
            component={RyhPromo}
            durationInFrames={TOTAL_FRAMES}
            fps={FPS}
            width={1920}
            height={1080}
            defaultProps={{ ending: 'comingSoon' as const }}
        />
        <Folder name="TikTok">
            <Composition
                id="TtPromo"
                component={PromoVertical}
                durationInFrames={PROMO_VERTICAL_FRAMES}
                fps={FPS}
                width={W}
                height={H}
                defaultProps={ttDefaults}
            />
            <Composition
                id="TtDashboard"
                component={TtDashboard}
                durationInFrames={DASHBOARD_FRAMES}
                fps={FPS}
                width={W}
                height={H}
                defaultProps={{ ...ttDefaults, music: false }}
            />
            <Composition
                id="TtCalendar"
                component={TtCalendar}
                durationInFrames={CALENDAR_FRAMES}
                fps={FPS}
                width={W}
                height={H}
                defaultProps={{ ...ttDefaults, music: false }}
            />
            <Composition
                id="TtMealPlanner"
                component={TtMealPlanner}
                durationInFrames={MEALS_AI_FRAMES}
                fps={FPS}
                width={W}
                height={H}
                defaultProps={{ ...ttDefaults, music: false }}
            />
            <Composition id="TtMeals" component={TtMeals} durationInFrames={MEALS_FRAMES} fps={FPS} width={W} height={H} defaultProps={ttDefaults} />
            <Composition
                id="TtShopping"
                component={TtShopping}
                durationInFrames={SHOPPING_FRAMES}
                fps={FPS}
                width={W}
                height={H}
                defaultProps={ttDefaults}
            />
            <Composition
                id="TtChores"
                component={TtChores}
                durationInFrames={CHORES_FRAMES}
                fps={FPS}
                width={W}
                height={H}
                defaultProps={ttDefaults}
            />
        </Folder>
    </>
);
