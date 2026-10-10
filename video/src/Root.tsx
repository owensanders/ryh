import React from 'react';
import { Composition } from 'remotion';
import { FPS } from './anim';
import { RyhPromo, TOTAL_FRAMES } from './RyhPromo';

export const RemotionRoot: React.FC = () => (
    <Composition id="RyhPromo" component={RyhPromo} durationInFrames={TOTAL_FRAMES} fps={FPS} width={1920} height={1080} />
);
