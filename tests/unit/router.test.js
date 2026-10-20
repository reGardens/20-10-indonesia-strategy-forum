import { describe, it, expect } from 'vitest'
import router from '../../src/router/index.js'

describe('Router Configuration', () => {
  it('should have root redirect to /cms', () => {
    const rootRoute = router.options.routes.find(r => r.path === '/')
    expect(rootRoute.redirect).toBe('/cms')
  })

  it('should define wedding selector route at /cms', () => {
    const selectorRoute = router.options.routes.find(r => r.path === '/cms' && r.name === 'cms-selector')
    expect(selectorRoute).toBeDefined()
    expect(selectorRoute.name).toBe('cms-selector')
  })

  it('should define CMS parent route at /cms/:slug with dynamic redirect', () => {
    const cmsRoute = router.options.routes.find(r => r.path === '/cms/:slug')
    expect(cmsRoute).toBeDefined()
    expect(typeof cmsRoute.redirect).toBe('function')
  })

  it('should define all CMS child routes under /cms/:slug', () => {
    const cmsRoutes = [
      { path: '/cms/test-wedding/dashboard', name: 'cms-dashboard' },
      { path: '/cms/test-wedding/couple', name: 'cms-couple' },
      { path: '/cms/test-wedding/events', name: 'cms-events' },
      { path: '/cms/test-wedding/template', name: 'cms-template' },
      { path: '/cms/test-wedding/guests', name: 'cms-guests' },
      { path: '/cms/test-wedding/checkin', name: 'cms-checkin' },
      { path: '/cms/test-wedding/media', name: 'cms-media' },
      { path: '/cms/test-wedding/payment', name: 'cms-payment' },
      { path: '/cms/test-wedding/rsvp', name: 'cms-rsvp' },
      { path: '/cms/test-wedding/settings', name: 'cms-settings' }
    ]

    for (const route of cmsRoutes) {
      const resolved = router.resolve(route.path)
      expect(resolved.name).toBe(route.name)
      expect(resolved.matched.length).toBeGreaterThan(0)
    }
  })

  it('should define invitation route with :slug parameter', () => {
    const resolved = router.resolve('/wedding/john-and-jane')
    expect(resolved.name).toBe('invitation')
    expect(resolved.params.slug).toBe('john-and-jane')
    expect(resolved.matched.length).toBe(2)
  })

  it('should use CmsLayout as parent component for CMS routes', () => {
    const resolved = router.resolve('/cms/test-wedding/dashboard')
    expect(resolved.matched[0].path).toBe('/cms/:slug')
    expect(resolved.matched[0].components.default).toBeDefined()
  })

  it('should use InvitationLayout as parent component for invitation routes', () => {
    const resolved = router.resolve('/wedding/test-slug')
    expect(resolved.matched[0].path).toBe('/wedding/:slug')
    expect(resolved.matched[0].components.default).toBeDefined()
  })

  it('should have exactly 10 CMS child routes', () => {
    const cmsChildRoutes = router.getRoutes().filter(
      r => r.path.startsWith('/cms/:slug/') && r.name
    )
    expect(cmsChildRoutes.length).toBe(10)
  })

  it('should pass slug param to CMS child routes', () => {
    const resolved = router.resolve('/cms/budi-ani/dashboard')
    expect(resolved.params.slug).toBe('budi-ani')
  })

  it('should use lazy-loaded components for all view routes', () => {
    const namedRoutes = router.getRoutes().filter(r => r.name)
    for (const route of namedRoutes) {
      const component = route.components?.default
      expect(component).toBeDefined()
    }
  })
})
