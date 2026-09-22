<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

definePageMeta({
  middleware: ['auth', 'cek-admin']
})

const { $api } = useNuxtApp() as any
const router = useRouter()
const route = useRoute()
const petugasId = route.params.id

const loading = ref(false)
const fetching = ref(true)

const form = reactive({
  name: '',
  email: '',
  no_telepon: '',
  password: ''
})

const fetchDetailPetugas = async () => {
  fetching.value = true
  try {
    const res = await $api.get(`/admin/petugas`)
    const list = res.data?.data ?? res.data
    const current = list.find((p: any) => String(p.id) === String(petugasId))

    if (!current) {
      alert('Data petugas tidak ditemukan!')
      router.push('/admin/petugas')
      return
    }

    form.name = current.name || ''
    form.email = current.email || ''
    form.no_telepon = current.no_telepon || current.no_hp || ''
  } catch (err: any) {
    console.error('Gagal mengambil detail petugas:', err)
    alert('Gagal mengambil data petugas.')
    router.push('/admin/petugas')
  } finally {
    fetching.value = false
  }
}

const submitEdit = async () => {
  const nameTrimmed = form.name.trim()
  const emailTrimmed = form.email.trim()
  const telpTrimmed = form.no_telepon.trim()

  if (!nameTrimmed || !emailTrimmed || !telpTrimmed) {
    alert('Nama, email, dan kontak telepon wajib diisi!')
    return
  }

  const cleanPhone = telpTrimmed.replace(/\D/g, '')
  if (cleanPhone.length < 10 || cleanPhone.length > 15) {
    alert('Nomor telepon harus berupa angka antara 10 - 15 digit!')
    return
  }

  if (form.password && form.password.trim().length < 6) {
    alert('Password baru minimal 6 karakter!')
    return
  }

  loading.value = true
  try {
    const payload = {
      name: nameTrimmed,
      email: emailTrimmed,
      no_telepon: cleanPhone,
      no_hp: cleanPhone
    }

    const res = await $api.put(`/admin/petugas/${petugasId}`, payload)

    if (form.password && form.password.trim()) {
      const pwRes = await $api.put(`/admin/petugas/${petugasId}/reset-password`, {
        password: form.password.trim()
      })
      alert(pwRes.data?.message || 'Password petugas berhasil diubah!')
    }

    alert(res.data?.message || 'Data petugas berhasil diperbarui!')
    router.push('/admin/petugas')
  } catch (err: any) {
    console.error('Gagal update petugas:', err)
    const errors = err?.response?.data?.errors
    if (errors?.no_telepon || errors?.no_hp) {
      alert('Nomor telepon sudah terdaftar pada petugas lain!')
    } else if (errors?.email) {
      alert('Email sudah digunakan oleh akun lain!')
    } else {
      alert(err?.response?.data?.message || 'Gagal memperbarui data petugas.')
    }
  } finally {
    loading.value = false
  }
}

const logout = async () => {
  try {
    await $api.post('/logout')
  } catch {}
  localStorage.removeItem('token')
  router.push('/')
}

onMounted(() => {
  fetchDetailPetugas()
})
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] flex font-sans antialiased text-slate-800">
    <!-- SIDEBAR PERSIS REFERENSI ADMIN -->
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
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 font-semibold text-xs transition"
          >
            <span class="text-sm">⊞</span>
            <span>Dashboard</span>
          </NuxtLink>

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

      <div class="space-y-3 pt-4 border-t border-slate-800/80">
        <div class="bg-slate-900/90 border border-slate-800 px-3.5 py-2.5 rounded-2xl flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-center text-xs font-bold">
            ADM
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-white truncate">Administrator</p>
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

    <!-- CONTENT -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <header class="bg-white px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">USER MODIFICATION</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Edit Data Petugas</h1>
        </div>

        <button
          @click="router.push('/admin/petugas')"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs"
        >
          <span>←</span> Kembali ke Daftar
        </button>
      </header>

      <div class="p-8 flex justify-center items-start">
        <div class="w-full max-w-lg bg-white rounded-3xl border border-slate-100 shadow-xs p-7 space-y-5">
          <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">FORM EDIT</span>
            <h2 class="text-lg font-black text-slate-900 tracking-tight mt-0.5">Perbarui Kontak Petugas</h2>
            <p class="text-xs text-slate-400 mt-1">Ubah nama lengkap, nomor WhatsApp, atau akun email petugas.</p>
          </div>

          <div v-if="fetching" class="py-12 text-center text-xs font-bold text-slate-400">
            Memuat data petugas...
          </div>

          <form v-else @submit.prevent="submitEdit" class="space-y-4">
            <!-- Nama Petugas -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Petugas</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Contoh: Rian Pratama"
                class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-xs font-semibold text-slate-800 outline-none transition"
              />
            </div>

            <!-- Email / Gmail -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Login (Gmail)</label>
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="Contoh: petugas@gmail.com"
                class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-xs font-semibold text-slate-800 outline-none transition"
              />
            </div>

            <!-- Kontak Telepon / WhatsApp -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Kontak WhatsApp / No. Telepon</label>
              <div class="relative">
                <span class="absolute left-4 top-3 text-xs font-bold text-slate-400">📞</span>
                <input
                  v-model="form.no_telepon"
                  type="tel"
                  required
                  placeholder="Contoh: 081234567890"
                  class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl pl-10 pr-4 py-3 text-xs font-mono font-bold text-slate-800 outline-none transition"
                />
              </div>
              <span class="text-[10px] text-slate-400 mt-1 block">Nomor harus unik dan belum digunakan petugas lain.</span>
            </div>

            <!-- Password Baru -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Password Baru (Opsional)</label>
              <input
                v-model="form.password"
                type="password"
                placeholder="Kosongkan jika tidak ingin mengubah password"
                class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-xs font-semibold text-slate-800 outline-none transition"
              />
              <span class="text-[10px] text-slate-400 mt-1 block">Jika diisi, minimal 6 karakter dan langsung aktif setelah simpan.</span>
            </div>

            <!-- Action Buttons -->
            <div class="pt-3 flex gap-3">
              <button
                type="submit"
                :disabled="loading"
                class="flex-1 bg-[#0284C7] hover:bg-[#0369A1] text-white py-3 rounded-2xl font-black text-xs transition cursor-pointer shadow-xs uppercase tracking-wider disabled:opacity-50"
              >
                {{ loading ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
              <button
                type="button"
                @click="router.push('/admin/petugas')"
                class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-5 py-3 rounded-2xl font-bold text-xs transition cursor-pointer"
              >
                Batal
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>