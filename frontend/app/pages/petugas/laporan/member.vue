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

          <NuxtLink to="/petugas/laporan/member" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs shadow-xs">
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
      <!-- HEADER -->
      <header class="bg-white px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0 print:hidden">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">FINANCIAL REPORTS</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Laporan Pembayaran Member</h1>
        </div>

        <div class="flex items-center gap-3">
          <NuxtLink
            to="/petugas/laporan/non-member"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>🚗</span> Ke Non-Member
          </NuxtLink>
          <button
            @click="cetak"
            class="bg-[#0284C7] hover:bg-[#0369A1] text-white px-4 py-2 rounded-xl font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>🖨️</span> Cetak Dokumen
          </button>
        </div>
      </header>

      <!-- BODY -->
      <div class="p-8 space-y-6">
        <!-- SUMMARY STATS CARD -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 print:hidden">
          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Transaksi</span>
            <h3 class="text-2xl font-black text-slate-900">{{ filteredList.length }} Data</h3>
            <span class="text-[11px] text-slate-400">Member tersaring</span>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Tagihan</span>
            <h3 class="text-2xl font-black text-indigo-600">Rp {{ formatRupiah(totalTagihan) }}</h3>
            <span class="text-[11px] text-slate-400">Total nominal invoice</span>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Kas Masuk (Cash)</span>
            <h3 class="text-2xl font-black text-emerald-600">Rp {{ formatRupiah(totalCash) }}</h3>
            <span class="text-[11px] text-slate-400">Kembalian: Rp {{ formatRupiah(totalKembalian) }}</span>
          </div>
        </div>

        <!-- PRINT HEADER (Hanya muncul saat cetak) -->
        <div class="hidden print:block text-center border-b pb-4 mb-4">
          <h2 class="text-xl font-black uppercase">LAPORAN PEMBAYARAN MEMBER PARKIR</h2>
          <p class="text-xs text-slate-600">Dicetak pada {{ printDateText || formatPrintDate() }}</p>
        </div>

        <!-- SEARCH & ACTION BAR -->
        <div class="flex items-center justify-between gap-4 print:hidden">
          <div class="relative w-72">
            <span class="absolute left-3.5 top-2.5 text-xs text-slate-400">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari plat nomor / nama member..."
              class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-cyan-500 transition"
            />
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="exportCsvMember"
              class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-xl font-bold text-[11px] shadow-xs transition cursor-pointer"
            >
              📄 CSV / Excel
            </button>
            <button
              type="button"
              @click="cetak"
              class="bg-[#0284C7] hover:bg-[#0369A1] text-white px-4 py-2 rounded-xl font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer"
            >
              <span>🖨️</span> Print
            </button>
          </div>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xs overflow-hidden print:border-none print:shadow-none">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                  <th class="py-3.5 px-5">No Plat</th>
                  <th class="py-3.5 px-5">Nama Member</th>
                  <th class="py-3.5 px-5 text-center">Bulan</th>
                  <th class="py-3.5 px-5 text-right">Tagihan</th>
                  <th class="py-3.5 px-5 text-right">Dibayar (Cash)</th>
                  <th class="py-3.5 px-5 text-right">Kembalian</th>
                  <th class="py-3.5 px-5 text-center">Petugas</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                <tr v-if="filteredList.length === 0">
                  <td colspan="7" class="py-12 text-center text-slate-400 font-bold">
                    Tidak ada data pembayaran member yang ditemukan.
                  </td>
                </tr>
                <tr v-else v-for="item in filteredList" :key="item.id" class="hover:bg-slate-50/50 transition">
                  <td class="py-4 px-5 font-mono font-extrabold text-slate-900 uppercase">
                    {{ item.no_plat || item.plat_nomor || '-' }}
                  </td>
                  <td class="py-4 px-5 font-bold text-slate-800">{{ item.nama_member }}</td>
                  <td class="py-4 px-5 text-center font-mono text-slate-500">{{ item.bulan || formatBulan(item.created_at) }}</td>
                  <td class="py-4 px-5 text-right font-mono font-bold text-slate-900">Rp {{ formatRupiah(item.total_harga || 150000) }}</td>
                  <td class="py-4 px-5 text-right font-mono font-extrabold text-emerald-600">Rp {{ formatRupiah(item.jumlah_bayar || 150000) }}</td>
                  <td class="py-4 px-5 text-right font-mono text-slate-500">Rp {{ formatRupiah(item.kembalian || 0) }}</td>
                  <td class="py-4 px-5 text-center capitalize font-medium text-slate-500">{{ item.petugas?.nama || 'Admin Pos' }}</td>
                </tr>
              </tbody>

              <!-- FOOTER BARIS TOTAL -->
              <tfoot v-if="filteredList.length > 0" class="bg-slate-50 border-t-2 border-slate-200 text-xs font-black text-slate-900">
                <tr>
                  <td colspan="3" class="py-4 px-5 text-right uppercase tracking-wider text-slate-500">
                    TOTAL KESELURUHAN
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-indigo-700">
                    Rp {{ formatRupiah(totalTagihan) }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-emerald-600">
                    Rp {{ formatRupiah(totalCash) }}
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

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({ 
  middleware: ['auth'] 
})

const { $api } = useNuxtApp()
const router = useRouter()

const laporanList = ref<any[]>([])
const searchQuery = ref('')
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

const fetchLaporan = async () => {
  try {
    const res = await $api.get('/laporan/member')
    laporanList.value = res.data.data || []
  } catch (err) {
    console.error('Gagal mengambil laporan member:', err)
  }
}

const filteredList = computed(() => {
  return laporanList.value.filter(item => 
    item.nama_member?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    (item.no_plat || item.plat_nomor)?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const totalTagihan = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.total_harga || 150000), 0)
})

const totalCash = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.jumlah_bayar || 150000), 0)
})

const totalKembalian = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.kembalian || 0), 0)
})

const exportCsvMember = () => {
  if (!filteredList.value.length) return

  const headers = ['No Plat', 'Nama Member', 'Bulan', 'Tagihan', 'Dibayar (Cash)', 'Kembalian', 'Petugas']
  const rows = filteredList.value.map((item) => [
    item.no_plat || item.plat_nomor || '-',
    item.nama_member || '-',
    item.bulan || formatBulan(item.created_at),
    Number(item.total_harga || 150000),
    Number(item.jumlah_bayar || 150000),
    Number(item.kembalian || 0),
    item.petugas?.nama || 'Admin Pos'
  ])

  const csv = [headers, ...rows]
    .map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `laporan-member-${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

const cetak = () => {
  if (typeof window !== 'undefined') window.print()
}

const formatRupiah = (val: any) => new Intl.NumberFormat('id-ID').format(Number(val || 0))

const formatBulan = (dateStr: any) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return `${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`
}

const logout = async () => {
  try {
    await $api.post('/logout')
  } catch {}
  localStorage.removeItem('token')
  router.push('/')
}

onMounted(() => {
  fetchLaporan()
  printDateText.value = formatPrintDate(new Date())
})
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