import test from 'node:test';
import assert from 'node:assert/strict';
import { financialPoints, metricData, financialDate } from './financialChart.js';

test('ordena fechas reales, descarta fechas inválidas y conserva el último dato duplicado', () => {
  const rows = [
    { key: '2026-11-02', revenue: 10 }, { key: '2026-11-01', revenue: 0 },
    { key: '2026-11-02', revenue: 12 }, { key: '2026-02-30', revenue: 90 },
    { key: 'invalid', revenue: 99 },
  ];
  assert.deepEqual(financialPoints(rows).map(({ time, revenue }) => [time, revenue]), [
    ['2026-11-01', 0], ['2026-11-02', 12],
  ]);
  assert.equal(rows.length, 5);
});

test('convierte meses anuales a fechas sin desplazar el calendario ecuatoriano', () => {
  assert.equal(financialPoints([{ key: '2026-01' }])[0].time, '2026-01-01');
  assert.match(financialDate('2026-01-01', true), /enero.*2026/);
  assert.match(financialDate('2026-11-01'), /^1 /);
});

test('mantiene ceros, decimales y pérdidas reales; los valores ausentes dejan huecos', () => {
  const points = financialPoints([
    { key: '2026-11-01', profit: 0 }, { key: '2026-11-02', profit: '-12.35' },
    { key: '2026-11-03', profit: null }, { key: '2026-11-04', profit: 'abc' },
  ]);
  assert.deepEqual(metricData(points, 'profit'), [
    { time: '2026-11-01', value: 0 }, { time: '2026-11-02', value: -12.35 },
    { time: '2026-11-03' }, { time: '2026-11-04' },
  ]);
  assert.deepEqual(financialPoints(), []);
});
