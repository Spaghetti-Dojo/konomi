#!/usr/bin/env node
/**
 * Konomi driver: launch and drive the plugin inside the wp-env WordPress site.
 *
 * Usage (from the repository root):
 *   node .claude/skills/run-konomi/driver.mjs <command> [args]
 *
 * Commands:
 *   status              Report wp-env containers, site HTTP code, plugin state.
 *   up                  Start wp-env and activate the plugin.
 *   down                Stop wp-env.
 *   smoke               Full user flow: logged out modal + logged in reaction toggle.
 *   shot <url> [file]   Screenshot a URL as the admin user.
 *   dump <url> [file]   Save the rendered HTML of a URL as the admin user.
 *   cli <args...>       Run WP-CLI inside the container.
 *
 * Environment:
 *   KONOMI_SITE    site URL          (default http://localhost:8888)
 *   KONOMI_USER    admin login       (default admin)
 *   KONOMI_PASS    admin password    (default password)
 *   KONOMI_OUT     screenshot folder (default .claude/skills/run-konomi/out)
 *   KONOMI_HEADED  set to 1 to watch the browser
 */

import { spawnSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { chromium } from 'playwright';

const SITE = process.env.KONOMI_SITE || 'http://localhost:8888';
const USER = process.env.KONOMI_USER || 'admin';
const PASS = process.env.KONOMI_PASS || 'password';
const OUT = process.env.KONOMI_OUT || '.claude/skills/run-konomi/out';

function sh(cmd, args, opts = {}) {
	const res = spawnSync(cmd, args, { encoding: 'utf8', ...opts });
	return { code: res.status, out: `${res.stdout || ''}${res.stderr || ''}` };
}

// wp-env prints an Xdebug "Could not connect" warning on every call. Drop it.
function wpCli(args) {
	const res = sh('npx', ['wp-env', 'run', 'cli', 'wp', ...args]);
	const out = res.out
		.split('\n')
		.filter((line) => !/Xdebug|Starting '|Ran `/.test(line))
		.join('\n')
		.trim();
	return { code: res.code, out };
}

function browser() {
	return chromium.launch({ headless: process.env.KONOMI_HEADED !== '1' });
}

async function login(page) {
	await page.goto(`${SITE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
	await page.fill('#user_login', USER);
	await page.fill('#user_pass', PASS);
	await Promise.all([
		page.waitForURL(/wp-admin/, { timeout: 30000 }),
		page.click('#wp-submit'),
	]);
}

function firstPostUrl() {
	const res = wpCli(['post', 'list', '--post_type=post', '--posts_per_page=1', '--field=url']);
	const url = res.out.split('\n').pop().trim();
	if (!url.startsWith('http')) {
		throw new Error(`Cannot read a post URL from WP-CLI: ${res.out}`);
	}
	return url.replace(/^http:\/\/localhost:8888/, SITE);
}

async function cmdStatus() {
	const ps = sh('docker', ['ps', '--format', '{{.Names}}']);
	const containers = ps.out
		.split('\n')
		.filter((name) => name.includes('wordpress') || name.includes('mysql'));
	const http = sh('curl', ['-s', '-o', '/dev/null', '-w', '%{http_code}', SITE]).out.trim();
	console.log(`containers:\n  ${containers.join('\n  ') || '(none)'}`);
	console.log(`site ${SITE} -> HTTP ${http}`);
	console.log(wpCli(['plugin', 'list', '--fields=name,status,version']).out);
}

async function cmdUp() {
	console.log(sh('npx', ['wp-env', 'start']).out);
	console.log(wpCli(['plugin', 'activate', 'konomi']).out);
	await cmdStatus();
}

function rowsDump() {
	return wpCli([
		'db',
		'query',
		'SELECT id, entity_id, user_id, entity_type, group_key FROM wp_konomi_interactions',
	]).out;
}

// WP-CLI prints a header row, so the count is the row lines minus that header.
function rowCount(groupKey) {
	const out = wpCli([
		'db',
		'query',
		`SELECT id FROM wp_konomi_interactions WHERE group_key = '${groupKey}'`,
	]).out;
	const lines = out.split('\n').filter((line) => line.trim() !== '');
	return Math.max(lines.length - 1, 0);
}

async function cmdSmoke() {
	mkdirSync(OUT, { recursive: true });
	const url = firstPostUrl();
	console.log(`post: ${url}`);
	// Reset the stored interactions so the run always starts from zero.
	wpCli(['db', 'query', 'DELETE FROM wp_konomi_interactions']);

	const br = await browser();
	const failures = [];

	// 1. Logged out: a click must open the login modal and store nothing.
	const anon = await br.newPage();
	await anon.goto(url, { waitUntil: 'networkidle' });
	await anon.click('button.wp-block-konomi-reaction');
	await anon.waitForTimeout(1000);
	// The modal is a <dialog> opened with showModal(), so read the `open` property.
	const modalOpen = await anon
		.locator('dialog.konomi-login-modal')
		.first()
		.evaluate((el) => el.open)
		.catch(() => false);
	console.log(`logged out -> login modal open: ${modalOpen}`);
	if (!modalOpen) failures.push('login modal did not open for the logged out visitor');
	await anon.screenshot({ path: `${OUT}/logged-out-modal.png` });

	// 2. Logged in: the click must toggle the button and increment the counter.
	const page = await br.newPage();
	await login(page);
	await page.goto(url, { waitUntil: 'networkidle' });
	const button = page.locator('button.wp-block-konomi-reaction').first();
	const count = page.locator('.konomi-count').first();
	const wasActive = await button.evaluate((el) => el.classList.contains('is-active'));
	console.log(`before: count=${(await count.textContent()).trim()} active=${wasActive}`);
	await button.click();
	await page
		.waitForFunction(
			() =>
				document
					.querySelector('button.wp-block-konomi-reaction')
					?.classList.contains('is-active'),
			null,
			{ timeout: 15000 }
		)
		.catch(() => failures.push('the reaction button never became active'));
	const afterCount = (await count.textContent()).trim();
	console.log(`after click: count=${afterCount}`);
	if (afterCount !== '1') failures.push(`counter is ${afterCount}, expected 1`);
	await page.screenshot({ path: `${OUT}/reaction-active.png` });

	// 3. Reload: the state must persist, so it reached the database.
	await page.reload({ waitUntil: 'networkidle' });
	const persisted = await page
		.locator('button.wp-block-konomi-reaction')
		.first()
		.evaluate((el) => el.classList.contains('is-active'));
	console.log(`after reload: active=${persisted} count=${(await count.textContent()).trim()}`);
	if (!persisted) failures.push('the reaction did not persist after a reload');
	console.log(`konomi_interactions:\n${rowsDump()}`);
	await page.screenshot({ path: `${OUT}/reaction-persisted.png` });

	// 4. Second click: the reaction must turn off and the row must be deleted.
	await button.click();
	await page
		.waitForFunction(
			() =>
				!document
					.querySelector('button.wp-block-konomi-reaction')
					?.classList.contains('is-active'),
			null,
			{ timeout: 15000 }
		)
		.catch(() => failures.push('the reaction button never became inactive again'));
	const offCount = (await count.textContent()).trim();
	console.log(`after second click: count=${offCount}`);
	if (offCount !== '0') failures.push(`counter is ${offCount} after toggle off, expected 0`);
	const reactionRows = rowCount('reaction');
	console.log(`reaction rows after toggle off: ${reactionRows}`);
	if (reactionRows !== 0) {
		failures.push('the reaction row survived the toggle off');
	}
	await page.screenshot({ path: `${OUT}/reaction-removed.png` });

	// 5. The bookmark block writes its own group, and removes it again.
	const bookmark = page.locator('button.wp-block-konomi-bookmark').first();
	await bookmark.click();
	await page
		.waitForFunction(
			() =>
				document
					.querySelector('button.wp-block-konomi-bookmark')
					?.classList.contains('is-active'),
			null,
			{ timeout: 15000 }
		)
		.catch(() => failures.push('the bookmark button never became active'));
	const bookmarkRows = rowCount('bookmark');
	console.log(`bookmark rows after click: ${bookmarkRows}`);
	if (bookmarkRows !== 1) {
		failures.push('the bookmark save did not reach the database');
	}
	await page.screenshot({ path: `${OUT}/bookmark-active.png` });

	await bookmark.click();
	await page
		.waitForFunction(
			() =>
				!document
					.querySelector('button.wp-block-konomi-bookmark')
					?.classList.contains('is-active'),
			null,
			{ timeout: 15000 }
		)
		.catch(() => failures.push('the bookmark button never became inactive again'));
	const bookmarkLeft = rowCount('bookmark');
	console.log(`bookmark rows after toggle off: ${bookmarkLeft}`);
	if (bookmarkLeft !== 0) {
		failures.push('the bookmark row survived the toggle off');
	}
	console.log(`konomi_interactions at the end:\n${rowsDump() || '(empty)'}`);

	await br.close();
	console.log(`screenshots: ${OUT}`);
	if (failures.length) {
		console.error(`SMOKE FAILED:\n - ${failures.join('\n - ')}`);
		process.exit(1);
	}
	console.log('SMOKE OK');
}

async function cmdShot(url, file = `${OUT}/shot.png`) {
	mkdirSync(OUT, { recursive: true });
	const br = await browser();
	const page = await br.newPage();
	await login(page);
	await page.goto(url, { waitUntil: 'networkidle' });
	await page.screenshot({ path: file, fullPage: true });
	await br.close();
	console.log(file);
}

async function cmdDump(url, file = `${OUT}/page.html`) {
	mkdirSync(OUT, { recursive: true });
	const br = await browser();
	const page = await br.newPage();
	await login(page);
	await page.goto(url, { waitUntil: 'networkidle' });
	writeFileSync(file, await page.content());
	await br.close();
	console.log(file);
}

const [cmd, ...args] = process.argv.slice(2);
const commands = {
	status: cmdStatus,
	up: cmdUp,
	down: async () => console.log(sh('npx', ['wp-env', 'stop']).out),
	smoke: cmdSmoke,
	shot: () => cmdShot(args[0], args[1]),
	dump: () => cmdDump(args[0], args[1]),
	cli: async () => console.log(wpCli(args).out),
};

if (!commands[cmd]) {
	console.error(`unknown command: ${cmd || '(none)'}\ncommands: ${Object.keys(commands).join(', ')}`);
	process.exit(2);
}
await commands[cmd]();
