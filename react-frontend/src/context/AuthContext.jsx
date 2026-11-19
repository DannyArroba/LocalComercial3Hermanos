import React, { createContext, useContext, useState, useEffect, useCallback, useRef } from 'react';
import { authApi } from '../api';

const AuthContext = createContext({
  user: null,
  loading: true,
  login: () => {},
  register: () => {},
  logout: () => {},
  refreshUser: () => {}
});

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const authRequest = useRef(0);

  const checkAuth = useCallback(async () => {
    const request = ++authRequest.current;
    try {
      const res = await authApi.check();
      if (request !== authRequest.current) return;
      setUser(res.data.status === 'success' ? res.data.user : null);
    } catch (err) {
      if (request !== authRequest.current) return;
      if (err.response?.status === 401) setUser(null);
      else console.error('Auth check failed', err);
    } finally {
      if (request === authRequest.current) setLoading(false);
    }
  }, []);

  useEffect(() => {
    checkAuth();
    const interval = window.setInterval(checkAuth, 30000);
    window.addEventListener('focus', checkAuth);
    return () => {
      authRequest.current += 1;
      window.clearInterval(interval);
      window.removeEventListener('focus', checkAuth);
    };
  }, [checkAuth]);

  const login = async (email, password) => {
    ++authRequest.current;
    const res = await authApi.login(email, password);
    if (res.data.status === 'success') {
      setUser(res.data.user);
    }
    return res.data;
  };

  const register = async (email, password) => {
    const res = await authApi.register(email, password);
    return res.data;
  };

  const logout = async () => {
    ++authRequest.current;
    await authApi.logout();
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, refreshUser: checkAuth }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
