import { describe, expect, it } from 'vitest';
import { dateLine, greeting } from './clock';

describe('greeting', () => {
    it('turns over at noon and at six, on the reader’s own clock', () => {
        expect(greeting(new Date(2026, 9, 23, 11, 59))).toBe('Good morning');
        expect(greeting(new Date(2026, 9, 23, 12, 0))).toBe('Good afternoon');
        expect(greeting(new Date(2026, 9, 23, 17, 59))).toBe('Good afternoon');
        expect(greeting(new Date(2026, 9, 23, 18, 0))).toBe('Good evening');
    });
});

describe('dateLine', () => {
    it('reads like a UK date', () => {
        expect(dateLine(new Date(2026, 9, 22))).toBe('Thursday 22 October');
    });
});
