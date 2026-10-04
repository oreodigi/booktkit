import { test, expect } from '@playwright/test';

test.describe('Organizer capability surface', () => {
  test('event type source exposes supported event choices', async ({ page }) => {
    test.skip(!process.env.BOOKTKIT_TEST_ORGANIZER_PASSWORD, 'authenticated organizer credentials not configured');
    await page.goto('/organizer/choose-event-type/', { waitUntil: 'domcontentloaded' });
    const body = await page.locator('body').innerText();
    expect(body).toMatch(/Online Event/i);
    expect(body).toMatch(/Venue Event/i);
  });

  test('special/festive event option capability check', async ({ page }) => {
    test.skip(!process.env.BOOKTKIT_TEST_ORGANIZER_PASSWORD, 'authenticated organizer credentials not configured');
    await page.goto('/organizer/choose-event-type/', { waitUntil: 'domcontentloaded' });
    const found = await page.getByText(/Festive|Cultural|Special Event/i).count();
    test.info().annotations.push({
      type: 'capability',
      description: found ? 'Special/Festive event option detected' : 'Special/Festive event option not detected'
    });
  });
});
