import { settingsService } from '@/api/services/settingsService'

export default {
  namespaced: true,

  state: () => ({
    data: null,
    loading: false,
    error: null
  }),

  mutations: {
    SET_DATA(state, data) {
      state.data = data
    },
    SET_LOADING(state, loading) {
      state.loading = loading
    },
    SET_ERROR(state, error) {
      state.error = error
    },
    RESET(state) {
      state.data = null
      state.loading = false
      state.error = null
    }
  },

  actions: {
    async fetchSettings({ commit, rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const data = await settingsService.get(slug)
        commit('SET_DATA', data)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat pengaturan')
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async saveSettings({ commit, rootState }, data) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const saved = await settingsService.save(slug, data)
        commit('SET_DATA', saved)
        return saved
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menyimpan pengaturan')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  },

  getters: {
    settings: (state) => state.data,
    isLoading: (state) => state.loading,
    error: (state) => state.error
  }
}
