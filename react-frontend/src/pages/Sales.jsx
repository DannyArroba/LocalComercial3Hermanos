import React, { useState, useEffect, useRef, useMemo } from 'react';
import { productsApi } from '../api';
import { Search, Filter, ShoppingCart, Loader2, Package, Check, Plus, Minus, ArrowRight, XCircle, Barcode } from 'lucide-react';
import { useCart } from '../context/CartContext';
import { motion, AnimatePresence } from 'framer-motion';
import { Link } from 'react-router-dom';
import Swal from 'sweetalert2';
import { priceLine } from '../utils/pricing';
import { normalizeSearch, searchProducts, visibleProducts } from '../utils/productSearch';

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
      className={`group h-full bg-white rounded-[2rem] border border-gray-100 shadow-[0_10px_30px_rgba(0,0,0,0.04)] hover:shadow-[0_20px_50px_rgba(22,163,74,0.15)] transition-all duration-500 overflow-hidden flex flex-col relative ${outOfStock ? 'opacity-75 grayscale' : ''}`}
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
                ${priceLine(product).total.toFixed(2)}
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
  const searchInputRef = useRef(null);
  const searchBoxRef = useRef(null);
  const categoryStripRef = useRef(null);
  const productNodesRef = useRef(new Map());
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('todas');
  const [browsingCategory, setBrowsingCategory] = useState(false);
  const [suggestionsOpen, setSuggestionsOpen] = useState(false);
  const [activeSuggestion, setActiveSuggestion] = useState(-1);
  const [locationRequest, setLocationRequest] = useState(null);
  const [highlightedProductId, setHighlightedProductId] = useState(null);

  const hasSearch = Boolean(normalizeSearch(search));
  const searchingGlobally = hasSearch && !browsingCategory;
  const matches = useMemo(() => searchProducts(products, search), [products, search]);
  const suggestions = matches.slice(0, 8);
  const showSuggestions = hasSearch && suggestionsOpen;
  const filteredProducts = useMemo(
    () => visibleProducts(products, search, selectedCategory, { browseCategory: browsingCategory }),
    [products, search, selectedCategory, browsingCategory]
  );

  const locateProduct = (product) => {
    setBrowsingCategory(true);
    setSuggestionsOpen(false);
    setActiveSuggestion(-1);
    setSelectedCategory(product.category || 'todas');
    setLocationRequest({ id: String(product.id), name: product.name, category: product.category });
  };

  useEffect(() => {
    if (!locationRequest) return;
    let timer;
    const frame = window.requestAnimationFrame(() => {
      const node = productNodesRef.current.get(locationRequest.id);
      if (!node) return;
      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const strip = categoryStripRef.current;
      const categoryButton = strip && [...strip.querySelectorAll('[data-category]')]
        .find((button) => button.dataset.category === locationRequest.category);
      if (categoryButton) {
        const stripRect = strip.getBoundingClientRect();
        const buttonRect = categoryButton.getBoundingClientRect();
        strip.scrollBy({ left: buttonRect.left - stripRect.left - (stripRect.width - buttonRect.width) / 2, behavior: reducedMotion ? 'auto' : 'smooth' });
      }
      setHighlightedProductId(locationRequest.id);
      node.focus({ preventScroll: true });
      node.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'center' });
      timer = window.setTimeout(() => setHighlightedProductId(null), 3200);
    });
    return () => {
      window.cancelAnimationFrame(frame);
      window.clearTimeout(timer);
    };
  }, [locationRequest]);

  const changeSearch = (value) => {
    setSearch(value);
    setBrowsingCategory(false);
    const isSearching = Boolean(normalizeSearch(value));
    setSuggestionsOpen(isSearching);
    if (!isSearching) setSelectedCategory('todas');
    setActiveSuggestion(-1);
    setLocationRequest(null);
    setHighlightedProductId(null);
  };

  const handleSearchKeys = (event) => {
    if (event.nativeEvent.isComposing) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      setSuggestionsOpen(false);
      setActiveSuggestion(-1);
    } else if (event.key === 'ArrowDown' && hasSearch && suggestions.length) {
      event.preventDefault();
      setSuggestionsOpen(true);
      setActiveSuggestion((index) => Math.min(index + 1, suggestions.length - 1));
    } else if (event.key === 'ArrowUp' && hasSearch && suggestions.length) {
      event.preventDefault();
      setSuggestionsOpen(true);
      setActiveSuggestion((index) => Math.max(0, index - 1));
    } else if (event.key === 'Enter' && showSuggestions && activeSuggestion >= 0) {
      event.preventDefault();
      locateProduct(suggestions[activeSuggestion]);
    }
  };

  useEffect(() => {
    if (showSuggestions && activeSuggestion >= 0) {
      searchBoxRef.current?.querySelector(`#product-suggestion-${activeSuggestion}`)
        ?.scrollIntoView({ block: 'nearest' });
    }
  }, [activeSuggestion, showSuggestions]);

  useEffect(() => {
    let barcodeBuffer = '';
    let lastKeyTime = Date.now();

    const processBarcode = async (code) => {
      const normalizedCode = code.trim();
      const product = products.find((item) =>
        String(item.barcode || '').trim() === normalizedCode ||
        (item.scan_codes || []).some((barcode) => String(barcode).trim() === normalizedCode)
      );

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

  const getItemCountInCart = (id) => {
    const item = cart.find(i => i.id === id);
    return item ? item.quantity : 0;
  };

  return (
    <div className="max-w-7xl mx-auto px-4 py-6 pb-24">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div className="w-full min-w-0">
          <h1 className="text-2xl font-black text-gray-900 tracking-tighter">Productos</h1>
          <div className="flex flex-col sm:flex-row sm:items-center gap-2 mt-1">
            <p className="text-sm text-gray-500 font-medium">Selecciona los productos que comprará el cliente</p>
            <div className="flex items-center gap-1.5 w-fit shrink-0 px-2 py-0.5 bg-green-50 text-green-600 rounded-full border border-green-100">
              <Barcode className="h-3 w-3" />
              <span className="text-[9px] font-black uppercase tracking-widest">Escaner activo</span>
            </div>
          </div>
          <div
            ref={searchBoxRef}
            className="relative mt-4"
            onBlur={(event) => {
              if (!event.currentTarget.contains(event.relatedTarget)) {
                setSuggestionsOpen(false);
                setActiveSuggestion(-1);
              }
            }}
          >
          <form
            role="search"
            className="flex w-full gap-2 rounded-2xl border border-gray-200 bg-white p-2 shadow-sm"
            onSubmit={(event) => {
              event.preventDefault();
              setBrowsingCategory(false);
              setLocationRequest(null);
              setHighlightedProductId(null);
              setSuggestionsOpen(false);
              setActiveSuggestion(-1);
            }}
          >
            <label htmlFor="product-search" className="sr-only">Buscar productos por nombre</label>
            <input
              ref={searchInputRef}
              id="product-search"
              type="search"
              role="combobox"
              autoComplete="off"
              aria-autocomplete="list"
              aria-expanded={showSuggestions}
              aria-controls="product-suggestions"
              aria-activedescendant={showSuggestions && activeSuggestion >= 0 ? `product-suggestion-${activeSuggestion}` : undefined}
              aria-describedby="product-search-hint"
              placeholder="Escribe el nombre del producto..."
              className="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-3 text-base font-medium text-gray-900 placeholder:text-gray-500 outline-none transition-all focus:border-primary-500 focus:ring-2 focus:ring-primary-200"
              value={search}
              onChange={(event) => changeSearch(event.target.value)}
              onFocus={() => setSuggestionsOpen(true)}
              onKeyDown={handleSearchKeys}
            />
            <button
              type="submit"
              className="flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
              aria-label="Buscar productos"
            >
              <Search className="h-5 w-5 shrink-0" aria-hidden="true" />
              Buscar
            </button>
          </form>
          {showSuggestions && (
            <div className="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
              <div className="border-b border-gray-100 px-4 py-2 text-xs font-medium text-gray-500">
                Coincidencias en toda la tienda · elige un producto para ubicarlo
              </div>
              <div id="product-suggestions" role="listbox" aria-label="Productos encontrados" className="max-h-80 overflow-y-auto overscroll-contain">
                {suggestions.map((product, index) => (
                  <button
                    key={product.id}
                    id={`product-suggestion-${index}`}
                    type="button"
                    role="option"
                    aria-selected={activeSuggestion === index}
                    tabIndex={-1}
                    className={`flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-primary-50 ${activeSuggestion === index ? 'bg-primary-50' : 'bg-white'}`}
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={() => locateProduct(product)}
                  >
                    <img src={product.image_url} alt="" className="h-11 w-11 shrink-0 rounded-lg border border-gray-100 bg-white object-contain" />
                    <span className="min-w-0 flex-1">
                      <span className="block text-sm font-semibold text-gray-900">{product.name}</span>
                      <span className="block text-xs text-gray-500">{product.category}{Number(product.stock) <= 0 ? ' · Agotado' : ''}</span>
                    </span>
                    <span className="shrink-0 text-sm font-bold text-primary-700">${priceLine(product).total.toFixed(2)}</span>
                    <ArrowRight className="h-4 w-4 shrink-0 text-gray-400" aria-hidden="true" />
                  </button>
                ))}
                {suggestions.length === 0 && (
                  <p className="px-4 py-5 text-sm text-gray-500">No encontramos coincidencias. Prueba con otra palabra o revisa la escritura.</p>
                )}
              </div>
              {matches.length > suggestions.length && (
                <button type="button" onMouseDown={(event) => event.preventDefault()} onClick={() => { setSuggestionsOpen(false); setActiveSuggestion(-1); }} className="w-full border-t border-gray-100 px-4 py-3 text-left text-sm font-semibold text-primary-700 hover:bg-primary-50">
                  Ver los {matches.length} resultados en la lista de productos
                </button>
              )}
            </div>
          )}
          </div>
          <p id="product-search-hint" className="mt-2 text-xs text-gray-500">
            {browsingCategory
              ? (selectedCategory === 'todas' ? 'Mostrando todos los productos.' : `Mostrando ${selectedCategory}. Borra la búsqueda para volver a Todas.`)
              : hasSearch ? `${matches.length} coincidencias en todas las categorías` : 'Busca en toda la tienda, aunque tengas una categoría seleccionada.'}
          </p>
          <p role="status" aria-live="polite" className="sr-only">
            {locationRequest ? `Producto ubicado: ${locationRequest.name}, categoría ${locationRequest.category}.` : ''}
          </p>
        </div>
      </div>

      {/* Filters Bar */}
      <div className="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm mb-6 flex flex-col md:flex-row gap-3 sticky top-16 z-30">
        <div ref={categoryStripRef} className="flex min-w-0 w-full items-center gap-2 overflow-x-auto pb-1 md:pb-0 no-scrollbar">
          <div className="p-2 bg-gray-50 rounded-lg text-gray-400">
            <Filter className="h-4 w-4" />
          </div>
          {['todas', ...categories].map(cat => (
            <button
              key={cat}
              data-category={cat}
              aria-pressed={selectedCategory === cat && !searchingGlobally}
              onClick={() => {
                setSelectedCategory((current) => !searchingGlobally && current === cat ? 'todas' : cat);
                setBrowsingCategory(true);
                setSuggestionsOpen(false);
                setActiveSuggestion(-1);
                setLocationRequest(null);
                setHighlightedProductId(null);
              }}
              className={`px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest whitespace-nowrap transition-all ${
                !searchingGlobally && selectedCategory === cat
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
              <div
                key={product.id} 
                ref={(node) => {
                  if (node) productNodesRef.current.set(String(product.id), node);
                  else productNodesRef.current.delete(String(product.id));
                }}
                tabIndex={-1}
                role="group"
                aria-label={product.name}
                className={`h-full scroll-mt-40 rounded-[2rem] focus:outline-none ${highlightedProductId === String(product.id) ? 'product-location-highlight' : ''}`}
              >
                <ProductCard product={product} addToCart={addToCart} getItemCountInCart={getItemCountInCart} />
              </div>
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
