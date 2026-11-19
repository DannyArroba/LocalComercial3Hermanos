import React from 'react';
import { ShoppingCart, Plus, Minus } from 'lucide-react';
import { useCart } from '../context/CartContext';
import { priceLine } from '../utils/pricing';

const ProductCard = ({ product }) => {
  const { addToCart } = useCart();
  const [quantity, setQuantity] = React.useState(1);

  return (
    <div className="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 flex flex-col">
      <div className="relative aspect-square overflow-hidden bg-gray-100">
        <img
          src={product.image_url}
          alt={product.name}
          className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
        />
        <div className="absolute top-2 right-2">
          <span className="bg-white/90 backdrop-blur-sm text-gray-800 text-xs font-bold px-3 py-1.5 rounded-full shadow-sm border border-gray-100">
            {product.category}
          </span>
        </div>
      </div>

      <div className="p-5 flex-1 flex flex-col">
        <h3 className="text-lg font-semibold text-gray-800 mb-1 line-clamp-1 group-hover:text-primary-600 transition-colors">
          {product.name}
        </h3>
        <p className="text-sm text-gray-500 line-clamp-2 mb-4 h-10">
          {product.description || "Producto fresco y de alta calidad seleccionado especialmente para ti."}
        </p>
        
        <div className="mt-auto flex items-center justify-between">
          <span className="text-2xl font-black text-primary-600">
            ${priceLine(product).total.toFixed(2)}
          </span>
        </div>

        <div className="mt-4 flex items-center space-x-2">
          <div className="flex items-center border border-gray-200 rounded-lg bg-gray-50">
            <button
              onClick={() => setQuantity(q => Math.max(1, q - 1))}
              className="p-2 text-gray-500 hover:text-primary-600 transition-colors"
            >
              <Minus className="h-4 w-4" />
            </button>
            <span className="w-8 text-center text-sm font-medium text-gray-700">{quantity}</span>
            <button
              onClick={() => setQuantity(q => q + 1)}
              className="p-2 text-gray-500 hover:text-primary-600 transition-colors"
            >
              <Plus className="h-4 w-4" />
            </button>
          </div>
          <button
            onClick={() => addToCart(product.id, quantity)}
            className="flex-1 bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg font-semibold flex items-center justify-center gap-2 transition-all active:scale-95 shadow-lg shadow-primary-200"
          >
            <ShoppingCart className="h-4 w-4" />
            Añadir
          </button>
        </div>
      </div>
    </div>
  );
};

export default ProductCard;
