import {test,expect} from '@playwright/test';
import {assertLoggedIn} from './helpers/login.js';
test('organizer session and event type chooser',async({page})=>{
 await assertLoggedIn(page,'organizer');
 const response=await page.goto('/organizer/choose-event-type/',{waitUntil:'domcontentloaded'});
 expect(response.status()).toBe(200);
 await expect(page.locator('body')).toContainText(/Online Event/i);
 await expect(page.locator('body')).toContainText(/Venue Event/i);
});
