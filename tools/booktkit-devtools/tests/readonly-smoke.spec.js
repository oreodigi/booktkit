import { test, expect } from '@playwright/test';
for (const route of ['/', '/events', '/organizer/login', '/customer/login']) {
  test(`read-only browser smoke ${route}`, async ({page})=>{
    await page.route('**/*', route=>['GET','HEAD','OPTIONS'].includes(route.request().method())?route.continue():route.abort());
    const response=await page.goto(route,{waitUntil:'domcontentloaded'});
    expect(response.status()).toBe(200);
    await expect(page.locator('body')).toBeVisible();
    await expect(page.locator('body')).not.toContainText('Internal Server Error');
  });
}
test('read-only API health',async({request})=>{
  const response=await request.get('/api/get-basic');expect(response.status()).toBe(200);
  expect(await response.json()).toBeTruthy();
});
