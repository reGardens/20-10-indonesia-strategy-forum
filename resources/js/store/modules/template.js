import axios from 'axios'

export default {
  namespaced: true,

  state: () => ({
    selectedId: 'jawa-klasik',
    templates: [],
    loading: false
  }),

  mutations: {
    SET_SELECTED_ID(state, id) {
      state.selectedId = id
    },
    SET_TEMPLATES(state, templates) {
      state.templates = templates
    },
    SET_LOADING(state, loading) {
      state.loading = loading
    }
  },

  actions: {
    async fetchTemplates({ commit }) {
      commit('SET_LOADING', true)
      try {
        const response = await axios.get('/api/templates')
        commit('SET_TEMPLATES', response.data)
      } catch (error) {
        console.error('Failed to fetch templates:', error)
      } finally {
        commit('SET_LOADING', false)
      }
    },

    selectTemplate({ commit }, templateId) {
      commit('SET_SELECTED_ID', templateId)
    }
  },

  getters: {
    selectedId: (state) => state.selectedId,
    templates: (state) => state.templates,
    selectedTemplate: (state) =>
      state.templates.find((t) => t.id === state.selectedId) || null,
    isLoading: (state) => state.loading
  }
}
