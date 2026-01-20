import { Head, useForm } from '@inertiajs/react';
import React from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface User {
    id: number;
    first_name?: string;
    last_name?: string;
    email: string;
    full_name?: string;
}

export default function RoleAssignment({
    users,
    roles,
}: {
    users: User[];
    roles: Record<string, string>;
}) {
    const { data, setData, post, processing, errors } = useForm({
        selectedUser: '',
        selectedRole: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/roles/assign');
    };

    return (
        <>
            <Head title="Role Assignment" />
            <div className="container mx-auto px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">Role Assignment</h1>

                <Card className="max-w-md">
                    <CardHeader>
                        <CardTitle>Assign Role to User</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label>Select User</Label>
                                <Select
                                    value={data.selectedUser}
                                    onValueChange={(value) =>
                                        setData('selectedUser', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choose User" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((user) => (
                                            <SelectItem
                                                key={user.id}
                                                value={user.id.toString()}
                                            >
                                                {user.full_name || user.email}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.selectedUser && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.selectedUser}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>Assign Role</Label>
                                <Select
                                    value={data.selectedRole}
                                    onValueChange={(value) =>
                                        setData('selectedRole', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(roles).map(
                                            ([key, label]) => (
                                                <SelectItem
                                                    key={key}
                                                    value={key}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                {errors.selectedRole && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.selectedRole}
                                    </p>
                                )}
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Assigning...' : 'Assign Role'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
