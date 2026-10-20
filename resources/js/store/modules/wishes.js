import { wishService } from '@/api/services/wishService'

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
    async fetchWishes({ commit, rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const items = await wishService.getAll(slug)
        commit('SET_ITEMS', items)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat ucapan')
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async submitWish({ commit, rootState }, data) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const item = await wishService.create(slug, data)
        commit('ADD_ITEM', item)
        return item
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal mengirim ucapan')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async updateWishStatus({ commit, rootState }, { id, status }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const updated = await wishService.updateStatus(slug, id, status)
        commit('UPDATE_ITEM', updated)
        return updated
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memperbarui status ucapan')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteWish({ commit, rootState }, id) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        await wishService.delete(slug, id)
        commit('REMOVE_ITEM', id)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menghapus ucapan')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  },

  getters: {
    wishes: (state) => state.items,
    approvedWishes: (state) => state.items.filter((w) => w.status === 'approved'),
    isLoading: (state) => state.loading,
    error: (state) => state.error
  }
}
