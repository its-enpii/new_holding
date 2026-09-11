import { spawn, spawnSync, type ChildProcess } from 'node:child_process';
import { mkdirSync, rmSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(import.meta.dirname, '..');
const env = {
  ...process.env,
  MOCK_PORT: '8124',
  MOCK_SECRET: process.env.MOCK_SECRET ?? 'e2e-subsidiary-secret',
};

const processes: Array<ChildProcess> = [];

function waitForHttp(url: string, timeoutMs = 20_000): void {
  const deadline = Date.now() + timeoutMs;
  while (Date.now() < deadline) {
    const result = spawnSync('curl', ['-sS', '-o', '/dev/null', '-w', '%{http_code}', url], {
      encoding: 'utf8',
    });
    if (['200', '401'].includes(result.stdout.trim())) return;
  }
  throw new Error(`Service did not become ready: ${url}`);
}

function killPort(port: string): void {
  spawnSync('fuser', ['-k', `${port}/tcp`], { stdio: 'ignore' });
}

async function setup(): Promise<void> {
  killPort('8123');
  killPort('8124');

  spawnSync('php', ['artisan', 'migrate:fresh', '--seed', '--no-interaction'], {
    cwd: repoRoot,
    stdio: 'inherit',
  });

  rmSync(path.join(repoRoot, 'e2e/artifacts'), { recursive: true, force: true });
  mkdirSync(path.join(repoRoot, 'e2e/artifacts'), { recursive: true });

  processes.push(spawn('node', ['mock-subsidiary.mjs'], {
    cwd: import.meta.dirname,
    env,
    stdio: ['ignore', 'ignore', 'inherit'],
  }));

  processes.push(spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8123'], {
    cwd: repoRoot,
    env,
    stdio: ['ignore', 'ignore', 'inherit'],
  }));

  for (const child of processes) {
    child.stdout?.on('data', () => {});
    child.stderr?.on('data', () => {});
  }

  waitForHttp('http://127.0.0.1:8124/api/v1/holding/ping', 10_000);
  waitForHttp('http://127.0.0.1:8123/login', 30_000);
}

export default setup;
