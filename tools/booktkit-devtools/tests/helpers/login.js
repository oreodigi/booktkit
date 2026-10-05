import { expect } from '@playwright/test';
import { rolePaths } from '../../src/auth.js';
export async function assertLoggedIn(page,role){
 const response=await page.goto(rolePaths[role].dashboard,{waitUntil:'domcontentloaded'});
 expect(response.status()).toBe(200);
 expect(new URL(page.url()).pathname.replace(/\/$/, '')).toBe(rolePaths[role].dashboard);
}
