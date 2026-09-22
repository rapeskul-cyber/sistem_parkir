<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Line, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

definePageMeta({
  middleware: 'auth'
})

const { $api } = useNuxtApp() as any
const router = useRouter()

const stats = ref({
  member_aktif: 0,
  member_pending: 0,
  pendapatan_kasir: 0,
  pendapatan_member: 0,
  total_penerimaan: 0,
  sedang_parkir: 0,
  masuk_member: 0,
  masuk_non_member: 0,
  keluar_member: 0,
  keluar_non_member: 0
})

const isScannerConnected = ref(false)
const timeFilter = ref<'today' | 'week' | 'month'>('today')
const chartMode = ref<'bar' | 'line'>('line')
const aktivitasTerbaru = ref<any[]>([])
let intervalId: any = null

const serverCharts = ref({
  today: {
    labels: ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
    current: [0, 0, 0, 0, 0, 0, 0, 0, 0],
    previous: [0, 0, 0, 0, 0, 0, 0, 0, 0],
    currLabel: 'Hari Ini',
    prevLabel: 'Kemarin'
  },
  week: {
    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
    current: [0, 0, 0, 0, 0, 0, 0],
    previous: [0, 0, 0, 0, 0, 0, 0],
    currLabel: 'Minggu Ini',
    prevLabel: 'Minggu Lalu'
  },
  month: {
    labels: ['Mgg 1', 'Mgg 2', 'Mgg 3', 'Mgg 4'],
    current: [0, 0, 0, 0],
    previous: [0, 0, 0, 0],
    currLabel: 'Bulan Ini',
    prevLabel: 'Bulan Lalu'
  }
})

const totalPendapatanGabungan = computed(() => {
  return Number(stats.value.total_penerimaan || stats.value.pendapatan_kasir || 0)
})

const persenMember = computed(() => {
  if (!stats.value.sedang_parkir) return 0
  return Math.round((stats.value.masuk_member / stats.value.sedang_parkir) * 100)
})

const persenNonMember = computed(() => {
  if (!stats.value.sedang_parkir) return 0
  return 100 - persenMember.value
})

const ARC_LENGTH = 157.08

const nonMemberArc = computed(() => {
  if (!stats.value.sedang_parkir) return 0
  return (persenNonMember.value / 100) * ARC_LENGTH
})

const memberArc = computed(() => {
  if (!stats.value.sedang_parkir) return 0
  return (persenMember.value / 100) * ARC_LENGTH
})

const datasetsByFilter = computed(() => {
  return serverCharts.value[timeFilter.value]
})

const chartData = computed(() => ({
  labels: datasetsByFilter.value.labels,
  datasets: [
    {
      label: datasetsByFilter.value.currLabel,
      borderColor: '#4F46E5',
      backgroundColor: chartMode.value === 'bar' ? '#4F46E5' : 'rgba(79, 70, 229, 0.08)',
      fill: chartMode.value === 'line',
      borderWidth: 2.5,
      borderRadius: chartMode.value === 'bar' ? 6 : 0,
      pointRadius: chartMode.value === 'line' ? 3 : 0,
      pointHoverRadius: 6,
      pointBackgroundColor: '#4F46E5',
      tension: 0.35,
      data: datasetsByFilter.value.current
    },
    {
      label: datasetsByFilter.value.prevLabel,
      borderColor: '#F59E0B',
      backgroundColor: chartMode.value === 'bar' ? '#F59E0B' : 'rgba(245, 158, 11, 0.05)',
      fill: chartMode.value === 'line',
      borderWidth: 2,
      borderRadius: chartMode.value === 'bar' ? 6 : 0,
      pointRadius: chartMode.value === 'line' ? 3 : 0,
      pointHoverRadius: 6,
      pointBackgroundColor: '#F59E0B',
      tension: 0.35,
      data: datasetsByFilter.value.previous
    }
  ]
}))

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0B0F19',
      titleFont: { size: 11, weight: 'bold' },
      bodyFont: { size: 11 },
      padding: 10,
      cornerRadius: 8
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#94A3B8', font: { size: 11, weight: 'bold' } }
    },
    y: {
      border: { dash: [4, 4] },
      grid: { color: '#F1F5F9' },
      ticks: { color: '#94A3B8', font: { size: 11 }, precision: 0 }
    }
  }
})

// PERBAIKAN: Deteksi Kamera Hardware tanpa query permission yang rentan ditolak
const checkScannerDevice = async () => {
  if (typeof navigator === 'undefined' || !navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
    isScannerConnected.value = false
    return
  }

  try {
    const devices = await navigator.mediaDevices.enumerateDevices()
    isScannerConnected.value = devices.some((d) => d.kind === 'videoinput')
  } catch {
    isScannerConnected.value = false
  }
}

const loadDashboardData = async () => {
  try {
    const [resStats, resAktif, resTransaksi] = await Promise.allSettled([
      $api.get('/dashboard/stats'),
      $api.get('/parkir/aktif'),
      $api.get('/transaksi')
    ])

    if (resStats.status === 'fulfilled' && resStats.value.data?.data) {
      const d = resStats.value.data.data
      stats.value.total_penerimaan = d.total_penerimaan ?? 0
      stats.value.member_aktif = d.member_aktif ?? 0
      stats.value.sedang_parkir = d.sedang_parkir ?? 0
      if (d.transaksi_terbaru) aktivitasTerbaru.value = d.transaksi_terbaru
      if (d.chart) serverCharts.value = d.chart
    }

    if (resAktif.status === 'fulfilled' && resAktif.value.data?.data) {
      const aktifList = resAktif.value.data.data
      stats.value.sedang_parkir = aktifList.length

      const mCount = aktifList.filter((x: any) =>
        Boolean(x.kode_tiket && x.kode_tiket.startsWith('MBR-')) ||
        String(x.tipe || '').toLowerCase() === 'member'
      ).length

      stats.value.masuk_member = mCount
      stats.value.masuk_non_member = Math.max(0, aktifList.length - mCount)
    }

    if (resTransaksi.status === 'fulfilled' && resTransaksi.value.data?.data) {
      const tList = resTransaksi.value.data.data
      stats.value.keluar_member = tList.filter((t: any) =>
        Boolean(t.kode_tiket && t.kode_tiket.startsWith('MBR-'))
      ).length
      stats.value.keluar_non_member = Math.max(0, tList.length - stats.value.keluar_member)
    }
  } catch (error) {
    console.error('Gagal memuat metrik dashboard:', error)
  }
}

const formatRupiah = (val: any) => {
  const num = Number(val)
  return isNaN(num) ? '0' : new Intl.NumberFormat('id-ID').format(num)
}

const formatWaktu = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

const logout = async () => {
  try {
    await $api.post('/logout')
  } catch {}
  localStorage.removeItem('token')
  localStorage.removeItem('role')
  router.push('/')
}

onMounted(() => {
  loadDashboardData()
  checkScannerDevice()
  intervalId = setInterval(loadDashboardData, 3000)

  if (typeof navigator !== 'undefined' && navigator.mediaDevices) {
    navigator.mediaDevices.addEventListener('devicechange', checkScannerDevice)
  }
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
  if (typeof navigator !== 'undefined' && navigator.mediaDevices) {
    navigator.mediaDevices.removeEventListener('devicechange', checkScannerDevice)
  }
})
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] flex font-sans antialiased text-slate-800">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#0B0F19] text-slate-400 flex flex-col justify-between py-6 px-4 shrink-0 select-none hidden md:flex">
      <div>
        <div class="flex items-center justify-between px-2 mb-7">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-cyan-500 text-[#0B0F19] flex items-center justify-center font-black text-sm">
              P
            </div>
            <div>
              <span class="text-base font-extrabold tracking-tight text-white block leading-none">PARKIR</span>
              <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">PLAZA ANDALAS</span>
            </div>
          </div>
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        </div>

        <nav class="space-y-1">
          <NuxtLink
            to="/petugas"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs transition shadow-xs"
          >
            <span class="text-sm">⊞</span>
            <span>Dashboard</span>
          </NuxtLink>

          <NuxtLink
            to="/petugas/keluar"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl hover:bg-slate-800/40 hover:text-white text-xs font-semibold transition"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">🚪</span>
              <span>Gate Keluar (Kasir)</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink
            to="/petugas/transaksi"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl hover:bg-slate-800/40 hover:text-white text-xs font-semibold transition"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">🚗</span>
              <span>Kelola Transaksi</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink
            to="/petugas/member/select"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl hover:bg-slate-800/40 hover:text-white text-xs font-semibold transition"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">👥</span>
              <span>Kelola Member</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink
            to="/petugas/laporan"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl hover:bg-slate-800/40 hover:text-white text-xs font-semibold transition"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">📊</span>
              <span>Laporan</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink
            to="/petugas/markir"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl hover:bg-slate-800/40 hover:text-white text-xs font-semibold transition"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">🅿️</span>
              <span>Sedang Parkir</span>
            </div>
            <span class="text-[10px] bg-indigo-600/30 text-indigo-400 border border-indigo-500/30 px-2 py-0.5 rounded-full font-bold">
              {{ stats.sedang_parkir || 0 }}
            </span>
          </NuxtLink>
        </nav>
      </div>

      <div class="space-y-3 pt-4 border-t border-slate-800/80">
        <div class="bg-slate-900/80 border border-slate-800 px-3.5 py-2.5 rounded-xl flex items-center gap-3">
          <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">
            P
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-white truncate">Petugas Parkir</p>
            <p class="text-[10px] text-emerald-400 font-medium">Sistem Aktif</p>
          </div>
        </div>

        <button
          @click="logout"
          class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 transition cursor-pointer"
        >
          <span>🚪</span>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <header class="bg-white px-7 py-4 flex items-center justify-between border-b border-slate-100 shrink-0">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Plaza Andalas System</span>
          <h1 class="text-lg font-extrabold text-slate-900 tracking-tight">Dashboard Overview</h1>
        </div>

        <div class="flex items-center gap-3">
          <div class="bg-slate-100 p-0.5 rounded-full flex items-center text-xs font-bold text-slate-500">
            <button
              @click="timeFilter = 'today'"
              :class="timeFilter === 'today' ? 'bg-[#0B0F19] text-white shadow-xs' : 'hover:text-slate-900'"
              class="px-3.5 py-1.5 rounded-full transition cursor-pointer"
            >
              Hari Ini
            </button>
            <button
              @click="timeFilter = 'week'"
              :class="timeFilter === 'week' ? 'bg-[#0B0F19] text-white shadow-xs' : 'hover:text-slate-900'"
              class="px-3.5 py-1.5 rounded-full transition cursor-pointer"
            >
              Minggu Ini
            </button>
            <button
              @click="timeFilter = 'month'"
              :class="timeFilter === 'month' ? 'bg-[#0B0F19] text-white shadow-xs' : 'hover:text-slate-900'"
              class="px-3.5 py-1.5 rounded-full transition cursor-pointer"
            >
              Bulan Ini
            </button>
          </div>
        </div>
      </header>

      <div class="p-7 space-y-6">
        <!-- 3 KARTU UTAMA -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- CARD 1: TOTAL PENDAPATAN GABUNGAN -->
          <NuxtLink
            to="/petugas/laporan"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md hover:border-indigo-300 transition group flex flex-col justify-between cursor-pointer"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition flex items-center justify-center text-sm font-bold">
                  💰
                </span>
                <span class="text-xs font-bold text-slate-700">Total Penerimaan Bersih</span>
              </div>
              <span class="text-[10px] font-bold text-indigo-600 group-hover:translate-x-0.5 transition">Laporan ›</span>
            </div>

            <div>
              <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                Rp {{ formatRupiah(totalPendapatanGabungan) }}
              </h3>
              <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2 pt-2 border-t border-slate-50">
                <span>Akumulasi Pembayaran Parkir</span>
                <span class="text-emerald-500 font-bold">● Lunas</span>
              </div>
            </div>
          </NuxtLink>

          <!-- CARD 2: SEDANG PARKIR -->
          <NuxtLink
            to="/petugas/markir"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md hover:border-sky-300 transition group flex flex-col justify-between cursor-pointer"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 group-hover:bg-sky-600 group-hover:text-white transition flex items-center justify-center text-sm">
                  🚗
                </span>
                <span class="text-xs font-bold text-slate-700">Sedang Parkir</span>
              </div>
              <span class="text-[10px] font-bold text-sky-600 group-hover:translate-x-0.5 transition">Radar ›</span>
            </div>

            <div>
              <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                {{ stats.sedang_parkir || 0 }} <span class="text-sm font-bold text-slate-400">Unit</span>
              </h3>
              <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2 pt-2 border-t border-slate-50">
                <span>Di Area Parkir</span>
                <span class="text-emerald-500 font-bold">● Sensor Live</span>
              </div>
            </div>
          </NuxtLink>

          <!-- CARD 3: DATABASE MEMBER -->
          <NuxtLink
            to="/petugas/member/select"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md hover:border-amber-300 transition group flex flex-col justify-between cursor-pointer"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-hover:text-white transition flex items-center justify-center text-sm">
                  👥
                </span>
                <span class="text-xs font-bold text-slate-700">Database Member</span>
              </div>
              <span class="text-[10px] font-bold text-amber-600 group-hover:translate-x-0.5 transition">Kelola ›</span>
            </div>

            <div>
              <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                {{ stats.member_aktif || 0 }} <span class="text-sm font-bold text-slate-400">Pelanggan</span>
              </h3>
              <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2 pt-2 border-t border-slate-50">
                <span>Member Aktif (Lunas)</span>
                <span class="text-cyan-500 font-bold">Langganan 30 Hari</span>
              </div>
            </div>
          </NuxtLink>
        </div>

        <!-- PANEL ARUS KELUAR & MASUK -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- KENDARAAN MASUK -->
          <NuxtLink
            to="/petugas/markir"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md hover:border-emerald-300 transition group cursor-pointer"
          >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
              <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-bold">↓</span>
                <h3 class="text-xs font-black uppercase text-slate-800 tracking-wider">Kendaraan di Dalam Area</h3>
              </div>
              <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Gate In ›</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Member</span>
                <p class="text-2xl font-black text-amber-500 mt-1">{{ stats.masuk_member }} <span class="text-xs font-bold text-slate-400">Unit</span></p>
                <span class="text-[9px] text-slate-400">Scan kartu QR</span>
              </div>
              <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Non-Member</span>
                <p class="text-2xl font-black text-rose-500 mt-1">{{ stats.masuk_non_member }} <span class="text-xs font-bold text-slate-400">Unit</span></p>
                <span class="text-[9px] text-slate-400">Tiket karcis</span>
              </div>
            </div>
          </NuxtLink>

          <!-- KENDARAAN KELUAR -->
          <NuxtLink
            to="/petugas/transaksi"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md hover:border-rose-300 transition group cursor-pointer"
          >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
              <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-bold">↑</span>
                <h3 class="text-xs font-black uppercase text-slate-800 tracking-wider">Riwayat Kendaraan Keluar</h3>
              </div>
              <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">Gate Out (Kasir) ›</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Member Selesai</span>
                <p class="text-2xl font-black text-indigo-600 mt-1">{{ stats.keluar_member }} <span class="text-xs font-bold text-slate-400">Unit</span></p>
                <span class="text-[9px] text-slate-400">Voucher 100% (Free)</span>
              </div>
              <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Non-Member Selesai</span>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.keluar_non_member }} <span class="text-xs font-bold text-slate-400">Unit</span></p>
                <span class="text-[9px] text-slate-400">Tunai lunas di kasir</span>
              </div>
            </div>
          </NuxtLink>
        </div>

        <!-- GRAFIK STATISTIK -->
        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
          <div class="flex items-center justify-between mb-5">
            <div class="bg-slate-100 p-0.5 rounded-lg flex items-center text-xs font-bold">
              <button
                @click="chartMode = 'bar'"
                :class="chartMode === 'bar' ? 'bg-cyan-600 text-white' : 'text-slate-500'"
                class="px-3 py-1 rounded-md transition cursor-pointer"
              >
                Bar Chart
              </button>
              <button
                @click="chartMode = 'line'"
                :class="chartMode === 'line' ? 'bg-cyan-600 text-white' : 'text-slate-500'"
                class="px-3 py-1 rounded-md transition cursor-pointer"
              >
                Line Chart
              </button>
            </div>

            <div class="flex items-center gap-4 text-xs font-bold text-slate-600">
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-xs bg-indigo-600"></span>
                <span>{{ datasetsByFilter.currLabel }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-xs bg-amber-500"></span>
                <span>{{ datasetsByFilter.prevLabel }}</span>
              </div>
            </div>
          </div>

          <div class="h-64 relative">
            <Line v-if="chartMode === 'line'" :data="chartData" :options="chartOptions" />
            <Bar v-else :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- MONITORING RADAR, HARDWARE GATE, DAN KASIR KELUAR -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <!-- DISTRIBUSI KENDARAAN AKTIF -->
          <NuxtLink
            to="/petugas/markir"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition flex flex-col justify-between cursor-pointer"
          >
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-800">Distribusi Kendaraan Aktif</h3>
              <span class="text-[10px] bg-slate-100 font-bold px-2 py-0.5 rounded text-slate-600">Radar ›</span>
            </div>

            <div class="py-5 flex flex-col items-center justify-center">
              <div class="relative w-40 h-20 overflow-hidden flex items-end justify-center">
                <svg viewBox="0 0 120 60" class="w-40 h-20 overflow-visible">
                  <path
                    d="M 10 60 A 50 50 0 0 1 110 60"
                    fill="none"
                    stroke="#F1F5F9"
                    stroke-width="12"
                    stroke-linecap="butt"
                  />
                  <path
                    v-if="nonMemberArc > 0"
                    d="M 10 60 A 50 50 0 0 1 110 60"
                    fill="none"
                    stroke="#F43F5E"
                    stroke-width="12"
                    stroke-linecap="butt"
                    :stroke-dasharray="`${nonMemberArc} ${ARC_LENGTH}`"
                    stroke-dashoffset="0"
                    class="transition-all duration-700 ease-out"
                  />
                  <path
                    v-if="memberArc > 0"
                    d="M 10 60 A 50 50 0 0 1 110 60"
                    fill="none"
                    stroke="#F59E0B"
                    stroke-width="12"
                    stroke-linecap="butt"
                    :stroke-dasharray="`${memberArc} ${ARC_LENGTH}`"
                    :stroke-dashoffset="`-${nonMemberArc}`"
                    class="transition-all duration-700 ease-out"
                  />
                </svg>

                <div class="absolute bottom-0 flex flex-col items-center pb-0.5 pointer-events-none">
                  <span class="text-2xl font-black text-slate-900 leading-none">{{ stats.sedang_parkir || 0 }}</span>
                  <span class="text-[9px] text-slate-400 font-bold mt-1 uppercase tracking-wider">Unit di Lokasi</span>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-50 text-[11px] font-bold">
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                <span class="text-slate-600">Non-Mbr:</span>
                <span class="font-mono text-slate-900 font-extrabold">
                  {{ stats.masuk_non_member }} 
                  <span class="text-[9px] text-slate-400 font-normal">({{ persenNonMember }}%)</span>
                </span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 shrink-0"></span>
                <span class="text-slate-600">Member:</span>
                <span class="font-mono text-slate-900 font-extrabold">
                  {{ stats.masuk_member }} 
                  <span class="text-[9px] text-slate-400 font-normal">({{ persenMember }}%)</span>
                </span>
              </div>
            </div>
          </NuxtLink>

          <!-- KAPASITAS GATE SENSOR -->
          <NuxtLink
            to="/petugas/keluar"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition flex flex-col justify-between cursor-pointer"
          >
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-800">Kapasitas Sensor Gate</h3>
              <span
                :class="isScannerConnected ? 'text-emerald-500' : 'text-amber-500'"
                class="font-bold text-[10px]"
              >
                {{ isScannerConnected ? 'Normal' : 'Warning' }}
              </span>
            </div>

            <div class="flex items-center justify-between py-4">
              <div class="space-y-1.5 text-[11px] text-slate-600 font-medium">
                <p>Gate Masuk Kiosk: <strong class="text-indigo-600">Aktif</strong></p>
                <p>
                  Scanner QR / Kartu: 
                  <strong :class="isScannerConnected ? 'text-emerald-600' : 'text-rose-500'">
                    {{ isScannerConnected ? 'Aktif' : 'Non Aktif' }}
                  </strong>
                </p>
                <p>Gate Kasir Keluar: <strong class="text-teal-500">Standby</strong></p>
              </div>

              <div class="relative w-24 h-24 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-[3px] border-indigo-600 border-t-transparent rotate-45"></div>
                <div
                  class="absolute inset-2 rounded-full border-[3px] border-r-transparent rotate-90 transition-colors duration-300"
                  :class="isScannerConnected ? 'border-amber-400' : 'border-rose-400'"
                ></div>
                <div class="absolute inset-4 rounded-full border-[3px] border-teal-400 border-b-transparent"></div>
                <div class="flex flex-col items-center">
                  <span class="text-sm font-black text-slate-900 leading-none">
                    {{ isScannerConnected ? '100%' : '66%' }}
                  </span>
                  <span
                    class="text-[8px] font-bold mt-0.5 tracking-wider"
                    :class="isScannerConnected ? 'text-emerald-500' : 'text-rose-500'"
                  >
                    {{ isScannerConnected ? 'ONLINE' : 'PARTIAL' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="pt-2.5 border-t border-slate-50 text-[10px] text-slate-400 text-center font-bold">
              {{ isScannerConnected ? 'Seluruh sensor terhubung' : 'Sensor webcam belum tercolok / aktif' }}
            </div>
          </NuxtLink>

          <!-- TRANSAKSI KASIR KELUAR TERBARU -->
          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-800">Transaksi Kasir Keluar</h3>
              <NuxtLink to="/petugas/transaksi" class="text-indigo-600 font-bold text-[10px] hover:underline">Semua ›</NuxtLink>
            </div>

            <div class="space-y-2 py-2">
              <div v-if="aktivitasTerbaru.length === 0" class="text-center text-slate-400 text-xs py-4">
                Belum ada transaksi keluar.
              </div>
              <div
                v-for="item in aktivitasTerbaru.slice(0, 3)"
                :key="item.id"
                class="flex items-center justify-between p-2 rounded-xl bg-slate-50 text-[11px]"
              >
                <div class="truncate">
                  <span class="font-bold text-slate-800 block truncate">{{ item.plat_nomor || item.no_plat || item.kode_tiket }}</span>
                  <span class="text-[9px] text-slate-400">{{ formatWaktu(item.created_at) }}</span>
                </div>
                <span class="font-bold text-emerald-600 shrink-0">
                  Rp {{ formatRupiah(item.total_tarif || item.total_bayar) }}
                </span>
              </div>
            </div>

            <div class="pt-2.5 border-t border-slate-50 text-[10px] text-slate-400 text-center font-bold">Sinkronisasi Kasir Realtime</div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>