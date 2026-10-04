import React from 'react';
import { Composition } from 'remotion';
import { FPS } from './anim';
import { HouseHubPromo, TOTAL_FRAMES } from './HouseHubPromo';

export const RemotionRoot: React.FC = () => (
    <Composition id="HouseHubPromo" component={HouseHubPromo} durationInFrames={TOTAL_FRAMES} fps={FPS} width={1920} height={1080} />
);
