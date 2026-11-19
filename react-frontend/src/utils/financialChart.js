export const financialMetrics = [
  { key: 'revenue', label: 'Ingresos', color: '#2962FF' },
  { key: 'expenses', label: 'Abastecimientos', color: '#E1575A' },
  { key: 'profit', label: 'Ganancia estimada con IVA', color: '#F28E2C' },
];

// Lightweight Charts needs unique, ascending calendar dates (no timezone shift).
export function financialPoints(data = []) {
  const points = new Map();
  for (const point of data) {
    const time = /^\d{4}-\d{2}$/.test(point.key) ? `${point.key}-01` : point.key;
    if (!/^\d{4}-\d{2}-\d{2}$/.test(time)) continue;
    const date = new Date(`${time}T00:00:00Z`);
    if (!Number.isFinite(date.getTime()) || date.toISOString().slice(0, 10) !== time) continue;
    points.set(time, { ...point, time });
  }
  return [...points.values()].sort((a, b) => a.time.localeCompare(b.time));
}

export function metricData(points, key) {
  return points.map(({ time, [key]: value }) => (
    value !== null && value !== undefined && value !== '' && Number.isFinite(Number(value))
      ? { time, value: Number(value) }
      : { time }
  ));
}

export function financialDate(time, annual = false) {
  const date = typeof time === 'string'
    ? new Date(`${time}T00:00:00Z`)
    : new Date(Date.UTC(time.year, time.month - 1, time.day));
  return new Intl.DateTimeFormat('es-EC', {
    timeZone: 'UTC', year: 'numeric', month: annual ? 'long' : 'short',
    ...(annual ? {} : { day: 'numeric' }),
  }).format(date);
}
