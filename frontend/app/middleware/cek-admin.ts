const normalizeRole = (role: string) => {
  const normalized = (role || '').toLowerCase().trim().replace(/[\s-]+/g, '_')
  return normalized === 'superadmin' ? 'super_admin' : normalized
}

export default defineNuxtRouteMiddleware((to, from) => {
  if (process.client) {
    const token = localStorage.getItem('token')
    const role = normalizeRole(localStorage.getItem('role') || '')

    if (!token) {
      localStorage.removeItem('role')
      alert('Sesi habis atau belum login, silakan login terlebih dahulu!')
      return navigateTo('/')
    }

    const isAdmin = role === 'admin' || role === 'super_admin'
    const isPetugas = role === 'petugas' || isAdmin

    if (to.path.startsWith('/admin') && !isAdmin) {
      alert('Akses ditolak! Kamu bukan admin.')
      return navigateTo('/petugas')
    }

    if (to.path.startsWith('/petugas') && !isPetugas) {
      alert('Akses ditolak! Silakan login sebagai petugas yang sah.')
      return navigateTo('/')
    }
  }
})