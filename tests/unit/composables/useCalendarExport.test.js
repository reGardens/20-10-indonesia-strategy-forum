import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { useCalendarExport } from '../../../src/composables/useCalendarExport.js'

const SAMPLE_EVENT = {
  name: 'Akad Nikah',
  date: '2024-12-25',
  startTime: '08:00',
  endTime: '10:00',
  venueName: 'Masjid Istiqlal',
  address: 'Jl. Taman Wijaya Kusuma, Jakarta Pusat'
}

describe('useCalendarExport', () => {
  describe('generateIcs', () => {
    it('generates valid iCalendar content with all fields', () => {
      const { generateIcs } = useCalendarExport()
      const ics = generateIcs(SAMPLE_EVENT)

      expect(ics).toContain('BEGIN:VCALENDAR')
      expect(ics).toContain('VERSION:2.0')
      expect(ics).toContain('PRODID:-//Wedding Invitation CMS//EN')
      expect(ics).toContain('BEGIN:VEVENT')
      expect(ics).toContain('DTSTART:20241225T080000')
      expect(ics).toContain('DTEND:20241225T100000')
      expect(ics).toContain('SUMMARY:Akad Nikah')
      expect(ics).toContain('LOCATION:Masjid Istiqlal, Jl. Taman Wijaya Kusuma, Jakarta Pusat')
      expect(ics).toContain('DESCRIPTION:Akad Nikah di Masjid Istiqlal')
      expect(ics).toContain('END:VEVENT')
      expect(ics).toContain('END:VCALENDAR')
    })

    it('uses CRLF line endings per RFC 5545', () => {
      const { generateIcs } = useCalendarExport()
      const ics = generateIcs(SAMPLE_EVENT)
      const lines = ics.split('\r\n')
      expect(lines[0]).toBe('BEGIN:VCALENDAR')
      expect(lines[lines.length - 1]).toBe('END:VCALENDAR')
    })

    it('handles event without venueName', () => {
      const { generateIcs } = useCalendarExport()
      const event = { ...SAMPLE_EVENT, venueName: '', address: 'Some Address' }
      const ics = generateIcs(event)

      expect(ics).toContain('LOCATION:Some Address')
      expect(ics).toContain('DESCRIPTION:Akad Nikah')
    })

    it('handles event without address', () => {
      const { generateIcs } = useCalendarExport()
      const event = { ...SAMPLE_EVENT, address: '' }
      const ics = generateIcs(event)

      expect(ics).toContain('LOCATION:Masjid Istiqlal')
      expect(ics).toContain('DESCRIPTION:Akad Nikah di Masjid Istiqlal')
    })

    it('handles event without venueName and address', () => {
      const { generateIcs } = useCalendarExport()
      const event = { ...SAMPLE_EVENT, venueName: '', address: '' }
      const ics = generateIcs(event)

      expect(ics).toContain('LOCATION:')
      expect(ics).toContain('DESCRIPTION:Akad Nikah')
    })

    it('formats different dates and times correctly', () => {
      const { generateIcs } = useCalendarExport()
      const event = {
        ...SAMPLE_EVENT,
        date: '2025-01-15',
        startTime: '14:30',
        endTime: '17:00'
      }
      const ics = generateIcs(event)

      expect(ics).toContain('DTSTART:20250115T143000')
      expect(ics).toContain('DTEND:20250115T170000')
    })
  })

  describe('generateGoogleCalendarUrl', () => {
    it('generates a valid Google Calendar URL', () => {
      const { generateGoogleCalendarUrl } = useCalendarExport()
      const url = generateGoogleCalendarUrl(SAMPLE_EVENT)

      expect(url).toContain('https://calendar.google.com/calendar/render?')
      expect(url).toContain('action=TEMPLATE')
    })

    it('includes event name as text parameter', () => {
      const { generateGoogleCalendarUrl } = useCalendarExport()
      const url = generateGoogleCalendarUrl(SAMPLE_EVENT)
      const params = new URL(url).searchParams

      expect(params.get('text')).toBe('Akad Nikah')
    })

    it('includes formatted dates parameter', () => {
      const { generateGoogleCalendarUrl } = useCalendarExport()
      const url = generateGoogleCalendarUrl(SAMPLE_EVENT)
      const params = new URL(url).searchParams

      expect(params.get('dates')).toBe('20241225T080000/20241225T100000')
    })

    it('includes location with venue and address', () => {
      const { generateGoogleCalendarUrl } = useCalendarExport()
      const url = generateGoogleCalendarUrl(SAMPLE_EVENT)
      const params = new URL(url).searchParams

      expect(params.get('location')).toBe(
        'Masjid Istiqlal, Jl. Taman Wijaya Kusuma, Jakarta Pusat'
      )
    })

    it('includes details with event description', () => {
      const { generateGoogleCalendarUrl } = useCalendarExport()
      const url = generateGoogleCalendarUrl(SAMPLE_EVENT)
      const params = new URL(url).searchParams

      expect(params.get('details')).toBe('Akad Nikah di Masjid Istiqlal')
    })

    it('handles event without venueName', () => {
      const { generateGoogleCalendarUrl } = useCalendarExport()
      const event = { ...SAMPLE_EVENT, venueName: '' }
      const url = generateGoogleCalendarUrl(event)
      const params = new URL(url).searchParams

      expect(params.get('location')).toBe('Jl. Taman Wijaya Kusuma, Jakarta Pusat')
      expect(params.get('details')).toBe('Akad Nikah')
    })
  })

  describe('downloadIcs', () => {
    let createObjectURLMock
    let revokeObjectURLMock

    beforeEach(() => {
      createObjectURLMock = vi.fn(() => 'blob:mock-url')
      revokeObjectURLMock = vi.fn()
      global.URL.createObjectURL = createObjectURLMock
      global.URL.revokeObjectURL = revokeObjectURLMock
    })

    afterEach(() => {
      vi.restoreAllMocks()
    })

    it('creates a blob and triggers download', () => {
      const { downloadIcs } = useCalendarExport()

      const clickMock = vi.fn()
      const appendChildMock = vi.spyOn(document.body, 'appendChild').mockImplementation(() => {})
      const removeChildMock = vi.spyOn(document.body, 'removeChild').mockImplementation(() => {})
      vi.spyOn(document, 'createElement').mockReturnValue({
        href: '',
        download: '',
        click: clickMock
      })

      downloadIcs(SAMPLE_EVENT)

      expect(createObjectURLMock).toHaveBeenCalledOnce()
      expect(clickMock).toHaveBeenCalledOnce()
      expect(revokeObjectURLMock).toHaveBeenCalledWith('blob:mock-url')
    })

    it('uses event name for the filename', () => {
      const { downloadIcs } = useCalendarExport()

      const linkElement = { href: '', download: '', click: vi.fn() }
      vi.spyOn(document, 'createElement').mockReturnValue(linkElement)
      vi.spyOn(document.body, 'appendChild').mockImplementation(() => {})
      vi.spyOn(document.body, 'removeChild').mockImplementation(() => {})

      downloadIcs(SAMPLE_EVENT)

      expect(linkElement.download).toBe('akad-nikah.ics')
    })
  })
})
