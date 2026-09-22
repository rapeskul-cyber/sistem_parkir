<script setup lang="ts">
import { ref } from 'vue'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/solid'
import { useRouter } from 'vue-router'

const router = useRouter()
const { $api } = useNuxtApp() as any

const form = ref({
  email: '',
  password: '',
})

const isLoading = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)

const normalizeRole = (role: string) => {
  const normalized = (role || '').toLowerCase().trim().replace(/[\s-]+/g, '_')
  return normalized === 'superadmin' ? 'super_admin' : normalized
}

const handleLogin = async () => {
  errorMessage.value = ''
  isLoading.value = true

  try {
    const response = await $api.post('/login', {
      email: form.value.email,
      password: form.value.password,
    })

    if (response.data && response.data.token) {
      localStorage.setItem('token', response.data.token)

      const userRole = normalizeRole(response.data.user?.role || 'petugas')
      localStorage.setItem('role', userRole)

      if (userRole === 'admin' || userRole === 'super_admin') {
        router.push('/admin/dashboard')
      } else {
        router.push('/petugas/')
      }
    }
  } catch (error: any) {
    localStorage.removeItem('token')
    localStorage.removeItem('role')
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat login.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#F4F4F0] flex items-center justify-center p-6 font-sans">
    <div class="bg-white p-8 sm:p-10 rounded-[35px] border-2 border-slate-900 shadow-[8px_8px_0px_0px_#0f172a] w-full max-w-md transition-all">
      
      <!-- Header Branding ala Referensi -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-slate-900 text-[#FACC15] font-black text-2xl border-2 border-slate-900 mb-4 shadow-[4px_4px_0px_0px_#FACC15]">
          P
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">PLAZA ANDALAS</h1>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Sistem Manajemen Parkir</p>
      </div>

      <!-- Pesan Error -->
      <div
        v-if="errorMessage"
        class="bg-amber-50 border-2 border-amber-400 text-amber-900 px-4 py-3 rounded-2xl text-xs font-bold mb-6 text-center animate-shake"
      >
        {{ errorMessage }}
      </div>

      <!-- Form Login -->
      <form @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2 ml-1">
            Email Petugas / Admin
          </label>
          <input
            v-model="form.email"
            type="email"
            required
            placeholder="nama@parkir.com"
            class="w-full bg-slate-50 border-2 border-slate-900 rounded-2xl px-5 py-3.5 text-sm font-semibold text-slate-900 outline-none focus:bg-white focus:ring-2 focus:ring-[#FACC15] transition"
          />
        </div>

        <div>
          <div class="flex items-center justify-between mb-2 ml-1">
            <label class="block text-xs font-black text-slate-700 uppercase tracking-wider">
              Password
            </label>
            <NuxtLink
              to="/forgot-password"
              class="text-xs font-black text-slate-500 hover:text-slate-900 hover:underline transition"
            >
              Lupa Password?
            </NuxtLink>
          </div>
          <div class="relative">
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              placeholder="••••••••"
              class="w-full bg-slate-50 border-2 border-slate-900 rounded-2xl pl-5 pr-12 py-3.5 text-sm font-semibold text-slate-900 outline-none focus:bg-white focus:ring-2 focus:ring-[#FACC15] transition"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-900 focus:outline-none cursor-pointer"
            >
              <EyeIcon v-if="showPassword" class="w-5 h-5" />
              <EyeSlashIcon v-else class="w-5 h-5" />
            </button>
          </div>
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full bg-[#FACC15] hover:bg-[#eab308] text-slate-950 font-black py-4 rounded-2xl border-2 border-slate-900 shadow-[4px_4px_0px_0px_#0f172a] hover:shadow-[2px_2px_0px_0px_#0f172a] hover:translate-x-[2px] hover:translate-y-[2px] transition-all disabled:opacity-50 cursor-pointer mt-4 uppercase tracking-wider text-xs"
        >
          {{ isLoading ? "MEMPROSES..." : "MASUK KE SISTEM →" }}
        </button>
      </form>

      <!-- Footer Info -->
      <div class="mt-8 text-center">
        <p class="text-[11px] font-bold text-slate-400">
          Secure Parking Control Panel v2.0
        </p>
      </div>

    </div>
  </div>
</template>