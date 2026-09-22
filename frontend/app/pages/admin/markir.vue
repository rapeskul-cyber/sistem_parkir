<template>
  <div class="min-h-screen bg-[#F8FAFC] flex font-sans antialiased text-slate-800">
    <!-- SIDEBAR ADMIN PERSIS REFERENSI -->
    <aside class="w-64 bg-[#0B0F19] text-slate-400 flex flex-col justify-between py-6 px-4 shrink-0 select-none hidden md:flex">
      <div>
        <!-- Brand Header -->
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

        <!-- Menu Links Admin -->
        <nav class="space-y-1">
          <NuxtLink
            to="/admin/dashboard"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-semibold text-xs transition"
          >
            <span class="text-sm">⊞</span>
            <span>Dashboard</span>
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

          <!-- Menu Aktif: Sedang Parkir -->
          <NuxtLink
            to="/admin/markir"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs shadow-xs"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm text-cyan-400">🅿️</span>
              <span>Sedang Parkir</span>
            </div>
            <span class="text-xs text-cyan-400 font-bold">●</span>
          </NuxtLink>
        </nav>
      </div>

      <!-- Bottom Profile Card & Logout -->
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

        <button
          @click="logout"
          class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 transition cursor-pointer"
        >
          <span>🚪</span>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- CONTENT AREA -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <!-- Top Bar Header -->
      <header class="bg-white px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">LIVE RADAR SYSTEM</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Monitoring Kendaraan Sedang Parkir</h1>
        </div>

        <div class="flex items-center gap-3">
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200/60">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
            Sync Realtime (3s)
          </span>
          <NuxtLink
            to="/admin/dashboard"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <span>←</span> Dashboard
          </NuxtLink>
        </div>
      </header>

      <!-- Main Body -->
      <div class="p-8 space-y-6">
        <!-- STATS COUNTER -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Unit Parkir</span>
              <h3 class="text-2xl font-black text-slate-900">{{ kendaraanAktifList.length }} Unit</h3>
              <span class="text-[11px] text-slate-400">Total kendaraan di dalam area</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-lg font-bold">
              🅿️
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Mobil Terparkir</span>
              <h3 class="text-2xl font-black text-indigo-600">{{ countMobil }} Unit</h3>
              <span class="text-[11px] text-slate-400">Tarif Rp 5.000 / jam</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center text-lg font-bold">
              🚗
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Motor Terparkir</span>
              <h3 class="text-2xl font-black text-cyan-600">{{ countMotor }} Unit</h3>
              <span class="text-[11px] text-slate-400">Tarif Rp 2.000 / jam</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-600 flex items-center justify-center text-lg font-bold">
              🛵
            </div>
          </div>
        </div>

        <!-- SEARCH & ACTION BAR -->
        <div class="flex items-center justify-between gap-4">
          <div class="relative w-72">
            <span class="absolute left-3.5 top-2.5 text-xs text-slate-400">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari kode tiket / plat nomor..."
              class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/20 transition"
            />
          </div>
          <span class="text-xs font-bold text-slate-400">Menampilkan {{ filteredList.length }} unit parkir</span>
        </div>

        <!-- TABLE SECTION -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                  <th class="py-3.5 px-5">Kode Tiket / Sesi</th>
                  <th class="py-3.5 px-5">Tipe</th>
                  <th class="py-3.5 px-5">Kategori Kendaraan</th>
                  <th class="py-3.5 px-5">No. Plat / Nama</th>
                  <th class="py-3.5 px-5">Waktu Masuk</th>
                  <th class="py-3.5 px-5 text-center">Status Sesi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                <tr v-if="filteredList.length === 0">
                  <td colspan="6" class="py-12 text-center text-slate-400 font-bold">
                    Tidak ada kendaraan yang sedang parkir saat ini.
                  </td>
                </tr>
                <tr
                  v-else
                  v-for="item in filteredList"
                  :key="item.id"
                  class="hover:bg-slate-50/60 transition"
                >
                  <td class="py-4 px-5 font-mono font-black text-indigo-600">
                    {{ item.kode_tiket }}
                  </td>
                  <td class="py-4 px-5">
                    <span
                      class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                      :class="item.tipe === 'Member' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-slate-100 text-slate-600'"
                    >
                      {{ item.tipe || 'Non-Member' }}
                    </span>
                  </td>
                  <td class="py-4 px-5 capitalize font-medium text-slate-700">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 text-[11px] font-bold">
                      {{ (item.kategori || '').toLowerCase().includes('mobil') ? '🚗 Mobil' : '🛵 Motor' }}
                    </span>
                  </td>
                  <td class="py-4 px-5 font-mono font-extrabold text-slate-900 uppercase">
                    {{ item.no_plat || '-' }}
                  </td>
                  <td class="py-4 px-5 text-slate-500 font-medium">
                    {{ formatTanggal(item.created_at) }}
                  </td>
                  <td class="py-4 px-5 text-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                      Sedang Parkir
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

definePageMeta({
  middleware: ['auth', 'cek-admin']
})

const { $api } = useNuxtApp()
const router = useRouter()

const kendaraanAktifList = ref<any[]>([])
const searchQuery = ref('')
let intervalId: any = null

const fetchKendaraanAktif = async () => {
  try {
    const res = await $api.get('/parkir/aktif')
    if (res.data && res.data.data) {
      kendaraanAktifList.value = res.data.data
    }
  } catch (error) {
    console.error('Gagal mengambil data kendaraan aktif:', error)
  }
}

const filteredList = computed(() => {
  if (!searchQuery.value) return kendaraanAktifList.value
  const q = searchQuery.value.toLowerCase()
  return kendaraanAktifList.value.filter(
    (item) =>
      item.kode_tiket?.toLowerCase().includes(q) ||
      item.no_plat?.toLowerCase().includes(q) ||
      item.kategori?.toLowerCase().includes(q)
  )
})

const countMobil = computed(() => {
  return kendaraanAktifList.value.filter((item) =>
    (item.kategori || '').toLowerCase().includes('mobil')
  ).length
})

const countMotor = computed(() => {
  return kendaraanAktifList.value.filter((item) =>
    !(item.kategori || '').toLowerCase().includes('mobil')
  ).length
})

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
  localStorage.removeItem('user')
  router.push('/')
}

onMounted(() => {
  fetchKendaraanAktif()
  intervalId = setInterval(fetchKendaraanAktif, 3000)
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})
</script>