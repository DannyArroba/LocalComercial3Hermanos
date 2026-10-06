import React, { useState, useEffect } from 'react';
import { dashboardApi, transactionsApi } from '../api';
import { TrendingUp, Package, AlertTriangle, Clock, DollarSign, ArrowRight, Users as UsersIcon, Receipt, X, Calendar, CreditCard, ShoppingBag, Download, Trophy, TrendingDown, ArrowUpRight } from 'lucide-react';
import { Link } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const chartMetrics = [
  { key: 'revenue', label: 'Ingresos', color: '#16a34a' },
  { key: 'expenses', label: 'Gastos', color: '#f97316' },
  { key: 'profit', label: 'Ganancia', color: '#2563eb' },
];

const FinancialLineChart = ({
  data = [],
  visibleMetrics,
  onToggleMetric,
  formatMoney,
  periodLabel,
  months,
  years,
  selectedMonth,
  selectedYear,
  isAnnual,
  onMonthChange,
  onYearChange,
  onAnnualChange,
}) => {
  const [hoveredPoint, setHoveredPoint] = useState(null);
  const [windowStart, setWindowStart] = useState(0);
  const [windowSize, setWindowSize] = useState(Math.min(12, Math.max(1, data.length)));
  const activeMetrics = chartMetrics.filter((metric) => visibleMetrics[metric.key]);
  const fullData = data.length ? data : [{ label: 'Sin datos', revenue: 0, expenses: 0, profit: 0 }];
  const safeWindowSize = Math.min(Math.max(2, windowSize), fullData.length);
  const safeWindowStart = Math.min(windowStart, Math.max(0, fullData.length - safeWindowSize));
  const chartData = fullData.slice(safeWindowStart, safeWindowStart + safeWindowSize);
  const values = chartData.flatMap((point) => activeMetrics.map((metric) => Number(point[metric.key] || 0)));
  const maxValue = Math.max(1, ...values);
  const minValue = Math.min(0, ...values);
  const range = maxValue - minValue || 1;
  const width = 760;
  const height = 300;
  const padding = { top: 24, right: 28, bottom: 46, left: 64 };
  const plotWidth = width - padding.left - padding.right;
  const plotHeight = height - padding.top - padding.bottom;
  const xFor = (index) => padding.left + (chartData.length === 1 ? plotWidth / 2 : (index / (chartData.length - 1)) * plotWidth);
  const yFor = (value) => padding.top + ((maxValue - value) / range) * plotHeight;
  const yTicks = Array.from({ length: 5 }, (_, index) => minValue + (range / 4) * index).reverse();
  const xStep = Math.max(1, Math.ceil(chartData.length / 7));

  useEffect(() => {
    setWindowStart(0);
    setWindowSize(Math.min(12, Math.max(1, data.length)));
  }, [data]);

  const handleWheel = (event) => {
    event.preventDefault();
    if (!fullData.length) return;

    if (event.shiftKey || Math.abs(event.deltaX) > Math.abs(event.deltaY)) {
      const direction = (event.deltaX || event.deltaY) > 0 ? 1 : -1;
      setWindowStart((current) => Math.min(Math.max(0, current + direction), Math.max(0, fullData.length - safeWindowSize)));
      return;
    }

    if (event.ctrlKey || Math.abs(event.deltaY) >= Math.abs(event.deltaX)) {
      const direction = event.deltaY > 0 ? 1 : -1;
      setWindowSize((current) => {
        const next = Math.min(fullData.length, Math.max(5, current + direction * 2));
        setWindowStart((start) => Math.min(start, Math.max(0, fullData.length - next)));
        return next;
      });
    }
  };

  const showAll = () => {
    setWindowStart(0);
    setWindowSize(fullData.length);
  };

  const pathFor = (metricKey) => chartData
    .map((point, index) => `${index === 0 ? 'M' : 'L'} ${xFor(index).toFixed(2)} ${yFor(Number(point[metricKey] || 0)).toFixed(2)}`)
    .join(' ');

  return (
    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
      <div className="p-5 border-b border-gray-50 bg-gray-50/50 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <h2 className="text-lg font-black text-gray-900 tracking-tight">Grafica financiera</h2>
          <p className="text-xs font-bold text-gray-400 mt-1">Evolucion de ingresos, gastos y ganancia en {periodLabel}</p>
        </div>
        <button
          onClick={showAll}
          className="w-fit px-3 py-2 rounded-xl border text-[10px] font-black uppercase tracking-widest transition-all bg-white text-gray-500 border-gray-200 hover:border-gray-300"
        >
          Ver todo
        </button>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-[260px_1fr]">
        <aside className="border-b xl:border-b-0 xl:border-r border-gray-100 bg-gray-50/60 p-4 space-y-4">
          <div>
            <p className="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Vista</p>
            <div className="flex gap-1 bg-gray-200 p-0.5 rounded-lg">
              <button
                onClick={() => onAnnualChange(false)}
                className={`flex-1 text-xs font-bold py-1.5 rounded-md transition-colors ${!isAnnual ? "bg-white text-primary-700 shadow-sm" : "text-gray-500 hover:text-gray-700"}`}
              >
                Mensual
              </button>
              <button
                onClick={() => onAnnualChange(true)}
                className={`flex-1 text-xs font-bold py-1.5 rounded-md transition-colors ${isAnnual ? "bg-white text-primary-700 shadow-sm" : "text-gray-500 hover:text-gray-700"}`}
              >
                Anual
              </button>
            </div>
          </div>

          <div className="space-y-2">
            {!isAnnual && (
              <select
                value={selectedMonth}
                onChange={(e) => onMonthChange(parseInt(e.target.value))}
                className="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 outline-none"
              >
                {months.map((month, index) => (
                  <option key={month} value={index + 1}>{month}</option>
                ))}
              </select>
            )}
            <select
              value={selectedYear}
              onChange={(e) => onYearChange(parseInt(e.target.value))}
              className="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 outline-none"
            >
              {years.map((year) => (
                <option key={year} value={year}>{year}</option>
              ))}
            </select>
          </div>

          <div>
            <p className="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Mostrar</p>
            <div className="space-y-2">
              {chartMetrics.map((metric) => (
                <button
                  key={metric.key}
                  onClick={() => onToggleMetric(metric.key)}
                  className={`w-full px-3 py-2 rounded-xl border text-[10px] font-black uppercase tracking-widest transition-all text-left ${
                    visibleMetrics[metric.key]
                      ? 'bg-gray-900 text-white border-gray-900 shadow-sm'
                      : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'
                  }`}
                >
                  <span className="inline-block w-2 h-2 rounded-full mr-1.5 align-middle" style={{ backgroundColor: metric.color }}></span>
                  {metric.label}
                </button>
              ))}
            </div>
          </div>
        </aside>

        <div className="p-4 lg:p-6 relative min-w-0">
          {hoveredPoint && (
            <div className="absolute right-6 top-6 z-10 bg-gray-950 text-white rounded-xl shadow-xl px-4 py-3 min-w-44">
              <p className="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">{hoveredPoint.label}</p>
              <div className="space-y-1">
                {activeMetrics.map((metric) => (
                  <div key={metric.key} className="flex items-center justify-between gap-4 text-xs font-bold">
                    <span className="flex items-center gap-2">
                      <span className="w-2 h-2 rounded-full" style={{ backgroundColor: metric.color }}></span>
                      {metric.label}
                    </span>
                    <span>{formatMoney(hoveredPoint[metric.key])}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          <div className="w-full overflow-hidden" onWheel={handleWheel}>
            <svg viewBox={`0 0 ${width} ${height}`} className="min-w-[720px] w-full h-[320px]" role="img" aria-label="Grafica financiera del periodo">
              <rect x="0" y="0" width={width} height={height} fill="#ffffff" />
              {yTicks.map((tick) => (
                <g key={tick}>
                  <line x1={padding.left} x2={width - padding.right} y1={yFor(tick)} y2={yFor(tick)} stroke="#e5e7eb" strokeDasharray="4 4" />
                  <text x={padding.left - 10} y={yFor(tick) + 4} textAnchor="end" className="fill-gray-400 text-[10px] font-bold">
                    ${Math.round(tick)}
                  </text>
                </g>
              ))}

              {chartData.map((point, index) => (
                index % xStep === 0 || index === chartData.length - 1 ? (
                  <g key={point.key || index}>
                    <text x={xFor(index)} y={height - 16} textAnchor="middle" className="fill-gray-400 text-[10px] font-bold">
                      {point.label}
                    </text>
                  </g>
                ) : null
              ))}

              {activeMetrics.map((metric) => (
                <g key={metric.key}>
                  <path d={pathFor(metric.key)} fill="none" stroke={metric.color} strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                  {chartData.map((point, index) => (
                    <circle
                      key={`${metric.key}-${point.key || index}`}
                      cx={xFor(index)}
                      cy={yFor(Number(point[metric.key] || 0))}
                      r="4.5"
                      fill="#ffffff"
                      stroke={metric.color}
                      strokeWidth="3"
                      className="cursor-pointer"
                      onMouseEnter={() => setHoveredPoint(point)}
                      onMouseLeave={() => setHoveredPoint(null)}
                    />
                  ))}
                </g>
              ))}
            </svg>
          </div>
          <div className="mt-3 flex items-center justify-between gap-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
            <span>Rueda: acercar/alejar</span>
            <span>{chartData[0]?.label} - {chartData[chartData.length - 1]?.label}</span>
            <span>Shift + rueda: desplazar</span>
          </div>
        </div>
      </div>
    </div>
  );
};

const Dashboard = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedTransaction, setSelectedTransaction] = useState(null);
  const [details, setDetails] = useState(null);
  const [loadingDetails, setLoadingDetails] = useState(false);
  const [selectedYear, setSelectedYear] = useState(null);
  const [selectedMonth, setSelectedMonth] = useState(null);
  const [isAnnual, setIsAnnual] = useState(false);
  const [visibleMetrics, setVisibleMetrics] = useState({
    revenue: true,
    expenses: true,
    profit: true,
  });

  const months = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];

  const years = data?.availableYears?.length
    ? data.availableYears
    : Array.from({ length: new Date().getFullYear() - 2019 }, (_, i) => new Date().getFullYear() - i);

  const periodLabel = data
    ? (data.monthlyStats.isAnnual ? `${data.monthlyStats.year}` : `${months[data.monthlyStats.month - 1]} ${data.monthlyStats.year}`)
    : '';

  const formatMoney = (value) => `$${parseFloat(value || 0).toFixed(2)}`;

  const toggleMetric = (metricKey) => {
    setVisibleMetrics((current) => {
      const enabledCount = Object.values(current).filter(Boolean).length;
      if (current[metricKey] && enabledCount === 1) return current;
      return { ...current, [metricKey]: !current[metricKey] };
    });
  };

  useEffect(() => {
    fetchStats();
  }, [selectedYear, selectedMonth, isAnnual]);

  const fetchStats = async () => {
    try {
      const res = await dashboardApi.getStats(selectedYear, selectedMonth, isAnnual);
      if (res.data.status === "success") {
        if (!selectedYear) setSelectedYear(res.data.monthlyStats.year);
        if (!selectedMonth) setSelectedMonth(res.data.monthlyStats.month);
        setData(res.data);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const fetchDetails = async (id) => {
    setLoadingDetails(true);
    setSelectedTransaction(id);
    try {
      const res = await transactionsApi.getDetails(id);
      if (res.data.status === 'success') {
        setDetails(res.data.data);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoadingDetails(false);
    }
  };

  const downloadPDF = () => {
    if (!details) return;

    const doc = new jsPDF();
    const { purchase, items } = details;
    const date = new Date(purchase.created_at).toLocaleString();

    // Header
    doc.setFontSize(22);
    doc.setTextColor(22, 163, 74); // primary-600
    doc.text('LOCAL COMERCIAL TRES HERMANOS', 105, 20, { align: 'center' });
    
    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text('Sistema de Gestión Interna', 105, 28, { align: 'center' });
    
    doc.setDrawColor(200);
    doc.line(20, 35, 190, 35);

    // Purchase Info
    doc.setFontSize(12);
    doc.setTextColor(0);
    doc.text(`${purchase.purchase_type === 'FACTURA' ? 'COMPROBANTE DE VENTA' : 'NOTA DE VENTA'}`, 20, 45);
    doc.setFontSize(10);
    doc.text(`Nro. Comprobante: #000${purchase.id}`, 20, 52);
    doc.text(`Fecha: ${date}`, 20, 57);

    // Customer Info
    if (purchase.purchase_type === 'FACTURA' && purchase.customer_name) {
      doc.setFontSize(11);
      doc.text('DATOS DEL CLIENTE:', 20, 75);
      doc.setFontSize(10);
      doc.text(`Nombre: ${purchase.customer_name}`, 20, 82);
      doc.text(`ID/Cédula: ${purchase.customer_idnumber || 'N/A'}`, 20, 87);
      doc.text(`Dirección: ${purchase.customer_address || 'N/A'}`, 20, 92);
      doc.text(`Teléfono: ${purchase.customer_phone || 'N/A'}`, 20, 97);
    } else {
      doc.text('Cliente: CONSUMIDOR FINAL', 20, 75);
    }

    // Table
    const tableData = items.map(item => [
      item.product_name,
      item.quantity,
      `$${parseFloat(item.unit_price).toFixed(2)}`,
      `${parseFloat(item.tax_rate || 0).toFixed(0)}%`,
      `$${parseFloat(item.line_total).toFixed(2)}`
    ]);

    autoTable(doc, {
      startY: (purchase.purchase_type === 'FACTURA' && purchase.customer_name) ? 105 : 85,
      head: [['Producto', 'Cant.', 'P. sin IVA', 'IVA', 'Total']],
      body: tableData,
      theme: 'striped',
      headStyles: { fillColor: [22, 163, 74] }
    });

    const finalY = doc.lastAutoTable.finalY + 10;

    doc.setFontSize(10);
    doc.text(`SUBTOTAL:`, 140, finalY);
    doc.text(`$${parseFloat(purchase.subtotal).toFixed(2)}`, 175, finalY, { align: 'right' });
    
    doc.text(`IVA (15%):`, 140, finalY + 7);
    doc.text(`$${parseFloat(purchase.iva).toFixed(2)}`, 175, finalY + 7, { align: 'right' });
    
    doc.setFontSize(12);
    doc.setFont(undefined, 'bold');
    doc.text(`TOTAL:`, 140, finalY + 16);
    doc.text(`$${parseFloat(purchase.total).toFixed(2)}`, 175, finalY + 16, { align: 'right' });

    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.text(`FORMA DE PAGO: ${purchase.payment_method || 'No registrada'}`, 20, finalY + 28);
    if (purchase.payment_method === 'EFECTIVO') {
      doc.text(`Efectivo recibido: $${parseFloat(purchase.amount_received || 0).toFixed(2)}`, 20, finalY + 35);
      doc.text(`Cambio entregado: $${parseFloat(purchase.change_amount || 0).toFixed(2)}`, 20, finalY + 42);
    } else if (purchase.payment_method === 'TRANSFERENCIA') {
      doc.text(`Nro. comprobante: ${purchase.transfer_reference || 'N/A'}`, 20, finalY + 35);
    }

    // Footer
    doc.setFontSize(8);
    doc.setFont(undefined, 'normal');
    doc.setTextColor(150);
    doc.text('Este documento es para control interno y no tiene validez tributaria ante el SRI.', 105, 280, { align: 'center' });

    doc.save(`Comprobante_3Hermanos_${purchase.id}.pdf`);
  };

  if (loading) return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>
  );

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-black text-gray-900 tracking-tighter">Panel Administrativo</h1>
        <p className="text-sm text-gray-500 font-medium mt-1">Monitorea el rendimiento de tu supermercado en tiempo real.</p>
      </div>

      {/* Stats Grid - More compact */}
      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <motion.div 
          whileHover={{ y: -2 }}
          className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 relative overflow-hidden group"
        >
          <div className="absolute top-0 right-0 w-16 h-16 bg-green-50 rounded-bl-3xl -z-0 transition-transform group-hover:scale-110 duration-500"></div>
          <div className="p-3 bg-green-600 text-white rounded-xl relative z-10 shadow-sm">
            <DollarSign className="h-5 w-5" />
          </div>
          <div className="relative z-10">
            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ventas Hoy</p>
            <p className="text-xl font-black text-gray-900 tracking-tight">{formatMoney(data.stats.salesToday)}</p>
          </div>
        </motion.div>

        <motion.div
          whileHover={{ y: -2 }}
          className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 relative overflow-hidden group"
        >
          <div className="absolute top-0 right-0 w-16 h-16 bg-orange-50 rounded-bl-3xl -z-0 transition-transform group-hover:scale-110 duration-500"></div>
          <div className="p-3 bg-orange-500 text-white rounded-xl relative z-10 shadow-sm">
            <TrendingDown className="h-5 w-5" />
          </div>
          <Link to="/inventory?history=1" className="relative z-10 hover:opacity-80 transition-opacity text-left">
            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Gastos de hoy</p>
            <p className="text-xl font-black text-gray-900 tracking-tight">{formatMoney(data.stats.expensesToday)}</p>
            <p className="text-[9px] font-bold text-orange-600 mt-1">Ver abastecimientos</p>
          </Link>
        </motion.div>

        <motion.div 
          whileHover={{ y: -2 }}
          className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 relative overflow-hidden group"
        >
          <div className="absolute top-0 right-0 w-16 h-16 bg-blue-50 rounded-bl-3xl -z-0 transition-transform group-hover:scale-110 duration-500"></div>
          <div className="p-3 bg-blue-600 text-white rounded-xl relative z-10 shadow-sm">
            <Package className="h-5 w-5" />
          </div>
          <div className="relative z-10">
            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Productos</p>
            <p className="text-xl font-black text-gray-900 tracking-tight">{data.stats.totalProducts}</p>
          </div>
        </motion.div>

        <motion.div 
          whileHover={{ y: -2 }}
          className="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 relative overflow-hidden group"
        >
          <div className="absolute top-0 right-0 w-16 h-16 bg-red-50 rounded-bl-3xl -z-0 transition-transform group-hover:scale-110 duration-500"></div>
          <div className="p-3 bg-red-500 text-white rounded-xl relative z-10 shadow-sm">
            <AlertTriangle className="h-5 w-5" />
          </div>
          <Link to="/inventory" className="relative z-10 hover:opacity-80 transition-opacity text-left">
            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Bajo Stock</p>
            <p className="text-xl font-black text-gray-900 tracking-tight">{data.stats.lowStockCount}</p>
          </Link>
        </motion.div>
      </div>

      {/* Main Content Grid - Balanced layout */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {/* Left Column: Estadísticas (narrower) */}
        <div className="lg:col-span-1 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
          <div className="p-4 border-b border-gray-50 bg-gray-50/50">
            <h2 className="text-sm font-black text-gray-900 flex items-center gap-2 mb-3">
              <TrendingUp className="h-4 w-4 text-primary-600" />
              Estadísticas
            </h2>
            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
              El periodo se cambia desde la grafica financiera.
            </p>
          </div>
          <div className="p-4 space-y-3 flex-1">
            <div className={`p-3 rounded-xl border ${data.monthlyStats.hasActivity ? 'bg-blue-50 border-blue-100 text-blue-700' : 'bg-amber-50 border-amber-100 text-amber-700'}`}>
              <p className="text-[9px] font-black uppercase tracking-widest">
                Periodo analizado
              </p>
              <p className="text-sm font-black mt-0.5">{periodLabel}</p>
              <p className="text-[10px] font-bold mt-1 opacity-80">
                {data.monthlyStats.salesCount} ventas · {data.monthlyStats.paidSupplyCount} gastos de proveedor
              </p>
              {!data.monthlyStats.hasActivity && (
                <p className="text-[10px] font-bold mt-1">
                  No hay movimientos en este periodo. Usa el selector para revisar meses anteriores.
                </p>
              )}
            </div>

            {/* Revenue */}
            <div className="p-3 bg-green-50 rounded-xl border border-green-100">
              <div className="flex items-center gap-2 text-green-600 mb-1">
                <TrendingUp className="h-3 w-3" />
                <span className="text-[9px] font-bold uppercase tracking-widest">
                  Ventas netas
                </span>
              </div>
              <p className="text-lg font-black text-green-700">
                {formatMoney(data.monthlyStats.revenue)}
              </p>
              <p className="text-[10px] font-bold text-green-700/70 mt-1">
                Bruto {formatMoney(data.monthlyStats.grossRevenue)} · Devoluciones {formatMoney(data.monthlyStats.returns)}
              </p>
            </div>

            {/* Expenses */}
            <div className="p-3 bg-orange-50 rounded-xl border border-orange-100">
              <div className="flex items-center gap-2 text-orange-600 mb-1">
                <TrendingDown className="h-3 w-3" />
                <span className="text-[9px] font-bold uppercase tracking-widest">
                  Gastos a proveedores
                </span>
              </div>
              <p className="text-lg font-black text-orange-700">
                {formatMoney(data.monthlyStats.expenses)}
              </p>
              <p className="text-[10px] font-bold text-orange-700/70 mt-1">
                Pagados/recibidos · Pendiente {formatMoney(data.monthlyStats.pendingSupplyTotal)}
              </p>
              {data.supplierExpenses?.length > 0 && (
                <div className="mt-2 space-y-1">
                  {data.supplierExpenses.map((supplier) => (
                    <div key={supplier.supplier_name} className="flex justify-between gap-2 text-[10px] font-bold text-orange-900/70">
                      <span className="truncate">{supplier.supplier_name}</span>
                      <span>{formatMoney(supplier.total)}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Net Profit */}
            <div className="p-3 bg-primary-50 rounded-xl border border-primary-200">
              <div className="flex items-center gap-2 text-primary-600 mb-1">
                <ArrowUpRight className="h-3 w-3" />
                <span className="text-[9px] font-bold uppercase tracking-widest">
                  Ganancia por ventas
                </span>
              </div>
              <p className="text-lg font-black text-primary-700">
                {formatMoney(data.monthlyStats.netProfit)}
              </p>
              <p className="text-[10px] font-bold text-primary-700/70 mt-1">
                Resultado estimado del periodo
              </p>
            </div>
          </div>
        </div>

        {/* Middle Column: Top 5 Más Vendidos */}
        <div className="lg:col-span-1 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
          <div className="p-4 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
            <h2 className="text-sm font-black text-gray-900 flex items-center gap-2">
              <Trophy className="h-4 w-4 text-yellow-500" />
              Top 5 Más Vendidos
            </h2>
            <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">{periodLabel}</span>
          </div>
          <div className="p-3 overflow-y-auto flex-1">
            {data.topProducts.length === 0 ? (
              <div className="text-center py-6 text-gray-400">
                <p className="text-sm font-medium">No hay datos de ventas aún.</p>
              </div>
            ) : (
              <div className="space-y-2">
                {data.topProducts.map((product, index) => (
                  <div key={product.id} className="relative">
                  <div 
                    className={`flex items-center gap-2 p-2 rounded-lg border transition-all ${
                      index === 0 
                        ? 'top-seller-metallic'
                        : 'bg-gray-50 border-gray-100 hover:border-primary-200 hover:bg-primary-50'
                    }`}
                  >
                    {/* Rank Badge */}
                    <div className={`w-7 h-7 rounded-md flex items-center justify-center font-black text-sm ${
                      index === 0 ? 'top-seller-medal' : 'bg-white border border-gray-200 text-gray-600'
                    }`}>
                      {index + 1}
                    </div>
                    {/* Product Image */}
                    <div className="w-8 h-8 rounded-md overflow-hidden border border-gray-100 bg-white flex-shrink-0">
                      <img src={product.image_url} alt={product.name} className="w-full h-full object-cover" />
                    </div>
                    {/* Product Info */}
                    <div className="flex-1 min-w-0">
                      <h3 className="font-bold text-gray-900 text-xs truncate">{product.name}</h3>
                      <p className="text-[9px] text-gray-500 font-medium">
                        {product.total_quantity} uds
                      </p>
                    </div>
                    {/* Revenue */}
                    <div className="text-right flex-shrink-0">
                      <p className={`font-black text-xs ${index === 0 ? 'text-amber-950 drop-shadow-[0_1px_0_rgba(255,255,255,0.65)]' : 'text-primary-600'}`}>
                        {formatMoney(product.total_revenue)}
                      </p>
                    </div>
                  </div>
                  {index === 0 && <span className="top-seller-star" aria-hidden="true">✦</span>}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Productos Sin Stock + Próximos a Acabarse (stacked) */}
        <div className="lg:col-span-1 flex flex-col gap-4">
          {/* Low Stock Products */}
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex-1">
            <div className="p-4 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
              <h2 className="text-sm font-black text-gray-900 flex items-center gap-2">
                <AlertTriangle className="h-4 w-4 text-red-500" />
                Productos Sin Stock
              </h2>
            </div>
            <div className="p-3 overflow-y-auto flex-1 max-h-[300px]">
              {data.lowStockProducts && data.lowStockProducts.length === 0 ? (
                <div className="text-center py-4 text-gray-400">
                  <p className="text-xs font-medium">¡Todo en stock!</p>
                </div>
              ) : (
                <div className="space-y-2">
                  {data.lowStockProducts && data.lowStockProducts.map((product, index) => (
                  <div 
                    key={product.id}
                    className="flex items-center gap-2 p-2 rounded-lg border transition-all bg-red-50 border-red-200"
                  >
                    {/* Product Image */}
                    <div className="w-8 h-8 rounded-md overflow-hidden border border-gray-100 bg-white flex-shrink-0">
                      <img src={product.image_url} alt={product.name} className="w-full h-full object-cover" />
                    </div>
                    {/* Product Info */}
                    <div className="flex-1 min-w-0">
                      <h3 className="font-bold text-gray-900 text-xs truncate">{product.name}</h3>
                      <p className="text-[9px] text-red-600 font-bold">
                        Stock: {product.stock}
                      </p>
                    </div>
                  </div>
                ))}
                </div>
              )}
            </div>
          </div>

          {/* Critical Stock Products (Proximos a quedarse sin) */}
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex-1">
            <div className="p-4 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
              <h2 className="text-sm font-black text-gray-900 flex items-center gap-2">
                <AlertTriangle className="h-4 w-4 text-yellow-500" />
                Próximos a Acabarse
              </h2>
            </div>
            <div className="p-3 overflow-y-auto flex-1 max-h-[300px]">
              {data.criticalStockProducts && data.criticalStockProducts.length === 0 ? (
                <div className="text-center py-4 text-gray-400">
                  <p className="text-xs font-medium">¡Todo bien!</p>
                </div>
              ) : (
                <div className="space-y-2">
                  {data.criticalStockProducts && data.criticalStockProducts.map((product, index) => (
                  <div 
                    key={product.id}
                    className="flex items-center gap-2 p-2 rounded-lg border transition-all bg-yellow-50 border-yellow-200"
                  >
                    {/* Product Image */}
                    <div className="w-8 h-8 rounded-md overflow-hidden border border-gray-100 bg-white flex-shrink-0">
                      <img src={product.image_url} alt={product.name} className="w-full h-full object-cover" />
                    </div>
                    {/* Product Info */}
                    <div className="flex-1 min-w-0">
                      <h3 className="font-bold text-gray-900 text-xs truncate">{product.name}</h3>
                      <p className="text-[9px] text-yellow-700 font-bold">
                        Stock: {product.stock}
                      </p>
                    </div>
                  </div>
                ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      <FinancialLineChart
        data={data.financialSeries}
        visibleMetrics={visibleMetrics}
        onToggleMetric={toggleMetric}
        formatMoney={formatMoney}
        periodLabel={periodLabel}
        months={months}
        years={years}
        selectedMonth={selectedMonth || data.monthlyStats.month}
        selectedYear={selectedYear || data.monthlyStats.year}
        isAnnual={isAnnual}
        onMonthChange={setSelectedMonth}
        onYearChange={setSelectedYear}
        onAnnualChange={setIsAnnual}
      />

      {/* Bottom Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Sales */}
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col max-h-[400px]">
          <div className="p-5 border-b border-gray-50 flex justify-between items-center bg-gray-50/50 shrink-0">
            <h2 className="text-lg font-black text-gray-900 flex items-center gap-2">
              <Clock className="h-4 w-4 text-primary-600" />
              Últimas Transacciones
            </h2>
          </div>
          <div className="divide-y divide-gray-50 overflow-y-auto custom-scrollbar">
            {data.recentSales.map((sale) => (
              <div 
                key={sale.id} 
                onClick={() => fetchDetails(sale.id)}
                className="p-4 flex justify-between items-center hover:bg-gray-50/80 transition-all group cursor-pointer"
              >
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center font-black text-[9px] text-gray-400 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                    #{sale.id}
                  </div>
                  <div>
                    <p className="font-bold text-gray-900 text-sm tracking-tight">Orden de Venta</p>
                    <p className="text-[10px] text-gray-400 font-medium">{new Date(sale.created_at).toLocaleString()}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="font-black text-primary-600 text-sm tracking-tight">${parseFloat(sale.total).toFixed(2)}</p>
                  <span className="px-1.5 py-0.5 bg-gray-100 text-gray-500 text-[7px] font-bold uppercase rounded-md tracking-widest">
                    {sale.purchase_type === 'FACTURA' ? 'Factura' : 'Nota'}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Quick Actions */}
        <div className="space-y-4">
          <h2 className="text-lg font-black text-gray-900">Accesos Directos</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Link to="/inventory" className="p-5 bg-primary-600 text-white rounded-2xl shadow-lg flex flex-col justify-between h-36 group hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
              <div className="absolute -top-8 -right-8 w-20 h-20 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
              <Package className="h-7 w-7 opacity-40 group-hover:opacity-100 transition-opacity" />
              <div className="relative z-10">
                <p className="font-black text-lg tracking-tight mb-1">Inventario</p>
                <p className="text-xs text-primary-100 font-medium">Control de stock</p>
              </div>
            </Link>
            <Link to="/users" className="p-5 bg-gray-900 text-white rounded-2xl shadow-lg flex flex-col justify-between h-36 group hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
              <div className="absolute -top-8 -right-8 w-20 h-20 bg-white/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
              <UsersIcon className="h-7 w-7 opacity-40 group-hover:opacity-100 transition-opacity" />
              <div className="relative z-10">
                <p className="font-black text-lg tracking-tight mb-1">Personal</p>
                <p className="text-xs text-gray-400 font-medium">Gestionar equipo</p>
              </div>
            </Link>
          </div>
        </div>
      </div>

      {/* Detail Modal */}
      <AnimatePresence>
        {selectedTransaction && (
          <div className="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              className="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden flex flex-col max-h-[85vh]"
            >
              <div className="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div className="flex items-center gap-2">
                  <div className="p-1.5 bg-primary-600 text-white rounded-lg">
                    <Receipt className="h-4 w-4" />
                  </div>
                  <h2 className="text-base font-black text-gray-900">Venta #{selectedTransaction}</h2>
                </div>
                <button 
                  onClick={() => { setSelectedTransaction(null); setDetails(null); }}
                  className="p-1.5 hover:bg-gray-200 rounded-lg transition-colors"
                >
                  <X className="h-4 w-4 text-gray-500" />
                </button>
              </div>

              <div className="flex-1 overflow-y-auto p-5">
                {loadingDetails ? (
                  <div className="flex justify-center items-center h-32">
                    <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
                  </div>
                ) : details ? (
                  <div className="space-y-5">
                    {/* Header Info */}
                    <div className="grid grid-cols-2 gap-3">
                      <div className="p-3 bg-gray-50 rounded-xl border border-gray-100">
                        <div className="flex items-center gap-1.5 text-gray-400 mb-0.5">
                          <Calendar className="h-2.5 w-2.5" />
                          <span className="text-[7px] font-bold uppercase tracking-widest">Fecha</span>
                        </div>
                        <p className="text-xs font-bold text-gray-900">{new Date(details.purchase.created_at).toLocaleDateString()}</p>
                      </div>
                      <div className="p-3 bg-gray-50 rounded-xl border border-gray-100">
                        <div className="flex items-center gap-1.5 text-gray-400 mb-0.5">
                          <Clock className="h-2.5 w-2.5" />
                          <span className="text-[7px] font-bold uppercase tracking-widest">Hora</span>
                        </div>
                        <p className="text-xs font-bold text-gray-900">{new Date(details.purchase.created_at).toLocaleTimeString()}</p>
                      </div>
                      <div className="p-3 bg-gray-50 rounded-xl border border-gray-100">
                        <div className="flex items-center gap-1.5 text-gray-400 mb-0.5">
                          <CreditCard className="h-2.5 w-2.5" />
                          <span className="text-[7px] font-bold uppercase tracking-widest">Tipo</span>
                        </div>
                        <p className="text-xs font-bold text-gray-900">
                          {details.purchase.purchase_type === 'FACTURA' ? 'Factura' : 'Nota'}
                        </p>
                      </div>
                      <div className="p-3 bg-primary-50 rounded-xl border border-primary-100">
                        <div className="flex items-center gap-1.5 text-primary-400 mb-0.5">
                          <ShoppingBag className="h-2.5 w-2.5" />
                          <span className="text-[7px] font-bold uppercase tracking-widest">Total</span>
                        </div>
                        <p className="text-sm font-black text-primary-600">${parseFloat(details.purchase.total).toFixed(2)}</p>
                      </div>
                    </div>

                    {/* Products List */}
                    <div>
                      <h3 className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                        <ShoppingBag className="h-2.5 w-2.5" />
                        Productos
                      </h3>
                      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
                        <table className="w-full text-left">
                          <thead>
                            <tr className="bg-gray-50 border-b border-gray-100">
                              <th className="px-4 py-2 text-[8px] font-bold text-gray-400 uppercase tracking-widest">Producto</th>
                              <th className="px-4 py-2 text-[8px] font-bold text-gray-400 uppercase tracking-widest text-center">Cant.</th>
                              <th className="px-4 py-2 text-[8px] font-bold text-gray-400 uppercase tracking-widest text-right">Precio</th>
                              <th className="px-4 py-2 text-[8px] font-bold text-gray-400 uppercase tracking-widest text-right">Subtotal</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-gray-50">
                            {details.items.map((item, i) => (
                              <tr key={i} className="hover:bg-gray-50/50 transition-colors">
                                <td className="px-4 py-2">
                                  <div className="flex items-center gap-2">
                                    <div className="h-6 w-6 rounded-md bg-gray-100 overflow-hidden border border-gray-100 flex-shrink-0">
                                      <img src={item.image_url} alt={item.product_name} className="h-full w-full object-cover" />
                                    </div>
                                    <span className="font-bold text-gray-900 text-xs">{item.product_name}</span>
                                  </div>
                                </td>
                                <td className="px-4 py-2 text-center">
                                  <span className="font-bold text-gray-900 text-xs">{item.quantity}</span>
                                </td>
                                <td className="px-4 py-2 text-right">
                                  <span className="font-bold text-gray-400 text-xs">${parseFloat(item.unit_price).toFixed(2)}</span>
                                </td>
                                <td className="px-4 py-2 text-right">
                                  <span className="font-black text-gray-900 text-xs">${parseFloat(item.line_total).toFixed(2)}</span>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>

                    {/* Footer Summary */}
                    <div className="flex justify-end pt-3">
                      <div className="w-full md:w-52 space-y-1.5">
                        <div className="flex justify-between items-center text-[10px] font-bold text-gray-400">
                          <span>Subtotal</span>
                          <span>${parseFloat(details.purchase.subtotal).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between items-center text-[10px] font-bold text-gray-400">
                          <span>IVA (15%)</span>
                          <span>${parseFloat(details.purchase.iva).toFixed(2)}</span>
                        </div>
                        <div className="pt-2 border-t border-gray-100 flex justify-between items-center">
                          <span className="text-xs font-black text-gray-900 uppercase tracking-widest">Total</span>
                          <span className="text-lg font-black text-primary-600 tracking-tight">${parseFloat(details.purchase.total).toFixed(2)}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                ) : null}
              </div>

              <div className="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                <button 
                  onClick={() => { setSelectedTransaction(null); setDetails(null); }}
                  className="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg font-black text-[10px] uppercase tracking-widest hover:bg-gray-300 transition-all"
                >
                  Cerrar
                </button>
                <button 
                  onClick={downloadPDF}
                  className="bg-primary-600 text-white px-5 py-2 rounded-lg font-black text-[10px] uppercase tracking-widest hover:bg-primary-700 transition-all flex items-center gap-1.5"
                >
                  <Download className="h-3.5 w-3.5" />
                  PDF
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default Dashboard;
