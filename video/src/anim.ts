import { Easing, interpolate, spring } from 'remotion';

export const FPS = 30;

type SpringConfig = Parameters<typeof spring>[0]['config'];

export const springs = {
    pop: { damping: 11, stiffness: 180, mass: 0.7 },
    snappy: { damping: 18, stiffness: 220, mass: 0.8 },
    smooth: { damping: 200, stiffness: 120 },
    bouncy: { damping: 8, stiffness: 140, mass: 0.8 },
} satisfies Record<string, SpringConfig>;

/** A 0→1 spring that starts at `delay` frames. */
export const spr = (frame: number, delay = 0, config: SpringConfig = springs.snappy, durationInFrames?: number) =>
    spring({ frame: frame - delay, fps: FPS, config, durationInFrames });

const clampOpts = { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' } as const;

/** Eased 0→1 progress between two frames. */
export const prog = (frame: number, from: number, to: number, easing: (t: number) => number = Easing.bezier(0.22, 1, 0.36, 1)) =>
    interpolate(frame, [from, to], [0, 1], { ...clampOpts, easing });

export const lerp = (t: number, a: number, b: number) => a + (b - a) * t;

export const range = (frame: number, input: number[], output: number[], easing?: (t: number) => number) =>
    interpolate(frame, input, output, { ...clampOpts, easing });

/** Standard exit: 1 until `start`, eases to 0 over `len` frames. */
export const exitOut = (frame: number, start: number, len = 12) => 1 - prog(frame, start, start + len, Easing.in(Easing.cubic));
