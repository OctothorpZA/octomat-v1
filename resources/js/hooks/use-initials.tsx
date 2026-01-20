import { useCallback } from 'react';

export function useInitials() {
    return useCallback((fullName?: string): string => {
        // 1. Clean the input and handle null/undefined
        const cleanedName = fullName?.trim() ?? '';

        // 2. Return early if empty
        if (!cleanedName) return '';

        // 3. Split by any whitespace and filter out any empty strings
        const names = cleanedName.split(/\s+/).filter(Boolean);

        if (names.length === 1) {
            return names[0][0].toUpperCase();
        }

        const firstInitial = names[0][0];
        const lastInitial = names[names.length - 1][0];

        return (firstInitial + lastInitial).toUpperCase();
    }, []);
}
