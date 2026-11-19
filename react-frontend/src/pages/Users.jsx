import React, { useState, useEffect } from 'react';
import { usersApi } from '../api';
import { UserPlus, Edit2, Trash2, X, Shield, User, Loader2, Key } from 'lucide-react';
import Swal from 'sweetalert2';
import { useAuth } from '../context/AuthContext';
import { defaultWorkerPermissions, storeSections, userDisplayName, userPermissions } from '../utils/access';

const Users = () => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [saving, setSaving] = useState(false);
  const { user: currentUser, refreshUser } = useAuth();
  const [formRole, setFormRole] = useState('cashier');
  const [permissions, setPermissions] = useState([...defaultWorkerPermissions]);
  const editingSelf = editingUser && Number(editingUser.id) === Number(currentUser?.id);

  const openEditor = (user = null) => {
    setEditingUser(user);
    setFormRole(user?.role === 'admin' ? 'admin' : 'cashier');
    setPermissions(user?.role === 'admin' ? [...defaultWorkerPermissions] : userPermissions(user || { role: 'cashier' }));
    setIsModalOpen(true);
  };

  const togglePermission = (key) => setPermissions((current) => current.includes(key)
    ? current.filter((value) => value !== key) : [...current, key]);

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    setLoading(true);
    try {
      const res = await usersApi.getAll();
      if (Array.isArray(res.data)) setUsers(res.data);
      else Swal.fire('Error', res.data.message || 'No se pudieron cargar los usuarios', 'error');
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    const result = await Swal.fire({
      title: '¿Eliminar usuario?',
      text: "Esta acción no se puede deshacer",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
      try {
        const res = await usersApi.delete(id);
        if (res.data.status === 'success') {
          setUsers(users.filter(u => u.id !== id));
          Swal.fire('Eliminado', 'Usuario eliminado con éxito.', 'success');
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('Error', err.response?.data?.message || 'No se pudo eliminar el usuario', 'error');
      }
    }
  };

  const handleSave = async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.name = String(data.name || '').trim();
    data.role = formRole;
    data.permissions = formRole === 'admin' ? storeSections.map(({ key }) => key) : permissions;

    if (!data.name) {
      Swal.fire('Error', 'Ingresa el nombre del trabajador', 'error');
      return;
    }

    // VALIDACIONES FRONTEND
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(data.email)) {
      Swal.fire('Error', 'Por favor ingresa un correo electrónico válido', 'error');
      return;
    }

    if (!editingUser && data.password.length < 8) {
      Swal.fire('Error', 'La contraseña debe tener al menos 8 caracteres para mayor seguridad', 'error');
      return;
    }

    if (editingUser && data.password && data.password.length < 8) {
      Swal.fire('Error', 'La nueva contraseña debe tener al menos 8 caracteres', 'error');
      return;
    }

    if (data.email.split('@')[0].length < 4) {
      Swal.fire('Error', 'El nombre de usuario (antes del @) debe tener al menos 4 caracteres', 'error');
      return;
    }
    
    setSaving(true);
    
    try {
      let res;
      if (editingUser) {
        res = await usersApi.update({ id: editingUser.id, ...data });
      } else {
        res = await usersApi.add(data);
      }

      if (res.data.status === 'success') {
        Swal.fire('Éxito', res.data.message, 'success');
        setIsModalOpen(false);
        setEditingUser(null);
        fetchUsers();
        await refreshUser();
      } else {
        Swal.fire('Error', res.data.message, 'error');
      }
    } catch (err) {
      Swal.fire('Error', err.response?.data?.message || 'Ocurrió un error al guardar', 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <div className="flex flex-wrap gap-4 justify-between items-center mb-8">
        <div>
          <h1 className="text-2xl font-black text-gray-900 tracking-tighter">Gestión de Usuarios</h1>
          <p className="text-sm text-gray-500 font-medium mt-1">Administra los accesos de trabajadores y administradores</p>
        </div>
        <button
          onClick={() => openEditor()}
          className="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest flex items-center gap-2 transition-all shadow-lg shadow-primary-100"
        >
          <UserPlus className="h-4 w-4" />
          Nuevo Usuario
        </button>
      </div>

      {loading ? (
        <div className="flex justify-center items-center h-64">
          <Loader2 className="h-10 w-10 animate-spin text-primary-600" />
        </div>
      ) : (
        <div className="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-x-auto">
          <table className="w-full text-left">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>
                <th className="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Usuario / Email</th>
                <th className="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Rol</th>
                <th className="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha Registro</th>
                <th className="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {users.map((user) => (
                <tr key={user.id} className="hover:bg-gray-50/50 transition-colors group">
                  <td className="px-6 py-3">
                    <div className="flex items-center gap-3">
                      <div className={`h-9 w-9 rounded-lg flex items-center justify-center font-bold text-sm ${user.role === 'admin' ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-600'}`}>
                        {userDisplayName(user).charAt(0).toUpperCase()}
                      </div>
                      <div><p className="font-bold text-gray-900 text-sm">{userDisplayName(user)}</p><p className="text-xs text-gray-500 mt-1">{user.email}</p></div>
                    </div>
                    <p className="mt-1.5 text-[10px] text-gray-500 max-w-56">{user.role === 'admin' ? 'Todas las secciones' : storeSections.filter(({ key }) => userPermissions(user).includes(key)).map(({ label }) => label).join(' · ') || 'Sin accesos habilitados'}</p>
                  </td>
                  <td className="px-6 py-3">
                    <div className="flex items-center gap-2">
                      {user.role === 'admin' ? (
                        <span className="px-2 py-0.5 bg-primary-100 text-primary-700 text-[9px] font-black uppercase rounded-lg flex items-center gap-1">
                          <Shield className="h-3 w-3" />
                          Administrador
                        </span>
                      ) : (
                        <span className="px-2 py-0.5 bg-gray-100 text-gray-600 text-[9px] font-black uppercase rounded-lg flex items-center gap-1">
                          <User className="h-3 w-3" />
                          Trabajador
                        </span>
                      )}
                    </div>
                  </td>
                  <td className="px-6 py-3 text-xs font-bold text-gray-500">
                    {new Date(user.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-6 py-3 text-right">
                    <div className="flex justify-end gap-1.5 opacity-80 group-hover:opacity-100 transition-opacity">
                      <button
                        onClick={() => openEditor(user)}
                        title={`Editar ${userDisplayName(user)}`}
                        className="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all"
                      >
                        <Edit2 className="h-5 w-5" />
                      </button>
                      <button
                        onClick={() => handleDelete(user.id)}
                        title={`Eliminar ${userDisplayName(user)}`}
                        disabled={Number(user.id) === Number(currentUser?.id)}
                        className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                      >
                        <Trash2 className="h-5 w-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div role="dialog" aria-modal="true" aria-labelledby="user-editor-title" className="bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200">
            <div className="p-6 border-b flex justify-between items-center bg-gray-50 shrink-0">
              <h2 id="user-editor-title" className="text-xl font-bold text-gray-900">
                {editingUser ? 'Editar Usuario' : 'Nuevo Usuario'}
              </h2>
              <button aria-label="Cerrar edición de usuario" onClick={() => setIsModalOpen(false)} className="p-2 hover:bg-gray-200 rounded-full transition-colors">
                <X className="h-6 w-6" />
              </button>
            </div>
            
            <form onSubmit={handleSave} className="p-5 sm:p-8 space-y-4 overflow-y-auto">
              <div className="space-y-2">
                <label htmlFor="user-name" className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Nombre del trabajador</label>
                <input id="user-name" name="name" type="text" required maxLength={120} defaultValue={editingUser ? userDisplayName(editingUser) : ''} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all" placeholder="Nombre y apellido" />
              </div>
              <div className="space-y-2">
                <label htmlFor="user-email" className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Email de Usuario</label>
                <input id="user-email" name="email" type="email" required defaultValue={editingUser?.email} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all" placeholder="ejemplo@3hermanos.com" />
              </div>

              <div className="space-y-2">
                <label htmlFor="user-password" className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Contraseña {editingUser && '(Dejar vacío para no cambiar)'}</label>
                <div className="relative">
                  <Key className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                  <input id="user-password" name="password" type="password" required={!editingUser} autoComplete="new-password" className="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all" placeholder="••••••••" />
                </div>
              </div>

              <div className="space-y-2">
                <label htmlFor="user-role" className="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Rol en el Sistema</label>
                <select id="user-role" name="role" value={formRole} onChange={(event) => setFormRole(event.target.value)} disabled={Boolean(editingSelf)} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-primary-500 outline-none transition-all appearance-none cursor-pointer disabled:opacity-60">
                  <option value="cashier">Trabajador (accesos personalizables)</option>
                  <option value="admin">Administrador (Control Total)</option>
                </select>
                {editingSelf && <p className="text-xs text-gray-500">Tu cuenta conserva el rol de administrador.</p>}
              </div>

              <fieldset className="pt-2">
                <legend className="text-xs font-bold text-gray-600 uppercase tracking-wider">Secciones habilitadas</legend>
                <p className="text-xs text-gray-500 mt-2 mb-3">{formRole === 'admin' ? 'Los administradores tienen acceso completo. Para personalizarlo, elige Trabajador.' : 'Activa las secciones que este trabajador puede ver y utilizar.'}</p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  {storeSections.map(({ key, label, description }) => {
                    const checked = formRole === 'admin' || permissions.includes(key);
                    return <button key={key} type="button" role="switch" aria-checked={checked} aria-label={`Acceso a ${label}`} disabled={formRole === 'admin'} onClick={() => togglePermission(key)} className={`flex items-center justify-between gap-3 rounded-xl border px-3 py-3 text-left outline-none focus-visible:ring-2 focus-visible:ring-primary-500 ${checked ? 'bg-primary-50 border-primary-200' : 'bg-white border-gray-200'} disabled:cursor-default`}>
                      <span className="min-w-0"><span className="block text-xs font-bold text-gray-800">{label}</span><span className="block text-[10px] text-gray-500 mt-1">{description}</span></span>
                      <span aria-hidden="true" className={`relative inline-flex h-6 w-10 shrink-0 rounded-full transition-colors ${checked ? 'bg-primary-600' : 'bg-gray-300'}`}><span className={`absolute top-1 h-4 w-4 rounded-full bg-white shadow-sm transition-transform ${checked ? 'translate-x-5' : 'translate-x-1'}`} /></span>
                    </button>;
                  })}
                </div>
              </fieldset>

              <div className="flex gap-4 mt-8">
                <button type="button" onClick={() => setIsModalOpen(false)} className="flex-1 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl transition-all">Cancelar</button>
                <button type="submit" disabled={saving} className="flex-1 py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-2xl transition-all shadow-lg shadow-primary-100 disabled:opacity-70 flex items-center justify-center gap-2">
                  {saving && <Loader2 className="h-5 w-5 animate-spin" />}
                  {editingUser ? 'Guardar Cambios' : 'Registrar Usuario'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default Users;
