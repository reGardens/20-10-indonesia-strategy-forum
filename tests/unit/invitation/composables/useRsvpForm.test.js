import { describe, it, expect, vi, beforeEach } from 'vitest'

// Mock Vue lifecycle hooks
vi.mock('vue', async () => {
  const actual = await vi.importActual('vue')
  return {
    ...actual,
    onUnmounted: vi.fn()
  }
})

// Mock services
vi.mock('../../../../src/api/services/rsvpService.js', () => ({
  rsvpService: {
    create: vi.fn().mockResolvedValue({ id: 'rsvp-1' })
  }
}))

vi.mock('../../../../src/api/services/wishService.js', () => ({
  wishService: {
    create: vi.fn().mockResolvedValue({ id: 'wish-1' })
  }
}))

vi.mock('../../../../src/api/services/settingsService.js', () => ({
  settingsService: {
    get: vi.fn().mockResolvedValue({ moderationEnabled: true })
  }
}))

import { useRsvpForm } from '../../../../src/invitation/composables/useRsvpForm.js'
import { rsvpService } from '../../../../src/api/services/rsvpService.js'
import { wishService } from '../../../../src/api/services/wishService.js'
import { settingsService } from '../../../../src/api/services/settingsService.js'

describe('useRsvpForm', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('initializes with default form state', () => {
    const { form, errors, submitting, submitted } = useRsvpForm()

    expect(form.name).toBe('')
    expect(form.status).toBe('')
    expect(form.numberOfGuests).toBe(1)
    expect(form.message).toBe('')
    expect(errors.name).toBe('')
    expect(errors.status).toBe('')
    expect(submitting.value).toBe(false)
    expect(submitted.value).toBe(false)
  })

  describe('submitRsvp', () => {
    it('validates name is required', async () => {
      const { form, errors, submitRsvp } = useRsvpForm()
      form.status = 'Hadir'

      await submitRsvp()

      expect(errors.name).toBe('Nama wajib diisi')
      expect(rsvpService.create).not.toHaveBeenCalled()
    })

    it('validates status is required', async () => {
      const { form, errors, submitRsvp } = useRsvpForm()
      form.name = 'Ahmad'

      await submitRsvp()

      expect(errors.status).toBe('Status kehadiran wajib dipilih')
      expect(rsvpService.create).not.toHaveBeenCalled()
    })

    it('submits valid RSVP to rsvpService', async () => {
      const { form, submitted, submitRsvp } = useRsvpForm()
      form.name = 'Ahmad Fauzi'
      form.status = 'Hadir'
      form.numberOfGuests = 2

      await submitRsvp()

      expect(rsvpService.create).toHaveBeenCalledWith({
        guestName: 'Ahmad Fauzi',
        status: 'Hadir',
        numberOfGuests: 2
      })
      expect(submitted.value).toBe(true)
    })

    it('trims whitespace from name', async () => {
      const { form, submitRsvp } = useRsvpForm()
      form.name = '  Ahmad Fauzi  '
      form.status = 'Hadir'

      await submitRsvp()

      expect(rsvpService.create).toHaveBeenCalledWith(
        expect.objectContaining({ guestName: 'Ahmad Fauzi' })
      )
    })

    it('rejects whitespace-only name', async () => {
      const { form, errors, submitRsvp } = useRsvpForm()
      form.name = '   '
      form.status = 'Hadir'

      await submitRsvp()

      expect(errors.name).toBe('Nama wajib diisi')
      expect(rsvpService.create).not.toHaveBeenCalled()
    })
  })

  describe('submitWish', () => {
    it('submits wish with pending status when moderation is enabled', async () => {
      settingsService.get.mockResolvedValue({ moderationEnabled: true })

      const { form, submitWish } = useRsvpForm()
      form.name = 'Ahmad'
      form.message = 'Selamat!'

      await submitWish()

      expect(wishService.create).toHaveBeenCalledWith({
        guestName: 'Ahmad',
        message: 'Selamat!',
        status: 'pending'
      })
    })

    it('submits wish with approved status when moderation is disabled', async () => {
      settingsService.get.mockResolvedValue({ moderationEnabled: false })

      const { form, submitWish } = useRsvpForm()
      form.name = 'Ahmad'
      form.message = 'Selamat!'

      await submitWish()

      expect(wishService.create).toHaveBeenCalledWith({
        guestName: 'Ahmad',
        message: 'Selamat!',
        status: 'approved'
      })
    })

    it('validates name is required for wish', async () => {
      const { form, errors, submitWish } = useRsvpForm()
      form.message = 'Selamat!'

      await submitWish()

      expect(errors.name).toBe('Nama wajib diisi')
      expect(wishService.create).not.toHaveBeenCalled()
    })

    it('does not submit if message is empty', async () => {
      const { form, submitWish } = useRsvpForm()
      form.name = 'Ahmad'
      form.message = ''

      await submitWish()

      expect(wishService.create).not.toHaveBeenCalled()
    })
  })
})
