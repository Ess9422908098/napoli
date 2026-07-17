import { defineStore } from 'pinia'
import http from '../api/http'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('erp_token') || null,
    user: JSON.parse(localStorage.getItem('erp_user') || 'null'),
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    role: (state) => state.user?.role,
    permissions: (state) => state.user?.permissions || [],
  },

  actions: {
    hasPermission(permission) {
      return this.role === 'admin' || this.permissions.includes(permission)
    },

    hasRole(...roles) {
      return this.role === 'admin' || roles.includes(this.role)
    },

    async login(username, password) {
      const { data } = await http.post('/login', { username, password })
      this.setSession(data.token, data.user)
      return data.user
    },

    async fetchMe() {
      const { data } = await http.get('/me')
      this.user = data.user
      localStorage.setItem('erp_user', JSON.stringify(data.user))
      return data.user
    },

    async logout() {
      try {
        await http.post('/logout')
      } finally {
        this.clearSession()
      }
    },

    setSession(token, user) {
      this.token = token
      this.user = user
      localStorage.setItem('erp_token', token)
      localStorage.setItem('erp_user', JSON.stringify(user))
    },

    clearSession() {
      this.token = null
      this.user = null
      localStorage.removeItem('erp_token')
      localStorage.removeItem('erp_user')
    },
  },
})
