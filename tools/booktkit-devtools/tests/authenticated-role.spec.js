import {test} from '@playwright/test';
import {assertLoggedIn} from './helpers/login.js';
test('dedicated account reaches its protected dashboard',async({page},info)=>{
 await assertLoggedIn(page,info.project.metadata.role);
});
