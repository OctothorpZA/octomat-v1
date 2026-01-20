import { renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { useInitials } from '../use-initials';

describe('useInitials', () => {
    it('should return the initials for a standard two-word name', () => {
        const { result } = renderHook(() => useInitials());
        const getInitials = result.current;
        expect(getInitials('John Doe')).toBe('JD');
    });

    it('should handle single names', () => {
        const { result } = renderHook(() => useInitials());
        expect(result.current('Prince')).toBe('P');
    });

    it('should handle more than two names by taking the first and last', () => {
        const { result } = renderHook(() => useInitials());
        expect(result.current('John Quincy Adams')).toBe('JA');
    });

    it('should handle multiple spaces and irregular whitespace', () => {
        const { result } = renderHook(() => useInitials());
        expect(result.current('  Jane    Smith  ')).toBe('JS');
    });

    it('should return an empty string for undefined or empty input', () => {
        const { result } = renderHook(() => useInitials());
        expect(result.current(undefined)).toBe('');
        expect(result.current('')).toBe('');
        expect(result.current('   ')).toBe('');
    });

    it('should always return uppercase initials', () => {
        const { result } = renderHook(() => useInitials());
        expect(result.current('lebron james')).toBe('LJ');
    });
});
