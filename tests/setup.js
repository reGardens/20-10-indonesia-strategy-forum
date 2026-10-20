// Test setup file for Vitest
// Configures jsdom environment and global test utilities

// Mock localStorage for service layer tests
const localStorageMock = (() => {
  let store = {}
  return {
    getItem: (key) => store[key] ?? null,
    setItem: (key, value) => { store[key] = String(value) },
    removeItem: (key) => { delete store[key] },
    clear: () => { store = {} },
    get length() { return Object.keys(store).length },
    key: (index) => Object.keys(store)[index] ?? null
  }
})()

Object.defineProperty(globalThis, 'localStorage', {
  value: localStorageMock
})

// Reset localStorage before each test
beforeEach(() => {
  localStorage.clear()
})
