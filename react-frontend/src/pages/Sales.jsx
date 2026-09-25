import React, { useState, useEffect } from 'react';
import { productsApi } from '../api';
import { Search, Filter, ShoppingCart, Loader2, Package, Check, Plus, Minus, ArrowRight, XCircle, Barcode } from 'lucide-react';
import { useCart } from '../context/CartContext';
import { motion, AnimatePresence } from 'framer-motion';
import { Link } from 'react-router-dom';
import Swal from 'sweetalert2';

const ProductCard = ({ product, addToCart, getItemCountInCart }) => {
  const [quantity, setQuantity] = useState(1);
  const inCartCount = getItemCountInCart(product.id);
  const outOfStock = parseInt(product.stock) <= 0;

  return (
    <motion.div
      layout
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, scale: 0.9 }}
      whileHover={{ y: -8 }}
      className={`group bg-white rounded-[2rem] border border-gray-100 shadow-[0_10px_30px_rgba(0,0,0,0.04)] hover:shadow-[0_20px_50px_rgba(22,163,74,0.15)] transition-all duration-500 overflow-hidden flex flex-col relative ${outOfStock ? 'opacity-75 grayscale' : ''}`}
    >
      {/* Badge de Oferta o Nuevo (Simulado) */}
      {!outOfStock && (
        <div className="absolute top-4 left-4 z-20">
          <span className="bg-yellow-400 text-yellow-900 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-tighter shadow-sm">Destacado</span>
        </div>
      )}

      <div className="relative aspect-[4/5] bg-gray-50 overflow-hidden">
        <img
          src={product.image_url}
          alt={product.name}
          className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out"
        />
        
        {/* Overlay de acción rápida al hover */}
        <div className="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

        {inCartCount > 0 && (
          <div className="absolute top-4 right-4 bg-primary-600 text-white w-10 h-10 rounded-2xl flex items-center justify-center font-black shadow-[0_10px_20px_rgba(22,163,74,0.4)] border-2 border-white animate-in zoom-in z-20">
            {inCartCount}
          </div>
        )}
        
        {outOfStock && (
          <div className="absolute inset-0 bg-gray-900/60 backdrop-blur-[2px] flex items-center justify-center z-20">
            <span className="bg-white text-gray-900 px-6 py-2 rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl">Agotado</span>
          </div>
        )}
      </div>

      <div className="p-4 flex-1 flex flex-col">
        <div className="flex justify-between items-start mb-2">
          <span className="text-[9px] font-black text-primary-600 uppercase tracking-widest bg-primary-50 px-2 py-0.5 rounded-lg">
            {product.category}
          </span>
          <div className="flex items-center gap-1 text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded-md">
            <Package className="h-2.5 w-2.5" />
            <span className="text-[9px] font-bold">{product.stock}</span>
          </div>
        </div>
        
        <h3 className="text-base font-bold text-gray-900 line-clamp-1 mb-3 group-hover:text-primary-600 transition-colors">{product.name}</h3>
        
        <div className="mt-auto space-y-4">
          <div className="flex items-center justify-between">
            <div className="flex flex-col">
              <span className="text-[9px] font-bold text-gray-400 uppercase">Precio al cliente</span>
              <span className="text-xl font-black text-gray-900 tracking-tighter">
                ${(Number(product.price) * (Number(product.applies_iva) === 1 ? 1.15 : 1)).toFixed(2)}
              </span>
              <span className="text-[9px] font-bold text-gray-400">
                {Number(product.applies_iva) === 1 ? `Base $${Number(product.price).toFixed(2)} + IVA` : 'Tarifa IVA 0%'}
              </span>
            </div>
            
            {!outOfStock && (
              <div className="flex items-center bg-gray-100 p-0.5 rounded-xl">
                <button
                  onClick={() => setQuantity(q => Math.max(1, q - 1))}
                  className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-white hover:text-primary-600 text-gray-500 transition-all shadow-none hover:shadow-sm"
                >
                  <Minus className="h-2.5 w-2.5" />
                </button>
                <span className="w-6 text-center text-[10px] font-black text-gray-700">{quantity}</span>
                <button
                  onClick={() => setQuantity(q => q + 1)}
                  className="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-white hover:text-primary-600 text-gray-500 transition-all shadow-none hover:shadow-sm"
                >
                  <Plus className="h-2.5 w-2.5" />
                </button>
              </div>
            )}
          </div>

          <button
            onClick={() => !outOfStock && addToCart(product, quantity)}
            disabled={outOfStock}
            className={`w-full py-3 rounded-xl transition-all active:scale-95 font-black text-[10px] uppercase tracking-[0.15em] flex items-center justify-center gap-2 shadow-sm ${
              inCartCount > 0 
                ? 'bg-primary-600 text-white shadow-[0_15px_30px_rgba(22,163,74,0.2)]' 
                : 'bg-gray-900 text-white hover:bg-primary-600 hover:shadow-[0_15px_30px_rgba(22,163,74,0.2)]'
            }`}
          >
            {inCartCount > 0 ? (
              <>
                <Check className="h-3.5 w-3.5" />
                Añadido ({inCartCount})
              </>
            ) : (
              <>
                <ShoppingCart className="h-3.5 w-3.5" />
                Agregar compra
              </>
            )}
          </button>
        </div>
      </div>
    </motion.div>
  );
};

const Sales = () => {
  const { addToCart, cart, cartCount, totalWithIva, clearCart } = useCart();
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('todas');

  useEffect(() => {
    let barcodeBuffer = '';
    let lastKeyTime = Date.now();

    const processBarcode = async (code) => {
      const normalizedCode = code.trim();
      const product = products.find((item) => String(item.barcode || '').trim() === normalizedCode);

      if (!product) {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'warning',
          title: `Codigo no encontrado: ${normalizedCode}`,
          showConfirmButton: false,
          timer: 2000
        });
        return;
      }

      if (parseInt(product.stock) <= 0) {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'Sin stock disponible',
          showConfirmButton: false,
          timer: 2000
        });
        return;
      }

      await addToCart(product, 1);
    };

    const handleKeyDown = (event) => {
      const target = event.target;
      const isTypingField =
        target?.tagName === 'INPUT' ||
        target?.tagName === 'TEXTAREA' ||
        target?.tagName === 'SELECT' ||
        target?.isContentEditable;

      if (isTypingField || event.ctrlKey || event.altKey || event.metaKey) {
        return;
      }

      const currentTime = Date.now();
      if (currentTime - lastKeyTime > 100) {
        barcodeBuffer = '';
      }

      if (event.key === 'Enter') {
        if (barcodeBuffer.length > 3) {
          processBarcode(barcodeBuffer);
        }
        barcodeBuffer = '';
      } else if (event.key.length === 1) {
        barcodeBuffer += event.key;
      }

      lastKeyTime = currentTime;
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [products, addToCart]);

  const handleCancelSale = async () => {
    const result = await Swal.fire({
      title: '¿Vaciar compras del cliente?',
      text: "Se quitarán todos los productos seleccionados",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#374151',
      confirmButtonText: 'Sí, cancelar',
      cancelButtonText: 'Continuar'
    });

    if (result.isConfirmed) {
      await clearCart();
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: 'Compras eliminadas',
        showConfirmButton: false,
        timer: 2000
      });
    }
  };

  useEffect(() => {
    fetchInitialData();
  }, []);

  const fetchInitialData = async () => {
    setLoading(true);
    try {
      const [prodRes, catRes] = await Promise.all([
        productsApi.getAll(),
        productsApi.getCategories()
      ]);
      setProducts(prodRes.data);
      setCategories(catRes.data);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const filteredProducts = products.filter(p => {
    const matchesSearch = p.name.toLowerCase().includes(search.toLowerCase());
    const matchesCategory = selectedCategory === 'todas' || p.category === selectedCategory;
    return matchesSearch && matchesCategory;
  });

  const getItemCountInCart = (id) => {
    const item = cart.find(i => i.id === id);
    return item ? item.quantity : 0;
  };

  return (
    <div className="max-w-7xl mx-auto px-4 py-6 pb-24">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
          <h1 className="text-2xl font-black text-gray-900 tracking-tighter">Productos</h1>
          <div className="flex items-center gap-2 mt-1">
            <p className="text-sm text-gray-500 font-medium">Selecciona los productos que comprará el cliente</p>
            <div className="flex items-center gap-1.5 px-2 py-0.5 bg-green-50 text-green-600 rounded-full border border-green-100">
              <Barcode className="h-3 w-3" />
              <span className="text-[9px] font-black uppercase tracking-widest">Escaner activo</span>
            </div>
          </div>
        </div>
      </div>

      {/* Filters Bar */}
      <div className="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm mb-6 flex flex-col md:flex-row gap-3 sticky top-16 z-30">
        <div className="relative flex-1">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            type="text"
            placeholder="Buscar por nombre de producto..."
            className="w-full pl-10 pr-4 py-2.5 bg-gray-50 rounded-xl outline-none focus:ring-2 focus:ring-primary-500 transition-all font-medium text-sm"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        
        <div className="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0 no-scrollbar">
          <div className="p-2 bg-gray-50 rounded-lg text-gray-400">
            <Filter className="h-4 w-4" />
          </div>
          {['todas', ...categories].map(cat => (
            <button
              key={cat}
              onClick={() => setSelectedCategory(cat)}
              className={`px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest whitespace-nowrap transition-all ${
                selectedCategory === cat 
                  ? 'bg-primary-600 text-white shadow-lg shadow-primary-200' 
                  : 'bg-gray-50 text-gray-400 hover:bg-gray-100'
              }`}
            >
              {cat}
            </button>
          ))}
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center items-center h-64">
          <Loader2 className="h-12 w-12 animate-spin text-primary-600" />
        </div>
      ) : (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
          <AnimatePresence>
            {filteredProducts.map((product) => (
              <ProductCard 
                key={product.id} 
                product={product} 
                addToCart={addToCart} 
                getItemCountInCart={getItemCountInCart} 
              />
            ))}
          </AnimatePresence>
        </div>
      )}

      {/* Checkout Bar (Floating) */}
      <AnimatePresence>
        {cartCount > 0 && (
          <motion.div
            initial={{ y: 100 }}
            animate={{ y: 0 }}
            exit={{ y: 100 }}
            className="fixed bottom-6 left-1/2 -translate-x-1/2 w-full max-w-4xl px-4 z-40"
          >
            <div className="bg-gray-900 text-white rounded-3xl p-4 shadow-2xl flex items-center justify-between border border-white/10 backdrop-blur-xl bg-gray-900/95">
              <div className="flex items-center gap-6 px-4">
                <div className="flex flex-col">
                  <span className="text-[10px] font-black uppercase text-gray-400 tracking-widest">Productos</span>
                  <span className="text-xl font-black">{cartCount} items</span>
                </div>
                <div className="h-8 w-px bg-white/10" />
                <div className="flex flex-col">
                  <span className="text-[10px] font-black uppercase text-primary-400 tracking-widest">Total (IVA Incl.)</span>
                  <span className="text-2xl font-black text-primary-400">${totalWithIva.toFixed(2)}</span>
                </div>
              </div>
              
              <div className="flex items-center gap-3">
                <button
                  onClick={handleCancelSale}
                  className="bg-red-600 hover:bg-red-700 text-white px-6 py-4 rounded-2xl font-black transition-all active:scale-95 flex items-center gap-2 shadow-xl shadow-red-900/20"
                  title="Vaciar compras del cliente"
                >
                  <XCircle className="h-6 w-6" />
                  <span className="hidden sm:inline text-xs uppercase tracking-widest">Cancelar</span>
                </button>

                <Link
                  to="/checkout"
                  className="bg-primary-600 hover:bg-primary-500 text-white px-10 py-4 rounded-2xl font-black flex items-center gap-3 transition-all active:scale-95 shadow-xl shadow-primary-900/20"
                >
                  COBRAR
                  <ArrowRight className="h-6 w-6" />
                </Link>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {filteredProducts.length === 0 && !loading && (
        <div className="text-center py-20">
          <div className="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
            <Search className="h-8 w-8 text-gray-300" />
          </div>
          <h3 className="text-lg font-bold text-gray-900">No se encontraron productos</h3>
          <p className="text-gray-500">Intenta con otros filtros o términos de búsqueda</p>
        </div>
      )}
    </div>
  );
};

export default Sales;
