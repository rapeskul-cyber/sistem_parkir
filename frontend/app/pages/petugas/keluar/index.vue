<template>
  <div class="min-h-screen bg-[#F8FAFC] flex font-sans antialiased text-slate-800">
    <div class="fixed top-0 left-0 w-1 h-1 opacity-0 pointer-events-none -z-50 overflow-hidden">
      <video ref="videoElement" class="w-[640px] h-[480px] object-cover" playsinline muted autoplay></video>
    </div>

    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#0B0F19] text-slate-400 flex flex-col justify-between py-6 px-4 shrink-0 select-none hidden md:flex no-print">
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
            <span class="text-sm">⊞</span> Dashboard
          </NuxtLink>
          <NuxtLink to="/petugas/keluar" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs shadow-xs">
            <div class="flex items-center gap-3">
              <span class="text-sm text-cyan-400">🚪</span> Gate Keluar (Kasir)
            </div>
            <span class="text-xs text-cyan-400 font-bold">●</span>
          </NuxtLink>
          <NuxtLink to="/petugas/transaksi" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">🚗</span> Kelola Transaksi
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>
          <NuxtLink to="/petugas/member/select" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">👥</span> Kelola Member
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>
          <NuxtLink to="/petugas/laporan" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">📊</span> Laporan
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>
          <NuxtLink to="/petugas/markir" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">🅿️</span> Sedang Parkir
            </div>
            <span class="text-xs text-slate-600">›</span>
          </NuxtLink>
        </nav>
      </div>

      <div class="space-y-3 pt-4 border-t border-slate-800/80">
        <div class="bg-slate-900/90 border border-slate-800 px-3.5 py-2.5 rounded-2xl flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-xs font-bold">
            P
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-white truncate">Petugas Parkir</p>
            <p class="text-[10px] text-emerald-400 font-medium">Pos Kasir Aktif</p>
          </div>
        </div>
        <button @click="logout" class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 transition cursor-pointer">
          <span>🚪</span> Logout
        </button>
      </div>
    </aside>

    <!-- CONTENT POS KASIR -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <header class="bg-white px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0 no-print">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">PLAZA ANDALAS SYSTEM</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Gate Keluar - Kasir Pembayaran</h1>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-[11px] font-bold">
            <span :class="isScannerReady ? 'bg-emerald-400 animate-pulse' : 'bg-rose-500'" class="w-2.5 h-2.5 rounded-full transition-all"></span>
            <span :class="isScannerReady ? 'text-emerald-400' : 'text-rose-400'">
              {{ isScannerReady ? 'SCANNER READY' : 'SCANNER OFFLINE' }}
            </span>
          </div>
          <NuxtLink to="/petugas" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs">
            <span>←</span> Dashboard
          </NuxtLink>
        </div>
      </header>

      <div class="p-8 flex justify-center items-start no-print">
        <div class="w-full max-w-lg space-y-5">
          <div class="bg-white rounded-3xl border border-slate-100 shadow-xs p-7 space-y-5">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">CHECK-OUT TERMINAL</span>
              <h2 class="text-lg font-black text-slate-900 tracking-tight mt-0.5">Validasi Tiket & Kartu Member</h2>
              <p class="text-xs text-slate-400 mt-1">Gunakan scanner fisik USB atau arahkan kode QR ke webcam.</p>
            </div>

            <!-- INPUT KODE -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode Tiket / Kartu Member</label>
              <div class="flex gap-2">
                <input
                  v-model="form.kode"
                  type="text"
                  @keydown.enter.prevent="prosesScan"
                  placeholder="Scan otomatis atau ketik kode..."
                  autofocus
                  class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3.5 text-xs font-black uppercase text-slate-800 outline-none transition"
                />
                <button
                  @click="prosesScan"
                  :disabled="loading || isProcessing"
                  type="button"
                  class="bg-[#0B0F19] hover:bg-slate-800 text-white px-5 rounded-2xl font-black text-xs transition cursor-pointer disabled:opacity-50 shrink-0"
                >
                  {{ loading ? '...' : 'Cari' }}
                </button>
              </div>
            </div>

            <!-- DETAIL TRANSAKSI -->
            <div v-if="detailTransaksi" class="space-y-4 border-t border-slate-100 pt-5">
              <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-2 text-xs">
                <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                  <span class="text-slate-400 font-bold">Tipe Akses</span>
                  <span class="font-mono font-black uppercase px-2 py-0.5 rounded-md text-[10px]" :class="tipeAkses === 'member' ? 'bg-cyan-100 text-cyan-700' : 'bg-indigo-100 text-indigo-700'">
                    {{ tipeAkses === 'member' ? 'LANGGANAN MEMBER' : 'TIKET UMUM (NON-MEMBER)' }}
                  </span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                  <span class="text-slate-400 font-bold">Kode Identitas</span>
                  <span class="font-mono font-black text-slate-800">{{ detailTransaksi.kode_tiket || detailTransaksi.kode_member }}</span>
                </div>
                <div v-if="detailTransaksi.nama_member" class="flex justify-between items-center py-1 border-b border-slate-200/60">
                  <span class="text-slate-400 font-bold">Nama Member</span>
                  <span class="font-bold text-slate-800">{{ detailTransaksi.nama_member }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                  <span class="text-slate-400 font-bold">Waktu Masuk</span>
                  <span class="font-medium text-slate-700">{{ formatWaktu(detailTransaksi.waktu_masuk) }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                  <span class="text-slate-400 font-bold">Durasi Terhitung</span>
                  <span class="font-black text-indigo-600">{{ durasiJam }} Jam</span>
                </div>
                <div class="flex justify-between items-center py-1">
                  <span class="text-slate-400 font-bold">Kategori Terpilih</span>
                  <span class="font-bold uppercase text-slate-800">
                    {{ form.kategori === 'mobil' ? '🚗 Mobil' : '🛵 Motor' }}
                  </span>
                </div>
              </div>

              <!-- PILIH KATEGORI KHUSUS MEMBER -->
              <div v-if="tipeAkses === 'member'">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Kendaraan Member Saat Keluar</label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    @click="pilihKategori('motor')"
                    class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                    :class="form.kategori === 'motor' ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                  >
                    <span>🛵</span> Motor (Rp 2.000/jam)
                  </button>
                  <button
                    type="button"
                    @click="pilihKategori('mobil')"
                    class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
                    :class="form.kategori === 'mobil' ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                  >
                    <span>🚗</span> Mobil (Rp 5.000/jam)
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">No. Plat Kendaraan</label>
                <input
                  v-model="form.nopol"
                  type="text"
                  placeholder="Contoh: BA 1234 XY"
                  class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-xs font-black uppercase text-slate-800 outline-none transition"
                />
              </div>

              <!-- MEMBER KELUAR (FREE) -->
              <div v-if="tipeAkses === 'member'" class="space-y-4">
                <div class="bg-[#0B0F19] text-white p-5 rounded-2xl space-y-2.5 shadow-inner">
                  <div class="flex justify-between items-center text-xs text-slate-400">
                    <span>Biaya Normal ({{ durasiJam }} Jam × Rp {{ formatRupiah(tarifPerJam) }})</span>
                    <span class="line-through text-slate-500 font-mono">Rp {{ formatRupiah(totalTarif) }}</span>
                  </div>
                  <div class="flex justify-between items-center text-xs text-cyan-400 font-bold">
                    <span>Voucher Langganan Member (100%)</span>
                    <span class="font-mono">- Rp {{ formatRupiah(totalTarif) }}</span>
                  </div>
                  <hr class="border-slate-800" />
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-300">TAGIHAN AKHIR</span>
                    <span class="text-xl font-black text-emerald-400 font-mono">Rp 0 (FREE)</span>
                  </div>
                </div>

                <button
                  @click="konfirmasiKeluarMember"
                  :disabled="loading"
                  type="button"
                  class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-black p-3.5 rounded-2xl shadow-xs transition cursor-pointer uppercase tracking-wider text-xs"
                >
                  {{ loading ? 'Memproses Transaksi...' : 'GUNAKAN VOUCHER & BUKA GATE KELUAR' }}
                </button>
              </div>

              <!-- NON-MEMBER -->
              <div v-else class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Uang Tunai Diterima (Rp)</label>
                  <div class="relative mb-2.5">
                    <span class="absolute left-4 top-3 text-xs font-bold text-slate-400">Rp</span>
                    <input
                      v-model.number="form.uang_bayar"
                      type="number"
                      min="0"
                      class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl pl-11 pr-4 py-3 text-xs font-black text-slate-800 outline-none transition"
                    />
                  </div>

                  <div class="flex flex-wrap gap-1.5">
                    <button
                      @click="setNominal(totalTarif)"
                      type="button"
                      class="text-[11px] font-bold px-3 py-1.5 rounded-xl border transition cursor-pointer"
                      :class="Number(form.uang_bayar) === totalTarif ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-xs' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'"
                    >
                      Uang Pas (Rp {{ formatRupiah(totalTarif) }})
                    </button>
                    <button
                      v-for="nominal in opsiPecahan"
                      :key="nominal"
                      @click="setNominal(nominal)"
                      type="button"
                      class="text-[11px] font-bold px-3 py-1.5 rounded-xl border transition cursor-pointer"
                      :class="Number(form.uang_bayar) === nominal ? 'bg-[#0B0F19] text-white border-[#0B0F19] shadow-xs' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'"
                    >
                      Rp {{ formatRupiah(nominal) }}
                    </button>
                  </div>
                </div>

                <div class="bg-[#0B0F19] text-white p-5 rounded-2xl space-y-3 shadow-inner">
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400">TOTAL TARIF ({{ durasiJam }} JAM × Rp {{ formatRupiah(tarifPerJam) }})</span>
                    <span class="text-xl font-black text-emerald-400 font-mono">Rp {{ formatRupiah(totalTarif) }}</span>
                  </div>
                  <hr class="border-slate-800" />
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400">KEMBALIAN (CASHBACK)</span>
                    <span class="text-base font-black text-emerald-400 font-mono">
                      Rp {{ formatRupiah(hitungKembalian) }}
                    </span>
                  </div>
                </div>

                <button
                  @click="konfirmasiBayarNonMember"
                  :disabled="loading"
                  type="button"
                  class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-black p-3.5 rounded-2xl shadow-xs transition cursor-pointer uppercase tracking-wider text-xs"
                >
                  {{ loading ? 'Memproses Transaksi...' : 'BAYAR & BUKA GATE KELUAR' }}
                </button>
              </div>
            </div>

            <button
              v-if="detailTransaksi"
              @click="batal"
              type="button"
              class="w-full bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold p-3 rounded-2xl transition cursor-pointer text-xs"
            >
              BATAL / RESET TRANSAKSI
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- MODAL STRUK INVOICE -->
    <div v-if="invoiceData" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <div id="print-struk" class="bg-white p-4 rounded-2xl border border-slate-200 text-xs space-y-3">
          <div class="text-center pb-2 border-b border-dashed border-slate-300">
            <h3 class="font-black text-slate-900 text-sm">PLAZA ANDALAS PARKING</h3>
            <p class="text-[10px] text-slate-400">BUKTI TRANSAKSI GATE KELUAR</p>
          </div>

          <div class="space-y-1.5 font-mono text-[11px] text-slate-700">
            <div class="flex justify-between">
              <span>Tipe Akses:</span>
              <span class="font-bold uppercase">{{ invoiceData.tipe }}</span>
            </div>
            <div class="flex justify-between">
              <span>Kategori:</span>
              <span class="font-bold uppercase">{{ invoiceData.kategori }}</span>
            </div>
            <div class="flex justify-between">
              <span>Kode:</span>
              <span class="font-bold">{{ invoiceData.kode }}</span>
            </div>
            <div v-if="invoiceData.nama" class="flex justify-between">
              <span>Nama:</span>
              <span class="font-bold">{{ invoiceData.nama }}</span>
            </div>
            <div class="flex justify-between">
              <span>No. Plat:</span>
              <span class="font-bold uppercase">{{ invoiceData.nopol }}</span>
            </div>
            <div class="flex justify-between">
              <span>Waktu Masuk:</span>
              <span>{{ formatWaktu(invoiceData.masuk) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Waktu Keluar:</span>
              <span>{{ formatWaktu(invoiceData.keluar) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Durasi:</span>
              <span class="font-bold">{{ invoiceData.durasi }} Jam</span>
            </div>
          </div>

          <div class="pt-2 border-t border-dashed border-slate-300 space-y-1 font-mono text-[11px]">
            <div class="flex justify-between text-slate-600">
              <span>Tarif Normal:</span>
              <span>Rp {{ formatRupiah(invoiceData.tarif_normal) }}</span>
            </div>
            <div v-if="invoiceData.diskon > 0" class="flex justify-between text-cyan-600 font-bold">
              <span>Voucher Member (100%):</span>
              <span>- Rp {{ formatRupiah(invoiceData.diskon) }}</span>
            </div>
            <div class="flex justify-between font-bold text-slate-900 text-xs pt-1 border-t border-slate-200">
              <span>TOTAL BAYAR:</span>
              <span>Rp {{ formatRupiah(invoiceData.total_bayar) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
              <span>Uang Diterima:</span>
              <span>Rp {{ formatRupiah(invoiceData.uang_diterima) }}</span>
            </div>
            <div class="flex justify-between font-bold text-emerald-600">
              <span>Kembalian (Cashback):</span>
              <span>Rp {{ formatRupiah(invoiceData.kembalian) }}</span>
            </div>
          </div>

          <div class="text-center pt-2 border-t border-dashed border-slate-300">
            <p class="text-[9px] text-slate-400 italic">Terima kasih atas kunjungan Anda!</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 no-print">
          <button
            @click="downloadInvoicePdf"
            :disabled="isExportingInvoicePdf"
            type="button"
            class="bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
          >
            <span>📄</span> {{ isExportingInvoicePdf ? 'Mendownload...' : 'Download PDF' }}
          </button>

          <button
            @click="cetakStrukPrint"
            type="button"
            class="bg-[#0B0F19] hover:bg-slate-800 text-white py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
          >
            <span>🖨️</span> Cetak / Simpan Struk
          </button>
        </div>

        <button
          @click="tutupInvoiceModal"
          type="button"
          class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer no-print"
        >
          Selesai / Tutup Transaksi
        </button>
      </div>
    </div>

    <!-- MODAL ALERT -->
    <div v-if="modal.show" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 no-print">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center shadow-2xl border border-slate-100">
        <h3 class="text-base font-black text-slate-900 mb-2">{{ modal.title }}</h3>
        <p class="text-xs text-slate-600 mb-6 leading-relaxed whitespace-pre-line">{{ modal.message }}</p>
        <button
          @click="closeModal"
          class="w-full py-3 rounded-2xl font-black text-xs text-white bg-[#0B0F19] hover:bg-slate-800 transition cursor-pointer shadow-xs"
        >
          Tutup / Mengerti
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'

definePageMeta({ middleware: 'auth' })

const { $api } = useNuxtApp() as any
const router = useRouter()

const loading = ref(false)
const isProcessing = ref(false)
const isExportingInvoicePdf = ref(false)
const detailTransaksi = ref<any>(null)
const invoiceData = ref<any>(null)
const tipeAkses = ref('')
const durasiJam = ref<number>(1)

const isScannerReady = ref(false)
const videoElement = ref<HTMLVideoElement | null>(null)

let mediaStream: MediaStream | null = null
let animationFrameId: number | null = null
let jsQRModule: any = null

let canvas: HTMLCanvasElement | null = null
let canvasCtx: CanvasRenderingContext2D | null = null

const modal = reactive({ show: false, title: '', message: '', onClose: null as any })
const triggerModal = (title: string, message: string, onCloseCallback: any = null) => {
  modal.title = title
  modal.message = message
  modal.onClose = onCloseCallback
  modal.show = true
}
const closeModal = () => {
  modal.show = false
  if (modal.onClose) modal.onClose()
}

const form = reactive({ kode: '', kategori: 'motor', nopol: '', uang_bayar: 2000 })

const pilihKategori = (kat: 'motor' | 'mobil') => {
  if (tipeAkses.value !== 'member') return
  form.kategori = kat
}

const loadJsQR = (): Promise<any> => {
  return new Promise((resolve, reject) => {
    if ((window as any).jsQR) return resolve((window as any).jsQR)
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js'
    script.onload = () => resolve((window as any).jsQR)
    script.onerror = () => reject(new Error('Gagal memuat jsQR'))
    document.head.appendChild(script)
  })
}

const startCamera = async () => {
  stopCamera()
  try {
    jsQRModule = await loadJsQR()
    const devices = await navigator.mediaDevices.enumerateDevices()
    const videoDevices = devices.filter(d => d.kind === 'videoinput')

    if (videoDevices.length === 0) {
      isScannerReady.value = false
      return
    }

    const iriun = videoDevices.find(d => d.label.toLowerCase().includes('iriun'))
    const targetDeviceId = iriun ? iriun.deviceId : videoDevices[0].deviceId

    const stream = await navigator.mediaDevices.getUserMedia({
      video: {
        deviceId: { exact: targetDeviceId },
        width: { ideal: 640 },
        height: { ideal: 480 }
      }
    })

    mediaStream = stream
    if (videoElement.value) {
      videoElement.value.srcObject = stream
      await videoElement.value.play()
    }

    isScannerReady.value = true
    scanLoop()
  } catch (err) {
    isScannerReady.value = false
  }
}

const scanLoop = () => {
  if (!videoElement.value || !mediaStream || !jsQRModule) return

  if (videoElement.value.readyState === videoElement.value.HAVE_ENOUGH_DATA) {
    if (!canvas) {
      canvas = document.createElement('canvas')
      canvasCtx = canvas.getContext('2d', { willReadFrequently: true })
    }

    canvas.width = videoElement.value.videoWidth
    canvas.height = videoElement.value.videoHeight

    if (canvasCtx) {
      canvasCtx.drawImage(videoElement.value, 0, 0, canvas.width, canvas.height)
      const imageData = canvasCtx.getImageData(0, 0, canvas.width, canvas.height)
      const code = jsQRModule(imageData.data, imageData.width, imageData.height, {
        inversionAttempts: 'dontInvert'
      })

      if (code && code.data && !isProcessing.value && !loading.value && !detailTransaksi.value) {
        form.kode = code.data.trim().replace(/[\r\n]+/g, '')
        prosesScan()
      }
    }
  }
  animationFrameId = requestAnimationFrame(scanLoop)
}

const stopCamera = () => {
  if (animationFrameId) cancelAnimationFrame(animationFrameId)
  if (mediaStream) {
    mediaStream.getTracks().forEach(t => t.stop())
    mediaStream = null
  }
}

let scanBuffer = ''
let lastKeyTime = Date.now()

const handleGlobalKeyDown = (e: KeyboardEvent) => {
  if (['Shift', 'Control', 'Alt', 'Meta'].includes(e.key)) return

  const currentTime = Date.now()
  if (currentTime - lastKeyTime > 100) scanBuffer = ''
  lastKeyTime = currentTime

  if (e.key === 'Enter') {
    if (scanBuffer.length >= 3 && !loading.value && !isProcessing.value && !detailTransaksi.value) {
      form.kode = scanBuffer.trim().replace(/[\r\n]+/g, '')
      prosesScan()
      scanBuffer = ''
      e.preventDefault()
    }
  } else {
    scanBuffer += e.key
  }
}

const tarifPerJam = computed(() => {
  return form.kategori === 'mobil' ? 5000 : 2000
})

const totalTarif = computed(() => {
  return tarifPerJam.value * Number(durasiJam.value || 1)
})

const opsiPecahan = computed(() =>
  form.kategori === 'mobil' ? [5000, 10000, 20000, 50000, 100000] : [2000, 5000, 10000, 20000, 50000]
)

const setNominal = (val: number) => {
  form.uang_bayar = Number(val)
}

const hitungKembalian = computed(() => Math.max(0, Number(form.uang_bayar || 0) - totalTarif.value))

const prosesScan = async () => {
  if (isProcessing.value || loading.value) return

  const cleanCode = form.kode.trim().replace(/[\r\n]+/g, '')
  if (!cleanCode) {
    triggerModal('Peringatan', 'Masukkan kode tiket atau member!')
    return
  }

  isProcessing.value = true
  loading.value = true

  try {
    const res = await $api.post('/gate/scan', { kode: cleanCode })
    tipeAkses.value = res.data.type
    detailTransaksi.value = res.data.data
    durasiJam.value = Number(res.data.data.durasi_jam) || 1
    
    form.kategori = (res.data.data.kategori || 'motor').toLowerCase()
    form.nopol = res.data.data.plat_nomor || ''

    if (res.data.type === 'tiket') {
      form.uang_bayar = totalTarif.value
    }
  } catch (err: any) {
    triggerModal('Gagal', err?.response?.data?.message || 'Data tidak ditemukan!')
    detailTransaksi.value = null
  } finally {
    loading.value = false
    setTimeout(() => {
      isProcessing.value = false
    }, 1200)
  }
}

const konfirmasiKeluarMember = async () => {
  if (!detailTransaksi.value) return

  loading.value = true
  try {
    const identitasKode = detailTransaksi.value.kode_tiket || detailTransaksi.value.kode_member || form.kode

    await $api.post('/transaksi/bayar', {
      tiket_id: detailTransaksi.value.id || null,
      kode: identitasKode,
      nopol: form.nopol || detailTransaksi.value.plat_nomor || '-',
      kategori: form.kategori,
      total_bayar: 0,
      bayar: 0,
      tipe: 'member'
    })

    invoiceData.value = {
      tipe: 'Member Langganan',
      kategori: form.kategori,
      kode: identitasKode,
      nama: detailTransaksi.value.nama_member,
      nopol: form.nopol || detailTransaksi.value.plat_nomor || '-',
      masuk: detailTransaksi.value.waktu_masuk,
      keluar: new Date(),
      durasi: durasiJam.value,
      tarif_normal: totalTarif.value,
      diskon: totalTarif.value,
      total_bayar: 0,
      uang_diterima: 0,
      kembalian: 0
    }
  } catch (err: any) {
    triggerModal('Gagal', err?.response?.data?.message || 'Gagal memproses transaksi member.')
  } finally {
    loading.value = false
  }
}

const konfirmasiBayarNonMember = async () => {
  if (!detailTransaksi.value) return

  if (Number(form.uang_bayar) < totalTarif.value) {
    triggerModal('Pembayaran Kurang', 'Uang tunai kurang dari total tarif!')
    return
  }

  loading.value = true
  try {
    const identitasKode = detailTransaksi.value.kode_tiket || form.kode

    await $api.post('/transaksi/bayar', {
      tiket_id: detailTransaksi.value.id || null,
      kode: identitasKode,
      nopol: form.nopol || '-',
      kategori: form.kategori,
      total_bayar: totalTarif.value,
      bayar: Number(form.uang_bayar),
      tipe: 'non-member'
    })

    invoiceData.value = {
      tipe: 'Tiket Non-Member',
      kategori: form.kategori,
      kode: identitasKode,
      nama: null,
      nopol: form.nopol || '-',
      masuk: detailTransaksi.value.waktu_masuk,
      keluar: new Date(),
      durasi: durasiJam.value,
      tarif_normal: totalTarif.value,
      diskon: 0,
      total_bayar: totalTarif.value,
      uang_diterima: Number(form.uang_bayar),
      kembalian: hitungKembalian.value
    }
  } catch (err: any) {
    triggerModal('Gagal', err?.response?.data?.message || 'Terjadi kesalahan transaksi.')
  } finally {
    loading.value = false
  }
}

const loadHtml2Pdf = (): Promise<any> => {
  return new Promise((resolve, reject) => {
    if ((window as any).html2pdf) {
      resolve((window as any).html2pdf)
      return
    }

    const script = document.createElement('script')
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js'
    script.onload = () => resolve((window as any).html2pdf)
    script.onerror = () => reject(new Error('Gagal memuat library PDF'))
    document.head.appendChild(script)
  })
}

const downloadInvoicePdf = async () => {
  const element = document.getElementById('print-struk')
  if (!element || !invoiceData.value) return

  isExportingInvoicePdf.value = true

  try {
    const html2pdf = await loadHtml2Pdf()
    const filename = `Struk-${invoiceData.value.kode || 'parkir'}-${Date.now()}.pdf`

    await html2pdf()
      .set({
        margin: 4,
        filename,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF: { unit: 'mm', format: 'a5', orientation: 'portrait' }
      })
      .from(element)
      .save()
  } catch (err) {
    console.error('Gagal download PDF struk:', err)
    triggerModal('Gagal', 'PDF struk gagal didownload. Silakan coba lagi.')
  } finally {
    isExportingInvoicePdf.value = false
  }
}

const cetakStrukPrint = () => {
  window.print()
}

const tutupInvoiceModal = () => {
  invoiceData.value = null
  batal()
}

const batal = () => {
  detailTransaksi.value = null
  tipeAkses.value = ''
  form.kode = ''
  form.nopol = ''
  form.kategori = 'motor'
  form.uang_bayar = 2000
  durasiJam.value = 1
}

const formatWaktu = (d: any) =>
  d ? new Date(d).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-'

const formatRupiah = (v: any) => new Intl.NumberFormat('id-ID').format(Number(v || 0))

const logout = async () => {
  try {
    await $api.post('/logout')
  } catch {}
  localStorage.removeItem('token')
  router.push('/')
}

onMounted(() => {
  window.addEventListener('keydown', handleGlobalKeyDown)
  startCamera()

  if (navigator.mediaDevices) {
    navigator.mediaDevices.addEventListener('devicechange', () => startCamera())
  }
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeyDown)
  if (navigator.mediaDevices) {
    navigator.mediaDevices.removeEventListener('devicechange', () => startCamera())
  }
  stopCamera()
})
</script>

<style scoped>
@media print {
  aside,
  header,
  .no-print {
    display: none !important;
  }
  body, main {
    background: white !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  #print-struk {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    width: 100% !important;
    max-width: 320px !important;
    margin: 0 auto !important;
  }
}
</style>