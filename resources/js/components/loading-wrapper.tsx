import React from 'react';
import { useState } from 'react';

import {
    SkeletonCard,
    SkeletonStats,
    SkeletonTable,
} from '@/components/ui/skeleton-table';

interface LoadingWrapperProps {
    loading: boolean;
    skeletonType?: 'table' | 'card' | 'stats' | 'custom';
    skeletonProps?: {
        component?: React.ReactNode;
        [key: string]: unknown;
    };
    children: React.ReactNode;
    className?: string;
}

export function LoadingWrapper({
    loading,
    skeletonType = 'card',
    skeletonProps = {},
    children,
    className = '',
}: LoadingWrapperProps) {
    if (loading) {
        switch (skeletonType) {
            case 'table':
                return (
                    <SkeletonTable {...skeletonProps} className={className} />
                );
            case 'card':
                return <SkeletonCard className={className} />;
            case 'stats':
                return (
                    <SkeletonStats {...skeletonProps} className={className} />
                );
            case 'custom':
                return (
                    <div className={className}>{skeletonProps.component}</div>
                );
            default:
                return <SkeletonCard className={className} />;
        }
    }

    return <>{children}</>;
}

// Hook for managing loading states
export function useLoadingState<T = unknown>(initialLoading = true, delay = 0) {
    const [loading, setLoading] = useState(initialLoading);
    const [data, setData] = useState<T | null>(null);

    const startLoading = () => setLoading(true);
    const stopLoading = () => setLoading(false);
    const setDataAndStopLoading = (newData: T) => {
        setData(newData);
        if (delay > 0) {
            setTimeout(() => setLoading(false), delay);
        } else {
            setLoading(false);
        }
    };

    return {
        loading,
        data,
        startLoading,
        stopLoading,
        setDataAndStopLoading,
    };
}

// Page-level loading wrapper
export function PageLoadingWrapper({
    loading,
    title = 'Loading...',
    children,
}: {
    loading: boolean;
    title?: string;
    children: React.ReactNode;
}) {
    if (loading) {
        return (
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-bold">{title}</h1>
                    <p className="text-muted-foreground">
                        Please wait while we load your content.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <SkeletonStats count={4} />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <SkeletonTable rows={8} columns={4} />
                    <SkeletonCard />
                </div>
            </div>
        );
    }

    return <>{children}</>;
}
