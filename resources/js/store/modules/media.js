import { mediaService } from '@/api/services/mediaService'

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
    REMOVE_ITEM(state, id) {
      state.items = state.items.filter((i) => i.id !== id)
    },
    REMOVE_ITEMS(state, ids) {
      state.items = state.items.filter((i) => !ids.includes(i.id))
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
    async fetchMedia({ commit, rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const items = await mediaService.getAll(slug)
        commit('SET_ITEMS', items)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat media')
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async addMedia({ commit, rootState }, data) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const item = await mediaService.create(slug, data)
        commit('ADD_ITEM', item)
        return item
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menambah media')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteMedia({ commit, rootState }, id) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        await mediaService.delete(slug, id)
        commit('REMOVE_ITEM', id)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menghapus media')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async bulkDeleteMedia({ commit, rootState }, ids) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        await mediaService.bulkDelete(slug, ids)
        commit('REMOVE_ITEMS', ids)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menghapus media')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async reorderMedia({ commit, rootState }, orderedIds) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const items = await mediaService.reorder(slug, orderedIds)
        commit('SET_ITEMS', items)
        return items
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal mengatur ulang urutan media')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  },

  getters: {
    media: (state) => state.items,
    mediaCount: (state) => state.items.length,
    isLoading: (state) => state.loading,
    error: (state) => state.error
  }
}
