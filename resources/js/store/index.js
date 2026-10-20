import { createStore } from 'vuex'
import wedding from './modules/wedding'
import couple from './modules/couple'
import events from './modules/events'
import guests from './modules/guests'
import rsvp from './modules/rsvp'
import wishes from './modules/wishes'
import media from './modules/media'
import payments from './modules/payments'
import settings from './modules/settings'
import template from './modules/template'

const store = createStore({
  modules: {
    wedding,
    couple,
    events,
    guests,
    rsvp,
    wishes,
    media,
    payments,
    settings,
    template
  }
})

export default store
