import path from 'node:path';
import { createRoleStorageState } from './auth.js';
export default async function globalSetup(config) {
  const roles = new Map();
  for (const project of config.projects) {
    if (project.metadata.role) roles.set(project.metadata.role, project.use.storageState);
  }
  // Sequential login avoids flooding the site and reports the first real auth failure.
  for (const [role, state] of roles) await createRoleStorageState(role, path.resolve(state));
}
