/**
 * Formatting helpers using Bahasa Indonesia locale (id-ID).
 * Used throughout the CMS and invitation app for consistent
 * date, time, and currency display.
 */

const LOCALE = 'id-ID'

/**
 * Formats a date in Bahasa Indonesia long format.
 * Example: "25 Desember 2024"
 *
 * @param {string|Date} date - The date to format
 * @returns {string} Formatted date string, or empty string if invalid
 */
export function formatDate(date) {
  if (date === null || date === undefined) {
    return ''
  }
  const d = date instanceof Date ? date : new Date(date)
  if (Number.isNaN(d.getTime())) {
    return ''
  }
  return d.toLocaleDateString(LOCALE, {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

/**
 * Formats a time string for display.
 * Example: "08:00 WIB"
 *
 * @param {string} time - Time in "HH:mm" format
 * @param {string} [timezone='WIB'] - Timezone label to append
 * @returns {string} Formatted time string, or empty string if invalid
 */
export function formatTime(time, timezone = 'WIB') {
  if (!time || typeof time !== 'string') {
    return ''
  }
  const parts = time.split(':')
  if (parts.length < 2) {
    return ''
  }
  const hours = parts[0].padStart(2, '0')
  const minutes = parts[1].padStart(2, '0')
  return `${hours}:${minutes} ${timezone}`
}

/**
 * Formats a number as Indonesian Rupiah currency.
 * Example: "Rp 500.000"
 *
 * @param {number} amount - The amount to format
 * @returns {string} Formatted currency string, or empty string if invalid
 */
export function formatCurrency(amount) {
  if (typeof amount !== 'number' || Number.isNaN(amount)) {
    return ''
  }
  return new Intl.NumberFormat(LOCALE, {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

/**
 * Formats a date and time range for event display.
 * Example: "25 Desember 2024, 08:00 - 10:00 WIB"
 *
 * @param {string|Date} date - The event date
 * @param {string} startTime - Start time in "HH:mm" format
 * @param {string} [endTime] - End time in "HH:mm" format
 * @param {string} [timezone='WIB'] - Timezone label
 * @returns {string} Formatted date-time range string
 */
export function formatEventDateTime(date, startTime, endTime, timezone = 'WIB') {
  const formattedDate = formatDate(date)
  if (!formattedDate) {
    return ''
  }

  const start = formatTime(startTime, '')
  if (!start) {
    return formattedDate
  }

  if (endTime) {
    const end = formatTime(endTime, '')
    if (end) {
      return `${formattedDate}, ${start} - ${end} ${timezone}`.trim()
    }
  }

  return `${formattedDate}, ${start} ${timezone}`.trim()
}
