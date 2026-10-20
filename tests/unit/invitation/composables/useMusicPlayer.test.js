import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// Track onUnmounted cleanup
let musicPlayerCleanup = null

vi.mock('vue', async () => {
  const actual = await vi.importActual('vue')
  return {
    ...actual,
    onUnmounted: vi.fn((cb) => {
      musicPlayerCleanup = cb
    })
  }
})

// Mock Audio
class MockAudio {
  constructor(src) {
    this.src = src
    this.loop = false
    this.paused = true
    this._playResolve = true
  }

  play() {
    if (this._playResolve) {
      this.paused = false
      return Promise.resolve()
    }
    return Promise.reject(new Error('Autoplay blocked'))
  }

  pause() {
    this.paused = true
  }
}

globalThis.Audio = MockAudio

import { useMusicPlayer } from '../../../../src/invitation/composables/useMusicPlayer.js'

describe('useMusicPlayer', () => {
  beforeEach(() => {
    musicPlayerCleanup = null
  })

  afterEach(() => {
    if (musicPlayerCleanup) musicPlayerCleanup()
  })

  it('initializes with isPlaying false', () => {
    const { isPlaying } = useMusicPlayer('/audio/test.mp3')
    expect(isPlaying.value).toBe(false)
  })

  it('creates an Audio element with the given src', () => {
    const { audioElement } = useMusicPlayer('/audio/test.mp3')
    expect(audioElement).toBeInstanceOf(MockAudio)
    expect(audioElement.src).toBe('/audio/test.mp3')
    expect(audioElement.loop).toBe(true)
  })

  it('toggle plays when not playing', async () => {
    const { isPlaying, toggle } = useMusicPlayer('/audio/test.mp3')
    expect(isPlaying.value).toBe(false)

    toggle()
    // Wait for the play promise to resolve
    await vi.waitFor(() => expect(isPlaying.value).toBe(true))
  })

  it('toggle pauses when playing', async () => {
    const { isPlaying, toggle } = useMusicPlayer('/audio/test.mp3')

    // Start playing
    toggle()
    await vi.waitFor(() => expect(isPlaying.value).toBe(true))

    // Pause
    toggle()
    expect(isPlaying.value).toBe(false)
  })

  it('autoplay triggers on first document click', async () => {
    const { isPlaying } = useMusicPlayer('/audio/test.mp3')
    expect(isPlaying.value).toBe(false)

    // Simulate first user interaction
    document.dispatchEvent(new Event('click'))
    await vi.waitFor(() => expect(isPlaying.value).toBe(true))
  })

  it('cleans up event listeners and audio on unmount', async () => {
    const { isPlaying, audioElement } = useMusicPlayer('/audio/test.mp3')

    // Start playing
    document.dispatchEvent(new Event('click'))
    await vi.waitFor(() => expect(isPlaying.value).toBe(true))

    // Trigger cleanup
    if (musicPlayerCleanup) musicPlayerCleanup()

    expect(isPlaying.value).toBe(false)
    expect(audioElement.src).toBe('')
  })
})
