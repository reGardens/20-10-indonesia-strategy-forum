import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { useClipboard } from '../../../src/composables/useClipboard.js'

describe('useClipboard', () => {
  let originalClipboard

  beforeEach(() => {
    originalClipboard = navigator.clipboard
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
    // Restore original clipboard
    if (originalClipboard) {
      Object.defineProperty(navigator, 'clipboard', {
        value: originalClipboard,
        writable: true,
        configurable: true
      })
    }
  })

  function mockClipboardAPI(writeTextFn) {
    Object.defineProperty(navigator, 'clipboard', {
      value: { writeText: writeTextFn },
      writable: true,
      configurable: true
    })
  }

  function removeClipboardAPI() {
    Object.defineProperty(navigator, 'clipboard', {
      value: undefined,
      writable: true,
      configurable: true
    })
  }

  describe('initial state', () => {
    it('returns copy function, copied ref, and error ref', () => {
      const { copy, copied, error } = useClipboard()
      expect(typeof copy).toBe('function')
      expect(copied.value).toBe(false)
      expect(error.value).toBe('')
    })
  })

  describe('copy with Clipboard API', () => {
    it('copies text successfully and returns true', async () => {
      const writeText = vi.fn().mockResolvedValue(undefined)
      mockClipboardAPI(writeText)

      const { copy, copied, error } = useClipboard()
      const result = await copy('1234567890')

      expect(result).toBe(true)
      expect(copied.value).toBe(true)
      expect(error.value).toBe('')
      expect(writeText).toHaveBeenCalledWith('1234567890')
    })

    it('copies bank account number', async () => {
      const writeText = vi.fn().mockResolvedValue(undefined)
      mockClipboardAPI(writeText)

      const { copy } = useClipboard()
      await copy('0987654321')

      expect(writeText).toHaveBeenCalledWith('0987654321')
    })

    it('copies invitation URL', async () => {
      const writeText = vi.fn().mockResolvedValue(undefined)
      mockClipboardAPI(writeText)

      const { copy } = useClipboard()
      const url = 'https://example.com/wedding/budi-ani?to=Ahmad+Fauzi'
      await copy(url)

      expect(writeText).toHaveBeenCalledWith(url)
    })

    it('sets error and returns false when Clipboard API fails', async () => {
      const writeText = vi.fn().mockRejectedValue(new Error('Permission denied'))
      mockClipboardAPI(writeText)

      const { copy, copied, error } = useClipboard()
      const result = await copy('test')

      expect(result).toBe(false)
      expect(copied.value).toBe(false)
      expect(error.value).toBe('Permission denied')
    })
  })

  describe('fallback for older browsers', () => {
    beforeEach(() => {
      // jsdom doesn't define execCommand, so we add it for fallback tests
      document.execCommand = vi.fn()
    })

    afterEach(() => {
      delete document.execCommand
    })

    it('uses fallback when Clipboard API is unavailable', async () => {
      removeClipboardAPI()
      document.execCommand.mockReturnValue(true)

      const { copy, copied } = useClipboard()
      const result = await copy('fallback-text')

      expect(result).toBe(true)
      expect(copied.value).toBe(true)
      expect(document.execCommand).toHaveBeenCalledWith('copy')
    })

    it('returns false when fallback execCommand fails', async () => {
      removeClipboardAPI()
      document.execCommand.mockReturnValue(false)

      const { copy, copied, error } = useClipboard()
      const result = await copy('fail-text')

      expect(result).toBe(false)
      expect(copied.value).toBe(false)
      expect(error.value).toContain('Failed to copy')
    })
  })

  describe('copied auto-reset', () => {
    it('resets copied to false after ~2 seconds', async () => {
      const writeText = vi.fn().mockResolvedValue(undefined)
      mockClipboardAPI(writeText)

      const { copy, copied } = useClipboard()
      await copy('test')

      expect(copied.value).toBe(true)

      vi.advanceTimersByTime(2000)

      expect(copied.value).toBe(false)
    })

    it('does not reset copied before 2 seconds', async () => {
      const writeText = vi.fn().mockResolvedValue(undefined)
      mockClipboardAPI(writeText)

      const { copy, copied } = useClipboard()
      await copy('test')

      vi.advanceTimersByTime(1999)
      expect(copied.value).toBe(true)
    })

    it('resets timeout when copy is called again', async () => {
      const writeText = vi.fn().mockResolvedValue(undefined)
      mockClipboardAPI(writeText)

      const { copy, copied } = useClipboard()

      await copy('first')
      expect(copied.value).toBe(true)

      vi.advanceTimersByTime(1500)
      expect(copied.value).toBe(true)

      // Copy again before timeout expires
      await copy('second')
      expect(copied.value).toBe(true)

      // Original timeout would have fired at 2000ms, but it was cleared
      vi.advanceTimersByTime(1500)
      expect(copied.value).toBe(true)

      // New timeout fires at 2000ms from second copy
      vi.advanceTimersByTime(500)
      expect(copied.value).toBe(false)
    })
  })

  describe('error state', () => {
    it('clears error on successful copy after a failed one', async () => {
      const writeText = vi.fn()
        .mockRejectedValueOnce(new Error('First failure'))
        .mockResolvedValueOnce(undefined)
      mockClipboardAPI(writeText)

      const { copy, error } = useClipboard()

      await copy('fail')
      expect(error.value).toBe('First failure')

      await copy('success')
      expect(error.value).toBe('')
    })

    it('handles error without message property', async () => {
      const writeText = vi.fn().mockRejectedValue({ notAMessage: true })
      mockClipboardAPI(writeText)

      const { copy, error } = useClipboard()
      await copy('test')

      expect(error.value).toBe('Failed to copy to clipboard')
    })
  })

  describe('empty and special text', () => {
    it('copies empty string', async () => {
      const writeText = vi.fn().mockResolvedValue(undefined)
      mockClipboardAPI(writeText)

      const { copy } = useClipboard()
      const result = await copy('')

      expect(result).toBe(true)
      expect(writeText).toHaveBeenCalledWith('')
    })

    it('copies text with special characters', async () => {
      const writeText = vi.fn().mockResolvedValue(undefined)
      mockClipboardAPI(writeText)

      const { copy } = useClipboard()
      const text = 'Bpk. Ahmad & Ibu Siti — Rek: 123-456'
      const result = await copy(text)

      expect(result).toBe(true)
      expect(writeText).toHaveBeenCalledWith(text)
    })
  })
})
