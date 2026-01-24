import { Skeleton } from '@/components/ui/skeleton';

interface SkeletonTableProps {
    rows?: number;
    columns?: number;
    showHeader?: boolean;
    className?: string;
}

export function SkeletonTable({
    rows = 5,
    columns = 4,
    showHeader = true,
    className = ''
}: SkeletonTableProps) {
    return (
        <div className={`space-y-4 ${className}`}>
            {/* Table Header Skeleton */}
            {showHeader && (
                <div className="flex space-x-4">
                    {Array.from({ length: columns }).map((_, i) => (
                        <Skeleton key={`header-${i}`} className="h-6 flex-1" />
                    ))}
                </div>
            )}

            {/* Table Rows Skeleton */}
            <div className="space-y-3">
                {Array.from({ length: rows }).map((_, rowIndex) => (
                    <div key={`row-${rowIndex}`} className="flex space-x-4">
                        {Array.from({ length: columns }).map((_, colIndex) => (
                            <Skeleton
                                key={`cell-${rowIndex}-${colIndex}`}
                                className="h-4 flex-1"
                                style={{
                                    width: colIndex === 0 ? '60%' : colIndex === columns - 1 ? '80%' : '100%'
                                }}
                            />
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
}

export function SkeletonCard({ className = '' }: { className?: string }) {
    return (
        <div className={`space-y-4 p-6 border rounded-lg ${className}`}>
            <div className="space-y-2">
                <Skeleton className="h-6 w-3/4" />
                <Skeleton className="h-4 w-1/2" />
            </div>
            <div className="space-y-2">
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-5/6" />
                <Skeleton className="h-4 w-4/6" />
            </div>
        </div>
    );
}

export function SkeletonStats({ count = 4, className = '' }: { count?: number; className?: string }) {
    return (
        <div className={`grid gap-4 md:grid-cols-2 lg:grid-cols-${Math.min(count, 4)} ${className}`}>
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="p-6 border rounded-lg space-y-2">
                    <Skeleton className="h-4 w-24" />
                    <Skeleton className="h-8 w-16" />
                    <Skeleton className="h-3 w-32" />
                </div>
            ))}
        </div>
    );
}