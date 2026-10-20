import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// Mock Vue lifecycle hooks since we're testing outside a component
vi.mock('vue', async () => {
  const actual = await vi.importActual('vue')
  return {
    ...actual,
    onUnmounted: vi.fn((cb) => {
      // Store the cleanup callback for manual invocation in tests
      useCountdownCleanup = cb
    })
  }
})

let useCountdownCleanup = null

import { useCountdown } from '../../../../src/invitation/composables/useCountdown.js'

describe('useCountdown', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    useCountdownCleanup = null
  })

  afterEach(() => {
    if (useCountdownCleanup) useCountdownCleanup()
    vi.useRealTimers()
  })

  it('returns correct countdown values for a future date', () => {
    const now = new Date('2024-06-01T00:00:00Z')
    vi.setSystemTime(now)

    // Target: 2 days, 3 hours, 4 minutes, 5 seconds from now
    const target = new Date('2024-06-03T03:04:05Z')
    const { days, hours, minutes, seconds, isExpired } = useCountdown(target)

    expect(days.value).toBe(2)
    expect(hours.value).toBe(3)
    expect(minutes.value).toBe(4)
    expect(seconds.value).toBe(5)
    expect(isExpired.value).toBe(false)
  })

  it('accepts a date string', () => {
    const now = new Date('2024-06-01T00:00:00Z')
    vi.setSystemTime(now)

    const { days, isExpired } = useCountdown('2024-06-02T00:00:00Z')

    expect(days.value).toBe(1)
    expect(isExpired.value).toBe(false)
  })

  it('shows expired when target date is in the past', () => {
    const now = new Date('2024-06-10T00:00:00Z')
    vi.setSystemTime(now)

    const { days, hours, minutes, seconds, isExpired } = useCountdown('2024-06-01T00:00:00Z')

    expect(days.value).toBe(0)
    expect(hours.value).toBe(0)
    expect(minutes.value).toBe(0)
    expect(seconds.value).toBe(0)
    expect(isExpired.value).toBe(true)
  })

  it('updates every second', () => {
    const now = new Date('2024-06-01T00:00:00Z')
    vi.setSystemTime(now)

    const { seconds } = useCountdown('2024-06-01T00:01:00Z')

    // Initially 60 seconds away
    expect(seconds.value).toBe(0) // 0 days, 0 hours, 1 minute, 0 seconds
    // Actually: diff = 60s => 0 days, 0 hours, 1 min, 0 sec

    // Advance 1 second
    vi.advanceTimersByTime(1000)
    // Now 59 seconds away => 0 days, 0 hours, 0 min, 59 sec
    expect(seconds.value).toBe(59)
  })

  it('transitions to expired when countdown reaches zero', () => {
    const now = new Date('2024-06-01T00:00:00Z')
    vi.setSystemTime(now)

    const { seconds, isExpired } = useCountdown('2024-06-01T00:00:03Z')

    expect(isExpired.value).toBe(false)
    expect(seconds.value).toBe(3)

    // Advance 3 seconds
    vi.advanceTimersByTime(3000)
    expect(isExpired.value).toBe(true)
    expect(seconds.value).toBe(0)
  })

  it('cleans up interval on unmount', () => {
    const now = new Date('2024-06-01T00:00:00Z')
    vi.setSystemTime(now)

    const { seconds } = useCountdown('2024-06-01T01:00:00Z')
    const initialSeconds = seconds.value

    // Trigger cleanup
    if (useCountdownCleanup) useCountdownCleanup()

    // Advance time — values should NOT change after cleanup
    vi.advanceTimersByTime(5000)
    expect(seconds.value).toBe(initialSeconds)
  })
})
