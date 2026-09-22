<template>
  <div class="min-h-screen bg-[#F8FAFC] flex font-sans antialiased text-slate-800">
    <!-- SIDEBAR PERSIS REFERENSI -->
    <aside class="w-64 bg-[#0B0F19] text-slate-400 flex flex-col justify-between py-6 px-4 shrink-0 select-none hidden md:flex">
      <div>
        <!-- Brand / Header Sidebar -->
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

        <!-- Menu Navigasi -->
        <nav class="space-y-1">
          <NuxtLink
            to="/petugas"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-semibold text-xs transition group"
          >
            <span class="text-sm">⊞</span>
            <span>Dashboard</span>
          </NuxtLink>

          <NuxtLink
            to="/petugas/keluar"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition group"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">🚪</span>
              <span>Gate Keluar (Kasir)</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink
            to="/petugas/transaksi"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition group"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">🚗</span>
              <span>Kelola Transaksi</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <!-- Active Page -->
          <NuxtLink
            to="/petugas/member/select"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs shadow-xs"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm text-cyan-400">👥</span>
              <span>Kelola Member</span>
            </div>
            <span class="text-xs text-cyan-400 font-bold">●</span>
          </NuxtLink>

          <NuxtLink
            to="/petugas/laporan"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition group"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">📊</span>
              <span>Laporan</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>

          <NuxtLink
            to="/petugas/markir"
            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition group"
          >
            <div class="flex items-center gap-3">
              <span class="text-sm">🅿️</span>
              <span>Sedang Parkir</span>
            </div>
            <span class="text-[10px] bg-indigo-600 text-white font-black px-2 py-0.5 rounded-full">
              {{ totalSedangParkir }}
            </span>
          </NuxtLink>
        </nav>
      </div>

      <!-- Bottom Profile Card & Logout -->
      <div class="space-y-3 pt-4 border-t border-slate-800/80">
        <div class="bg-slate-900/90 border border-slate-800 px-3.5 py-2.5 rounded-2xl flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-xs font-bold">
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

    <!-- KONTEN UTAMA -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <!-- HEADER -->
      <header class="bg-white px-8 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 shrink-0">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">PLAZA ANDALAS SYSTEM</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Data Member Parkir</h1>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="router.push('/petugas')"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-xs"
          >
            <span>←</span> Dashboard
          </button>

          <button
            @click="router.push('/petugas/member/tambah')"
            class="bg-[#0284C7] hover:bg-[#0369A1] text-white font-bold text-xs px-4 py-2 rounded-xl flex items-center gap-1.5 shadow-xs transition cursor-pointer"
          >
            <span class="text-sm font-bold">+</span>
            <span>Tambah Member</span>
          </button>
        </div>
      </header>

      <!-- BODY WRAPPER -->
      <div class="p-8 space-y-6">
        <!-- RINGKASAN METRIC REALTIME -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-lg">
                👥
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Member</p>
                <h3 class="text-lg font-black text-slate-900">{{ members.length }} Org</h3>
              </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Database</span>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-lg">
                ✓
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Member Aktif (Lunas)</p>
                <h3 class="text-lg font-black text-emerald-600">{{ countLunas }} Org</h3>
              </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-mono">Live</span>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center text-lg">
                !
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Expired / Tagihan Baru</p>
                <h3 class="text-lg font-black text-rose-600">{{ countExpired }} Org</h3>
              </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 font-mono">Tagihan</span>
          </div>
        </div>

        <!-- TABEL DATA MEMBER -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xs overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <h3 class="text-sm font-extrabold text-slate-900">Daftar Langganan Member</h3>
              <p class="text-[11px] text-slate-400 mt-0.5">Status hak akses dihitung otomatis berdasarkan tanggal hari ini vs tanggal expired</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
              <div class="relative">
                <input
                  v-model="search"
                  type="text"
                  placeholder="Cari nama / kode / perusahaan"
                  class="w-full sm:w-64 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-200"
                />
              </div>

              <select
                v-model="filterStatus"
                class="border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-200"
              >
                <option value="all">Semua status</option>
                <option value="active">Aktif</option>
                <option value="expired">Expired</option>
              </select>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                  <th class="py-3.5 px-5">Kode Member</th>
                  <th class="py-3.5 px-5">Nama Pelanggan</th>
                  <th class="py-3.5 px-5">Instansi/Perusahaan</th>
                  <th class="py-3.5 px-5">Tagihan</th>
                  <th class="py-3.5 px-5">Dibayar</th>
                  <th class="py-3.5 px-5">Status Hak Akses</th>
                  <th class="py-3.5 px-5">Masa Berlaku</th>
                  <th class="py-3.5 px-5 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs">
                <tr
                  v-for="item in filteredMembers"
                  :key="item.id"
                  class="hover:bg-slate-50/60 transition duration-150"
                >
                  <td class="py-4 px-5 font-mono font-bold text-indigo-600">
                    {{ item.kode_member }}
                  </td>
                  <td class="py-4 px-5 font-extrabold text-slate-800">
                    {{ item.nama_member }}
                  </td>
                  <td class="py-4 px-5 text-slate-500 font-medium">
                    {{ item.nama_perusahaan }}
                  </td>
                  <td class="py-4 px-5 font-bold text-slate-900 font-mono">
                    Rp {{ formatRupiah(item.total_harga || 150000) }}
                  </td>
                  <td class="py-4 px-5 font-bold text-slate-700 font-mono">
                    Rp {{ formatRupiah(item.jumlah_bayar || item.total_harga || 150000) }}
                  </td>

                  <!-- STATUS DINAMIS REALTIME (CEK TANGGAL SEKARANG) -->
                  <td class="py-4 px-5">
                    <span
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold"
                      :class="
                        cekStatusAktif(item)
                          ? 'bg-emerald-50 text-emerald-600 border border-emerald-200/60'
                          : 'bg-rose-50 text-rose-600 border border-rose-200/60'
                      "
                    >
                      <span
                        class="w-1.5 h-1.5 rounded-full"
                        :class="cekStatusAktif(item) ? 'bg-emerald-500' : 'bg-rose-500'"
                      ></span>
                      {{ cekStatusAktif(item) ? 'Lunas' : 'Belum Lunas' }}
                    </span>
                  </td>

                  <td class="py-4 px-5 font-medium text-slate-500 font-mono">
                    {{ formatTanggal(item.tanggal_expired) }}
                  </td>

                  <td class="py-4 px-5">
                    <div class="flex items-center justify-center gap-2">
                      <button
                        type="button"
                        @click.stop="lihatDetail(item.id)"
                        class="p-2 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-600 hover:text-white transition cursor-pointer"
                        title="Lihat Detail & QR"
                      >
                        <EyeIcon class="w-4 h-4" />
                      </button>

                      <button
                        type="button"
                        @click.stop="edit(item.id)"
                        class="p-2 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white transition cursor-pointer"
                        title="Edit Data Member"
                      >
                        <PencilIcon class="w-4 h-4" />
                      </button>

                      <button
                        v-if="!cekStatusAktif(item)"
                        type="button"
                        @click.stop="hapus(item.id)"
                        class="p-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition cursor-pointer"
                        title="Hapus Member"
                      >
                        <TrashIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="filteredMembers.length === 0">
                  <td colspan="8" class="py-14 text-center text-slate-400 text-xs font-semibold">
                    Tidak ada data member yang sesuai dengan pencarian atau filter.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>

    <!-- MODAL DETAIL KARTU MEMBER -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white rounded-3xl p-6 w-full max-w-[420px] shadow-2xl border border-slate-100 relative">
        <div class="text-center mb-5">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">DIGITAL PASS CARD</span>
          <h2 class="text-lg font-black text-slate-900 tracking-tight">Kartu Member Parkir</h2>
        </div>

        <div class="flex justify-center mb-5">
          <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center shadow-xs">
            <img v-if="qr" :src="qr" class="w-44 h-44 object-contain" alt="QR Code Member" />
            <div v-else class="w-44 h-44 flex items-center justify-center text-slate-400 font-bold text-xs">
              QR Tidak Tersedia
            </div>
          </div>
        </div>

        <div class="space-y-2 bg-slate-50 border border-slate-100 p-4 rounded-2xl text-xs">
          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-400 font-bold">Kode Member</span>
            <span class="font-mono font-black text-indigo-600">{{ detailMember.kode_member }}</span>
          </div>
          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-400 font-bold">Nama</span>
            <span class="font-extrabold text-slate-800">{{ detailMember.nama_member }}</span>
          </div>
          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-400 font-bold">Perusahaan</span>
            <span class="font-semibold text-slate-700">{{ detailMember.nama_perusahaan }}</span>
          </div>
          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-400 font-bold">Tagihan</span>
            <span class="font-black text-slate-900 font-mono">Rp {{ formatRupiah(detailMember.total_harga || 150000) }}</span>
          </div>
          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-400 font-bold">Status</span>
            <span
              :class="cekStatusAktif(detailMember) ? 'text-emerald-600' : 'text-rose-600'"
              class="font-black uppercase"
            >
              {{ cekStatusAktif(detailMember) ? 'LUNAS (AKTIF)' : 'BELUM LUNAS (TAGIHAN BARU)' }}
            </span>
          </div>
          <div class="flex justify-between items-center py-1">
            <span class="text-slate-400 font-bold">Berlaku Sampai</span>
            <span class="font-medium text-slate-700 font-mono">{{ formatTanggal(detailMember.tanggal_expired) }}</span>
          </div>
        </div>

        <div class="space-y-2 mt-5">
          <button
            @click="downloadMember"
            class="w-full bg-[#0284C7] hover:bg-[#0369A1] text-white py-3 rounded-2xl font-black text-xs transition cursor-pointer shadow-xs"
          >
            Download Kartu Member (PNG)
          </button>
          <button
            @click="tutupModal"
            class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-2xl font-bold text-xs transition cursor-pointer"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { EyeIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/solid'

definePageMeta({
  middleware: 'auth'
})

const { $api } = useNuxtApp()
const router = useRouter()

const members = ref<any[]>([])
const showModal = ref(false)
const detailMember = ref<any>({})
const qr = ref('')
const totalSedangParkir = ref(0)
const search = ref('')
const filterStatus = ref<'all' | 'active' | 'expired'>('all')

// FUNGSI INTI: Validasi status aktif secara realtime dari tanggal expired
const cekStatusAktif = (item: any): boolean => {
  if (!item || !item.tanggal_expired) return false
  const expDate = new Date(item.tanggal_expired)
  expDate.setHours(23, 59, 59, 999) // Masa aktif sampai detik terakhir hari tersebut
  return new Date() <= expDate
}

const filteredMembers = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  return members.value.filter((item) => {
    const matchesKeyword = !keyword ||
      [item.kode_member, item.nama_member, item.nama_perusahaan]
        .join(' ')
        .toLowerCase()
        .includes(keyword)

    const statusMatch = filterStatus.value === 'all'
      ? true
      : filterStatus.value === 'active'
        ? cekStatusAktif(item)
        : !cekStatusAktif(item)

    return matchesKeyword && statusMatch
  })
})

const countLunas = computed(() => {
  return members.value.filter((m) => cekStatusAktif(m)).length
})

const countExpired = computed(() => {
  return members.value.filter((m) => !cekStatusAktif(m)).length
})

const load = async () => {
  try {
    const res = await $api.get('/member')
    members.value = res.data.data || []

    const resAktif = await $api.get('/parkir/aktif')
    if (resAktif.data?.data) {
      totalSedangParkir.value = resAktif.data.data.length
    }
  } catch (error: any) {
    console.error('Gagal mengambil member:', error)
  }
}

const lihatDetail = async (id: number) => {
  try {
    const res = await $api.get(`/member/${id}`)
    if (!res.data.status) {
      alert(res.data.message || 'Data member tidak ditemukan')
      return
    }
    detailMember.value = res.data.data
    qr.value = res.data.data.qr
    showModal.value = true
  } catch (error: any) {
    console.error('ERROR DETAIL MEMBER:', error)
  }
}

const tutupModal = () => {
  showModal.value = false
  detailMember.value = {}
  qr.value = ''
}

const edit = (id: number) => {
  router.push(`/petugas/member/edit/${id}`)
}

const hapus = async (id: number) => {
  if (!confirm('Yakin ingin menghapus member ini?')) return

  try {
    await $api.delete(`/member/${id}`)
    alert('Member berhasil dihapus')
    await load()
  } catch (error: any) {
    console.error('Gagal hapus:', error)
    alert(error?.response?.data?.message || 'Gagal menghapus member')
  }
}

const downloadMember = () => {
  if (!detailMember.value?.kode_member || !qr.value) {
    alert('Data atau QR Code belum tersedia')
    return
  }

  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  canvas.width = 500
  canvas.height = 700

  ctx.fillStyle = '#FFFFFF'
  ctx.fillRect(0, 0, 500, 700)

  ctx.fillStyle = '#0B0F19'
  ctx.fillRect(0, 0, 500, 110)

  ctx.fillStyle = '#FFFFFF'
  ctx.font = 'bold 24px Arial'
  ctx.textAlign = 'center'
  ctx.fillText('KARTU MEMBER PARKIR', 250, 50)

  ctx.fillStyle = '#22D3EE'
  ctx.font = 'bold 13px Arial'
  ctx.fillText('PLAZA ANDALAS PARKING SYSTEM', 250, 80)

  ctx.textAlign = 'left'
  ctx.font = '16px Arial'
  ctx.fillStyle = '#64748B'
  ctx.fillText('Kode Member', 60, 170)
  ctx.fillText('Nama', 60, 210)
  ctx.fillText('Perusahaan', 60, 250)
  ctx.fillText('Status', 60, 290)

  ctx.fillStyle = '#0F172A'
  ctx.font = 'bold 16px Arial'
  ctx.fillText(`:  ${detailMember.value.kode_member}`, 180, 170)
  ctx.fillText(`:  ${detailMember.value.nama_member}`, 180, 210)
  ctx.fillText(`:  ${detailMember.value.nama_perusahaan}`, 180, 250)
  ctx.fillText(`:  ${cekStatusAktif(detailMember.value) ? 'LUNAS' : 'BELUM LUNAS'}`, 180, 290)

  ctx.fillStyle = '#F8FAFC'
  ctx.fillRect(140, 350, 220, 220)

  const img = new Image()
  img.onload = () => {
    ctx.drawImage(img, 150, 360, 200, 200)
    const link = document.createElement('a')
    link.download = `member-${detailMember.value.kode_member}.png`
    link.href = canvas.toDataURL('image/png')
    link.click()
  }
  img.src = qr.value
}

const logout = async () => {
  try {
    await $api.post('/logout')
  } catch {}
  localStorage.removeItem('token')
  router.push('/')
}

const formatRupiah = (angka: any) => {
  return new Intl.NumberFormat('id-ID').format(Number(angka || 0))
}

const formatTanggal = (tanggal: any) => {
  if (!tanggal) return '-'
  return new Date(tanggal).toLocaleDateString('id-ID')
}

onMounted(load)
</script>