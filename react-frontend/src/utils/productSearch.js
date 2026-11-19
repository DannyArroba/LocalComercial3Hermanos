const collator = new Intl.Collator('es', { sensitivity: 'base', numeric: true });

export const normalizeSearch = (value) => String(value ?? '')
  .normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase('es')
  .replace(/[^\p{L}\p{N}]+/gu, ' ').trim();

// Admitir errores breves sin convertir cada palabra en una coincidencia.
const isNearWord = (query, word) => {
  const limit = query.length >= 7 ? 2 : 1;
  if (query.length < 4 || Math.abs(query.length - word.length) > limit) return false;
  let previous = Array.from({ length: word.length + 1 }, (_, i) => i);
  for (let i = 1; i <= query.length; i++) {
    const next = [i];
    for (let j = 1; j <= word.length; j++) {
      next[j] = Math.min(next[j - 1] + 1, previous[j] + 1, previous[j - 1] + (query[i - 1] === word[j - 1] ? 0 : 1));
    }
    previous = next;
  }
  return previous[word.length] <= limit;
};

export const searchProducts = (products, query) => {
  const normalized = normalizeSearch(query);
  if (!normalized) return [...products].sort((a, b) => collator.compare(a.name, b.name));
  const tokens = normalized.split(/\s+/);
  return products.map((product) => {
    const name = normalizeSearch(product.name);
    const details = normalizeSearch(`${product.category ?? ''} ${product.description ?? ''}`);
    const codes = [product.barcode, ...(product.scan_codes ?? [])];
    let rank = Infinity;
    if (codes.some((code) => code && String(code).trim() === String(query).trim())) rank = 0;
    else if (name === normalized) rank = 0;
    else if (name.startsWith(normalized)) rank = 1;
    else if (name.includes(normalized)) rank = 2;
    else if (tokens.every((token) => name.includes(token))) rank = 3;
    else if (tokens.every((token) => `${name} ${details}`.includes(token))) rank = 4;
    else if (tokens.every((token) => name.includes(token) || name.split(' ').some((word) => isNearWord(token, word)))) rank = 5;
    return { product, rank };
  }).filter(({ rank }) => Number.isFinite(rank))
    .sort((a, b) => a.rank - b.rank || collator.compare(a.product.name, b.product.name))
    .map(({ product }) => product);
};

export const visibleProducts = (products, query, category, { browseCategory = false } = {}) => {
  // Al ubicar un producto o elegir una categoría, se conserva el texto sin
  // limitar la navegación. Al escribir de nuevo, la búsqueda vuelve a ser global.
  if (normalizeSearch(query) && !browseCategory) return searchProducts(products, query);
  return searchProducts(products.filter((product) => category === 'todas' || product.category === category), '');
};
