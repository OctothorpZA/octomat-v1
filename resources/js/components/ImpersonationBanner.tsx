import { Link, usePage } from '@inertiajs/react';
import { AlertTriangle, X } from 'lucide-react';

import { Alert, AlertDescription } from '@/components/ui/alert';

export function ImpersonationBanner() {
    const { impersonate } = usePage().props;

    const { isImpersonating, originalUser } =
        (impersonate as
            | { isImpersonating: boolean; originalUser: User | null }
            | undefined) || {};

    if (!isImpersonating || !originalUser) return null;

    return (
        <Alert className="border-orange-200 bg-orange-50">
            <AlertTriangle className="h-4 w-4" />
            <AlertDescription className="flex items-center justify-between">
                <span>
                    You are impersonating <strong>{originalUser.name}</strong>.
                    All actions will be performed as this user.
                </span>
                <Link
                    href="/impersonate/leave"
                    method="post"
                    as="button"
                    className="ml-4 inline-flex items-center rounded-md bg-orange-600 px-3 py-1 text-sm font-medium text-white hover:bg-orange-700"
                    onClick={(e: React.MouseEvent) => {
                        if (
                            !confirm(
                                'Are you sure you want to stop impersonating?',
                            )
                        ) {
                            e.preventDefault();
                        }
                    }}
                >
                    <X className="mr-1 h-3 w-3" />
                    Stop Impersonating
                </Link>
            </AlertDescription>
        </Alert>
    );
}
