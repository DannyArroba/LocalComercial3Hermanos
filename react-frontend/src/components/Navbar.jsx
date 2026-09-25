import React from 'react';
import { ShoppingCart, User, LogOut, Menu, Search, Bell, HelpCircle, Shield } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { Link } from 'react-router-dom';

const Navbar = () => {
  const auth = useAuth();
  const user = auth?.user;
  const logout = auth?.logout;
  const { cartCount, setIsOpen } = useCart();

  return (
    <nav className="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          {/* Left: Logo & Slogan */}
          <div className="flex items-center gap-6">
            <Link to="/" className="group flex items-center gap-2">
              <div className="bg-primary-600 text-white w-8 h-8 rounded-lg flex items-center justify-center font-black text-lg shadow-lg shadow-primary-200 group-hover:rotate-6 transition-transform">3</div>
              <div className="flex flex-col">
                <span className="text-[8px] font-black text-primary-600 uppercase leading-none">Local Comercial</span>
                <span className="text-sm font-black text-gray-900 uppercase leading-none mt-1">
                  Tres Hermanos
                </span>
              </div>
            </Link>


          </div>

          {/* Right: Actions */}
          <div className="flex items-center gap-4">
            {user ? (
              <>
                <div className="hidden md:flex items-center gap-6">
                  <Link to="/dashboard" className="text-xs font-black text-gray-500 hover:text-primary-600 transition-all uppercase tracking-widest">Dashboard</Link>
                  <Link to="/sales" className="text-xs font-black text-gray-500 hover:text-primary-600 transition-all uppercase tracking-widest">Ventas</Link>
                  <Link to="/inventory" className="text-xs font-black text-gray-500 hover:text-primary-600 transition-all uppercase tracking-widest">Inventario</Link>
                      <Link to="/suppliers" className="text-xs font-black text-gray-500 hover:text-primary-600 transition-all uppercase tracking-widest">Proveedores</Link>
                      <Link to="/customers" className="text-xs font-black text-gray-500 hover:text-primary-600 transition-all uppercase tracking-widest">Clientes</Link>
                  <Link to="/transactions" className="text-xs font-black text-gray-500 hover:text-primary-600 transition-all uppercase tracking-widest">Transacciones</Link>
                  {user.role === 'admin' && (
                    <Link to="/users" className="text-xs font-black text-gray-500 hover:text-primary-600 transition-all uppercase tracking-widest">Personal</Link>
                  )}
                </div>

                <div className="h-6 w-px bg-gray-100 hidden md:block"></div>

                <div className="flex items-center gap-1.5">
                  {/* Notification/Status Icons */}
                  <div className="flex items-center gap-0.5 mr-1">
                    <button className="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all relative">
                      <Bell className="h-4.5 w-4.5" />
                      <span className="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>
                    <Link to="/help" className="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all">
                      <HelpCircle className="h-4.5 w-4.5" />
                    </Link>
                  </div>

                  <div className="hidden lg:flex flex-col items-end mr-1">
                    <span className="text-[10px] font-black text-gray-900 leading-none">{user.email.split('@')[0]}</span>
                    <div className="flex items-center gap-0.5 mt-0.5">
                      <Shield className="h-1.5 w-1.5 text-primary-600" />
                      <span className="text-[8px] font-black text-primary-600 uppercase tracking-widest">{user.role}</span>
                    </div>
                  </div>
                  
                  <button
                    onClick={() => setIsOpen(true)}
                    className="p-2.5 bg-primary-50 text-primary-600 hover:bg-primary-100 rounded-xl transition-all relative group shadow-sm shadow-primary-100"
                  >
                    <ShoppingCart className="h-5 w-5 group-hover:scale-110 transition-transform" />
                    {cartCount > 0 && (
                      <span className="absolute top-1.5 right-1.5 inline-flex items-center justify-center px-1.5 py-0.5 text-[8px] font-black leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full border-2 border-white shadow-lg">
                        {cartCount}
                      </span>
                    )}
                  </button>

                  <button
                    onClick={logout}
                    className="p-2.5 text-gray-300 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all"
                    title="Cerrar sesión"
                  >
                    <LogOut className="h-5 w-5" />
                  </button>
                </div>
              </>
            ) : (
              <div className="flex items-center gap-3">
                <div className="hidden md:flex items-center gap-4 mr-2 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em]">
                  <Link to="/support" className="hover:text-primary-600 transition-colors">Soporte</Link>
                  <Link to="/help" className="hover:text-primary-600 transition-colors">Ayuda</Link>
                </div>
                <Link
                  to="/login"
                  className="relative group overflow-hidden bg-gray-900 text-white px-8 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-600 transition-all shadow-[0_10px_30px_rgba(0,0,0,0.1)] active:scale-95"
                >
                  <span className="relative z-10">Acceso Personal</span>
                  <div className="absolute inset-0 bg-gradient-to-r from-primary-600 to-green-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </Link>
              </div>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
