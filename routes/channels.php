<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Admin private channels for role change notifications
Broadcast::channel('admin.{adminId}', function (User $user, int $adminId) {
    // Only allow the admin user to listen to their own admin channel
    return $user->id === $adminId && $user->hasRole(['Super Admin', 'System Administrator']);
});
