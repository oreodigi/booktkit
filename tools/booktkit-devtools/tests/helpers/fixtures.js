import {expect} from '@playwright/test';
export async function fixtureLink(page,type){
 await page.goto('/events',{waitUntil:'domcontentloaded'});
 const link=page.locator(`a[href*="qa-fixture-${type}"]`).first();
 await expect(link).toBeVisible();return link.getAttribute('href');
}
