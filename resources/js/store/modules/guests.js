import { guestService } from '@/api/services/guestService'

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
    async fetchGuests({ commit, rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const items = await guestService.getAll(slug)
        commit('SET_ITEMS', items)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat daftar tamu')
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async createGuest({ commit, rootState }, data) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const item = await guestService.create(slug, data)
        commit('ADD_ITEM', item)
        return item
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menambah tamu')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async updateGuest({ commit, rootState }, { id, data }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const updated = await guestService.update(slug, id, data)
        commit('UPDATE_ITEM', updated)
        return updated
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memperbarui data tamu')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteGuest({ commit, rootState }, id) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        await guestService.delete(slug, id)
        commit('REMOVE_ITEM', id)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menghapus tamu')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async importGuests({ commit, rootState }, file) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const items = await guestService.importFromExcel(slug, file)
        commit('SET_ITEMS', items)
        return items
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal mengimpor data tamu')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async exportGuests({ rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        throw new Error('Tidak ada pernikahan aktif')
      }
      try {
        return await guestService.exportToExcel(slug)
      } catch (error) {
        throw error
      }
    },

    async searchGuests({ commit, rootState }, query) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const items = await guestService.search(slug, query)
        commit('SET_ITEMS', items)
        return items
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal mencari tamu')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  },

  getters: {
    guests: (state) => state.items,
    guestCount: (state) => state.items.length,
    isLoading: (state) => state.loading,
    error: (state) => state.error
  }
}
