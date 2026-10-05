import { test, expect } from '@playwright/test';
import { assertMutationAllowed } from '../src/safety.js';
import { nextStep, selectFirstOption } from './helpers/wizard.js';
for(const type of ['venue','online']) {
 test(`${type} wizard advances through type, details, schedule, location, media and publish`,async({page},info)=>{
  assertMutationAllowed();
  const role=info.project.metadata.role;
  page.on('dialog',dialog=>dialog.dismiss());
  await page.goto(`/${role}/add-event/?type=${type}`,{waitUntil:'domcontentloaded'});
  await expect(page.locator('.btk-wizard-nav button[data-step="1"]')).toHaveClass(/active/);
  await expect(page.locator('#EventSubmit')).toBeHidden();
  await nextStep(page,2);
  const title=page.locator('[name$="_title"]:visible').first();
  await title.fill(`QA ${type} ${Date.now()}`);
  await selectFirstOption(page.locator('[name$="_category_id"]:visible').first());
  await page.locator('[name$="_description"]:visible').first().fill('Dedicated staging event created by the automated wizard verification suite.');
  await nextStep(page,3);
  await page.locator('[name="date_type"][value="multiple"]').check();
  await expect(page.locator('[name="m_start_date[]"]').first()).toBeVisible();
  await page.locator('[name="date_type"][value="single"]').check();
  await expect(page.locator('[name="start_date"]')).toBeVisible();
  const date=new Date(Date.now()+86400000*30).toISOString().slice(0,10);
  for(const [name,value] of Object.entries({start_date:date,end_date:date,start_time:'10:00',end_time:'12:00'})) await page.locator(`[name="${name}"]`).fill(value);
  await nextStep(page,4);
  if(type==='online') await page.locator('[name="meeting_url"]').fill('https://example.invalid/qa-meeting');
  else await expect(page.locator('[name$="_address"]:visible').first()).toBeVisible();
  await nextStep(page,5);
  await expect(page.locator('[name="thumbnail"]')).toBeAttached();
  await nextStep(page,6);
  await expect(page.locator('#EventSubmit')).toBeVisible();
 });
}
