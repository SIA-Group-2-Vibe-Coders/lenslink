// frontend/assets/js/main.js

const API_BASE_URL = window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost'
    ? 'http://127.0.0.1:8000/api/'
    : 'https://lenslink-api-3w31.onrender.com/api/';

const STORAGE_BASE_URL = window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost'
    ? 'http://127.0.0.1:8000/storage/'
    : 'https://lenslink-api-3w31.onrender.com/storage/';

/**
 * Clear auth data from localStorage and redirect to login.
 */
function clearAuthAndRedirect() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    window.location.replace('login.html');
}

/**
 * Synchronous page guard — call this in <head> before body renders.
 * Prevents content flash on protected pages.
 *
 * @param {number|null} requiredRoleId  - null = any authenticated user
 *                                         1   = admin only
 *                                         2   = photographer (or client) only
 */
function guardPage(requiredRoleId = null) {
    try {
        const token   = localStorage.getItem('auth_token');
        const userStr = localStorage.getItem('user');

        if (!token) {
            window.location.replace('login.html');
            return;
        }

        if (requiredRoleId !== null && userStr) {
            const user = JSON.parse(userStr);
            if (!user || !user.role_id) return; // Let async checkAuth handle it

            if (requiredRoleId === 1 && user.role_id != 1) {
                // Non-admin trying to access admin page
                window.location.replace('dashboard.html');
                return;
            }

            if (requiredRoleId === 2 && user.role_id == 1) {
                // Admin trying to access photographer-only page
                window.location.replace('admin.html');
                return;
            }
        }
    } catch (e) {
        // If anything fails, send to login to be safe
        window.location.replace('login.html');
    }
}

/**
 * Generic API Call Function
 */
async function apiCall(endpoint, method = 'GET', data = null) {
    const config = {
        method,
        headers: {
            'Accept': 'application/json'
        }
    };

    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
    }

    if (data && method !== 'GET') {
        if (data instanceof FormData) {
            config.body = data;
        } else {
            config.headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(data);
        }
    }

    try {
        const response = await fetch(API_BASE_URL + endpoint, config);
        const result   = await response.json();

        // 401 Unauthenticated — token is invalid or expired
        if (response.status === 401 && !endpoint.includes('login') && !endpoint.includes('register')) {
            clearAuthAndRedirect();
            return;
        }

        // 403 Forbidden — authenticated but not permitted
        if (response.status === 403) {
            console.warn('Access forbidden:', result.message || 'You do not have permission.');
        }

        return {
            status: response.ok ? 'success' : 'error',
            ...result,
            statusCode: response.status
        };
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

/**
 * Helper to get full storage URL
 */
function getStorageUrl(path) {
    if (!path) return 'https://via.placeholder.com/300?text=No+Image';
    if (path.startsWith('http')) return path;
    return STORAGE_BASE_URL + path;
}

/**
 * Handle Logout
 */
async function logout() {
    try {
        await apiCall('logout', 'POST');
    } catch (e) {
        console.error('Logout failed', e);
    } finally {
        clearAuthAndRedirect();
    }
}

/**
 * Async check Authentication — use after DOM ready for full server-side validation.
 * For synchronous head-level guards, use guardPage() instead.
 */
async function checkAuth(requiredRole = null) {
    try {
        const res = await apiCall('profile', 'GET');
        if (!res || res.status !== 'success') {
            clearAuthAndRedirect();
            return null;
        }

        const user     = res.data;
        const userRole = parseInt(user.role_id);

        if (requiredRole !== null) {
            if (requiredRole === 2) {
                // Allow Photographers (2) and legacy Clients (3)
                if (userRole !== 2 && userRole !== 3) {
                    alert('Unauthorized access');
                    clearAuthAndRedirect();
                    return null;
                }
            } else if (userRole !== requiredRole) {
                alert('Unauthorized access');
                clearAuthAndRedirect();
                return null;
            }
        }

        // Keep localStorage fresh
        localStorage.setItem('user', JSON.stringify(user));
        return user;
    } catch (e) {
        console.warn('Profile sync failed. Operating in offline/cached mode:', e);
        const cachedUserStr = localStorage.getItem('user');
        if (cachedUserStr) {
            try {
                const cachedUser = JSON.parse(cachedUserStr);
                if (cachedUser) {
                    return cachedUser;
                }
            } catch (jsonErr) {}
        }
        clearAuthAndRedirect();
        return null;
    }
}
