import React from 'react';

export default function HistoryPagination({ page, total, pageSize = 50, onPageChange }) {
  const pages = Math.max(1, Math.ceil(total / pageSize));
  if (total <= pageSize) return null;
  return (
    <nav aria-label="Páginas del historial" className="flex flex-wrap items-center justify-between gap-3 p-4 border-t border-gray-100 bg-white shrink-0 text-xs text-gray-600">
      <span>{(page - 1) * pageSize + 1}–{Math.min(page * pageSize, total)} de {total} registros</span>
      <div className="flex items-center gap-3">
        <button type="button" disabled={page <= 1} onClick={() => onPageChange(page - 1)} className="border border-gray-200 rounded-lg px-3 py-2 font-bold hover:bg-gray-50 disabled:opacity-40">Anterior</button>
        <span aria-live="polite">{page} / {pages}</span>
        <button type="button" disabled={page >= pages} onClick={() => onPageChange(page + 1)} className="border border-gray-200 rounded-lg px-3 py-2 font-bold hover:bg-gray-50 disabled:opacity-40">Siguiente</button>
      </div>
    </nav>
  );
}
