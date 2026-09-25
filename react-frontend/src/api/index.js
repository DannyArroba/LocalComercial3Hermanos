import axios from 'axios';

const api = axios.create({
  baseURL: '/api/backend/api',
  withCredentials: true,
});

export const productsApi = {
  getAll: (category = 'todas') => api.get(`/products.php?category=${category}`),
  getCategories: () => api.get('/products.php?categories=true'),
  add: (formData) => api.post('/products_admin.php', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
  update: (formData) => api.post('/products_admin.php', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
  delete: (id) => {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    return api.post('/products_admin.php', formData);
  }
};

export const authApi = {
  login: (email, password) => api.post('/auth.php', { action: 'login', email, password }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  register: (email, password) => api.post('/auth.php', { action: 'register', email, password }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  logout: () => api.post('/auth.php', { action: 'logout' }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  check: () => api.post('/auth.php', { action: 'check' }, {
    headers: { 'Content-Type': 'application/json' }
  }),
};

export const cartApi = {
  get: () => api.get('/cart.php'),
  add: (id, quantity) => api.post('/cart.php', { action: 'add', id, quantity }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  remove: (id) => api.post('/cart.php', { action: 'remove', id }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  update: (id, quantity) => api.post('/cart.php', { action: 'update', id, quantity }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  clear: () => api.post('/cart.php', { action: 'clear' }, {
    headers: { 'Content-Type': 'application/json' }
  }),
};

export const checkoutApi = {
  process: (data) => api.post('/checkout.php', data, {
    headers: { 'Content-Type': 'application/json' }
  }),
};

export const dashboardApi = {
  getStats: (year, month, isAnnual) => api.get('/dashboard.php', { params: { year, month, is_annual: isAnnual } }),
  getPublicStats: () => api.get('/public_stats.php'),
};

export const usersApi = {
  getAll: () => api.get('/users.php'),
  add: (data) => api.post('/users.php', { action: 'add', ...data }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  update: (data) => api.post('/users.php', { action: 'update', ...data }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  delete: (id) => api.post('/users.php', { action: 'delete', id }, {
    headers: { 'Content-Type': 'application/json' }
  }),
};

export const customersApi = {
  getAll: () => api.get('/customers.php'),
  getById: (id) => api.get(`/customers.php?id=${id}`),
  findByIdNumber: (idNumber) => api.get(`/customers.php?id_number=${encodeURIComponent(idNumber)}`),
  add: (data) => api.post('/customers.php', { action: 'add', ...data }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  update: (data) => api.post('/customers.php', { action: 'update', ...data }, {
    headers: { 'Content-Type': 'application/json' }
  }),
  delete: (id) => api.post('/customers.php', { action: 'delete', id }, {
    headers: { 'Content-Type': 'application/json' }
  }),
};

export const transactionsApi = {
  getAll: () => api.get('/transactions.php?action=list'),
  getDetails: (id, type = 'VENTA') => api.get(`/transactions.php?action=details&id=${id}&type=${type}`),
};

export const returnsApi = {
  create: (data) => api.post('/returns.php', data, {
    headers: { 'Content-Type': 'application/json' }
  }),
};

export const suppliersApi = {
  getAll: () => api.get('/suppliers.php'),
  getById: (id) => api.get(`/suppliers.php?id=${id}`),
  add: (formData) => api.post('/suppliers.php', formData),
  update: (formData) => api.post('/suppliers.php', formData),
  delete: (formData) => api.post('/suppliers.php', formData),
};

export const stockPurchasesApi = {
  getAll: () => api.get('/stock_purchases.php'),
  create: (formData) => api.post('/stock_purchases.php', formData),
  markPaid: (purchaseId) => {
    const formData = new FormData();
    formData.append('action', 'mark_paid');
    formData.append('purchase_id', purchaseId);
    return api.post('/stock_purchases.php', formData);
  },
  markReceived: (purchaseId) => {
    const formData = new FormData();
    formData.append('action', 'mark_received');
    formData.append('purchase_id', purchaseId);
    return api.post('/stock_purchases.php', formData);
  },
  cancel: (purchaseId) => {
    const formData = new FormData();
    formData.append('action', 'cancel');
    formData.append('purchase_id', purchaseId);
    return api.post('/stock_purchases.php', formData);
  },
  refund: (purchaseId, reversalReason) => {
    const formData = new FormData();
    formData.append('action', 'refund');
    formData.append('purchase_id', purchaseId);
    formData.append('reversal_reason', reversalReason);
    return api.post('/stock_purchases.php', formData);
  },
};

export default api;
