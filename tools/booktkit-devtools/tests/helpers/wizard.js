import {expect} from '@playwright/test';
export async function nextStep(page,number){
 await page.locator('.btk-next').click();
 await expect(page.locator(`.btk-wizard-nav button[data-step="${number}"]`)).toHaveClass(/active/);
}
export async function selectFirstOption(locator){
 const value=await locator.locator('option').evaluateAll(options=>options.find(o=>o.value&&!o.disabled)?.value);
 if(!value)throw new Error('Fixture requires a selectable option');
 await locator.selectOption(value);
}
