import { coupleService } from '@/api/services/coupleService'

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
    async fetchCouple({ commit, rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const data = await coupleService.get(slug)
        commit('SET_DATA', data)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat data mempelai')
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async saveCouple({ commit, rootState }, data) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const saved = await coupleService.save(slug, data)
        commit('SET_DATA', saved)
        return saved
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menyimpan data mempelai')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  },

  getters: {
    couple: (state) => state.data,
    isLoading: (state) => state.loading,
    error: (state) => state.error
  }
}
