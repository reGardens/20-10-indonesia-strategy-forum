import { reactive, ref } from 'vue'
import { rsvpService } from '@/api/services/rsvpService.js'
import { wishService } from '@/api/services/wishService.js'
import { settingsService } from '@/api/services/settingsService.js'

/**
 * RSVP form composable for the guest-facing invitation.
 * Manages form state, validation, and submission to rsvpService and wishService.
 *
 * @returns {{
 *   form: { name: string, status: string, numberOfGuests: number, message: string },
 *   errors: { name: string, status: string },
 *   submitting: import('vue').Ref<boolean>,
 *   submitted: import('vue').Ref<boolean>,
 *   submitRsvp: () => Promise<void>,
 *   submitWish: () => Promise<void>
 * }}
 */
export function useRsvpForm() {
  const form = reactive({
    name: '',
    status: '',
    numberOfGuests: 1,
    message: ''
  })

  const errors = reactive({
    name: '',
    status: ''
  })

  const submitting = ref(false)
  const submitted = ref(false)

  /**
   * Validate the form fields.
   * @returns {boolean} true if valid
   */
  function validate() {
    let valid = true
    errors.name = ''
    errors.status = ''

    if (!form.name || !form.name.trim()) {
      errors.name = 'Nama wajib diisi'
      valid = false
    }

    if (!form.status) {
      errors.status = 'Status kehadiran wajib dipilih'
      valid = false
    }

    return valid
  }

  /**
   * Submit the RSVP form to rsvpService.
   */
  async function submitRsvp() {
    if (!validate()) return

    submitting.value = true
    try {
      await rsvpService.create({
        guestName: form.name.trim(),
        status: form.status,
        numberOfGuests: form.numberOfGuests
      })
      submitted.value = true
    } finally {
      submitting.value = false
    }
  }

  /**
   * Submit a wish/message to wishService.
   * Respects the moderation setting from settingsService.
   */
  async function submitWish() {
    if (!form.name || !form.name.trim()) {
      errors.name = 'Nama wajib diisi'
      return
    }
    if (!form.message || !form.message.trim()) {
      return
    }

    submitting.value = true
    try {
      const settings = await settingsService.get()
      const wishStatus = settings.moderationEnabled ? 'pending' : 'approved'

      await wishService.create({
        guestName: form.name.trim(),
        message: form.message.trim(),
        status: wishStatus
      })
    } finally {
      submitting.value = false
    }
  }

  return {
    form,
    errors,
    submitting,
    submitted,
    submitRsvp,
    submitWish
  }
}
