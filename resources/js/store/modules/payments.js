import { paymentService } from '@/api/services/paymentService'

export default {
  namespaced: true,

  state: () => ({
    bankAccounts: [],
    gifts: [],
    totalGifts: 0,
    loading: false,
    error: null
  }),

  mutations: {
    SET_BANK_ACCOUNTS(state, accounts) {
      state.bankAccounts = accounts
    },
    ADD_BANK_ACCOUNT(state, account) {
      state.bankAccounts.push(account)
    },
    UPDATE_BANK_ACCOUNT(state, updated) {
      const index = state.bankAccounts.findIndex((a) => a.id === updated.id)
      if (index !== -1) {
        state.bankAccounts.splice(index, 1, updated)
      }
    },
    REMOVE_BANK_ACCOUNT(state, id) {
      state.bankAccounts = state.bankAccounts.filter((a) => a.id !== id)
    },
    SET_GIFTS(state, gifts) {
      state.gifts = gifts
    },
    ADD_GIFT(state, gift) {
      state.gifts.push(gift)
    },
    SET_TOTAL_GIFTS(state, total) {
      state.totalGifts = total
    },
    SET_LOADING(state, loading) {
      state.loading = loading
    },
    SET_ERROR(state, error) {
      state.error = error
    },
    RESET(state) {
      state.bankAccounts = []
      state.gifts = []
      state.totalGifts = 0
      state.loading = false
      state.error = null
    }
  },

  actions: {
    async fetchPayments({ commit, rootState }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const [accounts, gifts, total] = await Promise.all([
          paymentService.getBankAccounts(slug),
          paymentService.getGifts(slug),
          paymentService.getTotal(slug)
        ])
        commit('SET_BANK_ACCOUNTS', accounts)
        commit('SET_GIFTS', gifts)
        commit('SET_TOTAL_GIFTS', total)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memuat data pembayaran')
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async addBankAccount({ commit, rootState }, data) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const account = await paymentService.addBankAccount(slug, data)
        commit('ADD_BANK_ACCOUNT', account)
        return account
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menambah rekening bank')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async updateBankAccount({ commit, rootState }, { id, data }) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const updated = await paymentService.updateBankAccount(slug, id, data)
        commit('UPDATE_BANK_ACCOUNT', updated)
        return updated
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal memperbarui rekening bank')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteBankAccount({ commit, rootState }, id) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        await paymentService.deleteBankAccount(slug, id)
        commit('REMOVE_BANK_ACCOUNT', id)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menghapus rekening bank')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async addGift({ commit, rootState }, data) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        const gift = await paymentService.addGift(slug, data)
        commit('ADD_GIFT', gift)
        const total = await paymentService.getTotal(slug)
        commit('SET_TOTAL_GIFTS', total)
        return gift
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal mencatat hadiah')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async deleteGift({ commit, rootState }, id) {
      const slug = rootState.wedding.activeSlug
      if (!slug) {
        commit('SET_ERROR', 'Tidak ada pernikahan aktif')
        return
      }
      commit('SET_LOADING', true)
      commit('SET_ERROR', null)
      try {
        await paymentService.deleteGift(slug, id)
        commit('SET_GIFTS', (await paymentService.getGifts(slug)))
        const total = await paymentService.getTotal(slug)
        commit('SET_TOTAL_GIFTS', total)
      } catch (error) {
        commit('SET_ERROR', error.message || 'Gagal menghapus hadiah')
        throw error
      } finally {
        commit('SET_LOADING', false)
      }
    }
  },

  getters: {
    bankAccounts: (state) => state.bankAccounts,
    gifts: (state) => state.gifts,
    totalGifts: (state) => state.totalGifts,
    isLoading: (state) => state.loading,
    error: (state) => state.error
  }
}
