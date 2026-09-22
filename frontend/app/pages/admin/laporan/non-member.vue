<template>
  <div class="min-h-screen bg-[#F8FAFC] flex font-sans antialiased text-slate-800">
    <!-- SIDEBAR PERSIS REFERENSI (HILANG SAAT PRINT) -->
    <aside class="print:hidden w-64 bg-[#0B0F19] text-slate-400 flex flex-col justify-between py-6 px-4 shrink-0 select-none hidden md:flex">
      <div>
        <div class="flex items-center justify-between px-2 mb-7">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-cyan-400 text-[#0B0F19] flex items-center justify-center font-black text-base shadow-[0_0_15px_rgba(34,211,238,0.3)]">
              P
            </div>
            <div>
              <span class="text-base font-extrabold tracking-tight text-white block leading-none">PARKIR</span>
              <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5 block">PLAZA ANDALAS</span>
            </div>
          </div>
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        </div>

        <nav class="space-y-1">
          <NuxtLink to="/admin/dashboard" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-semibold text-xs transition">
            <span class="text-sm">⊞</span>
            <span>Dashboard</span>
          </NuxtLink>
          <NuxtLink to="/admin/petugas" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">👥</span>
              <span>Kelola Petugas</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>
          <NuxtLink to="/admin/laporan/member" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs shadow-xs">
            <div class="flex items-center gap-3">
              <span class="text-sm text-cyan-400">📊</span>
              <span>Laporan</span>
            </div>
            <span class="text-xs text-cyan-400 font-bold">●</span>
          </NuxtLink>
          <NuxtLink to="/admin/markir" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
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
            A
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-white truncate">Admin Parkir</p>
            <p class="text-[10px] text-cyan-400 font-medium">Audit Keuangan</p>
          </div>
        </div>

        <button @click="logout" class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 transition cursor-pointer">
          <span>🚪</span>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto print:h-auto print:overflow-visible">
      <!-- HEADER TOPBAR -->
      <header class="print:hidden bg-white px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">PLAZA ANDALAS AUDIT LOG</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Laporan Pembayaran Parkir</h1>
        </div>

        <div class="flex items-center gap-2.5">
          <button @click="cetak" class="bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-black px-4 py-2 rounded-xl transition cursor-pointer shadow-xs flex items-center gap-1.5">
            <span>🖨️</span> Cetak Laporan
          </button>
          <NuxtLink to="/admin/dashboard" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
            <span>←</span> Dashboard
          </NuxtLink>
        </div>
      </header>

      <!-- PRINT-ONLY HEADER BANNER -->
      <div class="hidden print:block p-6 text-center border-b-2 border-black mb-4">
        <h1 class="text-2xl font-black uppercase">LAPORAN PEMBAYARAN TIKET NON-MEMBER</h1>
        <p class="text-sm font-bold">PLAZA ANDALAS MANAGEMENT SYSTEM</p>
        <p class="text-xs text-slate-600 mt-1">Dicetak pada: {{ new Date().toLocaleString('id-ID') }}</p>
      </div>

      <!-- BODY -->
      <div class="p-8 space-y-6 print:p-0">
        <!-- TOP CONTROLS: TAB PINDAH & SEARCH -->
        <div class="print:hidden flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <!-- Switcher Tab -->
          <div class="flex bg-slate-200/80 p-1 rounded-2xl w-fit">
            <NuxtLink
              to="/admin/laporan/member"
              class="px-5 py-2 rounded-xl text-xs font-black text-slate-600 hover:text-slate-900 transition"
            >
              ← Langganan Member
            </NuxtLink>
            <button
              class="px-5 py-2 rounded-xl text-xs font-black bg-[#0B0F19] text-white shadow-xs cursor-default"
            >
              Tiket Non-Member
            </button>
          </div>

          <!-- Search Bar -->
          <div class="relative w-72">
            <span class="absolute left-3.5 top-2.5 text-xs text-slate-400">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari plat nomor / kode tiket..."
              class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/20 transition"
            />
          </div>
        </div>

        <!-- STATS CARD RINGKASAN -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 print:hidden">
          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Transaksi</span>
              <h3 class="text-2xl font-black text-slate-900">{{ filteredList.length }} Unit</h3>
              <span class="text-[11px] text-slate-400">Tiket keluar terbayar</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-600 flex items-center justify-center font-bold text-lg">
              🧾
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Penerimaan</span>
              <h3 class="text-2xl font-black text-emerald-600">Rp {{ formatRupiah(totalPenerimaan) }}</h3>
              <span class="text-[11px] text-slate-400">Pemasukan pos gerbang</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-lg">
              ✓
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Uang Cash</span>
              <h3 class="text-2xl font-black text-indigo-600">Rp {{ formatRupiah(totalCash) }}</h3>
              <span class="text-[11px] text-slate-400">Kembalian: Rp {{ formatRupiah(totalKembalian) }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-lg">
              💵
            </div>
          </div>
        </div>

        <!-- GRAFIK STATISTIK PEMASUKAN TIKET NON-MEMBER (PRINT: HIDDEN) -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-xs space-y-4 print:hidden">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">GATE REVENUE RADAR</span>
              <h3 class="text-base font-extrabold text-slate-900">Grafik Pemasukan Kasir Pos Gerbang Keluar</h3>
            </div>

            <div class="flex items-center gap-3">
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

              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Gate Sync
              </span>
            </div>
          </div>

          <div class="h-64 relative w-full">
            <Line v-if="chartMode === 'line'" :data="chartData" :options="chartOptions" />
            <Bar v-else :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- TABEL DATA LAPORAN NON-MEMBER -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xs overflow-hidden print:border-black print:rounded-none">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider print:bg-slate-100 print:text-black">
                  <th class="py-3.5 px-5">No. Plat</th>
                  <th class="py-3.5 px-5">Kode Tiket</th>
                  <th class="py-3.5 px-5 text-right">Payment</th>
                  <th class="py-3.5 px-5 text-right">Cash</th>
                  <th class="py-3.5 px-5 text-right">Kembali</th>
                  <th class="py-3.5 px-5">Petugas Kasir</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                <tr v-if="filteredList.length === 0">
                  <td colspan="6" class="py-12 text-center text-slate-400 font-bold">
                    Tidak ada data transaksi non-member yang ditemukan.
                  </td>
                </tr>
                <tr v-for="item in filteredList" :key="item.id" class="hover:bg-slate-50/60 transition">
                  <td class="py-4 px-5 font-mono font-black text-slate-900 uppercase">{{ item.no_plat || '-' }}</td>
                  <td class="py-4 px-5 font-mono font-bold text-indigo-600">{{ item.kode_tiket }}</td>
                  <td class="py-4 px-5 text-right font-mono font-black text-emerald-600">Rp {{ formatRupiah(item.total_bayar) }}</td>
                  <td class="py-4 px-5 text-right font-mono font-extrabold text-indigo-600">Rp {{ formatRupiah(item.uang_bayar) }}</td>
                  <td class="py-4 px-5 text-right font-mono text-slate-500">Rp {{ formatRupiah(item.kembalian) }}</td>
                  <td class="py-4 px-5 capitalize font-medium text-slate-600">{{ item.petugas?.nama || 'Petugas' }}</td>
                </tr>
              </tbody>

              <!-- FOOTER TOTAL REKAP -->
              <tfoot v-if="filteredList.length > 0" class="bg-slate-50 border-t-2 border-slate-200 text-xs font-black text-slate-900">
                <tr>
                  <td colspan="2" class="py-4 px-5 text-right uppercase tracking-wider text-slate-500">
                    TOTAL
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-emerald-600">
                    Rp {{ formatRupiah(totalPenerimaan) }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-indigo-700">
                    Rp {{ formatRupiah(totalCash) }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-slate-600">
                    Rp {{ formatRupiah(totalKembalian) }}
                  </td>
                  <td class="py-4 px-5 text-slate-400 font-normal">
                    {{ filteredList.length }} Unit
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
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
const laporanList = ref<any[]>([])
const searchQuery = ref('')
const chartMode = ref<'bar' | 'line'>('line')

const fetchLaporan = async () => {
  try {
    const res = await $api.get('/laporan/non-member')
    laporanList.value = res.data.data || []
  } catch (err) {
    console.error('Gagal mengambil laporan non-member:', err)
  }
}

const filteredList = computed(() => {
  return laporanList.value.filter(item => 
    (item.no_plat || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    (item.kode_tiket || '').toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const totalPenerimaan = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.total_bayar || 0), 0)
})

const totalCash = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.uang_bayar || 0), 0)
})

const totalKembalian = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.kembalian || 0), 0)
})

// Konfigurasi data dinamis grafik kasir tiket non-member
const chartData = computed(() => {
  const dataMap: Record<string, number> = {}

  filteredList.value.forEach((item) => {
    const dateKey = item.created_at 
      ? new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
      : 'Hari Ini'
    dataMap[dateKey] = (dataMap[dateKey] || 0) + Number(item.total_bayar || 0)
  })

  const labels = Object.keys(dataMap)
  const values = Object.values(dataMap)

  const finalLabels = labels.length > 0 ? labels : ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']
  const finalValues = values.length > 0 ? values : [0, 0, 0, 0, 0, 0, 0]

  return {
    labels: finalLabels,
    datasets: [
      {
        label: 'Pendapatan Kasir (Rp)',
        borderColor: '#10B981',
        backgroundColor: chartMode.value === 'bar' ? '#10B981' : 'rgba(16, 185, 129, 0.12)',
        fill: chartMode.value === 'line',
        borderWidth: 2.5,
        borderRadius: chartMode.value === 'bar' ? 6 : 0,
        pointRadius: chartMode.value === 'line' ? 3 : 0,
        pointHoverRadius: 6,
        pointBackgroundColor: '#10B981',
        tension: 0.35,
        data: finalValues
      }
    ]
  }
})

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
      cornerRadius: 8,
      callbacks: {
        label: (context: any) => ` Kasir: Rp ${formatRupiah(context.parsed.y)}`
      }
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
      ticks: {
        color: '#94A3B8',
        font: { size: 11 },
        callback: (value: any) => 'Rp ' + Number(value).toLocaleString('id-ID')
      }
    }
  }
})

const cetak = () => window.print()
const formatRupiah = (val: any) => new Intl.NumberFormat('id-ID').format(Number(val || 0))

const logout = async () => {
  try { await $api.post('/logout') } catch {}
  localStorage.removeItem('token')
  router.push('/')
}

onMounted(() => fetchLaporan())
</script>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  main, main * {
    visibility: visible;
  }
  main {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: auto !important;
    overflow: visible !important;
    padding: 0 !important;
    background: white !important;
  }
}
</style>