<template>
  <div class="min-h-screen bg-[#F8FAFC] flex font-sans antialiased text-slate-800">
    <!-- SIDEBAR PERSIS REFERENSI ADMIN -->
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

        <!-- Menu Navigation -->
        <nav class="space-y-1">
          <NuxtLink
            to="/admin/dashboard"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-semibold text-xs transition"
          >
            <span class="text-sm">⊞</span>
            <span>Dashboard</span>
          </NuxtLink>

          <!-- Active: Kelola Petugas -->
          <NuxtLink
            to="/admin/petugas"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs shadow-xs"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm text-cyan-400">👥</span>
              <span>Kelola Petugas</span>
            </div>
            <span class="text-xs text-cyan-400 font-bold">●</span>
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

      <!-- Bottom Profile & Logout -->
      <div class="space-y-3 pt-4 border-t border-slate-800/80">
        <div class="bg-slate-900/90 border border-slate-800 px-3.5 py-2.5 rounded-2xl flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-center text-xs font-bold">
            ADM
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-white truncate">Administrator</p>
            <p class="text-[10px] text-cyan-400 font-medium">Hak Akses User</p>
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
      <!-- TOPBAR HEADER -->
      <header class="bg-white px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">USER ACCESS MANAGEMENT</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Manajemen Akun Petugas</h1>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            @click="router.push('/admin/dashboard')"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <span>←</span> Dashboard
          </button>
          <button
            @click="router.push('/admin/petugas/tambah')"
            class="bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-black px-4 py-2 rounded-xl transition cursor-pointer shadow-xs flex items-center gap-1.5"
          >
            <span>+</span> Tambah Petugas
          </button>
        </div>
      </header>

      <!-- BODY WRAPPER -->
      <div class="p-8 space-y-6">
        <!-- FILTER & SUMMARY -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="relative w-80">
            <span class="absolute left-3.5 top-2.5 text-xs text-slate-400">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama, email, atau no. telepon..."
              class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs font-semibold focus:outline-none focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/20 transition"
            />
          </div>
          <span class="text-xs font-bold text-slate-400">
            Terdaftar: <strong class="text-slate-700 font-mono">{{ filteredPetugas.length }}</strong> akun petugas
          </span>
        </div>

        <!-- TABEL PETUGAS -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                  <th class="py-3.5 px-5 w-16 text-center">No</th>
                  <th class="py-3.5 px-5">Nama Petugas</th>
                  <th class="py-3.5 px-5">Kontak WhatsApp</th>
                  <th class="py-3.5 px-5">Email Login</th>
                  <th class="py-3.5 px-5 text-center">Role Otoritas</th>
                  <th class="py-3.5 px-5 text-right">Tindakan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                <tr v-if="loading">
                  <td colspan="6" class="py-12 text-center text-slate-400 font-bold">
                    Memuat daftar akun petugas...
                  </td>
                </tr>
                <tr v-else-if="filteredPetugas.length === 0">
                  <td colspan="6" class="py-12 text-center text-slate-400 font-bold">
                    Tidak ada akun petugas yang ditemukan.
                  </td>
                </tr>
                <tr
                  v-else
                  v-for="(petugas, index) in filteredPetugas"
                  :key="petugas.id"
                  class="hover:bg-slate-50/60 transition"
                >
                  <td class="py-4 px-5 text-center font-mono font-bold text-slate-400">
                    {{ index + 1 }}
                  </td>
                  <td class="py-4 px-5 font-extrabold text-slate-900">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 font-black flex items-center justify-center text-xs">
                        {{ petugas.name ? petugas.name.charAt(0).toUpperCase() : 'P' }}
                      </div>
                      <span>{{ petugas.name }}</span>
                    </div>
                  </td>

                  <!-- NOMOR TELEPON -->
                  <td class="py-4 px-5 font-mono text-slate-700">
                    <span v-if="petugas.no_telepon || petugas.no_hp" class="inline-flex items-center gap-1.5 font-bold">
                      <span class="text-emerald-500">📞</span>
                      {{ petugas.no_telepon || petugas.no_hp }}
                    </span>
                    <span v-else class="text-slate-400 italic">Belum diisi</span>
                  </td>

                  <td class="py-4 px-5 font-mono text-slate-500">
                    {{ petugas.email }}
                  </td>

                  <!-- ROLE IDENTIFIER -->
                  <td class="py-4 px-5 text-center">
                    <span
                      v-if="petugas.role === 'admin'"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                      Super Admin
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                      Petugas Pos
                    </span>
                  </td>

                  <!-- AKSI: HANYA EDIT DAN HAPUS -->
                  <td class="py-4 px-5 text-right space-x-1.5">
                    <button
                      @click="router.push(`/admin/petugas/edit/${petugas.id}`)"
                      class="bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 px-3 py-1.5 rounded-xl text-[11px] font-bold transition cursor-pointer"
                    >
                      ✏️ Edit
                    </button>
                    <button
                      @click="hapusPetugas(petugas.id, petugas.name)"
                      class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-3 py-1.5 rounded-xl text-[11px] font-bold transition cursor-pointer"
                    >
                      Hapus
                    </button>
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
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth', 'cek-admin']
})

const { $api } = useNuxtApp() as any
const router = useRouter()

const listPetugas = ref<any[]>([])
const loading = ref(false)
const searchQuery = ref('')

const fetchPetugas = async () => {
  loading.value = true
  try {
    const res = await $api.get('/admin/petugas')
    if (res.data) {
      listPetugas.value = res.data.data ?? res.data
    }
  } catch (err: any) {
    console.error('Gagal mengambil data petugas:', err)
    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      router.push('/')
    }
  } finally {
    loading.value = false
  }
}

const filteredPetugas = computed(() => {
  if (!searchQuery.value) return listPetugas.value
  const q = searchQuery.value.toLowerCase()
  return listPetugas.value.filter((item) => {
    const nameMatch = item.name?.toLowerCase().includes(q)
    const emailMatch = item.email?.toLowerCase().includes(q)
    const phoneMatch = (item.no_telepon || item.no_hp || '').toLowerCase().includes(q)
    return nameMatch || emailMatch || phoneMatch
  })
})

const hapusPetugas = async (id: number, nama: string) => {
  if (!confirm(`Apakah kamu yakin ingin menghapus akun petugas "${nama}"?`)) {
    return
  }

  try {
    const res = await $api.delete(`/admin/petugas/${id}`)
    alert(res.data?.message || 'Akun petugas berhasil dihapus!')
    fetchPetugas()
  } catch (err: any) {
    alert(err?.response?.data?.message || 'Gagal menghapus akun petugas.')
  }
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
  fetchPetugas()
})
</script>