import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { priceLine } from '../../react-frontend/src/utils/pricing.js';

const cases = JSON.parse(readFileSync(process.argv[2], 'utf8'));
for (const entry of cases) {
  const actual = priceLine(entry.product, entry.quantity);
  for (const field of ['base', 'tax', 'total']) {
    assert.equal(Math.round(actual[field] * 100), Math.round(entry[field] * 100), JSON.stringify({ entry, actual, field }));
  }
}
console.log(`${cases.length} casos de precios y cantidades coinciden entre PHP y JavaScript.`);
