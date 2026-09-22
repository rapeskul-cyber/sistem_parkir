<script setup lang="ts">
import { ref, reactive, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

definePageMeta({
  middleware: [] // Halaman publik bebas akses
})

const { $api } = useNuxtApp() as any
const router = useRouter()

// 1 = Input Email, 2 = Input No Telp, 3 = Input OTP & Password Baru
const step = ref<number>(1)
const loading = ref<boolean>(false)
const resendLoading = ref<boolean>(false)

// Countdown timer expired 5 menit (300 detik)
const timeLeft = ref<number>(300)
let timerInterval: any = null

const startTimer = () => {
  if (timerInterval) clearInterval(timerInterval)
  timeLeft.value = 300 // 5 menit
  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--
    } else {
      clearInterval(timerInterval)
      timerInterval = null
    }
  }, 1000)
}

const formattedTime = computed(() => {
  const m = Math.floor(timeLeft.value / 60)
  const s = timeLeft.value % 60
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
})

const isExpired = computed(() => timeLeft.value <= 0)

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})

const form = reactive({
  email: '',
  no_telepon: '',
  otp: '',
  new_password: '',
  confirm_password: '',
  user_id: null
})

// Tahap 1: Verifikasi Email
const submitStep1 = async () => {
  if (!form.email) {
    alert('Silakan masukkan email akun Anda!')
    return
  }

  loading.value = true
  try {
    const res = await $api.post('/forgot-password/check-email', { email: form.email.trim() })
    if (res.data?.status) {
      step.value = 2
    }
  } catch (err: any) {
    alert(err?.response?.data?.message || 'Email tidak ditemukan dalam sistem!')
  } finally {
    loading.value = false
  }
}

// Tahap 2: Verifikasi No Telepon & Kirim OTP WA via Fonnte
const submitStep2 = async () => {
  const cleanPhone = form.no_telepon.replace(/\D/g, '')
  if (cleanPhone.length < 10) {
    alert('Nomor telepon minimal 10 digit!')
    return
  }

  loading.value = true
  try {
    const res = await $api.post('/forgot-password/send-otp', {
      email: form.email.trim(),
      no_telepon: cleanPhone
    })

        if (res.data?.status) {
      form.user_id = res.data.user_id
      alert(res.data.message || 'Kode OTP telah dikirim ke WhatsApp Anda!')
      step.value = 3
      startTimer() // Mulai countdown 5 menit
    }
  } catch (err: any) {
    alert(err?.response?.data?.message || 'Nomor telepon tidak cocok dengan akun email!')
  } finally {
    loading.value = false
  }
}

// Kirim Ulang OTP (Resend Code)
const resendOtp = async () => {
  if (!form.user_id || resendLoading.value) return
  resendLoading.value = true
  try {
    const res = await $api.post('/forgot-password/resend-otp', {
      user_id: form.user_id
    })

    if (res.data?.status) {
      form.otp = '' // Kosongkan form OTP
      alert(res.data.message || 'Kode OTP baru berhasil dikirim ke WhatsApp Anda!')
      startTimer() // Reset hitung mundur 5 menit
    }
  } catch (err: any) {
    alert(err?.response?.data?.message || 'Gagal mengirim ulang kode OTP. Coba lagi.')
  } finally {
    resendLoading.value = false
  }
}

// Tahap 3: Verifikasi OTP dan Simpan Password Baru
const submitStep3 = async () => {
  if (isExpired.value) {
    alert('Kode OTP sudah kedaluwarsa (lebih dari 5 menit)! Silakan klik tombol "Kirim Ulang Kode OTP".')
    return
  }

  if (form.otp.length !== 6) {
    alert('Kode OTP harus terdiri dari 6 digit angka!')
    return
  }

  if (form.new_password.length < 6) {
    alert('Password baru minimal 6 karakter!')
    return
  }

  if (form.new_password !== form.confirm_password) {
    alert('Konfirmasi password tidak cocok!')
    return
  }

  loading.value = true
  try {
    const res = await $api.post('/forgot-password/verify-reset', {
      user_id: form.user_id,
      otp: form.otp.trim(),
      password: form.new_password
    })

    if (res.data?.status) {
      if (timerInterval) clearInterval(timerInterval)
      alert(res.data.message || 'Password berhasil diubah!')
      router.push('/') // Redirect ke halaman login utama
    }
  } catch (err: any) {
    alert(err?.response?.data?.message || 'Kode OTP salah atau telah kedaluwarsa!')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#0B0F19] flex items-center justify-center p-4 font-sans text-slate-800">
    <div class="w-full max-w-md bg-white rounded-3xl p-8 shadow-2xl border border-slate-100 space-y-6">
      
      <!-- Brand Header -->
      <div class="text-center">
        <div class="w-12 h-12 rounded-2xl bg-cyan-400 text-[#0B0F19] flex items-center justify-center font-black text-xl mx-auto shadow-[0_0_20px_rgba(34,211,238,0.4)] mb-3">
          P
        </div>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">PLAZA ANDALAS PARKIR</span>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight mt-0.5">Pemulihan Akun Petugas</h2>
        <p class="text-xs text-slate-400 mt-1">Verifikasi identitas petugas untuk mereset kata sandi.</p>
      </div>

      <!-- Step Indicator -->
      <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] font-bold">
        <span :class="step >= 1 ? 'text-cyan-600 font-extrabold' : 'text-slate-400'">1. Email</span>
        <span class="text-slate-300">›</span>
        <span :class="step >= 2 ? 'text-cyan-600 font-extrabold' : 'text-slate-400'">2. WhatsApp</span>
        <span class="text-slate-300">›</span>
        <span :class="step === 3 ? 'text-cyan-600 font-extrabold' : 'text-slate-400'">3. OTP & Password</span>
      </div>

      <!-- TAHAP 1: INPUT GMAIL -->
      <form v-if="step === 1" @submit.prevent="submitStep1" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email / Gmail Petugas</label>
          <div class="relative">
            <span class="absolute left-3.5 top-3 text-xs text-slate-400">✉️</span>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="nama.petugas@parkir.com"
              class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl pl-10 pr-4 py-3 text-xs font-semibold text-slate-800 outline-none transition"
            />
          </div>
          <span class="text-[10px] text-slate-400 mt-1 block">Sistem akan mengecek apakah email terdaftar di database.</span>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-[#0284C7] hover:bg-[#0369A1] text-white py-3 rounded-2xl font-black text-xs transition cursor-pointer shadow-xs uppercase tracking-wider disabled:opacity-50"
        >
          {{ loading ? 'Memeriksa Email...' : 'Lanjutkan Verifikasi ›' }}
        </button>
      </form>

      <!-- TAHAP 2: INPUT NO TELEPON -->
      <form v-else-if="step === 2" @submit.prevent="submitStep2" class="space-y-4">
        <div class="p-3 bg-cyan-50/60 border border-cyan-100 rounded-xl text-[11px] text-cyan-800 font-semibold flex items-center justify-between">
          <span>Email: <strong>{{ form.email }}</strong></span>
          <button type="button" @click="step = 1" class="text-xs text-cyan-600 underline font-bold cursor-pointer">Ganti</button>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor WhatsApp Terdaftar</label>
          <div class="relative">
            <span class="absolute left-3.5 top-3 text-xs text-slate-400">📞</span>
            <input
              v-model="form.no_telepon"
              type="tel"
              required
              placeholder="081234567890"
              class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl pl-10 pr-4 py-3 text-xs font-mono font-bold text-slate-800 outline-none transition"
            />
          </div>
          <span class="text-[10px] text-slate-400 mt-1 block">Kode OTP akan dikirimkan otomatis ke nomor ini via Fonnte WA.</span>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            @click="step = 1"
            class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold text-xs transition cursor-pointer"
          >
            ← Kembali
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-2xl font-black text-xs transition cursor-pointer shadow-xs uppercase tracking-wider disabled:opacity-50"
          >
            {{ loading ? 'Mengirim OTP...' : 'Kirim Kode OTP WA 💬' }}
          </button>
        </div>
      </form>

            <!-- TAHAP 3: INPUT OTP & RESET PASSWORD -->
      <form v-else-if="step === 3" @submit.prevent="submitStep3" class="space-y-4">
        <!-- Banner Status OTP & Timer Countdown 5 Menit -->
        <div class="p-3.5 rounded-2xl border text-center transition-all" :class="isExpired ? 'bg-rose-50 border-rose-200' : 'bg-cyan-50/60 border-cyan-200'">
          <div class="flex items-center justify-between">
            <div class="text-left">
              <span class="text-[11px] font-bold block" :class="isExpired ? 'text-rose-700' : 'text-slate-700'">
                {{ isExpired ? '⚠️ Kode OTP Kedaluwarsa' : '⏱️ Masa Berlaku Kode OTP' }}
              </span>
              <p class="text-[10px] text-slate-500">
                {{ isExpired ? 'Kode telah melewati batas 5 menit dan tidak dapat digunakan.' : 'Selesaikan sebelum waktu kedaluwarsa habis.' }}
              </p>
            </div>
            <div class="text-right pl-2">
              <span class="font-mono font-black text-sm tracking-wider px-2.5 py-1 rounded-xl shadow-xs inline-block" :class="isExpired ? 'bg-rose-100 text-rose-700 border border-rose-300' : 'bg-white text-cyan-700 border border-cyan-200'">
                {{ isExpired ? '00:00' : formattedTime }}
              </span>
            </div>
          </div>

          <!-- Tombol Resend Code -->
          <div class="mt-2.5 pt-2.5 border-t flex items-center justify-between text-xs" :class="isExpired ? 'border-rose-200' : 'border-cyan-100'">
            <span class="text-[10px] text-slate-500">
              {{ isExpired ? 'Kode tidak berlaku lagi?' : 'Tidak menerima kode?' }}
            </span>
            <button
              type="button"
              @click="resendOtp"
              :disabled="resendLoading"
              class="font-bold text-[11px] inline-flex items-center gap-1 cursor-pointer transition" :class="isExpired ? 'text-rose-600 hover:text-rose-800 underline' : 'text-cyan-600 hover:text-cyan-800 underline'"
            >
              <span v-if="resendLoading" class="animate-spin text-xs">🔄</span>
              <span>{{ resendLoading ? 'Mengirim ulang...' : 'Kirim Ulang Kode (Resend)' }}</span>
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Masukkan 6 Digit Kode OTP</label>
          <input
            v-model="form.otp"
            type="text"
            maxlength="6"
            :disabled="isExpired"
            required
            placeholder="Contoh: 849201"
            class="w-full text-center tracking-[0.4em] font-mono text-xl font-black bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-slate-900 outline-none transition disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
          />
          <span v-if="isExpired" class="text-[10px] text-rose-500 font-semibold text-center block mt-1">
            Kode kedaluwarsa. Silakan klik tombol "Kirim Ulang Kode (Resend)" di atas.
          </span>
          <span v-else class="text-[10px] text-slate-400 text-center block mt-1">
            Periksa pesan WhatsApp yang baru masuk di HP Anda.
          </span>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Password Baru</label>
          <input
            v-model="form.new_password"
            type="password"
            required
            placeholder="Minimal 6 karakter..."
            class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-xs font-semibold text-slate-800 outline-none transition"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
          <input
            v-model="form.confirm_password"
            type="password"
            required
            placeholder="Ulangi password baru..."
            class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-xs font-semibold text-slate-800 outline-none transition"
          />
        </div>

        <button
          type="submit"
          :disabled="loading || isExpired"
          class="w-full bg-[#0B0F19] hover:bg-slate-800 text-white py-3 rounded-2xl font-black text-xs transition cursor-pointer shadow-xs uppercase tracking-wider disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Memverifikasi...' : isExpired ? 'OTP Kedaluwarsa (Kirim Ulang Dulu)' : 'Simpan Password Baru' }}
        </button>
      </form>

      <!-- Back to Login -->
      <div class="text-center pt-2 border-t border-slate-100">
        <NuxtLink to="/" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition">
          ← Kembali ke Halaman Login
        </NuxtLink>
      </div>
    </div>
  </div>
</template>