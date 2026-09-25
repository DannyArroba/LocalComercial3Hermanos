import React, { useState } from 'react';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import { checkoutApi, customersApi } from '../api';
import { ArrowLeft, Banknote, CheckCircle2, CreditCard, Loader2, Search, ShieldCheck, UserCheck, XCircle } from 'lucide-react';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const Checkout = () => {
  const { cart, cartTotal, ivaTotal, totalWithIva, clearCart } = useCart();
  const { user } = useAuth();
  const navigate = useNavigate();
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [searchingCustomer, setSearchingCustomer] = useState(false);
  const [customerLookup, setCustomerLookup] = useState(null);
  const [formData, setFormData] = useState({
    purchase_type: 'CONSUMIDOR_FINAL',
    customer_name: '', customer_email: '', customer_phone: '',
    customer_address: '', customer_idnumber: '',
    payment_method: 'EFECTIVO', amount_received: '', transfer_reference: ''
  });

  const cashReceived = Number(formData.amount_received || 0);
  const change = Math.max(0, cashReceived - totalWithIva);
  const cashIsEnough = cashReceived >= totalWithIva;

  const handleInputChange = (event) => {
    const { name } = event.target;
    let value = event.target.value;
    if (['customer_idnumber', 'customer_phone'].includes(name)) value = value.replace(/\D/g, '').slice(0, 10);
    setFormData((current) => ({ ...current, [name]: value }));
    if (name === 'customer_idnumber') setCustomerLookup(null);
  };

  const findCustomer = async () => {
    if (!/^\d{10}$/.test(formData.customer_idnumber)) {
      setCustomerLookup({ found: false, message: 'Ingresa una cedula de exactamente 10 digitos.' });
      return;
    }
    setSearchingCustomer(true);
    try {
      const response = await customersApi.findByIdNumber(formData.customer_idnumber);
      const customer = response.data.data;
      if (customer) {
        setFormData((current) => ({
          ...current,
          customer_name: customer.name || '', customer_email: customer.email || '',
          customer_phone: customer.phone || '', customer_address: customer.address || ''
        }));
        setCustomerLookup({ found: true, message: 'Cliente encontrado. Datos completados.' });
      } else {
        setCustomerLookup({ found: false, message: 'Cliente nuevo. Completa sus datos para guardarlo con esta venta.' });
      }
    } catch {
      setCustomerLookup({ found: false, message: 'No se pudo consultar el cliente.' });
    } finally {
      setSearchingCustomer(false);
    }
  };

  const validateCustomerStep = () => {
    if (formData.purchase_type === 'FACTURA') {
      if (!/^\d{10}$/.test(formData.customer_idnumber)) {
        Swal.fire('Cedula invalida', 'Debe contener exactamente 10 digitos.', 'error');
        return;
      }
      if (!/^09\d{8}$/.test(formData.customer_phone) || !formData.customer_name.trim() || !formData.customer_address.trim()) {
        Swal.fire('Datos incompletos', 'Completa nombre, celular y direccion del cliente.', 'error');
        return;
      }
    }
    setStep(2);
  };

  const generatePDF = (purchaseId) => {
    const doc = new jsPDF();
    doc.setFontSize(20);
    doc.setTextColor(22, 163, 74);
    doc.text('LOCAL COMERCIAL TRES HERMANOS', 105, 18, { align: 'center' });
    doc.setFontSize(10);
    doc.setTextColor(90);
    doc.text('COMPROBANTE DE VENTA', 105, 26, { align: 'center' });
    doc.setDrawColor(210);
    doc.line(18, 32, 192, 32);
    doc.setTextColor(20);
    doc.text(`Nro. #${String(purchaseId).padStart(6, '0')}`, 18, 41);
    doc.text(`Fecha: ${new Date().toLocaleString()}`, 18, 47);
    doc.text(`Vendedor: ${user.email}`, 18, 53);

    let startY = 65;
    if (formData.purchase_type === 'FACTURA') {
      doc.setFont(undefined, 'bold');
      doc.text('DATOS DEL CLIENTE', 18, 63);
      doc.setFont(undefined, 'normal');
      doc.text(`Nombre: ${formData.customer_name}`, 18, 70);
      doc.text(`Cedula: ${formData.customer_idnumber}`, 18, 76);
      doc.text(`Direccion: ${formData.customer_address}`, 18, 82);
      doc.text(`Telefono: ${formData.customer_phone}`, 18, 88);
      startY = 96;
    } else {
      doc.text('Cliente: CONSUMIDOR FINAL', 18, 63);
      startY = 70;
    }

    const rows = cart.map((item) => {
      const base = Number(item.price) * item.quantity;
      const tax = Number(item.applies_iva) === 1 ? Math.round(base * 0.15 * 100) / 100 : 0;
      return [item.name, item.quantity, `$${Number(item.price).toFixed(2)}`, Number(item.applies_iva) === 1 ? '15%' : '0%', `$${(base + tax).toFixed(2)}`];
    });
    autoTable(doc, {
      startY,
      head: [['Producto', 'Cant.', 'P. sin IVA', 'IVA', 'Total']],
      body: rows,
      theme: 'striped',
      headStyles: { fillColor: [22, 163, 74] }
    });

    const y = doc.lastAutoTable.finalY + 9;
    doc.text('Subtotal sin IVA:', 125, y);
    doc.text(`$${cartTotal.toFixed(2)}`, 190, y, { align: 'right' });
    doc.text('IVA 15%:', 125, y + 7);
    doc.text(`$${ivaTotal.toFixed(2)}`, 190, y + 7, { align: 'right' });
    doc.setFont(undefined, 'bold');
    doc.text('TOTAL A PAGAR:', 125, y + 16);
    doc.text(`$${totalWithIva.toFixed(2)}`, 190, y + 16, { align: 'right' });
    doc.setFont(undefined, 'normal');

    const paymentY = y + 29;
    doc.setFont(undefined, 'bold');
    doc.text('FORMA DE PAGO', 18, paymentY);
    doc.setFont(undefined, 'normal');
    if (formData.payment_method === 'EFECTIVO') {
      doc.text('Metodo: Efectivo', 18, paymentY + 7);
      doc.text(`Efectivo recibido: $${cashReceived.toFixed(2)}`, 18, paymentY + 14);
      doc.text(`Cambio entregado: $${change.toFixed(2)}`, 18, paymentY + 21);
    } else {
      doc.text('Metodo: Transferencia', 18, paymentY + 7);
      doc.text(`Nro. de comprobante: ${formData.transfer_reference}`, 18, paymentY + 14);
    }
    doc.save(`Comprobante_3Hermanos_${purchaseId}.pdf`);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!user) return navigate('/login');
    if (formData.payment_method === 'EFECTIVO' && !cashIsEnough) {
      Swal.fire('Falta efectivo', 'El valor recibido debe cubrir el total a pagar.', 'error');
      return;
    }
    if (formData.payment_method === 'TRANSFERENCIA' && !formData.transfer_reference.trim()) {
      Swal.fire('Comprobante requerido', 'Ingresa el numero del comprobante de transferencia.', 'error');
      return;
    }

    setLoading(true);
    try {
      const response = await checkoutApi.process(formData);
      if (response.data.status !== 'success') {
        Swal.fire('Error', response.data.message, 'error');
        return;
      }
      generatePDF(response.data.purchase_id);
      await Swal.fire('Cobro completado', `Total cobrado: $${Number(response.data.total).toFixed(2)}`, 'success');
      await clearCart();
      navigate('/sales');
    } catch (error) {
      Swal.fire('Error', error.response?.data?.message || 'No se pudo completar el cobro.', 'error');
    } finally {
      setLoading(false);
    }
  };

  if (cart.length === 0) {
    return <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 p-4"><XCircle className="h-16 w-16 text-red-200 mb-4" /><h2 className="text-2xl font-black">Venta vacia</h2><button onClick={() => navigate('/sales')} className="mt-6 bg-primary-600 text-white px-6 py-3 rounded-xl font-bold">Volver a Ventas</button></div>;
  }

  return (
    <div className="bg-gray-50 min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4">
        <button onClick={() => step === 2 ? setStep(1) : navigate(-1)} className="flex items-center gap-2 text-gray-600 hover:text-primary-600 mb-6 font-medium"><ArrowLeft className="h-4 w-4" />Volver</button>
        <div className="flex items-center gap-3 mb-8">
          {[['1', 'Datos y resumen'], ['2', 'Forma de pago']].map(([number, label]) => (
            <div key={number} className={`flex items-center gap-2 ${step >= Number(number) ? 'text-primary-700' : 'text-gray-400'}`}>
              <span className={`h-8 w-8 rounded-full flex items-center justify-center font-black ${step >= Number(number) ? 'bg-primary-600 text-white' : 'bg-gray-200'}`}>{number}</span>
              <span className="text-sm font-bold">{label}</span>
            </div>
          ))}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-7">
            {step === 1 ? (
              <div className="space-y-6">
                <h2 className="text-xl font-black text-gray-900">Datos del comprobante de venta</h2>
                <div className="grid grid-cols-2 gap-3">
                  <button type="button" onClick={() => setFormData((v) => ({ ...v, purchase_type: 'CONSUMIDOR_FINAL' }))} className={`p-3 rounded-xl border-2 font-bold ${formData.purchase_type === 'CONSUMIDOR_FINAL' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-gray-100'}`}>Consumidor final</button>
                  <button type="button" onClick={() => setFormData((v) => ({ ...v, purchase_type: 'FACTURA' }))} className={`p-3 rounded-xl border-2 font-bold ${formData.purchase_type === 'FACTURA' ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-gray-100'}`}>Comprobante con datos</button>
                </div>
                {formData.purchase_type === 'FACTURA' && (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="md:col-span-2"><label className="text-xs font-bold text-gray-500">CEDULA</label><div className="flex gap-2"><input name="customer_idnumber" value={formData.customer_idnumber} onChange={handleInputChange} className="flex-1 px-4 py-3 bg-gray-50 border rounded-xl" /><button type="button" onClick={findCustomer} className="px-4 bg-gray-900 text-white rounded-xl" title="Buscar cliente">{searchingCustomer ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}</button></div></div>
                    {customerLookup && <div className={`md:col-span-2 p-3 rounded-xl text-sm font-medium ${customerLookup.found ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-800'}`}><UserCheck className="inline h-4 w-4 mr-2" />{customerLookup.message}</div>}
                    <div><label className="text-xs font-bold text-gray-500">NOMBRE COMPLETO</label><input name="customer_name" value={formData.customer_name} onChange={handleInputChange} className="w-full px-4 py-3 bg-gray-50 border rounded-xl" /></div>
                    <div><label className="text-xs font-bold text-gray-500">CELULAR</label><input name="customer_phone" value={formData.customer_phone} onChange={handleInputChange} className="w-full px-4 py-3 bg-gray-50 border rounded-xl" /></div>
                    <div><label className="text-xs font-bold text-gray-500">CORREO</label><input name="customer_email" value={formData.customer_email} onChange={handleInputChange} className="w-full px-4 py-3 bg-gray-50 border rounded-xl" /></div>
                    <div><label className="text-xs font-bold text-gray-500">DIRECCION</label><input name="customer_address" value={formData.customer_address} onChange={handleInputChange} className="w-full px-4 py-3 bg-gray-50 border rounded-xl" /></div>
                  </div>
                )}
                <button type="button" onClick={validateCustomerStep} className="w-full py-4 bg-primary-600 text-white rounded-xl font-black flex items-center justify-center gap-2">Continuar al pago <CreditCard className="h-5 w-5" /></button>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-6">
                <div><h2 className="text-xl font-black text-gray-900">¿Como paga el cliente?</h2><p className="text-sm text-gray-500 mt-1">Selecciona el metodo y confirma los datos antes de generar el comprobante.</p></div>
                <div className="grid grid-cols-2 gap-3">
                  <button type="button" onClick={() => setFormData((v) => ({ ...v, payment_method: 'EFECTIVO' }))} className={`p-4 rounded-xl border-2 font-bold flex items-center justify-center gap-2 ${formData.payment_method === 'EFECTIVO' ? 'border-green-600 bg-green-50 text-green-700' : 'border-gray-100'}`}><Banknote className="h-5 w-5" />Efectivo</button>
                  <button type="button" onClick={() => setFormData((v) => ({ ...v, payment_method: 'TRANSFERENCIA' }))} className={`p-4 rounded-xl border-2 font-bold flex items-center justify-center gap-2 ${formData.payment_method === 'TRANSFERENCIA' ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-gray-100'}`}><CreditCard className="h-5 w-5" />Transferencia</button>
                </div>
                {formData.payment_method === 'EFECTIVO' ? (
                  <div className="space-y-4"><div><label className="text-xs font-bold text-gray-500">EFECTIVO RECIBIDO</label><input name="amount_received" type="number" min="0" step="0.01" autoFocus value={formData.amount_received} onChange={handleInputChange} className="w-full px-4 py-4 bg-gray-50 border rounded-xl text-2xl font-black" /></div><div className={`p-5 rounded-xl border ${cashIsEnough ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'}`}><p className="text-xs font-black uppercase text-gray-500">Cambio para el cliente</p><p className="text-3xl font-black text-gray-900 mt-1">${change.toFixed(2)}</p>{!cashIsEnough && <p className="text-xs text-yellow-800 mt-2">Faltan ${(totalWithIva - cashReceived).toFixed(2)} para completar el pago.</p>}</div></div>
                ) : (
                  <div><label className="text-xs font-bold text-gray-500">NUMERO DE COMPROBANTE DE TRANSFERENCIA</label><input name="transfer_reference" value={formData.transfer_reference} onChange={handleInputChange} placeholder="Ej. 458921736" className="w-full px-4 py-4 bg-gray-50 border rounded-xl font-mono text-lg" /><p className="text-xs text-gray-500 mt-2">Verifica el ingreso en la cuenta antes de confirmar.</p></div>
                )}
                <button type="submit" disabled={loading || (formData.payment_method === 'EFECTIVO' && !cashIsEnough)} className="w-full py-4 bg-primary-600 text-white rounded-xl font-black flex items-center justify-center gap-2 disabled:opacity-50">{loading ? <Loader2 className="h-5 w-5 animate-spin" /> : <CheckCircle2 className="h-5 w-5" />}Cobrar y generar comprobante</button>
                <div className="flex items-center justify-center gap-2 text-xs text-gray-400"><ShieldCheck className="h-4 w-4" />El cobro se registra junto con la venta.</div>
              </form>
            )}
          </div>

          <div className="bg-gray-900 rounded-3xl p-7 text-white h-fit lg:sticky lg:top-24">
            <h2 className="text-xl font-black mb-6">Resumen de la venta</h2>
            <div className="space-y-4 max-h-72 overflow-y-auto pr-2">
              {cart.map((item) => {
                const base = Number(item.price) * item.quantity;
                const tax = Number(item.applies_iva) === 1 ? Math.round(base * 0.15 * 100) / 100 : 0;
                return <div key={item.id} className="flex justify-between gap-4"><div><p className="font-bold text-sm">{item.name} x {item.quantity}</p><p className="text-xs text-gray-400">Base ${base.toFixed(2)} · IVA {Number(item.applies_iva) === 1 ? '15%' : '0%'}</p></div><p className="font-black">${(base + tax).toFixed(2)}</p></div>;
              })}
            </div>
            <div className="border-t border-white/10 mt-6 pt-5 space-y-3"><div className="flex justify-between text-gray-400"><span>Subtotal sin IVA</span><span>${cartTotal.toFixed(2)}</span></div><div className="flex justify-between text-gray-400"><span>IVA 15%</span><span>${ivaTotal.toFixed(2)}</span></div><div className="flex justify-between text-2xl font-black border-t border-white/10 pt-4"><span>Total a pagar</span><span className="text-primary-400">${totalWithIva.toFixed(2)}</span></div></div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Checkout;
