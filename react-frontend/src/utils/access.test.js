import test from 'node:test';
import assert from 'node:assert/strict';
import { canAccess, firstAllowedPath, userPermissions } from './access.js';

test('un trabajador nuevo conserva productos/ventas e inventario por defecto', () => {
  const worker = { role: 'cashier' };
  assert.deepEqual(userPermissions(worker), ['sales', 'inventory']);
  assert.equal(firstAllowedPath(worker), '/sales');
  assert.equal(canAccess(worker, 'dashboard'), false);
  assert.equal(canAccess(worker, 'users'), false);
});

test('revocar todos los accesos no recupera permisos predeterminados ni crea bucles', () => {
  const worker = { role: 'cashier', permissions: [] };
  assert.deepEqual(userPermissions(worker), []);
  assert.equal(firstAllowedPath(worker), '/sin-acceso');
  assert.equal(canAccess(worker, 'sales'), false);
  assert.equal(firstAllowedPath(null), '/sin-acceso');
});

test('cada módulo es independiente y permisos desconocidos no permiten gestionar usuarios', () => {
  const worker = { role: 'cashier', permissions: ['customers', 'users', 'unknown'] };
  assert.equal(firstAllowedPath(worker), '/customers');
  assert.equal(canAccess(worker, 'inventory'), false);
  assert.equal(canAccess(worker, 'users'), false);
  assert.deepEqual(userPermissions(worker), ['customers']);
});

test('el administrador conserva acceso a personal y todos los módulos', () => {
  const admin = { role: 'admin', permissions: [] };
  assert.equal(canAccess(admin, 'users'), true);
  assert.equal(canAccess(admin, 'inventory'), true);
  assert.equal(firstAllowedPath(admin), '/dashboard');
});
