# Real-Time Broadcasting Implementation

This document explains how to set up real-time notifications for role changes using the Pusher/Ably → Laravel Cloud Reverb hybrid approach.

## Overview

The system implements real-time broadcasting for:

- ✅ Role assignments (live notifications)
- ✅ Role removals (live notifications)
- ✅ Audit logging (database persistence)
- ✅ Toast notifications (UI feedback)

## Architecture

**Hybrid Approach:**

- **Local Development:** Pusher (free sandbox tier) or Ably (alternative)
- **Production:** Laravel Cloud Reverb (managed WebSocket server)
- **Seamless Migration:** Single environment variable change

## Setup Instructions

### 1. Install Dependencies

```bash
composer require ably/laravel-broadcaster
npm install laravel-echo pusher-js
```

### 2. Configure Broadcasting

Add to your `.env` file:

```env
# Broadcasting Configuration
BROADCAST_CONNECTION=ably

# Ably (Laravel Broadcasting Integration - Pusher protocol compatible)
ABLY_KEY=your_full_ably_key

# Alternative: Pusher (Local Development - FREE sandbox tier)
# BROADCAST_CONNECTION=pusher
# PUSHER_APP_ID=your_pusher_app_id
# PUSHER_APP_KEY=your_pusher_app_key
# PUSHER_APP_SECRET=your_pusher_app_secret
# PUSHER_APP_CLUSTER=mt1

# Frontend Environment Variables
VITE_BROADCAST_CONNECTION=ably
VITE_ABLY_PUBLIC_KEY=your_ably_public_key_part  # Only part before ':' in full key

# Alternative: Pusher frontend vars
# VITE_BROADCAST_CONNECTION=pusher
# VITE_PUSHER_APP_KEY=your_pusher_app_key
# VITE_PUSHER_APP_CLUSTER=mt1
```

### 3. Get Broadcasting Credentials

**For Ably (Primary Recommendation):**

1. Go to [Ably Dashboard](https://ably.com/)
2. Create an app and enable "Pusher protocol support"
3. Copy the full API key to `ABLY_KEY` and the public part (before `:`) to `VITE_ABLY_PUBLIC_KEY`

**For Pusher (Alternative):**

1. Go to [Pusher Dashboard](https://dashboard.pusher.com/)
2. Create a new app or use sandbox (free tier: 100 connections, 200k messages/day)
3. Copy the credentials to your `.env`

### 4. Build Frontend

```bash
npm run build
```

### 5. Test Real-Time Features

1. Open two browser tabs to `/admin/roles/assign`
2. Login as Super Admin in both tabs
3. In one tab, assign/remove a role
4. Watch the other tab update in real-time!

## Production Deployment (Laravel Cloud)

When deploying to Laravel Cloud:

1. **Attach WebSocket Cluster:**
    - Go to Laravel Cloud → Organization → Resources → WebSockets
    - Click "New WebSocket cluster"
    - Attach to your app environment

2. **Update Environment Variables:**

    ```env
    BROADCAST_CONNECTION=reverb
    # Remove Pusher credentials
    # Laravel Cloud auto-injects REVERB_* variables
    ```

3. **Frontend Environment:**
    ```env
    VITE_BROADCAST_CONNECTION=reverb
    # VITE_REVERB_APP_KEY will be auto-injected
    ```

## Features Implemented

### ✅ Broadcast Events

- `RoleAssigned` - Fired when roles are assigned
- `RoleRemoved` - Fired when roles are removed

### ✅ Private Channels

- `admin.{adminId}` - Private channel per admin user
- Secured authorization in `routes/channels.php`

### ✅ Real-Time UI Updates

- Live toast notifications
- Automatic page refresh on role changes
- Console logging for debugging

### ✅ Fallback Pattern

- Frontend automatically uses Pusher or Reverb
- Environment variable controls broadcaster
- No code changes needed for production

## File Structure

```
app/
├── Events/
│   ├── RoleAssigned.php     # Broadcast role assignment
│   └── RoleRemoved.php      # Broadcast role removal
├── Http/Controllers/Admin/
│   └── RoleAssignmentController.php  # Dispatches events

routes/
├── channels.php             # Channel authorization
└── web.php                  # Routes (unchanged)

resources/js/
├── echo.js                  # Echo configuration + toast system
├── app.tsx                 # Echo import
└── pages/admin/
    └── role-assignment.tsx  # Real-time listeners

config/
└── broadcasting.php         # Broadcasting configuration
```

## Testing

Run the existing audit tests to verify broadcasting:

```bash
php artisan test --filter="audit"
```

The tests will create real audit logs, and if broadcasting is working, you'll see real-time updates in open browser tabs.

## Migration Path

**Current:** Event dispatching + database logging
**Future:** WebSocket broadcasting + database logging

**Zero Breaking Changes:** Existing functionality works with or without broadcasting.

## Troubleshooting

### No Real-Time Updates

- Check Pusher credentials in `.env`
- Verify `BROADCAST_CONNECTION=pusher`
- Check browser console for Echo errors
- Ensure user is logged in as admin

### Authentication Errors

- Check `routes/channels.php` authorization
- Verify admin has required roles
- Check CSRF token in requests

### Production Issues

- Verify Laravel Cloud WebSocket cluster is attached
- Check auto-injected `REVERB_*` environment variables
- Ensure `VITE_BROADCAST_CONNECTION=reverb`

## Future Enhancements

- **Presence Channels:** Show online admins
- **Typing Indicators:** Real-time collaboration signals
- **Notification History:** Persistent notification center
- **Push Notifications:** Browser/desktop notifications

## Cost Analysis

**Pusher (Local Development):**

- Sandbox: 100 connections, 200k messages/day FREE
- Startup: $49/month for 500 connections

**Ably (Alternative):**

- Free tier: 3 million messages/month FREE
- Startup: $29/month for higher limits
- Good alternative to Pusher

**Laravel Cloud Reverb (Production):**

- Included with Laravel Cloud hosting
- Scales automatically with usage
- Often 30-50% cheaper than Pusher

**Total Cost:** ~$0 for first 1-2 months, then minimal ongoing costs.
