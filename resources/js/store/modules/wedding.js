import { weddingRegistryService } from '@/api/services/weddingRegistryService'
import axios from 'axios'

export default {
  namespaced: true,

  state: () => ({
    activeSlug: null,
    registry: [],
    loading: false,
    error: null,
    userProfile: null
  }),

  mutations: {
    SET_ACTIVE_SLUG(state, slug) {
      state.activeSlug = slug
    },
    SET_REGISTRY(state, registry) {
      state.registry = registry
    },
    SET_LOADING(state, loading) {
      state.loading = loading
    },
    SET_ERROR(state, error) {
      state.error = error
    },
    SET_USER_PROFILE(state, profile) {
      state.userProfile = profile
    }
  },

  actions: {
    async fetchRegistry({ commit }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const registry = await weddingRegistryService.getAll()
        commit('SET_REGISTRY', registry)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat daftar pernikahan')
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async setActiveWedding({ commit, dispatch, state }, slug) {
      const exists = state.registry.some(w => w.slug === slug)
      if (!exists) {
        commit('SET_ERROR', 'Pernikahan tidak ditemukan dalam registri')
        return
      }
      commit('SET_ACTIVE_SLUG', slug)
      dispatch('resetAllModules')
    },

    async createWedding({ commit, dispatch }, { slug, label }) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        await weddingRegistryService.create({ slug, label })
        await dispatch('fetchRegistry')
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal membuat pernikahan baru')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteWedding({ commit, dispatch, state }, slug) {
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        await weddingRegistryService.delete(slug)
        if (state.activeSlug === slug) {
          commit('SET_ACTIVE_SLUG', null)
        }
        await dispatch('fetchRegistry')
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menghapus pernikahan')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async fetchUserProfile({ commit }) {
      try {
        const response = await axios.get('/api/user/profile')
        commit('SET_USER_PROFILE', response.data)
      } catch (error) {
        console.error('Gagal memuat profil user', error)
      }
    },

    resetAllModules({ commit }) {
      commit('couple/RESET', null, { root: true })
      commit('events/RESET', null, { root: true })
      commit('guests/RESET', null, { root: true })
      commit('rsvp/RESET', null, { root: true })
      commit('wishes/RESET', null, { root: true })
      commit('media/RESET', null, { root: true })
      commit('payments/RESET', null, { root: true })
      commit('settings/RESET', null, { root: true })
    }
  },

  getters: {
    activeSlug: (state) => state.activeSlug,
    registry: (state) => state.registry,
    activeWedding: (state) => state.registry.find(w => w.slug === state.activeSlug) || null,
    userProfile: (state) => state.userProfile,
    allowedFeatures: (state) => state.userProfile?.allowed_features || []
  }
}
