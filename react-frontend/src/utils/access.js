export const storeSections = [
  { key: 'dashboard', label: 'Dashboard', path: '/dashboard', description: 'Indicadores y resumen de la tienda' },
  { key: 'sales', label: 'Productos y ventas', path: '/sales', description: 'Productos, caja y comprobantes' },
  { key: 'inventory', label: 'Inventario', path: '/inventory', description: 'Stock y abastecimientos' },
  { key: 'transactions', label: 'Transacciones', path: '/transactions', description: 'Historial de ventas y devoluciones' },
  { key: 'suppliers', label: 'Proveedores', path: '/suppliers', description: 'Datos de proveedores' },
  { key: 'customers', label: 'Clientes', path: '/customers', description: 'Datos e historial de clientes' },
];

export const defaultWorkerPermissions = ['sales', 'inventory'];

export function userPermissions(user) {
  if (!user) return [];
  if (user.role === 'admin') return storeSections.map(({ key }) => key);
  const keys = Array.isArray(user.permissions) ? user.permissions : defaultWorkerPermissions;
  return storeSections.map(({ key }) => key).filter((key) => keys.includes(key));
}

export function canAccess(user, key) {
  return Boolean(user) && (user.role === 'admin' || (key !== 'users' && userPermissions(user).includes(key)));
}

export function firstAllowedPath(user) {
  return storeSections.find(({ key }) => canAccess(user, key))?.path || '/sin-acceso';
}

export function userDisplayName(user) {
  return user?.name?.trim() || user?.email?.split('@')[0] || 'Usuario';
}
