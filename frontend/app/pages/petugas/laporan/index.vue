<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth']
})

const { $api } = useNuxtApp() as any
const router = useRouter()

const filterTab = ref<'all' | 'member' | 'non-member'>('all')
const searchQuery = ref('')
const loading = ref(false)
const printDateText = ref('')

const formatPrintDate = (date = new Date()) =>
  new Date(date).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })

const laporanMemberList = ref<any[]>([])
const laporanNonMemberList = ref<any[]>([])

const fetchSemuaLaporan = async () => {
  loading.value = true
  try {
    const [resMember, resNonMember] = await Promise.allSettled([
      $api.get('/laporan/member'),
      $api.get('/laporan/non-member')
    ])

    if (resMember.status === 'fulfilled' && resMember.value.data?.data) {
      laporanMemberList.value = resMember.value.data.data.map((item: any) => ({
        ...item,
        tipe: 'Member',
        kode: item.kode_tiket || item.kode_member || 'MBR-UNKNOWN',
        nama: item.nama_member || 'Member Langganan',
        plat: item.no_plat || item.plat_nomor || 'MEMBER-REGISTRATION',
        nominal_tagihan: Number(item.total_harga ?? item.total_bayar ?? 0),
        nominal_bayar: Number(item.jumlah_bayar ?? item.uang_bayar ?? 0),
        kembalian: Number(item.kembalian ?? 0),
        petugas: item.petugas || 'Petugas Pos Kasir',
        tanggal: item.created_at || item.tanggal_bayar || item.waktu_keluar
      }))
    }

    if (resNonMember.status === 'fulfilled' && resNonMember.value.data?.data) {
      laporanNonMemberList.value = resNonMember.value.data.data.map((item: any) => ({
        ...item,
        tipe: 'Non-Member',
        kode: item.kode_tiket || item.kode || 'TICKET-UNKNOWN',
        nama: item.nama_member || 'Umum (Tiket)',
        plat: item.no_plat || item.plat_nomor || '-',
        nominal_tagihan: Number(item.total_bayar ?? item.total_harga ?? 0),
        nominal_bayar: Number(item.uang_bayar ?? item.jumlah_bayar ?? 0),
        kembalian: Number(item.kembalian ?? 0),
        petugas: item.petugas || 'Petugas Pos Kasir',
        tanggal: item.created_at || item.tanggal_bayar || item.waktu_keluar
      }))
    }
  } catch (err) {
    console.error('Gagal mengambil laporan terpadu:', err)
  } finally {
    loading.value = false
  }
}

const combinedList = computed(() => {
  if (filterTab.value === 'member') return laporanMemberList.value
  if (filterTab.value === 'non-member') return laporanNonMemberList.value
  return [...laporanMemberList.value, ...laporanNonMemberList.value]
})

const filteredList = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return combinedList.value

  return combinedList.value.filter(
    (item) =>
      item.kode?.toLowerCase().includes(q) ||
      item.plat?.toLowerCase().includes(q) ||
      item.nama?.toLowerCase().includes(q)
  )
})

const totalPemasukanBersih = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.nominal_tagihan || 0), 0)
})

const totalUangTunaiFisik = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.nominal_bayar || 0), 0)
})

const totalKembalian = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.kembalian || 0), 0)
})

const exportCsvRekap = () => {
  if (!filteredList.value.length) return

  const headers = ['Tipe', 'Kode', 'Nama', 'Plat', 'Tagihan', 'Dibayar', 'Kembalian', 'Tanggal']
  const rows = filteredList.value.map((item) => [
    item.tipe || 'Member',
    item.kode || '-',
    item.nama || '-',
    item.plat || '-',
    Number(item.nominal_tagihan || 0),
    Number(item.nominal_bayar || 0),
    Number(item.kembalian || 0),
    formatTanggal(item.tanggal || new Date().toISOString())
  ])

  const csv = [headers, ...rows]
    .map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `rekap-transaksi-${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

const cetak = () => {
  if (typeof window !== 'undefined') window.print()
}

const formatRupiah = (val: any) => new Intl.NumberFormat('id-ID').format(Number(val || 0))

const formatTanggal = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const logout = async () => {
  try {
    await $api.post('/logout')
  } catch {}
  localStorage.removeItem('token')
  router.push('/')
}

onMounted(() => {
  fetchSemuaLaporan()
  printDateText.value = formatPrintDate(new Date())
})
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] flex font-sans antialiased text-slate-800">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#0B0F19] text-slate-400 flex flex-col justify-between py-6 px-4 shrink-0 select-none hidden md:flex print:hidden">
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
          <NuxtLink to="/petugas" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-semibold text-xs transition">
            <span class="text-sm">⊞</span>
            <span>Dashboard</span>
          </NuxtLink>

          <NuxtLink to="/petugas/keluar" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">🚪</span>
              <span>Gate Keluar (Kasir)</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink to="/petugas/transaksi" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">🚗</span>
              <span>Kelola Transaksi</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink to="/petugas/member/select" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">👥</span>
              <span>Kelola Member</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <!-- Active Menu: Laporan -->
          <NuxtLink to="/petugas/laporan" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs shadow-xs">
            <div class="flex items-center gap-3">
              <span class="text-sm text-cyan-400">📊</span>
              <span>Laporan</span>
            </div>
            <span class="text-xs text-cyan-400 font-bold">●</span>
          </NuxtLink>

          <NuxtLink to="/petugas/markir" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">🅿️</span>
              <span>Sedang Parkir</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>
        </nav>
      </div>

      <div class="pt-4 border-t border-slate-800/80">
        <button @click="logout" class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 transition cursor-pointer">
          <span>🚪</span>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <!-- HEADER BERSIH (HANYA CETAK LAPORAN DI KANAN ATAS) -->
      <header class="bg-white px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0 print:hidden">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">FINANCIAL & AUDIT REPORTS</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Laporan Rekapitulasi Parkir Terpadu</h1>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="exportCsvRekap"
            class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-xl font-bold text-[11px] shadow-xs transition cursor-pointer"
          >
            📄 CSV / Excel
          </button>
          <button
            @click="cetak"
            class="bg-[#0284C7] hover:bg-[#0369A1] text-white px-4 py-2 rounded-xl font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>🖨️</span> Print
          </button>
        </div>
      </header>

      <div class="p-8 space-y-6">
        <!-- SUMMARY STATS CARD -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 print:hidden">
          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Entri Transaksi</span>
            <h3 class="text-2xl font-black text-slate-900">{{ filteredList.length }} Rekap</h3>
            <span class="text-[11px] text-slate-400">Gabungan Member & Non-Member</span>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Pendapatan Bersih</span>
            <h3 class="text-2xl font-black text-emerald-600">Rp {{ formatRupiah(totalPemasukanBersih) }}</h3>
            <span class="text-[11px] text-slate-400">Total akumulasi pos kasir & iuran</span>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Uang Fisik Diterima</span>
            <h3 class="text-2xl font-black text-indigo-600">Rp {{ formatRupiah(totalUangTunaiFisik) }}</h3>
            <span class="text-[11px] text-slate-400">Kembalian: Rp {{ formatRupiah(totalKembalian) }}</span>
          </div>
        </div>

        <!-- PRINT HEADER (Hanya muncul saat print) -->
        <div class="hidden print:block text-center border-b pb-4 mb-4">
          <h2 class="text-xl font-black uppercase">LAPORAN REKAPITULASI PENDAPATAN PARKIR TERPADU</h2>
          <p class="text-xs text-slate-600">Dicetak pada {{ printDateText || formatPrintDate() }}</p>
        </div>

        <!-- SEARCH & FILTER TAB -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
          <div class="bg-slate-100 p-1 rounded-2xl flex items-center gap-1 shrink-0">
            <button
              type="button"
              @click="filterTab = 'all'"
              :class="filterTab === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
              class="px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer"
            >
              Semua ({{ combinedList.length }})
            </button>
            <button
              type="button"
              @click="filterTab = 'non-member'"
              :class="filterTab === 'non-member' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
              class="px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer"
            >
              Non-Member ({{ laporanNonMemberList.length }})
            </button>
            <button
              type="button"
              @click="filterTab = 'member'"
              :class="filterTab === 'member' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
              class="px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer"
            >
              Member ({{ laporanMemberList.length }})
            </button>
          </div>

          <div class="relative w-full sm:w-72">
            <span class="absolute left-3.5 top-2.5 text-xs text-slate-400">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari kode / plat / nama..."
              class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-cyan-500 transition"
            />
          </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xs overflow-hidden print:border-none print:shadow-none">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                  <th class="py-3.5 px-5">Kode Transaksi</th>
                  <th class="py-3.5 px-5">Tipe</th>
                  <th class="py-3.5 px-5">No. Plat / Kategori</th>
                  <th class="py-3.5 px-5">Pelanggan / Identitas</th>
                  <th class="py-3.5 px-5 text-right">Pendapatan Bersih</th>
                  <th class="py-3.5 px-5 text-right">Uang Fisik (Cash)</th>
                  <th class="py-3.5 px-5 text-right">Kembalian</th>
                  <th class="py-3.5 px-5 text-center">Waktu Transaksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                <tr v-if="loading">
                  <td colspan="8" class="py-12 text-center text-slate-400 font-bold">
                    Memuat data laporan terpadu...
                  </td>
                </tr>
                <tr v-else-if="filteredList.length === 0">
                  <td colspan="8" class="py-12 text-center text-slate-400 font-bold">
                    Tidak ada data laporan yang ditemukan.
                  </td>
                </tr>
                <tr
                  v-else
                  v-for="(item, idx) in filteredList"
                  :key="idx"
                  class="hover:bg-slate-50/50 transition"
                >
                  <td class="py-4 px-5 font-mono font-black text-indigo-600">
                    {{ item.kode }}
                  </td>
                  <td class="py-4 px-5">
                    <span
                      class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                      :class="item.tipe === 'Member'
                        ? 'bg-cyan-50 text-cyan-700 border border-cyan-200'
                        : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                    >
                      {{ item.tipe }}
                    </span>
                  </td>
                  <td class="py-4 px-5 font-mono font-extrabold text-slate-900 uppercase">
                    {{ item.plat }}
                  </td>
                  <td class="py-4 px-5 font-bold text-slate-800">
                    {{ item.nama }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono font-black text-slate-900">
                    Rp {{ formatRupiah(item.nominal_tagihan) }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono font-extrabold text-emerald-600">
                    Rp {{ formatRupiah(item.nominal_bayar) }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-slate-500">
                    Rp {{ formatRupiah(item.kembalian) }}
                  </td>
                  <td class="py-4 px-5 text-center text-slate-500 font-medium">
                    {{ formatTanggal(item.tanggal) }}
                  </td>
                </tr>
              </tbody>

              <!-- FOOTER TOTAL -->
              <tfoot v-if="filteredList.length > 0" class="bg-slate-50 border-t-2 border-slate-200 text-xs font-black text-slate-900">
                <tr>
                  <td colspan="4" class="py-4 px-5 text-right uppercase tracking-wider text-slate-500">
                    TOTAL KESELURUHAN (TERPADU)
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-emerald-600">
                    Rp {{ formatRupiah(totalPemasukanBersih) }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-indigo-700">
                    Rp {{ formatRupiah(totalUangTunaiFisik) }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-slate-600">
                    Rp {{ formatRupiah(totalKembalian) }}
                  </td>
                  <td class="py-4 px-5 text-center text-slate-400 font-normal">
                    {{ filteredList.length }} Data
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