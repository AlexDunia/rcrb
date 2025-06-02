import axios from 'axios';
import { clearSensitiveData, sanitizeInput, validateEmail, securityHeaders } from '@/utils/security';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Configure axios defaults
axios.defaults.withCredentials = true;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Request interceptor for security
axios.interceptors.request.use(async (config) => {
    // Add security headers
    config.headers['X-Requested-With'] = 'XMLHttpRequest';
    config.headers['Accept'] = 'application/json';

    // Prevent caching of sensitive routes
    if (config.url?.includes('/auth/')) {
        config.headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
        config.headers['Pragma'] = 'no-cache';
    }

    // Add Authorization header if token exists
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
    }

    return config;
});

// Response interceptor for security checks
axios.interceptors.response.use(
    (response) => {
        // Validate content type for security
        const contentType = response.headers['content-type'];
        if (contentType && !contentType.includes('application/json')) {
            throw new Error('Invalid response type received');
        }
        return response;
    },
    (error) => {
        // Handle security-related errors
        if (error.response?.status === 401 || error.response?.status === 419) {
            // Clear sensitive data and force logout on auth errors
            clearSensitiveData();
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

const authService = {
    async initializeAuth() {
        try {
            // Initialize CSRF protection
            const response = await axios.get(`${API_URL}/auth/init`, {
                withCredentials: true
            });

            // Set the CSRF token in axios headers
            const token = response.data.csrf_token;
            if (token) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
            }

            return true;
        } catch (error) {
            console.error('Failed to initialize auth:', error);
            return false;
        }
    },

    async register(userData) {
        try {
            // Initialize auth first
            await this.initializeAuth();

            const response = await axios.post(`${API_URL}/auth/register`, {
                ...userData,
                device_name: await this.generateFingerprint()
            });

            if (response.data.token) {
                localStorage.setItem('auth_token', response.data.token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
            }

            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    },

    async login(credentials) {
        try {
            // Input validation
            if (!credentials.email || !credentials.password) {
                throw new Error('Invalid credentials format');
            }

            // Validate email format
            if (!validateEmail(credentials.email)) {
                throw new Error('Invalid email format');
            }

            // Sanitize inputs
            const sanitizedEmail = sanitizeInput(credentials.email);

            // Initialize auth first
            await this.initializeAuth();

            const response = await axios.post(`${API_URL}/auth/login`, {
                email: sanitizedEmail,
                password: credentials.password,
                device_name: await this.generateFingerprint()
            });

            if (response.data.token) {
                localStorage.setItem('auth_token', response.data.token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
            }

            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    },

    async getCurrentUser() {
        try {
            const response = await axios.get(`${API_URL}/auth/user`);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    },

    async verifyToken() {
        try {
            const response = await axios.get(`${API_URL}/auth/verify`);
            return response.data;
        } catch (error) {
            this.logout();
            throw this.handleError(error);
        }
    },

    async logout() {
        try {
            await axios.post(`${API_URL}/auth/logout`);
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Clear all sensitive data
            clearSensitiveData();
            delete axios.defaults.headers.common['Authorization'];
            // Redirect to login
            window.location.href = '/login';
        }
    },

    // Generate secure device fingerprint
    async generateFingerprint() {
        const fpData = [
            navigator.userAgent,
            navigator.language,
            screen.width,
            screen.height,
            new Date().getTimezoneOffset(),
            navigator.hardwareConcurrency,
            navigator.deviceMemory,
            navigator.platform
        ].join('|');

        // Use SubtleCrypto for secure hash
        const msgBuffer = new TextEncoder().encode(fpData);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    },

    // Check authentication status
    async isAuthenticated() {
        try {
            await this.verifyToken();
            return true;
        } catch {
            return false;
        }
    },

    handleError(error) {
        if (error.response) {
            // Handle specific error cases
            switch (error.response.status) {
                case 401:
                case 419:
                    this.logout();
                    return {
                        status: error.response.status,
                        message: 'Session expired. Please login again.',
                    };
                case 422:
                    return {
                        status: error.response.status,
                        message: 'Validation failed',
                        errors: error.response.data.errors || {}
                    };
                case 429:
                    return {
                        status: error.response.status,
                        message: 'Too many attempts. Please try again later.',
                        retryAfter: parseInt(error.response.headers['retry-after']) || 300
                    };
                default:
                    return {
                        status: error.response.status,
                        message: error.response.data.message || 'An error occurred',
                        errors: error.response.data.errors || {}
                    };
            }
        }

        return {
            status: 500,
            message: 'Network error occurred',
            errors: {}
        };
    }
};

export default authService;
