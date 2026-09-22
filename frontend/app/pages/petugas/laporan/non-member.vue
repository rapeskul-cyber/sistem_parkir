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
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">TRANSACTION REPORTS</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Laporan Pembayaran Non-Member</h1>
        </div>

        <div class="flex items-center gap-3">
          <NuxtLink
            to="/petugas/laporan/member"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>👥</span> Ke Member
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
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tiket Selesai</span>
            <h3 class="text-2xl font-black text-slate-900">{{ filteredList.length }} Unit</h3>
            <span class="text-[11px] text-slate-400">Total kendaraan keluar kasir</span>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Pendapatan Kasir</span>
            <h3 class="text-2xl font-black text-emerald-600">Rp {{ formatRupiah(totalBayar) }}</h3>
            <span class="text-[11px] text-slate-400">Pemasukan bersih parkir</span>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Uang Fisik (Cash)</span>
            <h3 class="text-2xl font-black text-indigo-600">Rp {{ formatRupiah(totalUangFisik) }}</h3>
            <span class="text-[11px] text-slate-400">Kembalian: Rp {{ formatRupiah(totalKembalian) }}</span>
          </div>
        </div>

        <!-- PRINT HEADER (Hanya muncul saat cetak) -->
        <div class="hidden print:block text-center border-b pb-4 mb-4">
          <h2 class="text-xl font-black uppercase">LAPORAN TRANSAKSI TIKET PARKIR UMUM (NON-MEMBER)</h2>
          <p class="text-xs text-slate-600">PLAZA ANDALAS - TANGGAL CETAK: {{ new Date().toLocaleDateString('id-ID') }}</p>
        </div>

        <!-- SEARCH & ACTION BAR -->
        <div class="flex items-center justify-between gap-4 print:hidden">
          <div class="relative w-72">
            <span class="absolute left-3.5 top-2.5 text-xs text-slate-400">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari plat nomor / kode tiket..."
              class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-cyan-500 transition"
            />
          </div>
          <span class="text-xs font-bold text-slate-500">Ditemukan: {{ filteredList.length }} transaksi</span>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xs overflow-hidden print:border-none print:shadow-none">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                  <th class="py-3.5 px-5">No Plat</th>
                  <th class="py-3.5 px-5">Kode Tiket</th>
                  <th class="py-3.5 px-5 text-right">Tarif (Payment)</th>
                  <th class="py-3.5 px-5 text-right">Tunai (Cash)</th>
                  <th class="py-3.5 px-5 text-right">Kembalian</th>
                  <th class="py-3.5 px-5 text-center">Petugas Pos</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                <tr v-if="filteredList.length === 0">
                  <td colspan="6" class="py-12 text-center text-slate-400 font-bold">
                    Tidak ada data transaksi non-member yang ditemukan.
                  </td>
                </tr>
                <tr v-else v-for="item in filteredList" :key="item.id" class="hover:bg-slate-50/50 transition">
                  <td class="py-4 px-5 font-mono font-extrabold text-slate-900 uppercase">{{ item.no_plat || item.plat_nomor || '-' }}</td>
                  <td class="py-4 px-5 font-mono font-bold text-indigo-600">{{ item.kode_tiket }}</td>
                  <td class="py-4 px-5 text-right font-mono font-black text-slate-900">Rp {{ formatRupiah(item.total_bayar || item.total_tarif) }}</td>
                  <td class="py-4 px-5 text-right font-mono font-extrabold text-emerald-600">Rp {{ formatRupiah(item.uang_bayar) }}</td>
                  <td class="py-4 px-5 text-right font-mono text-slate-500">Rp {{ formatRupiah(item.kembalian) }}</td>
                  <td class="py-4 px-5 text-center capitalize font-medium text-slate-500">{{ item.petugas?.nama || 'Petugas Pos' }}</td>
                </tr>
              </tbody>

              <!-- FOOTER BARIS TOTAL -->
              <tfoot v-if="filteredList.length > 0" class="bg-slate-50 border-t-2 border-slate-200 text-xs font-black text-slate-900">
                <tr>
                  <td colspan="2" class="py-4 px-5 text-right uppercase tracking-wider text-slate-500">
                    TOTAL KESELURUHAN
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-emerald-600">
                    Rp {{ formatRupiah(totalBayar) }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-indigo-700">
                    Rp {{ formatRupiah(totalUangFisik) }}
                  </td>
                  <td class="py-4 px-5 text-right font-mono text-slate-600">
                    Rp {{ formatRupiah(totalKembalian) }}
                  </td>
                  <td class="py-4 px-5 text-center text-slate-400 font-normal">
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

definePageMeta({ 
  middleware: ['auth'] 
})

const { $api } = useNuxtApp()
const router = useRouter()

const laporanList = ref<any[]>([])
const searchQuery = ref('')

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
    (item.no_plat || item.plat_nomor)?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    item.kode_tiket?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const totalBayar = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.total_bayar || curr.total_tarif || 0), 0)
})

const totalUangFisik = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.uang_bayar || 0), 0)
})

const totalKembalian = computed(() => {
  return filteredList.value.reduce((acc, curr) => acc + Number(curr.kembalian || 0), 0)
})

const cetak = () => window.print()

const formatRupiah = (val: any) => new Intl.NumberFormat('id-ID').format(Number(val || 0))

const logout = async () => {
  try {
    await $api.post('/logout')
  } catch {}
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