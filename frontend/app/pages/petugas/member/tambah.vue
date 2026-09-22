<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'

definePageMeta({
  middleware: 'auth'
})

const TARIF_LANGGANAN = 150000

const { $api } = useNuxtApp() as any
const router = useRouter()

const form = reactive({
  nama_member: '',
  nama_perusahaan: '',
  uang_bayar: TARIF_LANGGANAN
})

const loading = ref<boolean>(false)
const isExportingPdf = ref<boolean>(false)
const suksesData = ref<any>(null)

// Pilihan nominal uang tunai cepat
const opsiPecahan = [150000, 200000, 300000, 500000]

const setNominal = (nominal: number) => {
  form.uang_bayar = Number(nominal)
}

const hitungKembalian = computed(() => {
  return Math.max(0, Number(form.uang_bayar || 0) - TARIF_LANGGANAN)
})

const memberCode = computed(() => {
  const data = suksesData.value
  if (!data) return 'DEFAULT'
  return data.kode_member || data.kode || (data.id ? `MBR-${data.id}` : 'DEFAULT')
})

const qrCodeUrl = computed(() => {
  if (suksesData.value?.qr) return suksesData.value.qr
  return `http://localhost:8000/api/qrcode/${memberCode.value}`
})

const formatDate = (dateString: any) => {
  if (!dateString) return '-'
  const d = new Date(dateString)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
}

const formatRupiah = (angka: any) => {
  return new Intl.NumberFormat('id-ID').format(Number(angka || 0))
}

const handleImageError = (e: any) => {
  if (suksesData.value?.id) {
    e.target.src = `http://localhost:8000/api/qrcode/${suksesData.value.id}`
  }
}

const submitForm = async () => {
  const namaTrimmed = form.nama_member.trim()
  const perusahaanTrimmed = form.nama_perusahaan.trim()

  if (!namaTrimmed || !perusahaanTrimmed) {
    alert('Nama member dan instansi/perusahaan wajib diisi!')
    return
  }

  if (Number(form.uang_bayar) < TARIF_LANGGANAN) {
    alert('Uang tunai kurang dari total tagihan langganan (Rp 150.000)!')
    return
  }

  loading.value = true
  try {
    const payload = {
      nama_member: namaTrimmed,
      nama_perusahaan: perusahaanTrimmed,
      total_harga: TARIF_LANGGANAN,
      jumlah_bayar: Number(form.uang_bayar),
      kembalian: hitungKembalian.value,
      status: 'lunas'
    }

    const res = await $api.post('/member', payload)
    suksesData.value = res.data.data || res.data
    suksesData.value.uang_diterima = Number(form.uang_bayar)
    suksesData.value.uang_kembalian = hitungKembalian.value
  } catch (err: any) {
    alert(err.response?.data?.message || 'Gagal mendaftarkan member')
  } finally {
    loading.value = false
  }
}

// 1. Buka halaman cetak aman yang bisa disimpan sebagai PDF dari browser
const downloadPdfLangsung = () => {
  const payload = `
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="UTF-8" />
        <title>Invoice Member</title>
        <style>
          body {
            margin: 0;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            background: #ffffff;
            color: #0f172a;
          }
          .card {
            max-width: 430px;
            margin: 0 auto;
            border: 1px solid #dfe7f0;
            border-radius: 18px;
            padding: 20px;
            box-sizing: border-box;
          }
          .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 18px;
          }
          .brand {
            display: flex;
            align-items: center;
            gap: 10px;
          }
          .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #a7f3d0;
          }
          .logo {
            width: 36px; height: 36px; border-radius: 10px;
            background: #0f172a; color: #22d3ee; display: flex;
            align-items: center; justify-content: center; font-weight: 900;
          }
          .title { font-size: 12px; font-weight: 900; letter-spacing: 0.06em; }
          .subtitle { font-size: 9px; color: #64748b; letter-spacing: 0.12em; }
          .qr {
            text-align: center; margin: 18px 0;
          }
          img {
            width: 170px; height: 170px; object-fit: contain;
          }
          .info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            font-size: 12px;
          }
          .row {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #e2e8f0; padding: 8px 0;
          }
          .label { color: #64748b; font-weight: 700; }
          .value { font-weight: 800; color: #0f172a; }
          .value.green { color: #059669; }
          .note { margin-top: 12px; color: #64748b; text-align: center; font-size: 10px; font-style: italic; }
        </style>
      </head>
      <body>
        <div class="card">
          <div class="header">
            <div class="brand">
              <div class="logo">P</div>
              <div>
                <div class="title">INVOICE & KARTU MEMBER</div>
                <div class="subtitle">PLAZA ANDALAS PARKING</div>
              </div>
            </div>
            <span class="badge">Lunas (Aktif)</span>
          </div>

          <div class="qr">
            <img src="${qrCodeUrl.value}" alt="QR Member" />
          </div>

          <div class="info">
            <div class="row"><span class="label">Kode Member</span><span class="value">${memberCode.value}</span></div>
            <div class="row"><span class="label">Nama Pelanggan</span><span class="value">${suksesData.value?.nama_member || '-'}</span></div>
            <div class="row"><span class="label">Instansi</span><span class="value">${suksesData.value?.nama_perusahaan || '-'}</span></div>
            <div class="row"><span class="label">Biaya Langganan</span><span class="value">Rp ${formatRupiah(TARIF_LANGGANAN)}</span></div>
            <div class="row"><span class="label">Uang Diterima</span><span class="value">Rp ${formatRupiah(suksesData.value?.uang_diterima || TARIF_LANGGANAN)}</span></div>
            <div class="row"><span class="label">Kembalian</span><span class="value green">Rp ${formatRupiah(suksesData.value?.uang_kembalian || 0)}</span></div>
            <div class="row" style="border-bottom: none;"><span class="label">Masa Berlaku</span><span class="value">${formatDate(suksesData.value?.tanggal_expired)}</span></div>
          </div>

          <div class="note">Harap simpan bukti pembayaran dan QR Code ini untuk akses gerbang masuk/keluar.</div>
        </div>
      </body>
    </html>
  `

  isExportingPdf.value = true

  try {
    const printWindow = window.open('', '_blank', 'width=900,height=900')
    if (!printWindow) {
      alert('Popup browser diblokir. Gunakan tombol Cetak Struk atau buka izin popup browser.')
      return
    }

    printWindow.document.write(payload)
    printWindow.document.close()
    printWindow.focus()
    setTimeout(() => {
      printWindow.print()
      setTimeout(() => printWindow.close(), 1000)
    }, 500)
  } catch (err) {
    console.error('Gagal membuka halaman cetak PDF:', err)
    alert('Gagal menyiapkan salinan PDF. Silakan gunakan tombol Cetak Struk.')
  } finally {
    isExportingPdf.value = false
  }
}

// 2. Cetak Struk ke Printer Fisik
const cetakKePrinter = () => {
  window.print()
}

// 3. Download Kartu Gambar PNG
const downloadKartu = () => {
  if (!suksesData.value?.kode_member && !memberCode.value) return

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
  ctx.fillText(`:  ${memberCode.value}`, 180, 170)
  ctx.fillText(`:  ${suksesData.value.nama_member}`, 180, 210)
  ctx.fillText(`:  ${suksesData.value.nama_perusahaan}`, 180, 250)
  ctx.fillText(':  LUNAS (AKTIF)', 180, 290)

  ctx.fillStyle = '#F8FAFC'
  ctx.fillRect(140, 350, 220, 220)

  const img = new Image()
  img.crossOrigin = 'anonymous'
  img.onload = () => {
    ctx.drawImage(img, 150, 360, 200, 200)
    const link = document.createElement('a')
    link.download = `member-${memberCode.value}.png`
    link.href = canvas.toDataURL('image/png')
    link.click()
  }
  img.src = qrCodeUrl.value
}

const logout = async () => {
  try {
    await $api.post('/logout')
  } catch {}
  localStorage.removeItem('token')
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] flex font-sans antialiased text-slate-800">
    <!-- SIDEBAR PERSIS REFERENSI -->
    <aside class="w-64 bg-[#0B0F19] text-slate-400 flex flex-col justify-between py-6 px-4 shrink-0 select-none hidden md:flex">
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
          <NuxtLink to="/petugas/member/select" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-800/90 text-white font-semibold text-xs shadow-xs">
            <div class="flex items-center gap-3">
              <span class="text-sm text-cyan-400">👥</span>
              <span>Kelola Member</span>
            </div>
            <span class="text-xs text-cyan-400 font-bold">●</span>
          </NuxtLink>
          <NuxtLink to="/petugas/laporan" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/40 text-xs font-semibold transition">
            <div class="flex items-center gap-3">
              <span class="text-sm">📊</span>
              <span>Laporan</span>
            </div>
            <span class="text-xs text-slate-600">›</span>
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
        <button @click="logout" class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 transition cursor-pointer">
          <span>🚪</span>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- CONTENT PANEL -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <header class="bg-white px-8 py-5 flex items-center justify-between border-b border-slate-100 shrink-0">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">PLAZA ANDALAS SYSTEM</span>
          <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Tambah Member Baru</h1>
        </div>

        <button @click="router.push('/petugas/member/select')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs">
          <span>←</span> Kembali ke Daftar
        </button>
      </header>

      <div class="p-8 flex justify-center items-start">
        <div class="w-full max-w-xl">
          <!-- FORM KASIR TRANSAKSI TAMBAH MEMBER -->
          <div v-if="!suksesData" class="bg-white rounded-3xl border border-slate-100 shadow-xs p-7 space-y-5">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">CHECK-IN TERMINAL</span>
              <h2 class="text-lg font-black text-slate-900 tracking-tight mt-0.5">Registrasi Langganan Bulanan</h2>
              <p class="text-xs text-slate-400 mt-1">Isi identitas pelanggan dan masukkan uang tunai pembayaran member.</p>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Member</label>
                <input
                  v-model="form.nama_member"
                  type="text"
                  placeholder="Contoh: Budi Santoso"
                  required
                  class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-xs font-semibold text-slate-800 outline-none transition"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Instansi / Perusahaan</label>
                <input
                  v-model="form.nama_perusahaan"
                  type="text"
                  placeholder="Contoh: PT. Maju Bersama / Umum"
                  required
                  class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl px-4 py-3 text-xs font-semibold text-slate-800 outline-none transition"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Uang Tunai Diterima (Rp)</label>
                <div class="relative mb-2.5">
                  <span class="absolute left-4 top-3 text-xs font-bold text-slate-400">Rp</span>
                  <input
                    v-model.number="form.uang_bayar"
                    type="number"
                    min="0"
                    placeholder="0"
                    class="w-full bg-slate-50 border border-slate-200 focus:border-[#0284C7] focus:bg-white focus:ring-2 focus:ring-[#0284C7]/20 rounded-2xl pl-11 pr-4 py-3 text-xs font-black text-slate-800 outline-none transition"
                  />
                </div>

                <div class="flex flex-wrap gap-1.5">
                  <button
                    @click="setNominal(TARIF_LANGGANAN)"
                    type="button"
                    class="text-[11px] font-bold px-3 py-1.5 rounded-xl border transition cursor-pointer"
                    :class="Number(form.uang_bayar) === TARIF_LANGGANAN ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-xs' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'"
                  >
                    Uang Pas (Rp {{ formatRupiah(TARIF_LANGGANAN) }})
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

              <!-- KOTAK KALKULASI KASIR & CASHBACK -->
              <div class="bg-[#0B0F19] text-white p-5 rounded-2xl space-y-3 shadow-inner">
                <div class="flex justify-between items-center">
                  <span class="text-xs font-bold text-slate-400">BIAYA LANGGANAN (30 HARI)</span>
                  <span class="text-xl font-black text-emerald-400">Rp {{ formatRupiah(TARIF_LANGGANAN) }}</span>
                </div>
                <hr class="border-slate-800" />
                <div class="flex justify-between items-center">
                  <span class="text-xs font-bold text-slate-400">KEMBALIAN (CASHBACK)</span>
                  <span class="text-base font-black text-emerald-400">
                    Rp {{ formatRupiah(hitungKembalian) }}
                  </span>
                </div>
              </div>

              <div class="pt-2 flex items-center gap-3">
                <button
                  type="submit"
                  :disabled="loading"
                  class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-2xl font-black text-xs transition cursor-pointer shadow-xs disabled:opacity-50 uppercase tracking-wider"
                >
                  {{ loading ? 'Memproses Transaksi...' : 'BAYAR & TERBITKAN KARTU MEMBER' }}
                </button>
                <button
                  type="button"
                  @click="router.back()"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-5 py-3.5 rounded-2xl font-bold text-xs transition cursor-pointer"
                >
                  Batal
                </button>
              </div>
            </form>
          </div>

          <!-- HASIL KARTU PASS, INVOICE & STRUK PEMBAYARAN -->
          <div v-else class="space-y-4">
            <div class="bg-emerald-50 border border-emerald-200/80 p-4 rounded-2xl flex items-center gap-3 no-print">
              <span class="w-8 h-8 rounded-xl bg-emerald-500 text-white font-bold flex items-center justify-center text-sm shadow-xs">✓</span>
              <div>
                <h4 class="text-xs font-black text-emerald-800">Registrasi Berhasil & Lunas!</h4>
                <p class="text-[11px] text-emerald-600 font-medium">Member telah terdaftar. QR Code siap di-scan langsung di palang pintu gate.</p>
              </div>
            </div>

            <!-- AREA STRUK / INVOICE CETAK (DIJADIKAN PDF & PRINT) -->
            <div id="print-area" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-4">
              <!-- Header Invoice -->
              <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                <div class="flex items-center gap-2.5">
                  <div class="w-9 h-9 rounded-xl bg-[#0B0F19] text-cyan-400 font-black flex items-center justify-center text-xs shadow-xs">
                    P
                  </div>
                  <div>
                    <h3 class="text-xs font-black text-slate-900 leading-none uppercase">INVOICE & KARTU MEMBER</h3>
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">PLAZA ANDALAS PARKING</span>
                  </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-300">
                  Lunas (Aktif)
                </span>
              </div>

              <!-- TAMPILAN QR CODE (Bisa Langsung Di-scan di Gate) -->
              <div class="flex justify-center my-3">
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-center">
                  <img
                    :src="qrCodeUrl"
                    alt="QR Code Member"
                    class="w-44 h-44 object-contain"
                    crossorigin="anonymous"
                    @error="handleImageError"
                  />
                </div>
              </div>

              <!-- Rincian Pembayaran & Member -->
              <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2.5 text-xs text-slate-800">
                <div class="flex justify-between items-center py-1 border-b border-slate-200/80">
                  <span class="text-slate-500 font-bold">Kode Member</span>
                  <span class="font-mono font-black text-indigo-600 text-sm">{{ memberCode }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-200/80">
                  <span class="text-slate-500 font-bold">Nama Pelanggan</span>
                  <span class="font-extrabold text-slate-900">{{ suksesData.nama_member }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-200/80">
                  <span class="text-slate-500 font-bold">Instansi / Perusahaan</span>
                  <span class="font-semibold text-slate-700">{{ suksesData.nama_perusahaan }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-200/80">
                  <span class="text-slate-500 font-bold">Biaya Langganan</span>
                  <span class="font-black text-slate-900 font-mono">Rp {{ formatRupiah(TARIF_LANGGANAN) }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-200/80">
                  <span class="text-slate-500 font-bold">Uang Diterima</span>
                  <span class="font-bold text-slate-900 font-mono">Rp {{ formatRupiah(suksesData.uang_diterima || TARIF_LANGGANAN) }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-200/80">
                  <span class="text-slate-500 font-bold">Kembalian (Cashback)</span>
                  <span class="font-black text-emerald-600 font-mono">Rp {{ formatRupiah(suksesData.uang_kembalian || 0) }}</span>
                </div>
                <div class="flex justify-between items-center py-1">
                  <span class="text-slate-500 font-bold">Masa Berlaku (30 Hari)</span>
                  <span class="font-semibold text-slate-800 font-mono">{{ formatDate(suksesData.tanggal_expired) }}</span>
                </div>
              </div>

              <!-- Footer Catatan Invoice -->
              <p class="text-[10px] text-center text-slate-400 italic pt-1">
                Harap simpan bukti pembayaran dan QR Code ini untuk akses gerbang masuk/keluar.
              </p>
            </div>

            <!-- Tombol Aksi Mandiri: Download PDF, Print Struk, Download Gambar, Kembali -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5 no-print">
              <!-- 1. Download PDF Otomatis -->
              <button
                @click="downloadPdfLangsung"
                :disabled="isExportingPdf"
                type="button"
                class="bg-rose-600 hover:bg-rose-700 text-white py-3 rounded-2xl font-black text-xs transition cursor-pointer shadow-xs flex items-center justify-center gap-1.5 disabled:opacity-50"
              >
                <span>📄</span> {{ isExportingPdf ? 'Mengunduh PDF...' : 'Download PDF' }}
              </button>

              <!-- 2. Cetak Struk / Print Fisik -->
              <button
                @click="cetakKePrinter"
                type="button"
                class="bg-[#0B0F19] hover:bg-slate-800 text-white py-3 rounded-2xl font-black text-xs transition cursor-pointer shadow-xs flex items-center justify-center gap-1.5"
              >
                <span>🖨️</span> Cetak Struk
              </button>

              <!-- 3. Download Gambar Kartu PNG -->
              <button
                @click="downloadKartu"
                type="button"
                class="bg-[#0284C7] hover:bg-[#0369A1] text-white py-3 rounded-2xl font-black text-xs transition cursor-pointer shadow-xs flex items-center justify-center gap-1.5"
              >
                <span>💾</span> Kartu PNG
              </button>

              <!-- 4. Kembali -->
              <button
                @click="router.push('/petugas/member/select')"
                type="button"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-3 rounded-2xl font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
              >
                <span>←</span> Ke Daftar
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
@page {
  margin: 0;
  size: auto;
}

@media print {
  aside,
  header,
  .no-print {
    display: none !important;
  }
  body, main {
    height: auto !important;
    overflow: visible !important;
    background: white !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  #print-area {
    border: 1px dashed #94a3b8 !important;
    box-shadow: none !important;
    padding: 20px !important;
    width: 100% !important;
    max-width: 440px !important;
    margin: 15px auto !important;
    page-break-inside: avoid;
  }
}
</style>