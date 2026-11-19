import test from 'node:test';
import assert from 'node:assert/strict';
import { searchProducts, visibleProducts } from './productSearch.js';

const products = [
  { id: 1, name: 'Arroz Real 10 kg', category: 'Arroces' },
  { id: 2, name: 'Aceite Girasoli 1 L', category: 'Aceites y grasas', barcode: '7861001720631' },
  { id: 3, name: 'Atún Real 180 g', category: 'Conservas' },
  { id: 4, name: 'Arroz Real 2 kg', category: 'Arroces' },
  { id: 5, name: 'Aceite La Favorita 1 L', category: 'Aceites y grasas' },
  { id: 6, name: 'Banchis surtidos 40 g', category: 'Snacks', description: 'Variedades natural, limón y picante.', barcode: '111', scan_codes: ['111', '222'] },
];

test('buscar aceite desde Arroces abarca la tienda sin cambiar los datos originales', () => {
  const original = structuredClone(products);
  assert.deepEqual(visibleProducts(products, 'aceite', 'Arroces').map((p) => p.id), [2, 5]);
  assert.deepEqual(products, original);
});

test('sin consulta respeta la categoría actual y ordena tamaños naturalmente', () => {
  assert.deepEqual(visibleProducts(products, '  ', 'Arroces').map((p) => p.id), [4, 1]);
});

test('acepta tildes omitidas, palabras en distinto orden y errores leves', () => {
  assert.equal(searchProducts(products, 'atun')[0].id, 3);
  assert.equal(searchProducts(products, 'girasoli aceite')[0].id, 2);
  assert.equal(searchProducts(products, 'aceitr')[0].id, 2);
  assert.equal(searchProducts(products, 'girsoli aceite')[0].id, 2);
});

test('encuentra una variedad agrupada y su código comercial alternativo', () => {
  assert.equal(searchProducts(products, 'limon')[0].id, 6);
  assert.equal(visibleProducts(products, '222', 'Arroces')[0].id, 6);
});

test('una búsqueda sin coincidencias no muestra productos ajenos', () => {
  assert.deepEqual(searchProducts(products, 'producto inexistente xyz'), []);
});

test('al navegar a la categoría conserva la consulta sin esconder los demás productos', () => {
  const query = 'girasoli';
  assert.deepEqual(visibleProducts(products, query, 'Arroces').map((p) => p.id), [2]);
  assert.deepEqual(visibleProducts(products, query, 'Aceites y grasas', { browseCategory: true }).map((p) => p.id), [2, 5]);
  assert.equal(visibleProducts(products, query, 'todas', { browseCategory: true }).length, products.length);
  assert.equal(visibleProducts(products, '', 'todas').length, products.length);
});
