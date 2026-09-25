import React, { useState, useEffect } from 'react';
import { transactionsApi, returnsApi } from '../api';
import { Clock, Search, Eye, X, Receipt, ShoppingBag, User, Calendar, CreditCard, Download, RotateCcw, Loader2 } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import Swal from 'sweetalert2';

const RETURN_REASONS = {
  MALA_ELECCION_PRODUCTO: 'Mala elección del producto',
  PRODUCTO_DANADO: 'Producto dañado o en mal estado',
  PRODUCTO_EQUIVOCADO: 'Producto diferente al solicitado',
  PRODUCTO_VENCIDO: 'Producto vencido'
};

const Transactions = () => {
  const [transactions, setTransactions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedTransaction, setSelectedTransaction] = useState(null);
  const [details, setDetails] = useState(null);
  const [loadingDetails, setLoadingDetails] = useState(false);
  const [isReturnOpen, setIsReturnOpen] = useState(false);
  const [returnSaleDetails, setReturnSaleDetails] = useState(null);
  const [savingReturn, setSavingReturn] = useState(false);
  const [returnForm, setReturnForm] = useState({
    purchase_id: '', items: {}, reason_category: 'MALA_ELECCION_PRODUCTO',
    customer_name: '', customer_idnumber: '', customer_phone: '', reason_detail: ''
  });

  useEffect(() => {
    fetchTransactions();
  }, []);

  const fetchTransactions = async () => {
    setLoading(true);
    try {
      const res = await transactionsApi.getAll();
      if (res.data.status === 'success') {
        setTransactions(res.data.data);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const fetchDetails = async (id, type = 'VENTA') => {
    setLoadingDetails(true);
    setSelectedTransaction({ id, type });
    try {
      const res = await transactionsApi.getDetails(id, type);
      if (res.data.status === 'success') {
        setDetails(res.data.data);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoadingDetails(false);
    }
  };

  const selectReturnSale = async (purchaseId) => {
    setReturnForm((current) => ({ ...current, purchase_id: purchaseId, items: {} }));
    setReturnSaleDetails(null);
    if (!purchaseId) return;
    const response = await transactionsApi.getDetails(purchaseId, 'VENTA');
    if (response.data.status === 'success') {
      const sale = response.data.data;
      setReturnSaleDetails(sale);
      setReturnForm((current) => ({
        ...current,
        customer_name: sale.purchase.customer_name || '',
        customer_idnumber: sale.purchase.customer_idnumber || '',
        customer_phone: sale.purchase.customer_phone || ''
      }));
    }
  };

  const handleReturnSubmit = async (event) => {
    event.preventDefault();
    setSavingReturn(true);
    try {
      const response = await returnsApi.create({
        ...returnForm,
        purchase_id: Number(returnForm.purchase_id),
        items: Object.entries(returnForm.items).map(([purchaseItemId, quantity]) => ({
          purchase_item_id: Number(purchaseItemId), quantity: Number(quantity)
        }))
      });
      if (response.data.status !== 'success') {
        Swal.fire('Error', response.data.message, 'error');
        return;
      }
      await Swal.fire('Devolución registrada', `${response.data.data.return_code} · Valor devuelto $${Number(response.data.data.total_refund).toFixed(2)}`, 'success');
      setIsReturnOpen(false);
      setReturnSaleDetails(null);
      setReturnForm({ purchase_id: '', items: {}, reason_category: 'MALA_ELECCION_PRODUCTO', customer_name: '', customer_idnumber: '', customer_phone: '', reason_detail: '' });
      fetchTransactions();
    } catch (error) {
      Swal.fire('Error', error.response?.data?.message || 'No se pudo registrar la devolución', 'error');
    } finally {
      setSavingReturn(false);
    }
  };

  const downloadPDF = () => {
    if (!details) return;

    const doc = new jsPDF();
    const { purchase, items } = details;
    const date = new Date(purchase.created_at).toLocaleString();

    // Header
    doc.setFontSize(22);
    doc.setTextColor(22, 163, 74); // primary-600
    doc.text('LOCAL COMERCIAL TRES HERMANOS', 105, 20, { align: 'center' });
    
    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text('Sistema de Gestión Interna', 105, 28, { align: 'center' });
    
    doc.setDrawColor(200);
    doc.line(20, 35, 190, 35);

    // Purchase Info
    doc.setFontSize(12);
    doc.setTextColor(0);
    doc.text(`${purchase.purchase_type === 'DEVOLUCION' ? 'COMPROBANTE DE DEVOLUCION' : purchase.purchase_type === 'FACTURA' ? 'COMPROBANTE DE VENTA' : 'NOTA DE VENTA'}`, 20, 45);
    doc.setFontSize(10);
    doc.text(`Nro. Comprobante: #000${purchase.id}`, 20, 52);
    doc.text(`Fecha: ${date}`, 20, 57);

    // Customer Info
    if (['FACTURA', 'DEVOLUCION'].includes(purchase.purchase_type) && purchase.customer_name) {
      doc.setFontSize(11);
      doc.text('DATOS DEL CLIENTE:', 20, 75);
      doc.setFontSize(10);
      doc.text(`Nombre: ${purchase.customer_name}`, 20, 82);
      doc.text(`ID/Cédula: ${purchase.customer_idnumber || 'N/A'}`, 20, 87);
      doc.text(`Dirección: ${purchase.customer_address || 'N/A'}`, 20, 92);
      doc.text(`Teléfono: ${purchase.customer_phone || 'N/A'}`, 20, 97);
    } else {
      doc.text('Cliente: CONSUMIDOR FINAL', 20, 75);
    }

    // Table
    const tableData = items.map(item => [
      item.product_name,
      item.quantity,
      `$${parseFloat(item.unit_price).toFixed(2)}`,
      `${parseFloat(item.tax_rate || 0).toFixed(0)}%`,
      `$${parseFloat(item.line_total).toFixed(2)}`
    ]);

    autoTable(doc, {
      startY: (['FACTURA', 'DEVOLUCION'].includes(purchase.purchase_type) && purchase.customer_name) ? 105 : 85,
      head: [['Producto', 'Cant.', 'P. sin IVA', 'IVA', 'Total']],
      body: tableData,
      theme: 'striped',
      headStyles: { fillColor: [22, 163, 74] }
    });

    const finalY = doc.lastAutoTable.finalY + 10;

    doc.setFontSize(10);
    doc.text(`SUBTOTAL:`, 140, finalY);
    doc.text(`$${parseFloat(purchase.subtotal).toFixed(2)}`, 175, finalY, { align: 'right' });
    
    doc.text(`IVA (15%):`, 140, finalY + 7);
    doc.text(`$${parseFloat(purchase.iva).toFixed(2)}`, 175, finalY + 7, { align: 'right' });
    
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text(`TOTAL:`, 140, finalY + 16);
    doc.text(`$${parseFloat(purchase.total).toFixed(2)}`, 175, finalY + 16, { align: 'right' });

    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.text(`FORMA DE PAGO: ${purchase.payment_method || 'No registrada'}`, 20, finalY + 28);
    if (purchase.payment_method === 'EFECTIVO') {
      doc.text(`Efectivo recibido: $${parseFloat(purchase.amount_received || 0).toFixed(2)}`, 20, finalY + 35);
      doc.text(`Cambio entregado: $${parseFloat(purchase.change_amount || 0).toFixed(2)}`, 20, finalY + 42);
    } else if (purchase.payment_method === 'TRANSFERENCIA') {
      doc.text(`Nro. comprobante: ${purchase.transfer_reference || 'N/A'}`, 20, finalY + 35);
    }

    // Footer
    doc.setFontSize(8);
    doc.setFont(undefined, 'normal');
    doc.setTextColor(150);
    doc.text('Este documento es para control interno y no tiene validez tributaria ante el SRI.', 105, 280, { align: 'center' });

    doc.save(`${purchase.purchase_type === 'DEVOLUCION' ? 'Devolucion' : 'Comprobante'}_3Hermanos_${purchase.id}.pdf`);
  };

  const filteredTransactions = transactions.filter(t => 
    t.id.toString().includes(search) ||
    t.purchase_type.toLowerCase().includes(search.toLowerCase()) ||
    (t.display_customer_name || '').toLowerCase().includes(search.toLowerCase()) ||
    (t.customer_idnumber || '').includes(search)
  );

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <div className="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-2xl font-black text-gray-900 tracking-tighter">Historial de Transacciones</h1>
          <p className="text-sm text-gray-500 font-medium">Consulta y detalla todas las ventas realizadas.</p>
        </div>
        
        <div className="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
          <button onClick={() => setIsReturnOpen(true)} className="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-2 shadow-sm">
            <RotateCcw className="h-4 w-4" />
            Devoluciones
          </button>
        <div className="relative w-full md:w-80">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            type="text"
            placeholder="Buscar por ID o tipo..."
            className="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-primary-500 transition-all font-medium text-sm shadow-sm"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600"></div>
        </div>
      ) : (
        <div className="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[680px] table-fixed text-left border-collapse">
              <thead>
                <tr className="bg-gray-50 border-b border-gray-100">
                  <th className="w-[25%] px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Movimiento</th>
                  <th className="w-[20%] px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha</th>
                  <th className="w-[28%] px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Cliente</th>
                  <th className="w-[17%] px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Importe</th>
                  <th className="sticky right-0 z-10 w-[10%] bg-gray-50 px-4 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Ver</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {filteredTransactions.map((t) => (
                  <tr key={t.record_key || t.id} className={`${t.record_type === 'DEVOLUCION' ? 'bg-red-50/60 hover:bg-red-100/60' : 'hover:bg-gray-50/50'} transition-colors group`}>
                    <td className="px-4 py-3">
                      <p className={`truncate font-black text-xs ${t.record_type === 'DEVOLUCION' ? 'text-red-700' : 'text-gray-800'}`}>{t.return_code || `Venta #${t.id}`}</p>
                      <span className={`mt-1 inline-flex px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider ${t.record_type === 'DEVOLUCION' ? 'bg-red-100 text-red-700' : t.purchase_type === 'FACTURA' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500'}`}>
                        {t.record_type === 'DEVOLUCION' ? 'Devolución' : t.purchase_type === 'FACTURA' ? 'Con datos' : 'Sin datos'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex flex-col">
                        <span className="font-bold text-gray-900 text-xs">{new Date(t.created_at).toLocaleDateString()}</span>
                        <span className="text-[10px] text-gray-400 font-bold uppercase">{new Date(t.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <p className="truncate text-xs font-bold text-gray-800" title={t.display_customer_name || 'Consumidor final'}>
                        {t.display_customer_name || 'Consumidor final'}
                      </p>
                      {t.customer_idnumber && (
                        <p className="truncate text-[9px] text-gray-400">CI {t.customer_idnumber}</p>
                      )}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <span className={`font-black text-sm ${t.record_type === 'DEVOLUCION' ? 'text-red-700' : 'text-gray-900'}`}>${Math.abs(parseFloat(t.total)).toFixed(2)}</span>
                    </td>
                    <td className={`sticky right-0 px-4 py-3 text-right ${t.record_type === 'DEVOLUCION' ? 'bg-red-50 group-hover:bg-red-100' : 'bg-white group-hover:bg-gray-50'}`}>
                      <button
                        onClick={() => fetchDetails(t.id, t.record_type || 'VENTA')}
                        className="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all"
                      >
                        <Eye className="h-5 w-5" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      <AnimatePresence>
        {isReturnOpen && (
          <div className="fixed inset-0 bg-black/40 backdrop-blur-sm z-[110] flex items-center justify-center p-4">
            <motion.div initial={{ opacity: 0, scale: 0.96 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.96 }} className="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
              <div className="p-5 border-b bg-red-50 flex justify-between items-center">
                <div>
                  <h2 className="text-xl font-black text-gray-900 flex items-center gap-2"><RotateCcw className="h-5 w-5 text-red-600" />Registrar devolución</h2>
                  <p className="text-xs text-gray-500 mt-1">Selecciona uno, varios o todos los productos de la venta.</p>
                </div>
                <button onClick={() => setIsReturnOpen(false)} title="Cerrar" className="p-2 hover:bg-red-100 rounded-full"><X className="h-5 w-5" /></button>
              </div>

              <form onSubmit={handleReturnSubmit} className="p-6 overflow-y-auto space-y-5">
                <div>
                  <label className="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Venta original</label>
                  <select required value={returnForm.purchase_id} onChange={(event) => selectReturnSale(event.target.value)} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">Selecciona una transacción...</option>
                    {transactions.filter((item) => item.record_type === 'VENTA').map((sale) => (
                      <option key={sale.record_key} value={sale.id}>Venta #{sale.id} · {new Date(sale.created_at).toLocaleDateString()} · ${Number(sale.total).toFixed(2)}</option>
                    ))}
                  </select>
                </div>

                {returnForm.purchase_id && !returnSaleDetails && <div className="flex justify-center py-4"><Loader2 className="h-7 w-7 animate-spin text-red-600" /></div>}

                {returnSaleDetails && (
                  <>
                    <div>
                      <div className="mb-2 flex items-center justify-between gap-3">
                        <label className="text-xs font-black text-gray-500 uppercase tracking-wider">Productos a devolver</label>
                        <button type="button" onClick={() => {
                          const allItems = {};
                          returnSaleDetails.items.filter((item) => Number(item.returnable_quantity) > 0).forEach((item) => { allItems[item.id] = Number(item.returnable_quantity); });
                          setReturnForm((current) => ({ ...current, items: allItems }));
                        }} className="text-[10px] font-black uppercase tracking-wider text-red-600 hover:text-red-700">Devolver todo</button>
                      </div>
                      <div className="overflow-hidden rounded-xl border border-gray-200 divide-y divide-gray-100">
                        {returnSaleDetails.items.filter((item) => Number(item.returnable_quantity) > 0).map((item) => {
                          const selected = returnForm.items[item.id] !== undefined;
                          return (
                            <div key={item.id} className={`flex items-center gap-3 p-3 ${selected ? 'bg-red-50/60' : 'bg-white'}`}>
                              <input type="checkbox" checked={selected} onChange={(event) => setReturnForm((current) => {
                                const items = { ...current.items };
                                if (event.target.checked) items[item.id] = 1;
                                else delete items[item.id];
                                return { ...current, items };
                              })} className="h-4 w-4 accent-red-600" />
                              <div className="min-w-0 flex-1">
                                <p className="truncate text-xs font-bold text-gray-900">{item.product_name}</p>
                                <p className="text-[9px] font-bold text-gray-400">Disponible para devolver: {item.returnable_quantity}</p>
                              </div>
                              <input aria-label={`Cantidad de ${item.product_name}`} disabled={!selected} type="number" min="1" max={item.returnable_quantity} value={selected ? returnForm.items[item.id] : ''} onChange={(event) => setReturnForm((current) => ({ ...current, items: { ...current.items, [item.id]: event.target.value } }))} className="w-20 rounded-lg border border-gray-200 bg-white px-2 py-2 text-center text-xs font-black disabled:bg-gray-100" />
                            </div>
                          );
                        })}
                        {returnSaleDetails.items.every((item) => Number(item.returnable_quantity) <= 0) && <p className="p-4 text-center text-xs font-bold text-gray-500">Esta venta ya no tiene productos disponibles para devolver.</p>}
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Motivo de la devolución</label>
                      <select required value={returnForm.reason_category} onChange={(event) => setReturnForm((current) => ({ ...current, reason_category: event.target.value }))} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-red-500">
                        {Object.entries(RETURN_REASONS).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                      </select>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="md:col-span-2"><label className="block text-xs font-black text-gray-500 uppercase mb-1">Nombre y apellido</label><input required value={returnForm.customer_name} onChange={(event) => setReturnForm((current) => ({ ...current, customer_name: event.target.value }))} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl" /></div>
                      <div><label className="block text-xs font-black text-gray-500 uppercase mb-1">Cédula</label><input required value={returnForm.customer_idnumber} onChange={(event) => setReturnForm((current) => ({ ...current, customer_idnumber: event.target.value.replace(/\D/g, '').slice(0, 10) }))} placeholder="10 dígitos" className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl" /></div>
                      <div><label className="block text-xs font-black text-gray-500 uppercase mb-1">Número de teléfono</label><input required value={returnForm.customer_phone} onChange={(event) => setReturnForm((current) => ({ ...current, customer_phone: event.target.value.replace(/\D/g, '').slice(0, 10) }))} placeholder="09XXXXXXXX" className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl" /></div>
                      <div className="md:col-span-2"><label className="block text-xs font-black text-gray-500 uppercase mb-1">Detalle adicional</label><textarea value={returnForm.reason_detail} onChange={(event) => setReturnForm((current) => ({ ...current, reason_detail: event.target.value }))} placeholder="Describe brevemente qué ocurrió..." className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl min-h-24" /></div>
                    </div>

                    <button type="submit" disabled={savingReturn || Object.keys(returnForm.items).length === 0} className="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-xl font-black flex justify-center items-center gap-2 disabled:opacity-50">
                      {savingReturn ? <Loader2 className="h-5 w-5 animate-spin" /> : <RotateCcw className="h-5 w-5" />}
                      Confirmar devolución
                    </button>
                  </>
                )}
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Detail Modal */}
      <AnimatePresence>
        {selectedTransaction && (
          <div className="fixed inset-0 bg-black/40 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              className="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]"
            >
              <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div className="flex items-center gap-3">
                  <div className="p-2 bg-primary-600 text-white rounded-xl">
                    <Receipt className="h-5 w-5" />
                  </div>
                  <h2 className="text-xl font-black text-gray-900 tracking-tight">
                    {selectedTransaction.type === 'DEVOLUCION' ? 'Detalles de devolución' : 'Detalles de venta'} #{selectedTransaction.id}
                  </h2>
                </div>
                <button 
                  onClick={() => { setSelectedTransaction(null); setDetails(null); }}
                  className="p-2 hover:bg-gray-200 rounded-full transition-colors"
                >
                  <X className="h-5 w-5 text-gray-500" />
                </button>
              </div>

              <div className="flex-1 overflow-y-auto p-8">
                {loadingDetails ? (
                  <div className="flex justify-center items-center h-40">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                  </div>
                ) : details ? (
                  <div className="space-y-8">
                    {/* Header Info */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                      <div className="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        <div className="flex items-center gap-2 text-gray-400 mb-1">
                          <Calendar className="h-3 w-3" />
                          <span className="text-[8px] font-black uppercase tracking-widest">Fecha</span>
                        </div>
                        <p className="text-xs font-black text-gray-900">{new Date(details.purchase.created_at).toLocaleDateString()}</p>
                      </div>
                      <div className="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        <div className="flex items-center gap-2 text-gray-400 mb-1">
                          <Clock className="h-3 w-3" />
                          <span className="text-[8px] font-black uppercase tracking-widest">Hora</span>
                        </div>
                        <p className="text-xs font-black text-gray-900">{new Date(details.purchase.created_at).toLocaleTimeString()}</p>
                      </div>
                      <div className="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        <div className="flex items-center gap-2 text-gray-400 mb-1">
                          <CreditCard className="h-3 w-3" />
                          <span className="text-[8px] font-black uppercase tracking-widest">Tipo</span>
                        </div>
                        <p className="text-xs font-black text-gray-900">
                          {details.purchase.purchase_type === 'DEVOLUCION' ? 'Devolución' : details.purchase.purchase_type === 'FACTURA' ? 'Comprobante de venta' : 'Nota de venta'}
                        </p>
                      </div>
                      <div className="p-4 bg-primary-50 rounded-2xl border border-primary-100">
                        <div className="flex items-center gap-2 text-primary-400 mb-1">
                          <ShoppingBag className="h-3 w-3" />
                          <span className="text-[8px] font-black uppercase tracking-widest">Total</span>
                        </div>
                        <p className="text-sm font-black text-primary-600">${parseFloat(details.purchase.total).toFixed(2)}</p>
                      </div>
                    </div>

                    {details.purchase.purchase_type === 'DEVOLUCION' && (
                      <div className="p-4 bg-red-50 border border-red-100 rounded-2xl text-sm">
                        <p className="font-black text-red-800">{details.purchase.return_code} · Venta original #{details.purchase.original_purchase_id}</p>
                        <p className="font-bold text-gray-900 mt-2">Motivo: {RETURN_REASONS[details.purchase.reason_category] || details.purchase.reason_category}</p>
                        {details.purchase.reason_detail && <p className="text-gray-600 mt-1">{details.purchase.reason_detail}</p>}
                        <p className="text-gray-600 mt-2">Cliente: {details.purchase.customer_name} · CI {details.purchase.customer_idnumber} · {details.purchase.customer_phone}</p>
                      </div>
                    )}

                    {/* Products List */}
                    <div>
                      <h3 className="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <ShoppingBag className="h-3 w-3" />
                        {details.purchase.purchase_type === 'DEVOLUCION' ? 'Productos devueltos' : 'Productos adquiridos'}
                      </h3>
                      <div className="bg-white border border-gray-100 rounded-[2rem] overflow-hidden">
                        <table className="w-full text-left">
                          <thead>
                            <tr className="bg-gray-50 border-b border-gray-100">
                              <th className="px-6 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest">Producto</th>
                              <th className="px-6 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Cant.</th>
                              <th className="px-6 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest text-right">Precio sin IVA</th>
                              <th className="px-6 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest text-right">Subtotal</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-gray-50">
                            {details.items.map((item, i) => (
                              <tr key={i} className="hover:bg-gray-50/50 transition-colors">
                                <td className="px-6 py-3">
                                  <div className="flex items-center gap-3">
                                    <div className="h-8 w-8 rounded-lg bg-gray-100 overflow-hidden border border-gray-100 flex-shrink-0">
                                      <img src={item.image_url} alt={item.product_name} className="h-full w-full object-cover" />
                                    </div>
                                    <span className="font-bold text-gray-900 text-xs">{item.product_name}</span>
                                  </div>
                                </td>
                                <td className="px-6 py-3 text-center">
                                  <span className="font-black text-gray-900 text-xs">{item.quantity}</span>
                                </td>
                                <td className="px-6 py-3 text-right">
                                  <span className="font-bold text-gray-400 text-xs">${parseFloat(item.unit_price).toFixed(2)}</span>
                                </td>
                                <td className="px-6 py-3 text-right">
                                  <span className="font-black text-gray-900 text-xs">${parseFloat(item.line_total).toFixed(2)}</span>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>

                    {/* Footer Summary */}
                    <div className="flex justify-end pt-4">
                      <div className="w-full md:w-64 space-y-2">
                        <div className="flex justify-between items-center text-xs font-bold text-gray-400">
                          <span>Subtotal</span>
                          <span>${parseFloat(details.purchase.subtotal).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between items-center text-xs font-bold text-gray-400">
                          <span>IVA (15%)</span>
                          <span>${parseFloat(details.purchase.iva).toFixed(2)}</span>
                        </div>
                        <div className="pt-2 border-t border-gray-100 flex justify-between items-center">
                          <span className="text-sm font-black text-gray-900 uppercase tracking-widest">Total</span>
                          <span className="text-2xl font-black text-primary-600 tracking-tighter">${parseFloat(details.purchase.total).toFixed(2)}</span>
                        </div>
                        <div className="mt-3 p-3 bg-gray-50 rounded-xl text-xs text-gray-600 space-y-1">
                          <p className="font-black text-gray-900">Pago: {details.purchase.payment_method || 'No registrado'}</p>
                          {details.purchase.payment_method === 'EFECTIVO' && <><p>Recibido: ${parseFloat(details.purchase.amount_received || 0).toFixed(2)}</p><p>Cambio: ${parseFloat(details.purchase.change_amount || 0).toFixed(2)}</p></>}
                          {details.purchase.payment_method === 'TRANSFERENCIA' && <p>Comprobante: {details.purchase.transfer_reference || 'N/A'}</p>}
                        </div>
                      </div>
                    </div>
                  </div>
                ) : null}
              </div>

              <div className="p-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button 
                  onClick={downloadPDF}
                  className="bg-primary-600 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-700 transition-all shadow-lg active:scale-95 flex items-center gap-2"
                >
                  <Download className="h-4 w-4" />
                  Descargar PDF
                </button>
                <button 
                  onClick={() => { setSelectedTransaction(null); setDetails(null); }}
                  className="bg-gray-900 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-all shadow-lg active:scale-95"
                >
                  Cerrar Detalles
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default Transactions;
