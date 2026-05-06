// frontend/assets/js/main.js

const API_BASE_URL = window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost' 
    ? 'http://127.0.0.1:8000/api/' 
    : '/api/'; // Assumes API is hosted on the same domain in production

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
            // Fetch handles Content-Type for FormData automatically
            config.body = data;
        } else {
            config.headers['Content-Type'] = 'application/json';
            config.body = JSON.stringify(data);
        }
    }

    try {
        const response = await fetch(API_BASE_URL + endpoint, config);
        const result = await response.json();
        
        // Handle unauthorized by redirecting
        if (response.status === 401 && !endpoint.includes('login') && !endpoint.includes('register')) {
            window.location.href = 'login.html';
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
    // If it's already a URL, return it
    if (path.startsWith('http')) return path;
    
    const base = API_BASE_URL.replace('/api/', '/storage/');
    return base + path;
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
        localStorage.removeItem('auth_token');
        window.location.href = 'login.html';
    }
}

/**
 * Check Authentication
 */
async function checkAuth(requiredRole = null) {
    try {
        const res = await apiCall('profile', 'GET');
        if (res.status !== 'success') {
            window.location.href = 'login.html';
            return null;
        }
        
        if (requiredRole && res.data.role_id != requiredRole) {
            alert('Unauthorized access');
            window.location.href = 'login.html';
            return null;
        }
        
        return res.data;
    } catch (e) {
        window.location.href = 'login.html';
        return null;
    }
}
