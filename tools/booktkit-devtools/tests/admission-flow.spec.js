import { test, expect } from '@playwright/test';

test.describe('Admission boundary and organizer scanner flow', () => {
  test('organizer scanner rejects an invalid issued-ticket token without server error', async ({ page }, info) => {
    test.skip(info.project.metadata.role !== 'organizer', 'organizer-only suite');
    const response = await page.request.post('/organizer/check-qrcode/', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      form: { booking_id: 'btk_booktkit_qa_invalid', direction: 'entry' }
    });
    expect(response.status()).toBeLessThan(500);
    const text = await response.text();
    expect(text.toLowerCase()).toMatch(/invalid|error|not found/);
  });

  test('staff admission endpoint cannot be used without staff authentication', async ({ request }) => {
    for (const direction of ['entry','exit']) {
      const response = await request.post('/api/staff-scanner/scan', {
        headers:{Accept:'application/json'},
        data:{booking_id:'btk_booktkit_qa_invalid', direction}
      });
      expect([401,403], direction).toContain(response.status());
    }
  });
});
