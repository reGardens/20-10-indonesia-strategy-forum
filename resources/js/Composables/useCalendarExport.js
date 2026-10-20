/**
 * Composable for generating calendar export files and links.
 * Supports .ics (iCalendar) file generation and Google Calendar URL generation.
 *
 * Event input shape:
 * {
 *   name: 'Akad Nikah',
 *   date: '2024-12-25',
 *   startTime: '08:00',
 *   endTime: '10:00',
 *   venueName: 'Masjid Istiqlal',
 *   address: 'Jl. Taman Wijaya Kusuma, Jakarta Pusat'
 * }
 *
 * @returns {{ generateIcs: (event: object) => string, downloadIcs: (event: object) => void, generateGoogleCalendarUrl: (event: object) => string }}
 */
export function useCalendarExport() {
  /**
   * Format a date string and time string into iCalendar DTSTART/DTEND format.
   * Output: YYYYMMDDTHHMMSS (local time, no timezone suffix)
   *
   * @param {string} date - Date in YYYY-MM-DD format
   * @param {string} time - Time in HH:MM format
   * @returns {string} Formatted datetime string
   */
  function formatIcsDateTime(date, time) {
    if (!date || typeof date !== 'string' || !time || typeof time !== 'string') {
      return ''
    }
    const [year, month, day] = date.split('-')
    const [hours, minutes] = time.split(':')
    if (!year || !month || !day || !hours || !minutes) {
      return ''
    }
    return `${year}${month}${day}T${hours}${minutes}00`
  }

  /**
   * Format a date and time into Google Calendar's required format.
   * Output: YYYYMMDDTHHMMSS (same as ICS but used in URL pairs)
   *
   * @param {string} date - Date in YYYY-MM-DD format
   * @param {string} time - Time in HH:MM format
   * @returns {string} Formatted datetime string
   */
  function formatGoogleDateTime(date, time) {
    return formatIcsDateTime(date, time)
  }

  /**
   * Build the location string from venue name and address.
   *
   * @param {string} venueName
   * @param {string} address
   * @returns {string}
   */
  function buildLocation(venueName, address) {
    const parts = [venueName, address].filter(Boolean)
    return parts.join(', ')
  }

  /**
   * Generate a valid .ics file content string for the given event.
   *
   * @param {object} event - Event data
   * @returns {string} Valid iCalendar (.ics) content
   */
  function generateIcs(event) {
    const date = event.date || ''
    const startTime = event.startTime || event.timeStart || ''
    const endTime = event.endTime || event.timeEnd || ''

    const dtStart = formatIcsDateTime(date, startTime)
    const dtEnd = formatIcsDateTime(date, endTime)
    const location = buildLocation(event.venueName || event.locationName, event.address || event.locationAddress)
    const description = (event.venueName || event.locationName)
      ? `${event.name || 'Acara'} di ${event.venueName || event.locationName}`
      : (event.name || 'Acara')

    const lines = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//Wedding Invitation CMS//EN',
      'BEGIN:VEVENT',
      `DTSTART:${dtStart}`,
      `DTEND:${dtEnd}`,
      `SUMMARY:${event.name || 'Acara'}`,
      `LOCATION:${location}`,
      `DESCRIPTION:${description}`,
      'END:VEVENT',
      'END:VCALENDAR'
    ]

    return lines.join('\r\n')
  }

  /**
   * Trigger a browser download of the .ics file for the given event.
   *
   * @param {object} event - Event data (same shape as generateIcs)
   */
  function downloadIcs(event) {
    const icsContent = generateIcs(event)
    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' })
    const url = URL.createObjectURL(blob)

    const link = document.createElement('a')
    link.href = url
    link.download = `${(event.name || 'event').replace(/\s+/g, '-').toLowerCase()}.ics`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  }

  /**
   * Generate a Google Calendar event creation URL for the given event.
   *
   * @param {object} event - Event data (same shape as generateIcs)
   * @returns {string} Google Calendar URL
   */
  function generateGoogleCalendarUrl(event) {
    const date = event.date || ''
    const startTime = event.startTime || event.timeStart || ''
    const endTime = event.endTime || event.timeEnd || ''

    const start = formatGoogleDateTime(date, startTime)
    const end = formatGoogleDateTime(date, endTime)
    const location = buildLocation(event.venueName || event.locationName, event.address || event.locationAddress)
    const details = (event.venueName || event.locationName)
      ? `${event.name || 'Acara'} di ${event.venueName || event.locationName}`
      : (event.name || 'Acara')

    const params = new URLSearchParams({
      action: 'TEMPLATE',
      text: event.name || 'Acara',
      dates: `${start}/${end}`,
      location,
      details
    })

    return `https://calendar.google.com/calendar/render?${params.toString()}`
  }

  return {
    generateIcs,
    downloadIcs,
    generateGoogleCalendarUrl
  }
}
