/**
 * Composable for encoding and decoding guest QR code data.
 *
 * The QR code string is a base64-encoded JSON payload containing guestId,
 * weddingSlug, and a checksum derived from those two values. The checksum
 * prevents casual tampering — decoding rejects payloads whose checksum
 * does not match the recomputed value.
 *
 * @returns {{ encode: (data: QrCodeData) => string, decode: (qrString: string) => QrCodeData | null }}
 */
export function useQrCode() {
  /**
   * Compute a simple numeric checksum from a string.
   * Uses a basic hash (similar to Java's String.hashCode) converted to
   * an unsigned 32-bit hex string so it is deterministic and portable.
   *
   * @param {string} input
   * @returns {string} hex checksum
   */
  function computeChecksum(input) {
    let hash = 0
    for (let i = 0; i < input.length; i++) {
      const char = input.charCodeAt(i)
      hash = ((hash << 5) - hash + char) | 0 // force 32-bit int
    }
    return (hash >>> 0).toString(16) // unsigned hex
  }

  /**
   * Encode guest identification data into a QR code string.
   *
   * @param {{ guestId: string, weddingSlug: string, checksum?: string }} data
   * @returns {string} base64-encoded QR code string
   */
  function encode(data) {
    if (!data || !data.guestId || !data.weddingSlug) {
      return ''
    }

    const checksum = data.checksum || computeChecksum(data.guestId + data.weddingSlug)

    const payload = JSON.stringify({
      guestId: data.guestId,
      weddingSlug: data.weddingSlug,
      checksum
    })

    return btoa(payload)
  }

  /**
   * Decode a QR code string back to guest identification data.
   * Returns null when the string is invalid or the checksum does not match.
   *
   * @param {string} qrString
   * @returns {{ guestId: string, weddingSlug: string, checksum: string } | null}
   */
  function decode(qrString) {
    if (!qrString || typeof qrString !== 'string') {
      return null
    }

    try {
      const json = atob(qrString)
      const parsed = JSON.parse(json)

      if (!parsed.guestId || !parsed.weddingSlug || !parsed.checksum) {
        return null
      }

      // Verify checksum to detect tampering
      const expectedChecksum = computeChecksum(parsed.guestId + parsed.weddingSlug)
      if (parsed.checksum !== expectedChecksum) {
        return null
      }

      return {
        guestId: parsed.guestId,
        weddingSlug: parsed.weddingSlug,
        checksum: parsed.checksum
      }
    } catch {
      return null
    }
  }

  return {
    encode,
    decode
  }
}
