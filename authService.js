import axios from '@/utils/axios';
import { sanitizeInput } from '@/utils/validators';
import router from '@/router';

const API_URL = '/api/auth';
const TOKEN_KEY = 'auth_token';
const USER_DATA_KEY = 'user_data';

// Configure axios defaults
axios.defaults.withCredentials = true;

// Request interceptor for API calls
axios.interceptors.request.use(async (config) => {
  const authToken = sessionStorage.getItem(TOKEN_KEY);
  if (authToken) {
    config.headers['Authorization'] = `Bearer ${authToken}`;
  }
  config.headers['Accept'] = 'application/json';
  config.headers['Content-Type'] = 'application/json';
  return config;
});

// Response interceptor
axios.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      authService.clearAuthData();
      router.push('/login?error=Unauthorized');
    }
    return Promise.reject(error);
  }
);

const authService = {
  async initializeAuth() {
    try {
      await axios.get(`${API_URL}/init`);
      if (sessionStorage.getItem(TOKEN_KEY)) {
        return await this.getCurrentUser();
      }
      return null;
    } catch (error) {
      console.error('Failed to initialize auth:', error);
      throw error;
    }
  },

  async login({ email, password }) {
    try {
      const userAgent = navigator.userAgent;
      const deviceName = `Vue - ${userAgent}`;
      const sanitizedData = {
        email: sanitizeInput(email.toLowerCase()),
        password,
        device_name: deviceName,
      };

      const response = await axios.post(`${API_URL}/login`, sanitizedData);

      if (response.data.token) {
        sessionStorage.setItem(TOKEN_KEY, response.data.token);
        sessionStorage.setItem(USER_DATA_KEY, JSON.stringify(response.data.user));
        sessionStorage.setItem('device_name', deviceName);
        if (response.data.user && response.data.user.role) {
          localStorage.setItem('userRole', response.data.user.role);
        }
      }

      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async register({ name, email, password, role }) {
    try {
      const sanitizedData = {
        name: sanitizeInput(name),
        email: sanitizeInput(email.toLowerCase()),
        password,
        role,
        device_name: 'web'
      };

      const response = await axios.post(`${API_URL}/register`, sanitizedData);

      if (response.data.token) {
        sessionStorage.setItem(TOKEN_KEY, response.data.token);
        sessionStorage.setItem(USER_DATA_KEY, JSON.stringify(response.data.user));
        if (response.data.user && response.data.user.role) {
          localStorage.setItem('userRole', response.data.user.role);
        }
      }

      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async logout() {
    try {
      const deviceName = sessionStorage.getItem('device_name') || 'web';
      const token = sessionStorage.getItem(TOKEN_KEY);

      if (token) {
        await axios.post(`${API_URL}/logout`, {
          device_name: deviceName
        });
      }
    } catch (error) {
      console.error('Logout failed:', error);
      throw error;
    } finally {
      this.clearAuthData();
      router.push('/login');
    }
  },

  clearAuthData() {
    sessionStorage.removeItem(TOKEN_KEY);
    sessionStorage.removeItem(USER_DATA_KEY);
    sessionStorage.removeItem('device_name');
    localStorage.removeItem('userRole');
  },

  isAuthenticated() {
    return !!sessionStorage.getItem(TOKEN_KEY);
  },

  getStoredUserData() {
    const userData = sessionStorage.getItem(USER_DATA_KEY);
    return userData ? JSON.parse(userData) : null;
  },

  async getCurrentUser() {
    try {
      const response = await axios.get(`${API_URL}/user`);
      sessionStorage.setItem(USER_DATA_KEY, JSON.stringify(response.data.user));
      return response.data.user;
    } catch (error) {
      console.error('Failed to fetch current user:', error);
      throw this.handleError(error);
    }
  },

  async verifyToken() {
    try {
      const response = await axios.get(`${API_URL}/verify`);
      return response.data.valid;
    } catch {
      return false;
    }
  },

  async googleLogin() {
    try {
      const response = await axios.get('https://127.0.0.1:8000/api/auth/google/redirect', {
        headers: {
          'Accept': 'application/json'
        }
      });
      if (response.data.url) {
        window.location.href = response.data.url;
      } else {
        throw new Error('Failed to get Google auth URL');
      }
    } catch (error) {
      console.error('Google sign-up failed:', error);
      throw this.handleError(error);
    }
  },

  async yahooLogin() {
    try {
      const response = await axios.get('https://127.0.0.1:8000/api/auth/yahoo/redirect', {
        headers: {
          'Accept': 'application/json'
        }
      });
      if (response.data.url) {
        window.location.href = response.data.url;
      } else {
        throw new Error('Failed to get Yahoo auth URL');
      }
    } catch (error) {
      console.error('Yahoo sign-up failed:', error);
      throw this.handleError(error);
    }
  },

  handleError(error) {
    if (error.response) {
      switch (error.response.status) {
        case 422:
          return {
            status: 422,
            errors: error.response.data.errors
          };
        case 401:
          this.clearAuthData();
          return {
            status: 401,
            message: 'Invalid credentials'
          };
        case 500:
          return {
            status: 500,
            message: 'An unexpected error occurred. Please try again.'
          };
        default:
          return {
            status: error.response.status,
            message: error.response.data.message || 'An error occurred'
          };
      }
    }
    return {
      status: 0,
      message: 'Network error. Please check your connection.'
    };
  }
};

export default authService;
