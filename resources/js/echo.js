import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Echo
window.Pusher = Pusher;

// Configure Echo with fallback pattern for easy switching between Pusher, Reverb, and Ably
window.Echo = new Echo({
    broadcaster:
        import.meta.env.VITE_BROADCAST_CONNECTION === 'reverb'
            ? 'reverb'
            : import.meta.env.VITE_BROADCAST_CONNECTION === 'ably'
              ? 'pusher' // Ably uses pusher protocol compatibility
              : 'pusher',

    // Use fallback pattern for keys - supports Pusher, Reverb, and Ably
    key:
        import.meta.env.VITE_PUSHER_APP_KEY ||
        import.meta.env.VITE_REVERB_APP_KEY ||
        import.meta.env.VITE_ABLY_PUBLIC_KEY, // Ably: use public key part only (before :)

    // Host configuration with fallbacks
    wsHost:
        import.meta.env.VITE_PUSHER_HOST ||
        import.meta.env.VITE_REVERB_HOST ||
        (import.meta.env.VITE_BROADCAST_CONNECTION === 'ably'
            ? 'realtime-pusher.ably.io'
            : (console.warn(
                  'No wsHost configured for broadcaster, using localhost',
              ),
              'localhost')),

    wsPort:
        import.meta.env.VITE_PUSHER_PORT ??
        import.meta.env.VITE_REVERB_PORT ??
        80,

    wssPort:
        import.meta.env.VITE_PUSHER_PORT ??
        import.meta.env.VITE_REVERB_PORT ??
        443,

    // TLS configuration
    forceTLS:
        (import.meta.env.VITE_PUSHER_SCHEME ||
            import.meta.env.VITE_REVERB_SCHEME ||
            (import.meta.env.VITE_BROADCAST_CONNECTION === 'ably'
                ? 'https'
                : null)) === 'https',

    enabledTransports: ['ws', 'wss'],

    // Ably-specific options (safe to include always)
    encrypted:
        import.meta.env.VITE_BROADCAST_CONNECTION === 'ably' || undefined,

    // Pusher-specific options (ignored by Reverb/Ably)
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,

    // Authentication - required for private/presence channels
    auth: {
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content'),
        },
    },

    // Additional options for debugging
    ...(import.meta.env.DEV && {
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                // Custom authorization logic if needed
                console.log(
                    'Authorizing channel:',
                    channel.name,
                    'with socket:',
                    socketId,
                );
                callback(false, {}); // Allow by default in dev
            },
        }),
    }),
});

// Enhanced toast notification system with queue management
window.toastQueue = [];
window.isShowingToast = false;

window.showToast = (message, type = 'info', duration = 5000) => {
    // Add to queue
    window.toastQueue.push({ message, type, duration });

    // Process queue if not already showing
    if (!window.isShowingToast) {
        showNextToast();
    }
};

function showNextToast() {
    if (window.toastQueue.length === 0) {
        window.isShowingToast = false;
        return;
    }

    window.isShowingToast = true;
    const { message, type, duration } = window.toastQueue.shift();

    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className =
            'fixed top-4 right-4 z-50 space-y-2 pointer-events-none';
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toast = document.createElement('div');
    const baseClasses =
        'p-4 rounded-lg shadow-lg max-w-sm border cursor-pointer pointer-events-auto transform translate-x-full transition-all duration-300 ease-out animate-in fade-in slide-in-from-right-4';

    toast.className = `${baseClasses} ${
        type === 'success'
            ? 'bg-green-50 border-green-200 text-green-800 hover:bg-green-100'
            : type === 'error'
              ? 'bg-red-50 border-red-200 text-red-800 hover:bg-red-100'
              : type === 'warning'
                ? 'bg-yellow-50 border-yellow-200 text-yellow-800 hover:bg-yellow-100'
                : 'bg-blue-50 border-blue-200 text-blue-800 hover:bg-blue-100'
    }`;

    // Add icon based on type
    const icon =
        type === 'success'
            ? '✅'
            : type === 'error'
              ? '❌'
              : type === 'warning'
                ? '⚠️'
                : 'ℹ️';

    toast.innerHTML = `
        <div class="flex items-start space-x-3">
            <span class="text-lg flex-shrink-0">${icon}</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium break-words">${message}</p>
            </div>
            <button class="flex-shrink-0 text-gray-400 hover:text-gray-600 text-lg leading-none transition-colors" onclick="event.stopPropagation(); this.parentElement.parentElement.remove()">×</button>
        </div>
        <div class="mt-3 bg-current opacity-20 h-1 rounded-full overflow-hidden">
            <div class="h-full bg-current transition-all ease-linear progress-bar" style="width: 100%; transition-duration: ${duration}ms;"></div>
        </div>
    `;

    // Make entire toast clickable to dismiss
    toast.addEventListener('click', () => {
        dismissToast(toast);
    });

    // Add to container
    toastContainer.appendChild(toast);

    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 10);

    // Start progress bar animation
    setTimeout(() => {
        const progressBar = toast.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = '0%';
        }
    }, 50);

    // Auto remove after specified duration
    const timeoutId = setTimeout(() => {
        if (toast.parentElement) {
            dismissToast(toast);
        }
    }, duration);

    // Store timeout ID for cleanup
    toast._timeoutId = timeoutId;
}

function dismissToast(toast) {
    // Clear timeout if it exists
    if (toast._timeoutId) {
        clearTimeout(toast._timeoutId);
    }

    // Animate out
    toast.classList.add('translate-x-full', 'opacity-0');
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
        // Show next toast in queue
        setTimeout(() => showNextToast(), 100);
    }, 300);
}
