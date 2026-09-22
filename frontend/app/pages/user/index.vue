<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { jsPDF } from 'jspdf'

definePageMeta({
  layout: false
})

const { $api } = useNuxtApp() as any

const loading = ref(false)
const cardInput = ref('')
const inputRef = ref<HTMLInputElement | null>(null)

// Status Hardware Scanner/Webcam
// true = Hijau (Terhubung & Siap), false = Merah (Tidak Terdeteksi)
const isScannerReady = ref(false)
const scannerLabel = ref('Mendeteksi Scanner...')

// Status Gerbang / Palang
const gateStatus = ref<'standby' | 'processing' | 'success' | 'error'>('standby')
const statusMessage = ref('Standby: Silakan ambil tiket atau scan kartu')

// Video Element untuk Auto-Scan QR
const videoRef = ref<HTMLVideoElement | null>(null)
let videoStream: MediaStream | null = null
let scanInterval: any = null

const modal = reactive({ show: false, title: '', message: '' })

const triggerModal = (title: string, message: string) => {
  modal.title = title
  modal.message = message
  modal.show = true
}

const setGateState = (status: 'standby' | 'processing' | 'success' | 'error', message: string, duration = 3000) => {
  gateStatus.value = status
  statusMessage.value = message

  if (status === 'success' || status === 'error') {
    setTimeout(() => {
      gateStatus.value = 'standby'
      statusMessage.value = 'Standby: Silakan ambil tiket atau scan kartu'
      if (inputRef.value) inputRef.value.focus()
    }, duration)
  }
}

// =========================================================================
// 1. CEK HARDWARE WEBCAM OTOMATIS & RUN BACKGROUND SCANNER
// =========================================================================
const checkHardwareAndStartScanner = async () => {
  if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
    isScannerReady.value = false
    scannerLabel.value = 'Scanner Offline (Browser Tidak Mendukung)'
    return
  }

  try {
    const devices = await navigator.mediaDevices.enumerateDevices()
    const hasVideoInput = devices.some(device => device.kind === 'videoinput')

    if (!hasVideoInput) {
      isScannerReady.value = false
      scannerLabel.value = 'Scanner Offline: Webcam Tidak Terdeteksi'
      return
    }

    // Ada webcam tercolok -> Langsung aktifkan stream di background
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 480 } }
    })

    videoStream = stream
    isScannerReady.value = true
    scannerLabel.value = 'Scanner Online: Siap Membaca QR / Kartu'

    if (videoRef.value) {
      videoRef.value.srcObject = stream
      await videoRef.value.play()
    }

    // Deteksi QR otomatis jika didukung browser
    if ('BarcodeDetector' in window) {
      const detector = new (window as any).BarcodeDetector({ formats: ['qr_code', 'code_128', 'ean_13'] })
      scanInterval = setInterval(async () => {
        if (!videoRef.value || loading.value || gateStatus.value === 'processing') return
        try {
          const codes = await detector.detect(videoRef.value)
          if (codes && codes.length > 0) {
            const rawVal = codes[0].rawValue
            if (rawVal) {
              processMemberGate(rawVal)
            }
          }
        } catch {}
      }, 400)
    }
  } catch (err) {
    console.warn('Webcam terpasang tapi izin ditolak atau sedang sibuk:', err)
    isScannerReady.value = false
    scannerLabel.value = 'Scanner Offline'
  }
}

// =========================================================================
// 2. HARDWARE USB BARCODE / RFID LISTENER (BACKGROUND INSTANT SCAN)
// =========================================================================
let scanBuffer = ''
let lastKeyTime = Date.now()

const handleGlobalKeyDown = (e: KeyboardEvent) => {
  if (['Shift', 'Control', 'Alt', 'Meta'].includes(e.key)) return

  const currentTime = Date.now()
  if (currentTime - lastKeyTime > 80) {
    scanBuffer = ''
  }
  lastKeyTime = currentTime

  if (e.key === 'Enter') {
    if (scanBuffer.length >= 3 && !loading.value) {
      processMemberGate(scanBuffer)
      scanBuffer = ''
      e.preventDefault()
    }
  } else {
    scanBuffer += e.key
  }
}

// =========================================================================
// 3. CETAK TIKET PARKIR MANDIRI
// =========================================================================
const printTicket = async (kategori: 'motor' | 'mobil' = 'motor') => {
  loading.value = true
  setGateState('processing', `Mencetak Tiket ${kategori.toUpperCase()}...`)

  try {
    const res = await $api.post('/tiket', { kategori })
    if (res.data?.data) {
      const tiket = res.data.data

      const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: [80, 110]
      })

      doc.setFont('Helvetica', 'bold')
      doc.setFontSize(13)
      doc.text('PLAZA ANDALAS', 40, 12, { align: 'center' })

      doc.setFontSize(8)
      doc.setFont('Helvetica', 'normal')
      doc.text(`TIKET MASUK (${kategori.toUpperCase()})`, 40, 18, { align: 'center' })

      doc.setLineDash([1, 1], 0)
      doc.line(10, 22, 70, 22)

      if (tiket.qr_code) {
        try {
          const canvas = document.createElement('canvas')
          canvas.width = 200
          canvas.height = 200
          const ctx = canvas.getContext('2d')
          const img = new Image()
          img.src = tiket.qr_code

          await new Promise((resolve, reject) => {
            img.onload = () => {
              ctx?.drawImage(img, 0, 0, 200, 200)
              const pngData = canvas.toDataURL('image/png')
              doc.addImage(pngData, 'PNG', 25, 25, 30, 30)
              resolve(true)
            }
            img.onerror = reject
          })
        } catch (e) {
          console.warn('QR gagal disisipkan ke PDF', e)
        }
      }

      doc.setFont('Helvetica', 'bold')
      doc.setFontSize(14)
      doc.text(tiket.kode_tiket.toUpperCase(), 40, 62, { align: 'center' })
      doc.line(10, 67, 70, 67)

      doc.setFontSize(8)
      doc.setFont('Helvetica', 'normal')
      const waktuMasuk = tiket.waktu_masuk
        ? new Date(tiket.waktu_masuk).toLocaleString('id-ID')
        : new Date().toLocaleString('id-ID')
      doc.text(`Masuk: ${waktuMasuk}`, 40, 75, { align: 'center' })
      doc.line(10, 81, 70, 81)

      doc.setFontSize(8)
      doc.setFont('Helvetica', 'bold')
      doc.text('SIMPAN TIKET INI UNTUK KELUAR', 40, 92, { align: 'center' })

      doc.save(`Tiket-Parkir-${tiket.kode_tiket}.pdf`)

      setGateState('success', 'TIKET BERHASIL DICETAK! PALANG TERBUKA', 4000)
      triggerModal(
        'Tiket Diterbitkan!',
        `Kode: ${tiket.kode_tiket}\nKategori: ${kategori.toUpperCase()}\nFile struk terunduh. Palang terbuka!`
      )
    }
  } catch (err: any) {
    setGateState('error', 'GAGAL MENERBITKAN TIKET', 3000)
    triggerModal('Gagal', err?.response?.data?.message || 'Gagal menerbitkan tiket!')
  } finally {
    loading.value = false
  }
}

// =========================================================================
// 4. VERIFIKASI MEMBER
// =========================================================================
const processMemberGate = async (rawCode: string) => {
  const kode = rawCode.trim()
  if (!kode || loading.value) return

  loading.value = true
  setGateState('processing', 'Memvalidasi Kartu Member...')

  try {
    const res = await $api.post('/gate/masuk-member', { kode })
    if (res.data?.status) {
      setGateState('success', 'MEMBER VALID! PALANG TERBUKA', 4000)
      triggerModal('Akses Member Valid', `${res.data.message}\nPalang pintu terbuka. Silakan masuk.`)
    } else {
      setGateState('error', 'AKSES DITOLAK: MEMBER TIDAK AKTIF', 3000)
      triggerModal('Akses Ditolak', res.data?.message || 'Member tidak terdaftar.')
    }
  } catch (err: any) {
    setGateState('error', 'AKSES DITOLAK: TIDAK DITEMUKAN', 3000)
    triggerModal('Akses Ditolak', err?.response?.data?.message || 'Member tidak terdaftar atau belum lunas!')
  } finally {
    cardInput.value = ''
    loading.value = false
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleGlobalKeyDown)
  checkHardwareAndStartScanner()

  // Listener jika ada webcam yang baru dicolok atau dicabut
  if (navigator.mediaDevices) {
    navigator.mediaDevices.addEventListener('devicechange', checkHardwareAndStartScanner)
  }

  if (inputRef.value) inputRef.value.focus()
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeyDown)
  if (navigator.mediaDevices) {
    navigator.mediaDevices.removeEventListener('devicechange', checkHardwareAndStartScanner)
  }
  if (scanInterval) clearInterval(scanInterval)
  if (videoStream) {
    videoStream.getTracks().forEach(t => t.stop())
  }
})
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] flex flex-col font-sans antialiased text-slate-800">
    <!-- VIDEO STREAM TERSEMBUNYI UNTUK SCAN OTOMATIS -->
    <video ref="videoRef" class="hidden" playsinline muted></video>

    <!-- TOP BAR KIOSK -->
    <header class="bg-white px-8 py-4 flex items-center justify-between border-b border-slate-100 shrink-0">
      <div>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">PLAZA ANDALAS SYSTEM</span>
        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Kiosk Gate Masuk</h1>
      </div>

      <!-- LAMPU INDIKATOR WEBCAM / SCANNER -->
      <div class="flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-900 border border-slate-800 shadow-xs">
        <div class="flex items-center gap-2">
          <!-- LAMPU HIJAU / MERAH -->
          <span
            :class="isScannerReady 
              ? 'bg-emerald-400 shadow-[0_0_12px_#34d399] animate-pulse' 
              : 'bg-rose-500 shadow-[0_0_12px_#f43f5e]'"
            class="w-3 h-3 rounded-full transition-all duration-300"
          ></span>

          <span
            :class="isScannerReady ? 'text-emerald-400' : 'text-rose-400'"
            class="text-[11px] font-mono font-bold tracking-tight"
          >
            {{ isScannerReady ? 'SCANNER READY' : 'SCANNER OFFLINE' }}
          </span>
        </div>
      </div>
    </header>

    <!-- CONTENT KIOSK -->
    <main class="flex-1 flex flex-col justify-center items-center p-6">
      <!-- NOTIFIKASI STATUS SCANNER -->
      <div
        :class="isScannerReady 
          ? 'bg-emerald-50 border-emerald-200 text-emerald-800' 
          : 'bg-rose-50 border-rose-200 text-rose-800'"
        class="w-full max-w-md px-4 py-2.5 mb-4 rounded-2xl border flex items-center justify-between text-xs font-bold transition-all"
      >
        <div class="flex items-center gap-2">
          <span :class="isScannerReady ? 'bg-emerald-500' : 'bg-rose-500'" class="w-2 h-2 rounded-full"></span>
          <span>{{ scannerLabel }}</span>
        </div>
        <span class="text-[10px] uppercase tracking-wider font-mono opacity-80">
          {{ isScannerReady ? 'Camera Active' : 'No Device' }}
        </span>
      </div>

      <!-- STATUS GERBANG / PALANG -->
      <div
        :class="{
          'bg-slate-900 text-slate-300 border-slate-800': gateStatus === 'standby',
          'bg-amber-500 text-slate-950 border-amber-600': gateStatus === 'processing',
          'bg-emerald-600 text-white border-emerald-700 shadow-lg shadow-emerald-500/20': gateStatus === 'success',
          'bg-rose-600 text-white border-rose-700': gateStatus === 'error'
        }"
        class="w-full max-w-md px-4 py-3 mb-4 rounded-2xl border flex items-center justify-between text-xs font-black tracking-wide transition-all"
      >
        <span>{{ statusMessage }}</span>
        <span class="text-[10px] opacity-80 uppercase font-mono">
          Palang: {{ gateStatus === 'success' ? 'TERBUKA' : 'TERTUTUP' }}
        </span>
      </div>

      <!-- KIOSK CARD -->
      <div class="w-full max-w-md bg-white rounded-3xl border border-slate-100 shadow-xs p-7 space-y-6">
        
        <!-- HEADER POS -->
        <div class="text-center space-y-1.5">
          <div class="w-12 h-12 mx-auto rounded-2xl bg-[#0B0F19] text-cyan-400 flex items-center justify-center text-xl font-black shadow-md">
            P
          </div>
          <h2 class="text-lg font-black text-slate-900 tracking-tight">TIKET NON-MEMBER</h2>
          <p class="text-xs text-slate-400 font-medium">
            Tekan tombol kendaraan di bawah untuk cetak struk tiket masuk
          </p>
        </div>

        <!-- TOMBOL PILIHAN KENDARAAN (PRINT TIKET) -->
        <div class="grid grid-cols-2 gap-3">
          <button
            type="button"
            @click="printTicket('motor')"
            :disabled="loading"
            class="p-4 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-cyan-50 hover:border-cyan-400 hover:text-cyan-700 font-bold text-xs flex flex-col items-center gap-1 transition cursor-pointer disabled:opacity-50 group"
          >
            <span class="text-2xl group-hover:scale-110 transition">🛵</span>
            <span class="text-slate-800 font-extrabold group-hover:text-cyan-700">Motor</span>
            <span class="text-[10px] text-slate-400">Rp 2.000 / jam</span>
          </button>

          <button
            type="button"
            @click="printTicket('mobil')"
            :disabled="loading"
            class="p-4 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-cyan-50 hover:border-cyan-400 hover:text-cyan-700 font-bold text-xs flex flex-col items-center gap-1 transition cursor-pointer disabled:opacity-50 group"
          >
            <span class="text-2xl group-hover:scale-110 transition">🚗</span>
            <span class="text-slate-800 font-extrabold group-hover:text-cyan-700">Mobil</span>
            <span class="text-[10px] text-slate-400">Rp 5.000 / jam</span>
          </button>
        </div>

        <div class="relative flex items-center justify-center my-1">
          <div class="border-t border-slate-200 w-full"></div>
          <span class="bg-white px-3 text-[10px] font-black uppercase text-slate-400 tracking-wider absolute">ATAU</span>
        </div>

        <!-- SCANNER ZONE KHUSUS MEMBER -->
        <div class="space-y-3">
          <div class="text-center">
            <h3 class="text-sm font-black text-slate-900 uppercase">Scan Kartu / QR Member</h3>
            <p class="text-[11px] text-slate-400 mt-0.5">
              Cukup dekatkan barcode/QR ke arah kamera atau scanner
            </p>
          </div>

          <!-- INPUT BACKUP (STANDBY OTOMATIS) -->
          <form @submit.prevent="processMemberGate(cardInput)" class="flex gap-2">
            <input
              ref="inputRef"
              v-model="cardInput"
              type="text"
              placeholder="Scanner Standby..."
              class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-sm font-mono font-black text-center uppercase tracking-wider text-slate-800 outline-none transition"
            />
            <button
              type="submit"
              :disabled="loading || !cardInput"
              class="bg-[#0284C7] hover:bg-[#0369A1] text-white px-5 rounded-2xl font-black text-xs transition cursor-pointer disabled:opacity-50 shrink-0"
            >
              Scan
            </button>
          </form>
        </div>

      </div>
    </main>

    <!-- MODAL POPUP NOTIFIKASI -->
    <div v-if="modal.show" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center shadow-2xl border border-slate-100">
        <h3 class="text-base font-black text-slate-900 mb-2">{{ modal.title }}</h3>
        <p class="text-xs text-slate-600 mb-6 leading-relaxed whitespace-pre-line">{{ modal.message }}</p>
        <button
          type="button"
          @click="modal.show = false"
          class="w-full py-3 rounded-2xl font-black text-xs text-white bg-[#0B0F19] hover:bg-slate-800 transition cursor-pointer shadow-xs"
        >
          Tutup / Mengerti
        </button>
      </div>
    </div>
  </div>
</template>