import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface UserFiltersProps {
    search: string;
    onSearchChange: (value: string) => void;
    roleFilter: string;
    onRoleFilterChange: (value: string) => void;
    availableRoles: Array<{ key: string; label: string }>;
}

export function UserFilters({
    search,
    onSearchChange,
    roleFilter,
    onRoleFilterChange,
    availableRoles,
}: UserFiltersProps) {
    return (
        <div className="mb-6 flex flex-col gap-4 sm:flex-row">
            <div className="flex-1">
                <Label htmlFor="search">Search Users</Label>
                <Input
                    id="search"
                    type="search"
                    placeholder="Search by name or email..."
                    value={search}
                    onChange={(e) => onSearchChange(e.target.value)}
                    className="mt-1"
                />
            </div>

            <div className="sm:w-48">
                <Label htmlFor="role-filter">Filter by Role</Label>
                <Select value={roleFilter} onValueChange={onRoleFilterChange}>
                    <SelectTrigger className="mt-1">
                        <SelectValue placeholder="All roles" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All roles</SelectItem>
                        {availableRoles.map((role) => (
                            <SelectItem key={role.key} value={role.key}>
                                {role.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="flex items-end">
                <Button
                    variant="outline"
                    onClick={() => {
                        onSearchChange('');
                        onRoleFilterChange('');
                    }}
                >
                    Clear Filters
                </Button>
            </div>
        </div>
    );
}
