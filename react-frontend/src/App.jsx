import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { CartProvider } from './context/CartContext';
import Navbar from './components/Navbar';
import CartDrawer from './components/CartDrawer';
import Home from './pages/Home';
import Login from './pages/Login';
import Checkout from './pages/Checkout';
import Inventory from './pages/Inventory';
import Sales from './pages/Sales';
import Dashboard from './pages/Dashboard';
import Users from './pages/Users';
import Support from './pages/Support';
import Help from './pages/Help';
import Terms from './pages/Terms';
import Transactions from './pages/Transactions';
import Customers from './pages/Customers';
import Suppliers from './pages/Suppliers';
import { useAuth } from './context/AuthContext';
import { canAccess, firstAllowedPath } from './utils/access';

function NoAccess() {
  return <main className="max-w-xl mx-auto px-5 py-16 text-center"><h1 className="text-2xl font-bold text-gray-900">Sin secciones habilitadas</h1><p className="mt-3 text-gray-500">Pide al administrador que active los accesos que necesitas para trabajar.</p></main>;
}

function SectionRoute({ user, section, children }) {
  if (!user) return <Navigate to="/login" replace />;
  return canAccess(user, section) ? children : <Navigate to={firstAllowedPath(user)} replace />;
}

function AppContent() {
  const auth = useAuth();
  const user = auth?.user || null;
  const loading = auth?.loading ?? true;

  if (loading) return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>
  );

  return (
    <Router>
      <div className="min-h-screen bg-white">
        <Navbar />
        {canAccess(user, 'sales') && <CartDrawer />}
        <Routes>
          <Route path="/" element={user ? <Navigate to={firstAllowedPath(user)} replace /> : <Home />} />
          <Route path="/login" element={user ? <Navigate to={firstAllowedPath(user)} replace /> : <Login />} />
          <Route path="/dashboard" element={<SectionRoute user={user} section="dashboard"><Dashboard /></SectionRoute>} />
          <Route path="/sales" element={<SectionRoute user={user} section="sales"><Sales /></SectionRoute>} />
          <Route path="/inventory" element={<SectionRoute user={user} section="inventory"><Inventory /></SectionRoute>} />
          <Route path="/users" element={<SectionRoute user={user} section="users"><Users /></SectionRoute>} />
          <Route path="/transactions" element={<SectionRoute user={user} section="transactions"><Transactions /></SectionRoute>} />
          <Route path="/customers" element={<SectionRoute user={user} section="customers"><Customers /></SectionRoute>} />
          <Route path="/suppliers" element={<SectionRoute user={user} section="suppliers"><Suppliers /></SectionRoute>} />
          <Route path="/checkout" element={<SectionRoute user={user} section="sales"><Checkout /></SectionRoute>} />
          <Route path="/sin-acceso" element={user ? (firstAllowedPath(user) === '/sin-acceso' ? <NoAccess /> : <Navigate to={firstAllowedPath(user)} replace />) : <Navigate to="/login" replace />} />
          <Route path="/support" element={<Support />} />
          <Route path="/help" element={<Help />} />
          <Route path="/terms" element={<Terms />} />
          <Route path="*" element={<Navigate to="/" />} />
        </Routes>
      </div>
    </Router>
  );
}

function App() {
  return (
    <AuthProvider>
      <CartProvider>
        <AppContent />
      </CartProvider>
    </AuthProvider>
  );
}

export default App;
