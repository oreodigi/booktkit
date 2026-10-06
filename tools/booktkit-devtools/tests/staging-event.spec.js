import { test, expect } from '@playwright/test';
import { assertMutationAllowed } from '../src/safety.js';
import { nextStep, selectFirstOption, selectLocation } from './helpers/wizard.js';
for(const scenario of [
 {name:'venue',type:'venue',special:false},
 {name:'online',type:'online',special:false},
 {name:'box-office',type:'venue',special:true},
]) {
 test(`${scenario.name} event saves through all wizard steps`,async({page},info)=>{
  const {type,special}=scenario;
  assertMutationAllowed();
  const role=info.project.metadata.role;
  page.on('dialog',dialog=>dialog.dismiss());
  await page.goto(`/${role}/add-event/?type=${type}${special?'&special=1':''}`,{waitUntil:'domcontentloaded'});
  if(special){
   await expect(page.locator('[name="box_office_enabled"]')).toHaveValue('1');
   await page.locator('[name="reentry_policy"]').selectOption('limited');
   await page.locator('[name="max_reentries"]').fill('2');
   await page.locator('[name="box_office_locations[0][name]"]').fill('QA Main Counter');
   await page.locator('[name="box_office_locations[0][address]"]').fill('Synthetic QA counter');
  }
  await expect(page.locator('.btk-wizard-nav button[data-step="1"]')).toHaveClass(/active/);
  await expect(page.locator('#EventSubmit')).toBeHidden();
  await nextStep(page,2);
  const title=page.locator('[name$="_title"]:visible').first();
  await title.fill(`QA ${scenario.name} ${Date.now()}`);
  await selectFirstOption(page.locator('[name$="_category_id"]:visible').first());
  await page.locator('[name$="_description"]:visible').first().fill('Dedicated staging event created by the automated wizard verification suite.');
  await nextStep(page,3);
  await page.locator('label').filter({has:page.locator('[name="date_type"][value="multiple"]')}).click();
  await expect(page.locator('[name="m_start_date[]"]').first()).toBeVisible();
  await page.locator('label').filter({has:page.locator('[name="date_type"][value="single"]')}).click();
  await expect(page.locator('[name="start_date"]')).toBeVisible();
  const date=new Date(Date.now()+86400000*30).toISOString().slice(0,10);
  for(const [name,value] of Object.entries({start_date:date,end_date:date,start_time:'10:00',end_time:'12:00'})) await page.locator(`[name="${name}"]`).fill(value);
  await nextStep(page,4);
  if(type==='online') await page.locator('[name="meeting_url"]').fill('https://example.invalid/qa-meeting');
  else {
   await page.locator('[name$="_address"]:visible').first().fill('Synthetic QA venue, Jalgaon');
   const country=page.locator('[name$="_country"]').first();
   await selectLocation(page,country,'India');
   const state=page.locator('[name$="_state"]').first();
   await selectLocation(page,state,'Maharashtra');
   await selectLocation(page,page.locator('[name$="_city"]').first(),'Jalgaon');
  }
  await nextStep(page,5);
  const buffer=Buffer.from(await page.evaluate(()=>{const c=document.createElement('canvas');c.width=320;c.height=230;const x=c.getContext('2d');x.fillStyle='#fd7e14';x.fillRect(0,0,320,230);return c.toDataURL('image/png').split(',')[1];}),'base64');
  const file={name:'qa-event.png',mimeType:'image/png',buffer};
  await page.locator('[name="thumbnail"]').setInputFiles(file);
  await page.locator('#btkCropModal .btk-apply').click();
  await expect(page.locator('#btkCropModal')).toHaveCount(0);
  await page.locator('input.dz-hidden-input').setInputFiles(file);
  await page.locator('#btkCropModal .btk-apply').click();
  await expect(page.locator('[name="slider_images[]"]')).toHaveCount(1);
  await nextStep(page,6);
  await expect(page.locator('#EventSubmit')).toBeVisible();
  await page.locator('[name="status"]').selectOption('1');
  await page.locator('[name="is_featured"]').selectOption('no');
  const saved=page.waitForResponse(r=>r.url().includes('/event-store')&&r.request().method()==='POST');
  await page.locator('#EventSubmit').click();
  const response=await saved;
  expect(response.status()).toBeLessThan(400);
  await expect(page).not.toHaveURL(/add-event/);
 });
}
