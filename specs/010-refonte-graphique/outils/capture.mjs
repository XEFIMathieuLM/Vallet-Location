import { chromium } from 'playwright-core';
import fs from 'node:fs';

const base = process.env.BASE ?? 'http://localhost:8110';
const out = process.argv[2];
const only = process.argv[3] ? process.argv[3].split(',') : null;
fs.mkdirSync(out, { recursive: true });

const staffPages = [
    ['02-tableau-de-bord', '/dashboard'],
    ['03-planning', '/planning'],
    ['04-disponibilites', '/disponibilites'],
    ['05-reservations', '/reservations'],
    ['06-reservation-detail', '/reservations/5'],
    ['07-transmissions', '/facturation/transmissions'],
    ['08-cautions', '/cautions'],
    ['09-ventes', '/ventes'],
    ['10-vgp', '/vgp/machines'],
    ['11-grands-comptes', '/grands-comptes'],
    ['12-demandes-en-ligne', '/demandes-en-ligne'],
    ['13-machines', '/machines'],
    ['14-nouvelle-reservation', '/reservations/nouvelle'],
    ['15-parametres', '/settings/profile'],
];
const portalPages = [
    ['21-espace-client-recherche', '/espace-client'],
    ['21b-espace-client-resultats', '/espace-client?categorie=1&agence=1&du=2026-10-26&au=2026-10-30'],
    ['22-espace-client-demandes', '/espace-client/demandes'],
    ['23-espace-client-reservations', '/espace-client/reservations'],
];
const viewports = { desktop: { width: 1440, height: 900 }, mobile: { width: 375, height: 812 } };
const theme = process.env.DARK ? '-sombre' : '';

const wanted = (name) => !only || only.some((prefix) => name.startsWith(prefix));

const shoot = async (page, name, vp) => {
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(400);
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    if (overflow > 0) console.log(`OVERFLOW ${name} ${vp}: ${overflow}px`);
    await page.screenshot({ path: `${out}/${name}-${vp}${theme}.png`, fullPage: true });
};

const browser = await chromium.launch({ channel: 'chrome', headless: true });
for (const [vp, size] of Object.entries(viewports)) {
    const context = await browser.newContext({ viewport: size, deviceScaleFactor: 1, colorScheme: 'light' });
    if (theme) {
        await context.addInitScript(() => localStorage.setItem('flux.appearance', 'dark'));
    }
    const page = await context.newPage();

    if (wanted('01')) {
        await page.goto(`${base}/login`);
        await shoot(page, '01-connexion', vp);
    }
    if (wanted('20')) {
        await page.goto(`${base}/espace-client/connexion`);
        await shoot(page, '20-espace-client-connexion', vp);
    }

    await page.goto(`${base}/login`);
    await page.fill('input[type=email]', process.env.STAFF_EMAIL);
    await page.fill('input[type=password]', process.env.STAFF_PASSWORD);
    await Promise.all([page.waitForURL('**/dashboard'), page.click('button[type=submit]')]);
    for (const [name, path] of staffPages.filter(([name]) => wanted(name))) {
        await page.goto(base + path);
        await shoot(page, name, vp);
    }

    if (process.env.CUSTOMER_EMAIL) {
        await page.goto(`${base}/espace-client/connexion`);
        await page.fill('input[type=email]', process.env.CUSTOMER_EMAIL);
        await page.fill('input[type=password]', process.env.CUSTOMER_PASSWORD);
        await Promise.all([page.waitForURL('**/espace-client'), page.click('button[type=submit]')]);
        for (const [name, path] of portalPages.filter(([name]) => wanted(name))) {
            await page.goto(base + path);
            await shoot(page, name, vp);
        }
    }
    await context.close();
}
await browser.close();
