import { test, expect } from '@playwright/test';
import fs from 'node:fs';

test('Maps callback is defined before an immediately loaded API initializes event search', async ({ page }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  // Force the API callback to run immediately, independent of third-party timing/key access.
  await page.route('https://maps.googleapis.com/maps/api/js?*', route => route.fulfill({
    contentType: 'application/javascript',
    body: `window.google = { maps: {
      Geocoder: function () {},
      places: { SearchBox: function (input) {
        input.dataset.mapsInitialized = 'true';
        this.addListener = function () {};
      } }
    } }; window.initMap();`
  }));
  await page.goto('/events', { waitUntil: 'load' });
  await expect(page.locator('#location')).toHaveAttribute('data-maps-initialized', 'true');
  expect(errors).toEqual([]);
});

test('all event map templates register their callback before loading the API', () => {
  for (const role of ['backend', 'organizer']) {
    for (const mode of ['create', 'edit']) {
      const path = `../../source/website/resources/views/${role}/event/${mode}.blade.php`;
      const template = fs.readFileSync(path, 'utf8');
      const callback = template.indexOf(mode === 'create' ? 'js/map-init.js' : 'js/edit-map-init.js');
      const api = template.indexOf('maps.googleapis.com/maps/api/js');
      expect(callback, path).toBeGreaterThan(-1);
      expect(api, path).toBeGreaterThan(callback);
    }
  }
});

test('pages without event search do not request Google Maps', async ({ page }) => {
  const maps = [];
  page.on('request', request => {
    if (request.url().startsWith('https://maps.googleapis.com/maps/api/js')) maps.push(request.url());
  });
  await page.goto('/', { waitUntil: 'load' });
  expect(maps).toEqual([]);
});
