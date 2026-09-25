import React, { useState, useEffect } from 'react';
import { productsApi, suppliersApi, stockPurchasesApi } from '../api';
import { Plus, Edit2, Trash2, X, Upload, Loader2, Package, TrendingUp, ChevronDown, Barcode, Building2, ClipboardList, CreditCard, Truck, AlertTriangle, Ban, RotateCcw } from 'lucide-react';
import Swal from 'sweetalert2';
import { useCart } from '../context/CartContext';
import { useSearchParams } from 'react-router-dom';

const Inventory = () => {
  const [searchParams] = useSearchParams();
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [suppliers, setSuppliers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isPurchaseModalOpen, setIsPurchaseModalOpen] = useState(false);
  const [isHistoryOpen, setIsHistoryOpen] = useState(false);
  const [purchaseHistory, setPurchaseHistory] = useState([]);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [purchaseProduct, setPurchaseProduct] = useState(null);
  const [saving, setSaving] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState('');
  const [categoryOpen, setCategoryOpen] = useState(false);
  const [selectedImageName, setSelectedImageName] = useState('');
  const [selectedSupplierId, setSelectedSupplierId] = useState('');
  const [purchaseQuantity, setPurchaseQuantity] = useState(1);
  const [purchaseCost, setPurchaseCost] = useState('');
  const [productPrice, setProductPrice] = useState('');
  const [productAppliesIva, setProductAppliesIva] = useState('1');

  useEffect(() => {
    fetchProducts();
    fetchCategories();
    fetchSuppliers();
  }, []);

  useEffect(() => {
    if (searchParams.get('history') === '1') {
      setIsHistoryOpen(true);
      fetchPurchaseHistory();
    }
  }, [searchParams]);

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const res = await productsApi.getAll();
      setProducts(res.data);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const fetchCategories = async () => {
    try {
      const res = await productsApi.getCategories();
      setCategories(res.data);
    } catch (err) {
      console.error(err);
    }
  };
  
  const fetchSuppliers = async () => {
    try {
      const res = await suppliersApi.getAll();
      setSuppliers(res.data);
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    if (editingProduct) {
      setSelectedCategory(editingProduct.category);
      setSelectedSupplierId(editingProduct.supplier_id || '');
      setProductPrice(editingProduct.price || '');
      setProductAppliesIva(String(editingProduct.applies_iva ?? 1));
    } else {
      setSelectedCategory('');
      setSelectedSupplierId('');
      setProductPrice('');
      setProductAppliesIva('1');
    }
    setCategoryOpen(false);
    setSelectedImageName('');
  }, [editingProduct, isModalOpen]);

  const handlePurchase = async (e) => {
    e.preventDefault();
    setSaving(true);
    const quantity = parseInt(purchaseQuantity);
    const cost = parseFloat(purchaseCost);
    const purchaseData = new FormData();
    purchaseData.append('action', 'create');
    purchaseData.append('product_id', purchaseProduct.id);
    purchaseData.append('quantity', quantity);
    purchaseData.append('cost_price', cost);
    if (purchaseProduct.supplier_id) {
      purchaseData.append('supplier_id', purchaseProduct.supplier_id);
    }

    try {
      const res = await stockPurchasesApi.create(purchaseData);
      if (res.data.status === 'success') {
        Swal.fire(
          'Solicitud registrada',
          `Se solicitaron ${quantity} unidades por $${parseFloat(res.data.data.total).toFixed(2)}. El stock aumentara cuando confirmes la llegada.`,
          'success'
        );
        setIsPurchaseModalOpen(false);
        fetchPurchaseHistory();
      } else {
        Swal.fire('Error', res.data.message, 'error');
      }
    } catch (err) {
      Swal.fire('Error', err.response?.data?.message || 'No se pudo registrar el abastecimiento', 'error');
    } finally {
      setSaving(false);
    }
  };

  const fetchPurchaseHistory = async () => {
    setHistoryLoading(true);
    try {
      const res = await stockPurchasesApi.getAll();
      if (res.data.status === 'success') setPurchaseHistory(res.data.data || []);
    } catch (err) {
      Swal.fire('Error', 'No se pudo cargar el historial de abastecimiento', 'error');
    } finally {
      setHistoryLoading(false);
    }
  };

  const openPurchaseHistory = () => {
    setIsHistoryOpen(true);
    fetchPurchaseHistory();
  };

  const updatePurchaseStatus = async (purchase, action) => {
    const receiving = action === 'receive';
    const result = await Swal.fire({
      title: receiving ? '¿Confirmar llegada?' : '¿Registrar el pago?',
      text: receiving
        ? `Se sumaran ${purchase.quantity} unidades de ${purchase.product_name} al inventario.`
        : `Se registrara un gasto de $${parseFloat(purchase.total).toFixed(2)}.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: receiving ? 'Si, ya llego' : 'Si, registrar pago',
      cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;

    setSaving(true);
    try {
      const res = receiving
        ? await stockPurchasesApi.markReceived(purchase.id)
        : await stockPurchasesApi.markPaid(purchase.id);
      if (res.data.status !== 'success') {
        Swal.fire('Error', res.data.message, 'error');
        return;
      }
      Swal.fire('Actualizado', res.data.message, 'success');
      await fetchPurchaseHistory();
      if (receiving) await fetchProducts();
    } catch (err) {
      Swal.fire('Error', err.response?.data?.message || 'No se pudo actualizar la solicitud', 'error');
    } finally {
      setSaving(false);
    }
  };

  const cancelOrRefundPurchase = async (purchase) => {
    const cancelling = purchase.status === 'SOLICITADO';
    const result = await Swal.fire({
      title: cancelling ? '¿Cancelar solicitud?' : '¿Registrar reversa del pago?',
      text: cancelling
        ? 'La solicitud quedara cancelada y no afectara el stock ni los gastos.'
        : `El pago de $${parseFloat(purchase.total).toFixed(2)} quedara reversado y se generara un codigo de seguimiento.`,
      icon: 'warning',
      input: cancelling ? undefined : 'textarea',
      inputLabel: cancelling ? undefined : 'Motivo de la reversa',
      inputPlaceholder: cancelling ? undefined : 'Ej. El proveedor confirmo que no dispone del producto',
      inputValidator: cancelling ? undefined : (value) => !value?.trim() ? 'Debes indicar el motivo de la reversa' : undefined,
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      confirmButtonText: cancelling ? 'Si, cancelar' : 'Registrar reversa',
      cancelButtonText: 'Volver'
    });
    if (!result.isConfirmed) return;

    setSaving(true);
    try {
      const res = cancelling
        ? await stockPurchasesApi.cancel(purchase.id)
        : await stockPurchasesApi.refund(purchase.id, result.value.trim());
      if (res.data.status !== 'success') {
        Swal.fire('Error', res.data.message, 'error');
        return;
      }
      Swal.fire('Actualizado', res.data.message, 'success');
      await fetchPurchaseHistory();
    } catch (err) {
      Swal.fire('Error', err.response?.data?.message || 'No se pudo actualizar el abastecimiento', 'error');
    } finally {
      setSaving(false);
    }
  };

  const statusStyles = {
    SOLICITADO: 'bg-yellow-100 text-yellow-800',
    PAGADO: 'bg-blue-100 text-blue-800',
    RECIBIDO: 'bg-green-100 text-green-800',
    CANCELADO: 'bg-gray-200 text-gray-700',
    REEMBOLSADO: 'bg-red-100 text-red-700'
  };

  const handleDelete = async (id) => {
    const result = await Swal.fire({
      title: '¿Estás seguro?',
      text: "No podrás revertir esto",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
      try {
        await productsApi.delete(id);
        setProducts(products.filter(p => p.id !== id));
        Swal.fire('Eliminado', 'El producto ha sido eliminado.', 'success');
      } catch (err) {
        Swal.fire('Error', 'No se pudo eliminar el producto', 'error');
      }
    }
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    const formData = new FormData(e.target);
    formData.append('supplier_id', selectedSupplierId);
    
    if (editingProduct) {
      formData.append('action', 'update');
      formData.append('id', editingProduct.id);
    } else {
      formData.append('action', 'add');
    }

    try {
      const res = await productsApi.update(formData);
      if (res.data.status === 'success') {
        Swal.fire('Éxito', res.data.message, 'success');
        setIsModalOpen(false);
        setEditingProduct(null);
        fetchProducts();
        fetchCategories(); // Actualizar lista de categorías por si se agregó una nueva
      } else {
        Swal.fire('Error', res.data.message, 'error');
      }
    } catch (err) {
      const message = err.response?.data?.message || 'Ocurrió un error al guardar';
      Swal.fire('Error', message, 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <div className="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4 mb-8">
        <div>
          <h1 className="text-2xl font-black text-gray-900 tracking-tighter">Panel de Control - Alimentos</h1>
          <p className="text-sm text-gray-500 font-medium mt-1">Administra el stock de productos frescos y abarrotes</p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <button
            onClick={openPurchaseHistory}
            className="bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest flex items-center gap-2 transition-all"
          >
            <ClipboardList className="h-4 w-4" />
            Historial de abastecimiento
          </button>
          <button
            onClick={() => { setEditingProduct(null); setIsModalOpen(true); }}
            className="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest flex items-center gap-2 transition-all shadow-lg shadow-primary-100"
          >
            <Plus className="h-4 w-4" />
            Nuevo Producto
          </button>
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center items-center h-64">
          <Loader2 className="h-10 w-10 animate-spin text-primary-600" />
        </div>
      ) : (
        <div className="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[900px] table-fixed text-left">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="w-[25%] px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Producto</th>
                  <th className="w-[20%] px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Clasificación</th>
                  <th className="w-[16%] px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Precios</th>
                  <th className="w-[16%] px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Costos</th>
                  <th className="w-[9%] px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Stock</th>
                  <th className="w-[14%] sticky right-0 z-10 px-4 py-4 bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right shadow-[-8px_0_12px_-12px_rgba(0,0,0,0.35)]">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {products.map((product) => {
                  const lowStock = parseInt(product.stock) < 10;
                  return (
                <tr key={product.id} className={`${lowStock ? 'bg-red-50/70 hover:bg-red-100/70' : 'hover:bg-gray-50/50'} transition-colors group`}>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <div className="h-10 w-10 shrink-0 rounded-lg bg-gray-100 overflow-hidden border border-gray-100">
                        <img src={product.image_url} alt={product.name} className="h-full w-full object-cover" />
                      </div>
                      <div className="min-w-0">
                        <div className="flex items-center gap-2">
                          <span className="font-bold text-gray-900 text-sm truncate">{product.name}</span>
                          {lowStock && <AlertTriangle title="Stock bajo" className="h-4 w-4 shrink-0 text-red-600" />}
                        </div>
                        <div className="flex items-center gap-1 mt-1 text-gray-400">
                          <Barcode className="h-3 w-3 shrink-0" />
                          <span className="text-[10px] font-mono truncate">{product.barcode || 'Sin código'}</span>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-1.5">
                      <Building2 className="h-3.5 w-3.5 shrink-0 text-gray-400" />
                      <span className="text-xs font-semibold text-gray-700 truncate">{product.supplier_name || 'Sin proveedor'}</span>
                    </div>
                    <span className="inline-block mt-1.5 max-w-full truncate px-2 py-0.5 bg-gray-100 text-gray-600 text-[9px] font-black uppercase rounded-lg">{product.category}</span>
                  </td>
                  <td className="px-4 py-3">
                    <p className="font-black text-primary-700 text-sm">${(Number(product.price) * (Number(product.applies_iva) === 1 ? 1.15 : 1)).toFixed(2)}</p>
                    <p className="text-[9px] font-bold text-gray-400">Base ${parseFloat(product.price).toFixed(2)} · {Number(product.applies_iva) === 1 ? 'IVA 15%' : 'IVA 0%'}</p>
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-700">
                    <p className="font-bold">Compra: ${parseFloat(product.cost_price ?? 0).toFixed(2)}</p>
                    <p className="text-[9px] text-gray-500 mt-1">Inventario: ${(parseFloat(product.cost_price ?? 0) * parseInt(product.stock)).toFixed(2)}</p>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <Package className={`h-3.5 w-3.5 ${lowStock ? 'text-red-500' : 'text-gray-400'}`} />
                      <span className={`font-black text-sm ${lowStock ? 'text-red-700' : 'text-gray-900'}`}>{product.stock}</span>
                    </div>
                  </td>
                  <td className={`sticky right-0 z-[5] px-4 py-3 text-right shadow-[-8px_0_12px_-12px_rgba(0,0,0,0.35)] ${lowStock ? 'bg-red-50 group-hover:bg-red-100' : 'bg-white group-hover:bg-gray-50'}`}>
                    <div className="flex justify-end gap-1 opacity-75 group-hover:opacity-100 transition-opacity">
                      <button
                        onClick={() => {
                          setPurchaseProduct(product);
                          setPurchaseQuantity(1);
                          setPurchaseCost(product.cost_price || '');
                          setIsPurchaseModalOpen(true);
                        }}
                        className="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-all"
                        title="Abastecer stock"
                      >
                        <TrendingUp className="h-5 w-5" />
                      </button>
                      <button
                        onClick={() => { setEditingProduct(product); setIsModalOpen(true); }}
                        className="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all"
                        title="Editar"
                      >
                        <Edit2 className="h-5 w-5" />
                      </button>
                      <button
                        onClick={() => handleDelete(product.id)}
                        className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                        title="Eliminar"
                      >
                        <Trash2 className="h-5 w-5" />
                      </button>
                    </div>
                  </td>
                </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Purchase Modal (Abastecimiento) */}
      {isPurchaseModalOpen && (
        <div className="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-200 max-h-[90vh] flex flex-col">
            <div className="p-4 md:p-6 border-b flex justify-between items-center bg-green-50 shrink-0">
              <h2 className="text-lg md:text-xl font-bold text-green-900 flex items-center gap-2">
                <TrendingUp className="h-5 w-5" />
                Solicitar abastecimiento
              </h2>
              <button onClick={() => setIsPurchaseModalOpen(false)} className="p-2 hover:bg-green-100 rounded-full transition-colors text-green-800">
                <X className="h-6 w-6" />
              </button>
            </div>
            <form onSubmit={handlePurchase} className="p-4 md:p-8 space-y-6 overflow-y-auto">
              <div className="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl">
                <div className="h-12 w-12 rounded-xl overflow-hidden border border-gray-100 bg-white">
                  <img src={purchaseProduct?.image_url} alt={purchaseProduct?.name} className="h-full w-full object-cover" />
                </div>
                <div>
                  <p className="font-bold text-gray-900">{purchaseProduct?.name}</p>
                  <p className="text-xs text-gray-500">Stock actual: {purchaseProduct?.stock} unidades</p>
                  <p className="text-xs text-gray-500">Proveedor: {purchaseProduct?.supplier_name || 'Sin proveedor asignado'}</p>
                </div>
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Cantidad a Ingresar</label>
                <input
                  name="quantity"
                  type="number"
                  min="1"
                  required
                  autoFocus
                  value={purchaseQuantity}
                  onChange={(event) => setPurchaseQuantity(event.target.value)}
                  className="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all text-xl font-bold text-center"
                />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Costo pagado por unidad</label>
                <input
                  name="cost_price"
                  type="number"
                  min="0.01"
                  step="0.01"
                  required
                  value={purchaseCost}
                  onChange={(event) => setPurchaseCost(event.target.value)}
                  className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-green-500 outline-none transition-all font-bold"
                />
                <p className="text-xs text-gray-500">Es el precio que cobra el proveedor, no el precio de venta al cliente.</p>
              </div>
              <div className="flex items-center justify-between p-4 bg-green-50 border border-green-100 rounded-2xl">
                <span className="text-xs font-black uppercase tracking-wider text-green-700">Gasto del abastecimiento</span>
                <span className="text-xl font-black text-green-800">
                  ${((parseFloat(purchaseQuantity) || 0) * (parseFloat(purchaseCost) || 0)).toFixed(2)}
                </span>
              </div>
              <button
                type="submit"
                disabled={saving}
                className="w-full py-4 bg-green-600 hover:bg-green-700 text-white font-bold rounded-2xl transition-all shadow-lg shadow-green-100 disabled:opacity-70 flex items-center justify-center gap-2"
              >
                {saving && <Loader2 className="h-5 w-5 animate-spin" />}
                Registrar solicitud
              </button>
            </form>
          </div>
        </div>
      )}

      {isHistoryOpen && (
        <div className="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-6xl overflow-hidden max-h-[90vh] flex flex-col">
            <div className="p-5 border-b flex justify-between items-center bg-gray-50 shrink-0">
              <div>
                <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                  <ClipboardList className="h-5 w-5 text-primary-600" />
                  Historial de abastecimiento
                </h2>
                <p className="text-xs text-gray-500 mt-1">Controla solicitudes, pagos y productos recibidos.</p>
              </div>
              <button onClick={() => setIsHistoryOpen(false)} className="p-2 hover:bg-gray-200 rounded-full transition-colors" title="Cerrar">
                <X className="h-6 w-6" />
              </button>
            </div>

            <div className="overflow-auto flex-1">
              {historyLoading ? (
                <div className="h-64 flex items-center justify-center">
                  <Loader2 className="h-9 w-9 animate-spin text-primary-600" />
                </div>
              ) : purchaseHistory.length === 0 ? (
                <div className="h-64 flex flex-col items-center justify-center text-gray-400">
                  <ClipboardList className="h-10 w-10 mb-3" />
                  <p className="font-bold text-gray-600">Aun no hay solicitudes registradas</p>
                </div>
              ) : (
                <table className="w-full text-left min-w-[900px]">
                  <thead className="bg-gray-50 border-b border-gray-100 sticky top-0">
                    <tr>
                      <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Solicitud</th>
                      <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Producto</th>
                      <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Proveedor</th>
                      <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Cantidad</th>
                      <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Costo unitario</th>
                      <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total</th>
                      <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Estado</th>
                      <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Accion</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {purchaseHistory.map((purchase) => (
                      <tr key={purchase.id} className="hover:bg-gray-50/60">
                        <td className="px-5 py-4">
                          <p className="font-black text-sm text-gray-900">#{purchase.id}</p>
                          <p className="text-[10px] text-gray-500">{new Date(purchase.created_at).toLocaleString()}</p>
                        </td>
                        <td className="px-5 py-4">
                          <div className="flex items-center gap-3">
                            <img src={purchase.image_url} alt={purchase.product_name} className="h-9 w-9 rounded-lg object-cover bg-gray-100" />
                            <span className="font-bold text-sm text-gray-900">{purchase.product_name}</span>
                          </div>
                        </td>
                        <td className="px-5 py-4 text-xs font-semibold text-gray-700">{purchase.supplier_name || 'Sin proveedor'}</td>
                        <td className="px-5 py-4 text-sm font-black text-gray-900">{purchase.quantity}</td>
                        <td className="px-5 py-4 text-sm text-gray-700">${parseFloat(purchase.cost_price).toFixed(2)}</td>
                        <td className="px-5 py-4 text-sm font-black text-gray-900">${parseFloat(purchase.total).toFixed(2)}</td>
                        <td className="px-5 py-4">
                          <span className={`px-3 py-1 rounded-full text-[10px] font-black tracking-wider ${statusStyles[purchase.status] || 'bg-gray-100 text-gray-700'}`}>
                            {purchase.status}
                          </span>
                          {purchase.paid_at && <p className="text-[9px] text-gray-400 mt-1">Pago: {new Date(purchase.paid_at).toLocaleString()}</p>}
                          {purchase.received_at && <p className="text-[9px] text-gray-400 mt-1">Llegada: {new Date(purchase.received_at).toLocaleString()}</p>}
                          {purchase.cancelled_at && <p className="text-[9px] text-gray-400 mt-1">Cancelado: {new Date(purchase.cancelled_at).toLocaleString()}</p>}
                          {purchase.refunded_at && <p className="text-[9px] text-gray-400 mt-1">Reembolso: {new Date(purchase.refunded_at).toLocaleString()}</p>}
                          {purchase.reversal_code && <p className="text-[9px] font-black text-red-700 mt-1">Codigo: {purchase.reversal_code}</p>}
                          {purchase.reversal_reason && <p className="text-[9px] text-gray-500 mt-1 max-w-48">Motivo: {purchase.reversal_reason}</p>}
                        </td>
                        <td className="px-5 py-4 text-right">
                          {purchase.status === 'SOLICITADO' && (
                            <div className="flex justify-end gap-2">
                              <button onClick={() => cancelOrRefundPurchase(purchase)} disabled={saving} title="Cancelar solicitud" className="p-2 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg disabled:opacity-60">
                                <Ban className="h-4 w-4" />
                              </button>
                              <button
                                onClick={() => updatePurchaseStatus(purchase, 'pay')}
                                disabled={saving}
                                className="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-black uppercase tracking-wider disabled:opacity-60"
                              >
                                <CreditCard className="h-4 w-4" />
                                Marcar pagado
                              </button>
                            </div>
                          )}
                          {purchase.status === 'PAGADO' && (
                            <div className="flex justify-end gap-2">
                              <button onClick={() => cancelOrRefundPurchase(purchase)} disabled={saving} title="Registrar reembolso" className="p-2 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg disabled:opacity-60">
                                <RotateCcw className="h-4 w-4" />
                              </button>
                              <button
                                onClick={() => updatePurchaseStatus(purchase, 'receive')}
                                disabled={saving}
                                className="inline-flex items-center gap-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-[10px] font-black uppercase tracking-wider disabled:opacity-60"
                              >
                                <Truck className="h-4 w-4" />
                                Confirmar llegada
                              </button>
                            </div>
                          )}
                          {purchase.status === 'RECIBIDO' && (
                            <span className="text-xs font-bold text-green-700">Proceso finalizado</span>
                          )}
                          {(purchase.status === 'CANCELADO' || purchase.status === 'REEMBOLSADO') && (
                            <span className="text-xs font-bold text-gray-500">Proceso cerrado</span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in zoom-in-95 duration-200 max-h-[90vh] flex flex-col">
            <div className="p-4 md:p-6 border-b flex justify-between items-center bg-gray-50 shrink-0">
              <h2 className="text-lg md:text-xl font-bold text-gray-900">
                {editingProduct ? 'Editar Producto' : 'Nuevo Producto'}
              </h2>
              <button onClick={() => setIsModalOpen(false)} className="p-2 hover:bg-gray-200 rounded-full transition-colors">
                <X className="h-6 w-6" />
              </button>
            </div>
            
            <form onSubmit={handleSave} className="p-4 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto">
              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Nombre</label>
                <input
                  name="name"
                  required
                  defaultValue={editingProduct?.name}
                  className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                />
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Codigo de barras</label>
                <div className="relative">
                  <Barcode className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                  <input
                    name="barcode"
                    defaultValue={editingProduct?.barcode || ''}
                    placeholder="Escanea o escribe el codigo..."
                    className="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all font-mono"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Precio base sin IVA</label>
                <input
                  name="price"
                  type="number"
                  step="0.01"
                  required
                  value={productPrice}
                  onChange={(event) => setProductPrice(event.target.value)}
                  className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                />
                <p className="text-xs text-gray-500">Precio antes de impuestos.</p>
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">IVA del producto</label>
                <select
                  name="applies_iva"
                  value={productAppliesIva}
                  onChange={(event) => setProductAppliesIva(event.target.value)}
                  className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                >
                  <option value="1">Aplica IVA 15%</option>
                  <option value="0">Tarifa IVA 0%</option>
                </select>
                <p className="text-xs font-bold text-primary-700">
                  Precio al cliente: ${(Number(productPrice || 0) * (productAppliesIva === '1' ? 1.15 : 1)).toFixed(2)}
                </p>
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Costo de compra al proveedor</label>
                <input
                  name="cost_price"
                  type="number"
                  min="0.01"
                  step="0.01"
                  required
                  defaultValue={editingProduct?.cost_price ?? 0}
                  className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                />
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Stock</label>
                <input
                  name="stock"
                  type="number"
                  required
                  defaultValue={editingProduct?.stock}
                  className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                />
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Proveedor</label>
                <div className="relative">
                  <select
                    value={selectedSupplierId}
                    onChange={(e) => setSelectedSupplierId(e.target.value)}
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                  >
                    <option value="">Sin proveedor</option>
                    {suppliers.map(supplier => (
                      <option key={supplier.id} value={supplier.id}>
                        {supplier.company_name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Categoría</label>
                <div className="relative">
                  <input
                    name="category"
                    required
                    autoComplete="off"
                    value={selectedCategory}
                    onChange={(event) => {
                      setSelectedCategory(event.target.value);
                      setCategoryOpen(true);
                    }}
                    onFocus={() => setCategoryOpen(true)}
                    onBlur={() => setTimeout(() => setCategoryOpen(false), 150)}
                    placeholder="Escribe o selecciona una categoría..."
                    className="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                  />
                  <button
                    type="button"
                    title="Mostrar categorías"
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={() => setCategoryOpen((open) => !open)}
                    className="absolute right-2 top-1/2 -translate-y-1/2 h-9 w-9 flex items-center justify-center text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-colors"
                  >
                    <ChevronDown className={`h-4 w-4 transition-transform ${categoryOpen ? 'rotate-180' : ''}`} />
                  </button>

                  {categoryOpen && (
                    <div className="absolute z-20 mt-2 w-full max-h-52 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-xl">
                      {categories
                        .filter((category) =>
                          category.toLowerCase().includes(selectedCategory.trim().toLowerCase())
                        )
                        .map((category) => (
                          <button
                            key={category}
                            type="button"
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => {
                              setSelectedCategory(category);
                              setCategoryOpen(false);
                            }}
                            className="w-full px-4 py-3 text-left text-sm font-semibold text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-colors"
                          >
                            {category}
                          </button>
                        ))}

                      {selectedCategory.trim() &&
                        !categories.some(
                          (category) => category.toLowerCase() === selectedCategory.trim().toLowerCase()
                        ) && (
                          <div className="px-4 py-3 border-t border-gray-100 text-xs text-gray-500">
                            Se guardará la nueva categoría <strong>{selectedCategory.trim()}</strong>
                          </div>
                        )}

                      {categories.filter((category) =>
                        category.toLowerCase().includes(selectedCategory.trim().toLowerCase())
                      ).length === 0 &&
                        !selectedCategory.trim() && (
                          <div className="px-4 py-3 text-xs text-gray-500">
                            No hay categorías registradas. Escribe una nueva.
                          </div>
                        )}
                    </div>
                  )}
                </div>
                <p className="text-xs text-gray-400 ml-1">Selecciona una existente o escribe una nueva.</p>
              </div>

              <div className="space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Imagen</label>
                <div className="relative">
                  <input
                    name="image"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    onChange={(event) => setSelectedImageName(event.target.files?.[0]?.name || '')}
                    className="absolute inset-0 opacity-0 cursor-pointer"
                  />
                  <div className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl flex items-center gap-2 text-gray-500">
                    <Upload className="h-5 w-5" />
                    <span className="text-sm truncate">
                      {selectedImageName || (editingProduct?.image ? 'Cambiar imagen...' : 'Subir imagen...')}
                    </span>
                  </div>
                </div>
                <p className="text-xs text-gray-400 ml-1">JPG, PNG, WEBP o GIF. Máximo 5 MB.</p>
              </div>

              <div className="col-span-2 space-y-2">
                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Descripción</label>
                <textarea
                  name="description"
                  rows="3"
                  defaultValue={editingProduct?.description || ''}
                  className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all resize-none"
                  placeholder="Descripción breve del producto"
                />
              </div>

              <div className="col-span-2 flex gap-4 mt-4">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="flex-1 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl transition-all"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="flex-1 py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl transition-all shadow-lg shadow-primary-100 disabled:opacity-70 flex items-center justify-center gap-2"
                >
                  {saving && <Loader2 className="h-5 w-5 animate-spin" />}
                  {editingProduct ? 'Guardar Cambios' : 'Crear Producto'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default Inventory;
