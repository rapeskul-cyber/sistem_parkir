<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'

definePageMeta({
  middleware: 'auth'
})

interface MemberPayload {
  nama_member: string
  nama_perusahaan: string
  total_harga?: number
  jumlah_bayar?: number
  status?: string
}

const TARIF_MAKSIMAL = 150000

const { $api } = useNuxtApp() as any
const router = useRouter()
const route = useRoute()

const loading = ref<boolean>(false)
const originalStatus = ref<string>('')

const form = reactive({
  id: null as number | null,
  kode_member: '',
  nama_member: '',
  nama_perusahaan: '',
  total_harga: TARIF_MAKSIMAL,
  jumlah_bayar: 0
})

const isLunas = computed(() => originalStatus.value === 'lunas')

// Status kalkulasi otomatis realtime
const statusOtomatis = computed(() => {
  if (isLunas.value) return 'lunas'
  return form.jumlah_bayar >= TARIF_MAKSIMAL ? 'lunas' : 'belum lunas'
})

// Watcher agar angka tidak bisa melampaui 150.000 dalam kondisi apapun
watch(
  () => form.jumlah_bayar,
  (newVal) => {
    if (newVal > TARIF_MAKSIMAL) {
      form.jumlah_bayar = TARIF_MAKSIMAL
    } else if (newVal < 0 || isNaN(newVal)) {
      form.jumlah_bayar = 0
    }
  }
)

const formatRupiah = (val: number | string): string => {
  return new Intl.NumberFormat('id-ID').format(Number(val || 0))
}

const fetchDetailMember = async (): Promise<void> => {
  try {
    const res = await $api.get(`/member/${route.params.id}`)
    if (!res.data?.status || !res.data?.data) {
      alert(res.data?.message || 'Data member tidak ditemukan.')
      router.push('/petugas/member/select')
      return
    }

    const data = res.data.data
    form.id = data.id
    form.kode_member = data.kode_member
    form.nama_member = data.nama_member
    form.nama_perusahaan = data.nama_perusahaan
    form.total_harga = TARIF_MAKSIMAL
    form.jumlah_bayar = Math.min(TARIF_MAKSIMAL, Math.max(0, Number(data.jumlah_bayar || 0)))
    originalStatus.value = data.status
  } catch (error: any) {
    console.error('Gagal mengambil data member:', error)
    alert(error?.response?.data?.message || 'Terjadi kesalahan sistem saat memuat data.')
  }
}

const updateDataMember = async (): Promise<void> => {
  const namaTrimmed = form.nama_member.trim()
  const perusahaanTrimmed = form.nama_perusahaan.trim()

  if (!namaTrimmed || !perusahaanTrimmed) {
    alert('Nama member dan instansi/perusahaan wajib diisi!')
    return
  }

  // Kunci keras nilai maksimal 150.000 sebelum kirim
  const nominalFinal = Math.min(TARIF_MAKSIMAL, Math.max(0, Number(form.jumlah_bayar || 0)))

  const payload: MemberPayload = {
    nama_member: namaTrimmed,
    nama_perusahaan: perusahaanTrimmed
  }

  if (!isLunas.value) {
    payload.total_harga = TARIF_MAKSIMAL
    payload.jumlah_bayar = nominalFinal
    payload.status = nominalFinal >= TARIF_MAKSIMAL ? 'lunas' : 'belum lunas'
  }

  loading.value = true
  try {
    const res = await $api.put(`/member/${route.params.id}`, payload)
    if (res.data?.status) {
      alert('Data member berhasil diperbarui.')
      router.push('/petugas/member/select')
    } else {
      alert(res.data?.message || 'Gagal memperbarui member.')
    }
  } catch (error: any) {
    console.error('Gagal memperbarui data member:', error)
    alert(error?.response?.data?.message || 'Terjadi kesalahan pada server saat memperbarui data.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDetailMember()
})
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center p-5 font-sans antialiased text-slate-800">
    <div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-[480px] border border-slate-100">
      
      <!-- Header Form -->
      <div class="text-center mb-6">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Edit Data Member</h1>
        <p class="text-xs text-slate-400 mt-1">Perbarui profil dan administrasi langganan parkir</p>
      </div>

      <!-- Ringkasan Status -->
      <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 mb-6 flex items-center justify-between">
        <div>
          <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Kode Member</span>
          <span class="font-mono font-black text-indigo-600 text-sm">{{ form.kode_member }}</span>
        </div>

        <div class="text-right">
          <span
            class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider inline-block"
            :class="isLunas ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
          >
            {{ isLunas ? 'Status: Lunas' : 'Status: Belum Lunas' }}
          </span>
        </div>
      </div>

      <!-- Form Edit -->
      <form @submit.prevent="updateDataMember" class="space-y-4">
        
        <!-- Field Nama Member -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Member</label>
          <input
            v-model="form.nama_member"
            type="text"
            required
            placeholder="Contoh: Budi Santoso"
            class="w-full p-3 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 bg-white transition"
          />
        </div>

        <!-- Field Perusahaan -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Instansi / Perusahaan (PT)</label>
          <input
            v-model="form.nama_perusahaan"
            type="text"
            required
            placeholder="Contoh: PT. Maju Bersama / Umum"
            class="w-full p-3 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 bg-white transition"
          />
        </div>

        <!-- Tarif Standar -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Tarif Langganan Bulanan</label>
          <div class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold font-mono text-slate-600">
            Rp {{ formatRupiah(TARIF_MAKSIMAL) }}
          </div>
        </div>

        <!-- Input Nominal Pembayaran -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-bold text-slate-700">Nominal Pembayaran Diterima (Rp)</label>
            <span v-if="isLunas" class="text-[10px] font-bold text-slate-400 italic">🔒 Terkunci (Lunas)</span>
            <span v-else class="text-[10px] font-bold text-slate-400">Maks. Rp 150.000</span>
          </div>

          <div class="relative">
            <input
              v-model.number="form.jumlah_bayar"
              type="number"
              min="0"
              :max="TARIF_MAKSIMAL"
              :disabled="isLunas"
              required
              class="w-full p-3 rounded-xl border text-xs font-bold font-mono transition"
              :class="isLunas 
                ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200' 
                : 'bg-white text-slate-900 border-slate-300 focus:outline-none focus:ring-2 focus:ring-cyan-500'"
            />
          </div>
        </div>

        <!-- Status Langganan Otomatis -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Status Langganan (Otomatis)</label>
          <div
            class="w-full p-3 rounded-xl border text-xs font-black uppercase flex items-center justify-between"
            :class="statusOtomatis === 'lunas' 
              ? 'bg-emerald-50 border-emerald-200 text-emerald-700' 
              : 'bg-rose-50 border-rose-200 text-rose-700'"
          >
            <span>{{ statusOtomatis === 'lunas' ? 'LUNAS (AKSES AKTIF)' : 'BELUM LUNAS (AKSES DIBATASI)' }}</span>
            <span class="text-sm">{{ statusOtomatis === 'lunas' ? '✓' : '✕' }}</span>
          </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="pt-3 space-y-2">
          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-[#0284C7] hover:bg-[#0369A1] disabled:bg-slate-300 text-white py-3 rounded-xl font-black text-xs transition cursor-pointer shadow-xs uppercase tracking-wider"
          >
            {{ loading ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>

          <button
            type="button"
            @click="router.push('/petugas/member/select')"
            class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer"
          >
            Kembali
          </button>
        </div>
      </form>
    </div>
  </div>
</template>