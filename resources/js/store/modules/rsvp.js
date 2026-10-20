import { rsvpService } from '@/api/services/rsvpService'

export default {
  namespaced: true,

  state: () => ({
    items: [],
    summary: { hadir: 0, tidakHadir: 0, mungkin: 0 },
    loading: false,
    error: null
  }),

  mutations: {
    SET_ITEMS(state, items) {
      state.items = items
    },
    ADD_ITEM(state, item) {
      state.items.push(item)
    },
    SET_SUMMARY(state, summary) {
      state.summary = summary
    },
    SET_LOADING(state, loading) {
      state.loading = loading
    },
    SET_ERROR(state, error) {
      state.error = error
    },
    RESET(state) {
      state.items = []
      state.summary = { hadir: 0, tidakHadir: 0, mungkin: 0 }
      state.loading = false
      state.error = null
    }
  },

  actions: {
    async fetchRsvps({ commit, rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const items = await rsvpService.getAll(slug)
        commit('SET_ITEMS', items)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat data RSVP')
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async submitRsvp({ commit, rootState }, data) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const item = await rsvpService.create(slug, data)
        commit('ADD_ITEM', item)
        return item
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal mengirim RSVP')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async getSummary({ commit, rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const summary = await rsvpService.getSummary(slug)
        commit('SET_SUMMARY', summary)
        return summary
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat ringkasan RSVP')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  },

  getters: {
    rsvps: (state) => state.items,
    summary: (state) => state.summary,
    totalRsvps: (state) => state.items.length,
    isLoading: (state) => state.loading,
    error: (state) => state.error
  }
}
