import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createChart, LineSeries, ColorType, CrosshairMode, LineStyle } from 'lightweight-charts';
import { financialMetrics, financialPoints, metricData, financialDate } from '../utils/financialChart';
import { useTheme } from '../context/ThemeContext';

export default function FinancialLineChart({
  data = [], visibleMetrics, onToggleMetric, formatMoney, periodLabel,
  months, years, selectedMonth, selectedYear, isAnnual,
  availablePeriods = [], currentDate, historyStart, dataPeriodKey,
  onMonthChange, onYearChange, onAnnualChange,
}) {
  const { isDark } = useTheme();
  const themeOptions = useMemo(() => ({
    layout: {
      textColor: isDark ? '#CBD5E1' : '#475569',
      background: { type: ColorType.Solid, color: isDark ? '#101F36' : '#FFFFFF' },
    },
    grid: {
      vertLines: { color: isDark ? '#1D2F48' : '#EEF1F4' },
      horzLines: { color: isDark ? '#24354C' : '#E2E8F0' },
    },
    crosshair: {
      vertLine: { color: isDark ? '#94A3B8' : '#64748B', labelBackgroundColor: isDark ? '#334B6B' : '#475569' },
      horzLine: { color: isDark ? '#94A3B8' : '#64748B', labelBackgroundColor: isDark ? '#334B6B' : '#475569' },
    },
    rightPriceScale: { borderColor: isDark ? '#33465F' : '#E2E8F0' },
    timeScale: { borderColor: isDark ? '#33465F' : '#E2E8F0' },
  }), [isDark]);
  const containerRef = useRef(null);
  const chartRef = useRef(null);
  const seriesRef = useRef({});
  const priceLinesRef = useRef({});
  const fittedPeriodRef = useRef(null);
  const [hoverTime, setHoverTime] = useState(null);
  const points = useMemo(() => financialPoints(data), [data]);
  const latest = useRef({ points, isAnnual, formatMoney });
  latest.current = { points, isAnnual, formatMoney };
  // The selected controls can change before their request returns. Fit only when
  // the data's own period changes, so a pending request cannot fit the old dates.
  const periodKey = dataPeriodKey ?? `${selectedYear}-${isAnnual ? 'annual' : selectedMonth}`;
  const hovered = points.find((point) => point.time === hoverTime);
  const displayed = hovered || points.at(-1);
  const hasMovement = points.some((point) => financialMetrics.some(({ key }) => Number(point[key])));
  const firstPeriod = historyStart?.slice(0, 7) || availablePeriods
    .map(({ year, month }) => `${year}-${String(month).padStart(2, '0')}`)
    .sort()[0];
  const lastPeriod = currentDate?.slice(0, 7);
  const monthOptions = months.map((label, index) => ({ label, value: index + 1 }))
    .filter(({ value }) => {
      const period = `${selectedYear}-${String(value).padStart(2, '0')}`;
      return (!firstPeriod || period >= firstPeriod) && (!lastPeriod || period <= lastPeriod);
    });
  const yearOptions = years.filter((year) => (!firstPeriod || year >= Number(firstPeriod.slice(0, 4)))
    && (!lastPeriod || year <= Number(lastPeriod.slice(0, 4))));

  const showAll = () => {
    const chart = chartRef.current;
    if (!chart) return;
    // Dragging the value axis disables autoscaling. Annual totals would otherwise
    // keep the daily value range and render above the visible chart.
    chart.priceScale('right').applyOptions({ autoScale: true });
    chart.timeScale().fitContent();
  };

  useEffect(() => {
    const chart = createChart(containerRef.current, {
      ...themeOptions,
      autoSize: true,
      layout: {
        ...themeOptions.layout,
        fontFamily: 'Inter, system-ui, sans-serif', fontSize: 12, attributionLogo: true,
      },
      crosshair: { ...themeOptions.crosshair, mode: CrosshairMode.Normal },
      rightPriceScale: {
        ...themeOptions.rightPriceScale, scaleMargins: { top: 0.15, bottom: 0.12 },
        alignLabels: true, minimumWidth: 88,
      },
      timeScale: { ...themeOptions.timeScale, timeVisible: false, fixLeftEdge: true, fixRightEdge: true },
      localization: {
        locale: 'es-EC',
        priceFormatter: (value) => latest.current.formatMoney(value),
        timeFormatter: (time) => financialDate(time, latest.current.isAnnual),
      },
      handleScroll: { vertTouchDrag: false, horzTouchDrag: true },
    });
    chartRef.current = chart;
    for (const metric of financialMetrics) {
      seriesRef.current[metric.key] = chart.addSeries(LineSeries, {
        color: metric.color, lineWidth: 3, priceLineVisible: false,
        lastValueVisible: false, crosshairMarkerRadius: 5,
        priceFormat: { type: 'price', precision: 2, minMove: 0.01 },
      });
    }
    const onCrosshair = ({ time, point }) => {
      if (!time || !point || point.x < 0 || point.y < 0) {
        setHoverTime(null);
        return;
      }
      setHoverTime(typeof time === 'string' ? time : `${time.year}-${String(time.month).padStart(2, '0')}-${String(time.day).padStart(2, '0')}`);
    };
    chart.subscribeCrosshairMove(onCrosshair);
    return () => {
      chart.unsubscribeCrosshairMove(onCrosshair);
      chart.remove();
      chartRef.current = null;
      seriesRef.current = {};
      priceLinesRef.current = {};
      fittedPeriodRef.current = null;
    };
  }, []);

  useEffect(() => {
    // Repaint the existing canvas without touching data, zoom, or the selected period.
    chartRef.current?.applyOptions(themeOptions);
  }, [themeOptions]);

  useEffect(() => {
    for (const { key, color } of financialMetrics) {
      const series = seriesRef.current[key];
      const values = metricData(points, key);
      series.setData(values);
      const latestValue = values.findLast((point) => Number.isFinite(point.value));
      if (latestValue) {
        // Keep the value badge at the real latest date, even when viewing older points.
        const options = {
          price: latestValue.value, color, lineWidth: 1, lineStyle: LineStyle.Dashed,
          axisLabelVisible: true, lineVisible: true,
        };
        if (priceLinesRef.current[key]) priceLinesRef.current[key].applyOptions(options);
        else priceLinesRef.current[key] = series.createPriceLine(options);
      } else if (priceLinesRef.current[key]) {
        series.removePriceLine(priceLinesRef.current[key]);
        delete priceLinesRef.current[key];
      }
    }
    if (points.length && fittedPeriodRef.current !== periodKey) {
      showAll();
      fittedPeriodRef.current = periodKey;
      setHoverTime(null);
    }
  }, [points, periodKey]);

  useEffect(() => {
    for (const { key } of financialMetrics) seriesRef.current[key].applyOptions({ visible: Boolean(visibleMetrics[key]) });
  }, [visibleMetrics]);

  return (
    <section className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6" aria-label="Gráfica financiera">
      <div className="p-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 className="text-lg font-black text-gray-900">Gráfica financiera</h2>
          <p className="text-sm text-gray-500 mt-1">{periodLabel} · {isAnnual ? 'Valores por mes' : 'Valores por día'} · USD</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <div className="flex rounded-lg bg-gray-100 p-1" aria-label="Vista del período">
            {[false, true].map((annual) => <button key={String(annual)} type="button" aria-pressed={isAnnual === annual} onClick={() => onAnnualChange(annual)} className={`px-3 py-2 text-xs font-bold rounded-md ${isAnnual === annual ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'}`}>{annual ? 'Anual' : 'Mensual'}</button>)}
          </div>
          {!isAnnual && <select aria-label="Mes de la gráfica" value={selectedMonth} onChange={(e) => onMonthChange(Number(e.target.value))} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">{monthOptions.map(({ label, value }) => <option key={value} value={value}>{label}</option>)}</select>}
          <select aria-label="Año de la gráfica" value={selectedYear} onChange={(e) => onYearChange(Number(e.target.value))} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">{yearOptions.map((year) => <option key={year} value={year}>{year}</option>)}</select>
          <button type="button" onClick={showAll} className="border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50">Ver todo</button>
        </div>
      </div>
      <div className="p-4 sm:p-5">
        <div className="flex flex-wrap items-center justify-between gap-2 mb-3 text-xs text-gray-500">
          <span>{displayed ? financialDate(displayed.time, isAnnual) : 'Sin datos en este período'}</span>
          <span>{hovered ? 'Valores de la fecha seleccionada' : 'Pasa sobre las líneas para consultar una fecha'}</span>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-4">
          {financialMetrics.map(({ key, label, color }) => (
            <button key={key} type="button" onClick={() => onToggleMetric(key)} aria-pressed={Boolean(visibleMetrics[key])} title={`Mostrar u ocultar ${label.toLowerCase()}`} className={`flex items-center justify-between gap-3 px-4 py-3 rounded-xl border text-left transition-colors ${visibleMetrics[key] ? 'border-gray-200 bg-gray-50' : 'border-dashed border-gray-200 text-gray-400'}`}>
              <span className="flex min-w-0 items-center gap-2 text-xs font-bold"><span className="w-2.5 h-2.5 shrink-0 rounded-full" style={{ backgroundColor: visibleMetrics[key] ? color : '#CBD5E1' }} />{label}</span>
              <span className="shrink-0 font-bold text-sm tabular-nums">{visibleMetrics[key] ? (displayed?.[key] != null && Number.isFinite(Number(displayed[key])) ? formatMoney(displayed[key]) : '—') : 'Oculto'}</span>
            </button>
          ))}
        </div>
        <div className="relative">
          <div ref={containerRef} className="w-full h-[320px] sm:h-[360px]" role="img" aria-label={`Ingresos, gastos y ganancia en dólares durante ${periodLabel}. Los valores por fecha se muestran arriba y en la tabla de datos.`} />
          {!hasMovement && <div className="absolute top-4 left-4 right-4 pointer-events-none text-center"><span className="inline-block rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-xs text-gray-500">{points.length ? 'Sin movimientos registrados en este período' : 'No hay datos disponibles para este período'}</span></div>}
        </div>
        <div className="mt-3 flex flex-wrap justify-between gap-2 text-xs text-gray-500">
          <span>Rueda o pellizco: acercar · Arrastra: recorrer · Ver todo: restablecer</span>
        </div>
        <details className="mt-3 text-xs text-gray-600">
          <summary className="cursor-pointer font-semibold py-2">Ver datos por {isAnnual ? 'mes' : 'día'}</summary>
          <div className="overflow-x-auto max-h-64 overflow-y-auto"><table className="w-full text-left tabular-nums"><caption className="sr-only">Datos financieros de {periodLabel} en USD</caption><thead><tr><th scope="col" className="p-2">Fecha</th>{financialMetrics.map(({ key, label }) => <th scope="col" key={key} className="p-2 text-right">{label}</th>)}</tr></thead><tbody>{points.map((point) => <tr key={point.time} className="border-t border-gray-100"><th scope="row" className="p-2 font-normal">{financialDate(point.time, isAnnual)}</th>{financialMetrics.map(({ key }) => <td key={key} className="p-2 text-right">{point[key] != null && Number.isFinite(Number(point[key])) ? formatMoney(point[key]) : '—'}</td>)}</tr>)}</tbody></table></div>
        </details>
      </div>
    </section>
  );
}
