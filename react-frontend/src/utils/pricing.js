// El catálogo guarda precios base con dos decimales. Calcular en centavos
// evita diferencias entre el precio mostrado y el IVA por línea del backend.
export const priceLine = (product, quantity = 1) => {
  const baseCents = Math.round(Number(product.price) * 100) * Number(quantity);
  const taxCents = Number(product.applies_iva) === 1 ? Math.round(baseCents * 15 / 100) : 0;
  return { base: baseCents / 100, tax: taxCents / 100, total: (baseCents + taxCents) / 100 };
};
