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
  middleware: ['auth', 'cek-admin']
})

const { $api } = useNuxtApp() as any
const router = useRouter()

const stats = ref({
  pendapatan_kasir: 0,
  pendapatan_member: 0,
  sedang_parkir: 0,
  total_petugas: 0
})

const simulasi = ref({
  effective_now: '',
  mode: 'realtime',
  loading: false,
  message: ''
})

const isScannerConnected = ref(false)
const timeFilter = ref<'today' | 'week' | 'month'>('today')
const chartMode = ref<'bar' | 'line'>('line')
const aktivitasTerbaru = ref<any[]>([])
const tanggalHariIni = ref(
  new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
)
let intervalId: any = null

const normalizeRole = (role: string) => {
  const normalized = (role || '').toLowerCase().trim().replace(/[\s-]+/g, '_')
  return normalized === 'superadmin' ? 'super_admin' : normalized
}

const canUseSimulasi = computed(() => {
  const role = normalizeRole(localStorage.getItem('role') || '')
  return role === 'admin' || role === 'super_admin'
})

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
  return Number(stats.value.pendapatan_kasir || 0) + Number(stats.value.pendapatan_member || 0)
})

const datasetsByFilter = computed(() => {
  return serverCharts.value[timeFilter.value]
})

const chartData = computed(() => ({
  labels: datasetsByFilter.value.labels,
  datasets: [
    {
      label: datasetsByFilter.value.currLabel,
      borderColor: '#0284C7',
      backgroundColor: chartMode.value === 'bar' ? '#0284C7' : 'rgba(2, 132, 199, 0.12)',
      fill: chartMode.value === 'line',
      borderWidth: 2.5,
      borderRadius: chartMode.value === 'bar' ? 6 : 0,
      pointRadius: chartMode.value === 'line' ? 3 : 0,
      pointHoverRadius: 6,
      pointBackgroundColor: '#0284C7',
      tension: 0.35,
      data: datasetsByFilter.value.current
    },
    {
      label: datasetsByFilter.value.prevLabel,
      borderColor: '#94A3B8',
      backgroundColor: chartMode.value === 'bar' ? '#94A3B8' : 'rgba(148, 163, 184, 0.05)',
      fill: chartMode.value === 'line',
      borderWidth: 1.5,
      borderRadius: chartMode.value === 'bar' ? 6 : 0,
      borderDash: [5, 5],
      pointRadius: chartMode.value === 'line' ? 3 : 0,
      pointHoverRadius: 6,
      pointBackgroundColor: '#94A3B8',
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

const checkScannerDevice = async () => {
  if (typeof navigator === 'undefined' || !navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
    isScannerConnected.value = false
    return
  }

  try {
    if (navigator.permissions && navigator.permissions.query) {
      const permissionStatus = await navigator.permissions.query({ name: 'camera' as any })
      if (permissionStatus.state === 'denied') {
        isScannerConnected.value = false
        return
      }
    }

    const devices = await navigator.mediaDevices.enumerateDevices()
    isScannerConnected.value = devices.some((d) => d.kind === 'videoinput')
  } catch {
    isScannerConnected.value = false
  }
}

const fetchSimulasiStatus = async () => {
  if (!canUseSimulasi.value) return

  try {
    const res = await $api.get('/simulasi/status')
    if (res.data?.data) {
      simulasi.value.effective_now = res.data.data.effective_now || ''
      simulasi.value.mode = res.data.data.mode || 'realtime'
      simulasi.value.message = res.data.data.mode === 'simulasi'
        ? 'Mode simulasi aktif'
        : 'Mode realtime aktif'
    }
  } catch (error: any) {
    simulasi.value.message = error?.response?.data?.message || 'Simulasi tidak tersedia untuk role ini.'
  }
}

const triggerSimulasi = async (action: 'advance' | 'rewind' | 'reset') => {
  if (!canUseSimulasi.value) {
    alert('Hanya admin yang dapat mengakses fitur simulasi waktu.')
    return
  }

  simulasi.value.loading = true
  simulasi.value.message = 'Memproses simulasi waktu...'

  try {
    const endpoint = action === 'advance'
      ? '/simulasi/advance-month'
      : action === 'rewind'
        ? '/simulasi/rewind-month'
        : '/simulasi/reset'

    const res = await $api.post(endpoint)
    if (res.data?.data?.effective_now) {
      simulasi.value.effective_now = res.data.data.effective_now
    }

    simulasi.value.message = res.data?.message || 'Simulasi berhasil diproses.'
    await fetchSimulasiStatus()
    await loadDashboardData()
  } catch (error: any) {
    const msg = error?.response?.data?.message || 'Gagal memproses simulasi waktu.'
    simulasi.value.message = msg
    alert(msg)
  } finally {
    simulasi.value.loading = false
  }
}

const loadDashboardData = async () => {
  try {
    const [resStats, resAktif, resLaporanMember, resLaporanNonMember, resPetugas] = await Promise.allSettled([
      $api.get('/dashboard/stats'),
      $api.get('/parkir/aktif'),
      $api.get('/laporan/member'),
      $api.get('/laporan/non-member'),
      $api.get('/admin/petugas')
    ])

    if (resStats.status === 'fulfilled' && resStats.value.data?.data) {
      const d = resStats.value.data.data
      stats.value.pendapatan_kasir = Number(d.total_penerimaan ?? d.pendapatan_hari_ini ?? 0)
      if (d.transaksi_terbaru) aktivitasTerbaru.value = d.transaksi_terbaru
      if (d.chart) serverCharts.value = d.chart
    }

    if (resAktif.status === 'fulfilled' && resAktif.value.data?.data) {
      stats.value.sedang_parkir = resAktif.value.data.data.length
    }

    if (resLaporanMember.status === 'fulfilled' && resLaporanMember.value.data?.data) {
      const mList = resLaporanMember.value.data.data
      stats.value.pendapatan_member = mList.reduce(
        (acc: number, curr: any) => acc + Number(curr.jumlah_bayar || 0),
        0
      )
    }

    if (resLaporanNonMember.status === 'fulfilled' && resLaporanNonMember.value.data?.data) {
      const nmList = resLaporanNonMember.value.data.data
      stats.value.pendapatan_kasir = nmList.reduce(
        (acc: number, curr: any) => acc + Number(curr.total_bayar || curr.total_tarif || 0),
        0
      )
    }

    // Penanganan langsung data akun petugas sesuai endpoint /admin/petugas
    if (resPetugas.status === 'fulfilled' && resPetugas.value.data) {
      const responseData = resPetugas.value.data
      const rawList = responseData.data ?? responseData
      if (Array.isArray(rawList)) {
        stats.value.total_petugas = rawList.length
      }
    }
  } catch (error) {
    console.error('Gagal sinkronisasi data dashboard admin:', error)
  }
}

const formatRupiah = (val: any) => {
  const num = Number(val)
  return isNaN(num) ? '0' : new Intl.NumberFormat('id-ID').format(num)
}

const formatSimulasiTime = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
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

onMounted(async () => {
  await loadDashboardData()
  await fetchSimulasiStatus()
  checkScannerDevice()
  intervalId = setInterval(() => {
    loadDashboardData()
    fetchSimulasiStatus()
  }, 3000)

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
    <!-- SIDEBAR ADMIN -->
    <aside class="w-64 bg-[#0B0F19] text-slate-400 flex flex-col justify-between py-6 px-4 shrink-0 select-none hidden md:flex">
      <div>
        <div class="flex items-center justify-between px-2 mb-7">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-cyan-400 text-[#0B0F19] flex items-center justify-center font-black text-base shadow-[0_0_15px_rgba(34,211,238,0.3)]">
              P
            </div>
            <div>
              <span class="text-base font-extrabold tracking-tight text-white block leading-none">ADMIN PARKIR</span>
              <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5 block">PLAZA ANDALAS</span>
            </div>
          </div>
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        </div>

        <nav class="space-y-1">
          <NuxtLink
            to="/admin/dashboard"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs shadow-xs"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm text-cyan-400">⊞</span>
              <span>Dashboard</span>
            </div>
            <span class="text-xs text-cyan-400 font-bold">●</span>
          </NuxtLink>

          <NuxtLink
            to="/admin/petugas"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">👥</span>
              <span>Kelola Petugas</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink
            to="/admin/laporan/member"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">📊</span>
              <span>Laporan</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink
            to="/admin/markir"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">🅿️</span>
              <span>Sedang Parkir</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>
        </nav>
      </div>

      <div class="space-y-3 pt-4 border-t border-slate-800/80">
        <div class="bg-slate-900/90 border border-slate-800 px-3.5 py-2.5 rounded-2xl flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-center text-xs font-bold">
            ADM
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-white truncate">Super Administrator</p>
            <p class="text-[10px] text-cyan-400 font-medium">Control Center</p>
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

    <!-- CONTENT UTAMA DASHBOARD ADMIN -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <header class="bg-white px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">EXECUTIVE OVERVIEW</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Dashboard Plaza Andalas</h1>
        </div>

        <div class="flex items-center gap-3 flex-wrap justify-end">
          <div v-if="canUseSimulasi" class="bg-slate-100 p-0.5 rounded-full flex items-center text-xs font-bold text-slate-500">
            <button
              type="button"
              :disabled="simulasi.loading"
              @click="triggerSimulasi('rewind')"
              class="px-3 py-1.5 rounded-full transition cursor-pointer disabled:opacity-50"
            >
              -1 Bulan
            </button>
            <button
              type="button"
              :disabled="simulasi.loading"
              @click="triggerSimulasi('reset')"
              class="px-3 py-1.5 rounded-full transition cursor-pointer disabled:opacity-50"
            >
              Reset
            </button>
            <button
              type="button"
              :disabled="simulasi.loading"
              @click="triggerSimulasi('advance')"
              class="px-3 py-1.5 rounded-full transition cursor-pointer disabled:opacity-50"
            >
              +1 Bulan
            </button>
          </div>

          <div class="bg-slate-100 text-slate-700 px-3.5 py-1.5 rounded-xl text-xs font-bold">
            {{ tanggalHariIni }}
          </div>
        </div>
      </header>

      <div class="p-8 space-y-6">
        <div v-if="canUseSimulasi" class="bg-white border border-slate-200 rounded-2xl px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3 shadow-xs">
          <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Waktu sistem</p>
            <p class="text-sm font-bold text-slate-800">{{ formatSimulasiTime(simulasi.effective_now || new Date().toISOString()) }}</p>
          </div>
          <div class="flex items-center gap-2 text-xs font-semibold">
            <span
              :class="simulasi.mode === 'simulasi' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
              class="px-2.5 py-1 rounded-full"
            >
              {{ simulasi.mode === 'simulasi' ? 'Mode simulasi' : 'Mode realtime' }}
            </span>
            <span class="text-slate-500">{{ simulasi.message || 'Siap digunakan' }}</span>
          </div>
        </div>
        <!-- 3 KARTU METRIK UTAMA -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- CARD 1: PENDAPATAN GABUNGAN -->
          <NuxtLink
            to="/admin/laporan/member"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md hover:border-indigo-300 transition group flex flex-col justify-between cursor-pointer"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition flex items-center justify-center text-sm font-bold">
                  💰
                </span>
                <span class="text-xs font-bold text-slate-700">Total Pendapatan</span>
              </div>
              <span class="text-[10px] font-bold text-indigo-600 group-hover:translate-x-0.5 transition">Audit ›</span>
            </div>
            <div>
              <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                Rp {{ formatRupiah(totalPendapatanGabungan) }}
              </h3>
              <div class="flex items-center justify-between text-[10px] text-slate-400 mt-2 pt-2 border-t border-slate-50">
                <span>Kasir: Rp {{ formatRupiah(stats.pendapatan_kasir) }}</span>
                <span>Member: Rp {{ formatRupiah(stats.pendapatan_member) }}</span>
              </div>
            </div>
          </NuxtLink>

          <!-- CARD 2: SEDANG PARKIR -->
          <NuxtLink
            to="/admin/markir"
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
              <div class="flex items-center justify-between text-[10px] text-slate-400 mt-2 pt-2 border-t border-slate-50">
                <span>Di Dalam Gedung</span>
                <span class="text-emerald-500 font-bold">● Sensor Live</span>
              </div>
            </div>
          </NuxtLink>

          <!-- CARD 3: TOTAL PETUGAS TERDAFTAR -->
          <NuxtLink
            to="/admin/petugas"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md hover:border-cyan-300 transition group flex flex-col justify-between cursor-pointer"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-600 group-hover:bg-cyan-600 group-hover:text-white transition flex items-center justify-center text-sm font-bold">
                  👮
                </span>
                <span class="text-xs font-bold text-slate-700">Petugas Terdaftar</span>
              </div>
              <span class="text-[10px] font-bold text-cyan-600 group-hover:translate-x-0.5 transition">Kelola ›</span>
            </div>
            <div>
              <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                {{ stats.total_petugas || 0 }} <span class="text-sm font-bold text-slate-400">Akun</span>
              </h3>
              <div class="flex items-center justify-between text-[10px] text-slate-400 mt-2 pt-2 border-t border-slate-50">
                <span>Shift & Akses Kasir</span>
                <span class="font-bold text-slate-700">Terotorisasi</span>
              </div>
            </div>
          </NuxtLink>
        </div>

        <!-- GRAFIK STATISTIK REALTIME -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <div class="bg-slate-100 p-0.5 rounded-lg flex items-center text-xs font-bold">
              <button
                type="button"
                @click="chartMode = 'bar'"
                :class="chartMode === 'bar' ? 'bg-[#0284C7] text-white' : 'text-slate-500'"
                class="px-3 py-1 rounded-md transition cursor-pointer"
              >
                Bar Chart
              </button>
              <button
                type="button"
                @click="chartMode = 'line'"
                :class="chartMode === 'line' ? 'bg-[#0284C7] text-white' : 'text-slate-500'"
                class="px-3 py-1 rounded-md transition cursor-pointer"
              >
                Line Chart
              </button>
            </div>

            <div class="flex items-center gap-4 text-xs font-bold text-slate-600">
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-xs bg-[#0284C7]"></span>
                <span>{{ datasetsByFilter.currLabel }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-xs bg-slate-400"></span>
                <span>{{ datasetsByFilter.prevLabel }}</span>
              </div>
            </div>
          </div>

          <div class="h-64 relative w-full">
            <Line v-if="chartMode === 'line'" :data="chartData" :options="chartOptions" />
            <Bar v-else :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- MONITORING RADAR & LOG TRANSAKSI -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <!-- DISTRIBUSI KENDARAAN -->
          <NuxtLink
            to="/admin/markir"
            class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition flex flex-col justify-between cursor-pointer"
          >
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-800">Distribusi Kendaraan Aktif</h3>
              <span class="text-[10px] bg-slate-100 font-bold px-2 py-0.5 rounded text-slate-600">Radar ›</span>
            </div>
            <div class="py-5 flex flex-col items-center justify-center">
              <div class="relative w-40 h-20 overflow-hidden flex items-end justify-center">
                <div class="w-40 h-40 rounded-full border-[12px] border-indigo-600 border-b-transparent border-l-rose-500 border-t-amber-400 rotate-[-45deg]"></div>
                <div class="absolute bottom-0 flex flex-col items-center">
                  <span class="text-2xl font-black text-slate-900 leading-none">{{ stats.sedang_parkir || 0 }}</span>
                  <span class="text-[9px] text-slate-400 font-bold mt-0.5 uppercase">Unit di Lokasi</span>
                </div>
              </div>
            </div>
            <div class="flex items-center justify-center gap-3 pt-3 border-t border-slate-50 text-[10px] font-bold text-slate-500">
              <div class="flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>Non-Member</span>
              </div>
              <div class="flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span>Member</span>
              </div>
            </div>
          </NuxtLink>

          <!-- KAPASITAS SENSOR GATE -->
          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-800">Kapasitas Sensor Gate</h3>
              <span :class="isScannerConnected ? 'text-emerald-500' : 'text-amber-500'" class="font-bold text-[10px]">
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
              {{ isScannerConnected ? 'Seluruh sensor gate terhubung' : 'Sensor webcam/kamera scanner offline' }}
            </div>
          </div>

          <!-- LOG AKTIVITAS TERBARU -->
          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-800">Transaksi Kasir Keluar</h3>
              <NuxtLink to="/admin/laporan/member" class="text-indigo-600 font-bold text-[10px] hover:underline">Semua ›</NuxtLink>
            </div>
            <div class="space-y-2 py-2">
              <div v-if="aktivitasTerbaru.length === 0" class="text-center text-slate-400 text-xs py-4">
                Belum ada transaksi kasir keluar.
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
            <div class="pt-2.5 border-t border-slate-50 text-[10px] text-slate-400 text-center font-bold">Sinkronisasi Database Realtime</div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>