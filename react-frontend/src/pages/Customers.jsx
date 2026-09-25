import React, { useEffect, useMemo, useState } from 'react';
import { customersApi } from '../api';
import {
  Edit2,
  Loader2,
  Mail,
  MapPin,
  Phone,
  Plus,
  Search,
  Trash2,
  UserRound,
  X,
} from 'lucide-react';
import Swal from 'sweetalert2';

const emptyCustomer = {
  name: '',
  id_number: '',
  email: '',
  phone: '',
  address: '',
};

const Customers = () => {
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [search, setSearch] = useState('');
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState(null);

  const fetchCustomers = async () => {
    setLoading(true);
    try {
      const response = await customersApi.getAll();
      if (response.data.status === 'success') {
        setCustomers(response.data.data);
      }
    } catch (error) {
      Swal.fire('Error', 'No se pudo cargar la lista de clientes', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCustomers();
  }, []);

  const filteredCustomers = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return customers;
    return customers.filter((customer) =>
      [customer.name, customer.id_number, customer.phone, customer.email]
        .filter(Boolean)
        .some((value) => value.toLowerCase().includes(term))
    );
  }, [customers, search]);

  const openForm = (customer = null) => {
    setEditingCustomer(customer);
    setIsFormOpen(true);
  };

  const handleSave = async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(event.currentTarget).entries());

    if (!/^\d{10}$/.test(data.id_number)) {
      Swal.fire('Cedula invalida', 'Debe contener exactamente 10 digitos', 'error');
      return;
    }
    if (!/^09\d{8}$/.test(data.phone)) {
      Swal.fire('Celular invalido', 'Debe iniciar con 09 y contener 10 digitos', 'error');
      return;
    }

    setSaving(true);
    try {
      const response = editingCustomer
        ? await customersApi.update({ id: editingCustomer.id, ...data })
        : await customersApi.add(data);

      if (response.data.status !== 'success') {
        Swal.fire('Error', response.data.message, 'error');
        return;
      }

      setIsFormOpen(false);
      setEditingCustomer(null);
      await fetchCustomers();
      Swal.fire('Listo', response.data.message, 'success');
    } catch (error) {
      Swal.fire('Error', error.response?.data?.message || 'No se pudo guardar el cliente', 'error');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (customer) => {
    const result = await Swal.fire({
      title: 'Eliminar cliente',
      text: `Se eliminara a ${customer.name}`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;

    const response = await customersApi.delete(customer.id);
    if (response.data.status === 'success') {
      setCustomers((current) => current.filter((item) => item.id !== customer.id));
      Swal.fire('Eliminado', response.data.message, 'success');
    } else {
      Swal.fire('No se pudo eliminar', response.data.message, 'error');
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-2xl font-black text-gray-900">Gestion de Clientes</h1>
          <p className="text-sm text-gray-500 mt-1">Administra los datos principales de los clientes</p>
        </div>
        <button
          onClick={() => openForm()}
          className="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-xl font-black text-xs uppercase flex items-center justify-center gap-2 shadow-lg shadow-primary-100"
        >
          <Plus className="h-4 w-4" />
          Nuevo cliente
        </button>
      </div>

      <div className="relative mb-5 max-w-md">
        <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
        <input
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder="Buscar por nombre, cedula o celular"
          className="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-primary-500"
        />
      </div>

      {loading ? (
        <div className="h-64 flex items-center justify-center">
          <Loader2 className="h-9 w-9 animate-spin text-primary-600" />
        </div>
      ) : (
        <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
          <div className="overflow-x-auto">
            <table className="w-full text-left">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase">Cliente</th>
                  <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase">Telefono</th>
                  <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase">Correo</th>
                  <th className="px-5 py-4 text-[10px] font-black text-gray-400 uppercase">Direccion</th>
                  <th className="px-5 py-4 text-right text-[10px] font-black text-gray-400 uppercase">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredCustomers.map((customer) => (
                  <tr key={customer.id} className="hover:bg-gray-50">
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-3">
                        <div className="h-10 w-10 bg-primary-50 text-primary-700 rounded-lg flex items-center justify-center">
                          <UserRound className="h-5 w-5" />
                        </div>
                        <div>
                          <p className="font-bold text-sm text-gray-900">{customer.name}</p>
                          <p className="text-xs text-gray-500">CI {customer.id_number}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-2 text-xs font-semibold text-gray-700">
                        <Phone className="h-4 w-4 text-gray-400" />
                        {customer.phone}
                      </div>
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-2 text-xs text-gray-600">
                        <Mail className="h-4 w-4 text-gray-400" />
                        {customer.email || 'Sin correo'}
                      </div>
                    </td>
                    <td className="px-5 py-4 max-w-xs">
                      <div className="flex items-start gap-2 text-xs text-gray-600">
                        <MapPin className="h-4 w-4 text-gray-400 flex-shrink-0" />
                        <span>{customer.address}</span>
                      </div>
                    </td>
                    <td className="px-5 py-4">
                      <div className="flex justify-end gap-1">
                        <button onClick={() => openForm(customer)} title="Editar" className="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg">
                          <Edit2 className="h-4 w-4" />
                        </button>
                        <button onClick={() => handleDelete(customer)} title="Eliminar" className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {isFormOpen && (
        <div className="fixed inset-0 z-[100] bg-black/40 flex items-center justify-center p-4">
          <div className="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div className="px-4 md:px-6 py-4 md:py-5 border-b flex items-center justify-between bg-gray-50 shrink-0">
              <h2 className="font-black text-lg">{editingCustomer ? 'Editar cliente' : 'Registrar cliente'}</h2>
              <button onClick={() => setIsFormOpen(false)} className="p-2 hover:bg-gray-200 rounded-lg"><X className="h-5 w-5" /></button>
            </div>
            <form onSubmit={handleSave} className="p-4 md:p-6 grid grid-cols-1 md:grid-cols-2 gap-4 overflow-y-auto">
              {Object.entries({
                name: ['Nombre completo', 'text'],
                id_number: ['Cedula', 'text'],
                email: ['Correo electronico', 'email'],
                phone: ['Celular', 'text'],
              }).map(([name, [label, type]]) => (
                <label key={name} className="space-y-1">
                  <span className="text-xs font-bold text-gray-500 uppercase">{label}</span>
                  <input
                    name={name}
                    type={type}
                    required={name !== 'email'}
                    maxLength={name === 'id_number' || name === 'phone' ? 10 : undefined}
                    defaultValue={editingCustomer?.[name] ?? emptyCustomer[name]}
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-primary-500"
                  />
                </label>
              ))}
              <label className="md:col-span-2 space-y-1">
                <span className="text-xs font-bold text-gray-500 uppercase">Direccion</span>
                <textarea
                  name="address"
                  required
                  rows="3"
                  defaultValue={editingCustomer?.address || ''}
                  className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                />
              </label>
              <div className="md:col-span-2 flex justify-end gap-3 pt-3">
                <button type="button" onClick={() => setIsFormOpen(false)} className="px-5 py-3 bg-gray-100 rounded-xl font-bold">Cancelar</button>
                <button disabled={saving} className="px-5 py-3 bg-primary-600 text-white rounded-xl font-bold flex items-center gap-2 disabled:opacity-60">
                  {saving && <Loader2 className="h-4 w-4 animate-spin" />}
                  Guardar cliente
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default Customers;
