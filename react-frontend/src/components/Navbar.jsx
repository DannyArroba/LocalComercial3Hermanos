import React, { useState, useEffect } from 'react';
import { ShoppingCart, LogOut, Menu, X, Bell, HelpCircle, Shield, Sun, Moon } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { Link, useLocation } from 'react-router-dom';
import { canAccess, storeSections, userDisplayName } from '../utils/access';
import { useTheme } from '../context/ThemeContext';

const Navbar = () => {
  const auth = useAuth();
  const user = auth?.user;
  const logout = auth?.logout;
  const { cartCount, setIsOpen } = useCart();
  const { isDark, toggleTheme } = useTheme();
  const [menuOpen, setMenuOpen] = useState(false);
  const location = useLocation();
  const links = storeSections.filter(({ key }) => canAccess(user, key));
  if (user?.role === 'admin') links.push({ key: 'users', label: 'Personal', path: '/users' });
  useEffect(() => { setMenuOpen(false); }, [location.pathname]);

  return (
    <nav className="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
      <div className="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          {/* Left: Logo & Slogan */}
          <div className="flex shrink-0 items-center gap-6">
            <Link to="/" className="group flex items-center gap-2">
              <div className="bg-primary-600 text-white w-8 h-8 rounded-lg flex items-center justify-center font-black text-lg shadow-lg shadow-primary-200 group-hover:rotate-6 transition-transform">3</div>
              <div className="flex flex-col">
                <span className="text-[8px] font-black text-primary-600 uppercase leading-none">Local Comercial</span>
                <span className="text-xs sm:text-sm font-black text-gray-900 uppercase leading-none mt-1">
                  Tres Hermanos
                </span>
              </div>
            </Link>


          </div>

          {/* Right: Actions */}
          <div className="flex min-w-0 items-center gap-1 sm:gap-3">
            <button type="button" onClick={toggleTheme} aria-pressed={isDark} aria-label={isDark ? 'Cambiar a tema claro' : 'Cambiar a azul marino'} title={isDark ? 'Cambiar a tema claro' : 'Cambiar a azul marino'} className="shrink-0 p-2 sm:p-2.5 rounded-xl text-gray-600 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition-colors focus-visible:outline-2 focus-visible:outline-primary-500">
              {isDark ? <Sun className="h-4 w-4 sm:h-5 sm:w-5" /> : <Moon className="h-4 w-4 sm:h-5 sm:w-5" />}
            </button>
            {user ? (
              <>
                <div className="hidden xl:flex items-center gap-4">
                  {links.map(({ key, label, path }) => <Link key={key} to={path} className="text-[10px] font-black text-gray-500 hover:text-primary-600 transition-all uppercase tracking-wider">{label}</Link>)}
                </div>

                <div className="h-6 w-px bg-gray-100 hidden xl:block"></div>

                <div className="flex items-center gap-0.5 sm:gap-1.5">
                  {/* Notification/Status Icons */}
                  <div className="hidden sm:flex items-center gap-0.5 mr-1">
                    <button className="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all relative">
                      <Bell className="h-4.5 w-4.5" />
                      <span className="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>
                    <Link to="/help" className="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all">
                      <HelpCircle className="h-4.5 w-4.5" />
                    </Link>
                  </div>

                  <div className="hidden lg:flex flex-col items-end mr-1">
                    <span className="text-[10px] font-black text-gray-900 leading-none max-w-28 truncate" title={userDisplayName(user)}>{userDisplayName(user)}</span>
                    <div className="flex items-center gap-0.5 mt-0.5">
                      <Shield className="h-1.5 w-1.5 text-primary-600" />
                      <span className="text-[8px] font-black text-primary-600 uppercase tracking-widest">{user.role === 'admin' ? 'Administrador' : 'Trabajador'}</span>
                    </div>
                  </div>
                  
                  {canAccess(user, 'sales') && <button
                    onClick={() => setIsOpen(true)}
                    title="Abrir carrito"
                    className="p-1.5 sm:p-2.5 bg-primary-50 text-primary-600 hover:bg-primary-100 rounded-xl transition-all relative group shadow-sm shadow-primary-100"
                  >
                    <ShoppingCart className="h-5 w-5 group-hover:scale-110 transition-transform" />
                    {cartCount > 0 && (
                      <span className="absolute top-1.5 right-1.5 inline-flex items-center justify-center px-1.5 py-0.5 text-[8px] font-black leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full border-2 border-white shadow-lg">
                        {cartCount}
                      </span>
                    )}
                  </button>}

                  <button type="button" onClick={() => setMenuOpen((open) => !open)} aria-label="Abrir menú de secciones" aria-expanded={menuOpen} aria-controls="store-mobile-menu" className="xl:hidden p-2 sm:p-2.5 text-gray-500 hover:bg-gray-100 rounded-xl">
                    {menuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                  </button>

                  <button
                    onClick={logout}
                    className="p-1.5 sm:p-2.5 text-gray-300 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all"
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
                  className="relative group overflow-hidden bg-gray-900 text-white px-3 sm:px-8 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-600 transition-all shadow-[0_10px_30px_rgba(0,0,0,0.1)] active:scale-95"
                >
                  <span className="relative z-10">Acceso Personal</span>
                  <div className="absolute inset-0 bg-gradient-to-r from-primary-600 to-green-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </Link>
              </div>
            )}
          </div>
        </div>
        {user && menuOpen && <div id="store-mobile-menu" className="xl:hidden border-t border-gray-100 py-3 grid grid-cols-2 gap-2">
          {links.map(({ key, label, path }) => <Link key={key} to={path} className={`px-3 py-3 rounded-xl text-sm font-bold ${location.pathname === path ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50'}`}>{label}</Link>)}
          {links.length === 0 && <span className="col-span-2 px-3 text-xs text-gray-500">Sin secciones habilitadas</span>}
        </div>}
      </div>
    </nav>
  );
};

export default Navbar;
