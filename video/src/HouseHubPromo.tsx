import React from 'react';
import { AbsoluteFill, Audio, interpolate, Sequence, staticFile } from 'remotion';
import { fonts } from './theme';
import { Calendar } from './scenes/Calendar';
import { Chores } from './scenes/Chores';
import { Dashboard } from './scenes/Dashboard';
import { Hook } from './scenes/Hook';
import { Intro } from './scenes/Intro';
import { MealPlanner } from './scenes/MealPlanner';
import { Montage } from './scenes/Montage';
import { Notifications } from './scenes/Notifications';
import { Outro } from './scenes/Outro';
import { Phone } from './scenes/Phone';
import { Shopping } from './scenes/Shopping';

// Scene lengths are whole bars of the 120 BPM track (1 bar = 60 frames), so
// every cut lands on a downbeat. The music's drop is at the dashboard (bar 5).
const scenes: { id: string; frames: number; Component: React.FC }[] = [
    { id: 'Intro', frames: 120, Component: Intro },
    { id: 'Hook', frames: 180, Component: Hook },
    { id: 'Dashboard', frames: 240, Component: Dashboard },
    { id: 'MealPlanner', frames: 240, Component: MealPlanner },
    { id: 'Shopping', frames: 180, Component: Shopping },
    { id: 'Calendar', frames: 180, Component: Calendar },
    { id: 'Chores', frames: 240, Component: Chores },
    { id: 'Notifications', frames: 120, Component: Notifications },
    { id: 'Phone', frames: 360, Component: Phone },
    { id: 'Montage', frames: 240, Component: Montage },
    // Six bars: the price holds for three, the logo lands on the final chord.
    { id: 'Outro', frames: 360, Component: Outro },
];

export const TOTAL_FRAMES = scenes.reduce((a, s) => a + s.frames, 0);

export const HouseHubPromo: React.FC = () => {
    let from = 0;
    return (
        <AbsoluteFill style={{ background: '#07090f', fontFamily: fonts.sans }}>
            <Audio
                src={staticFile('audio/music.wav')}
                volume={(f) => interpolate(f, [0, 10, TOTAL_FRAMES - 30, TOTAL_FRAMES], [0, 0.55, 0.55, 0], { extrapolateRight: 'clamp' })}
            />
            {scenes.map(({ id, frames, Component }) => {
                const start = from;
                from += frames;
                return (
                    <Sequence key={id} name={id} from={start} durationInFrames={frames}>
                        <Component />
                    </Sequence>
                );
            })}
        </AbsoluteFill>
    );
};
