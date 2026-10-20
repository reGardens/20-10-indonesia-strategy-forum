/**
 * URL encoding/decoding utilities for guest names.
 * Handles spaces, special characters, and Unicode characters
 * commonly found in Indonesian names.
 */

/**
 * Encodes a guest name for use in a URL parameter.
 * Uses encodeURIComponent for full Unicode support, then
 * replaces %20 with + for cleaner URLs (standard query param encoding).
 *
 * @param {string} name - The guest name to encode
 * @returns {string} URL-encoded guest name
 */
export function encodeGuestName(name) {
  if (!name || typeof name !== 'string') {
    return ''
  }
  return encodeURIComponent(name).replace(/%20/g, '+')
}

/**
 * Decodes a URL-encoded guest name back to the original string.
 * Reverses the encoding done by encodeGuestName.
 *
 * @param {string} encoded - The URL-encoded guest name
 * @returns {string} Decoded guest name
 */
export function decodeGuestName(encoded) {
  if (!encoded || typeof encoded !== 'string') {
    return ''
  }
  return decodeURIComponent(encoded.replace(/\+/g, '%20'))
}
