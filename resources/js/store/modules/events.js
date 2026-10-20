import { eventService } from '@/api/services/eventService'

export default {
  namespaced: true,

  state: () => ({
    items: [],
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
    UPDATE_ITEM(state, updated) {
      const index = state.items.findIndex((i) => i.id === updated.id)
      if (index !== -1) {
        state.items.splice(index, 1, updated)
      }
    },
    REMOVE_ITEM(state, id) {
      state.items = state.items.filter((i) => i.id !== id)
    },
    SET_LOADING(state, loading) {
      state.loading = loading
    },
    SET_ERROR(state, error) {
      state.error = error
    },
    RESET(state) {
      state.items = []
      state.loading = false
      state.error = null
    }
  },

  actions: {
    async fetchEvents({ commit, rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const items = await eventService.getAll(slug)
        commit('SET_ITEMS', items)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat data acara')
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async createEvent({ commit, rootState }, data) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const item = await eventService.create(slug, data)
        commit('ADD_ITEM', item)
        return item
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal membuat acara')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async updateEvent({ commit, rootState }, { id, data }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const updated = await eventService.update(slug, id, data)
        commit('UPDATE_ITEM', updated)
        return updated
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memperbarui acara')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteEvent({ commit, rootState }, id) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        await eventService.delete(slug, id)
        commit('REMOVE_ITEM', id)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menghapus acara')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  },

  getters: {
    events: (state) => state.items,
    isLoading: (state) => state.loading,
    error: (state) => state.error
  }
}
