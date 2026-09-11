import { spawnSync } from 'node:child_process';

export default function teardown(): void {
  spawnSync('fuser', ['-k', '8123/tcp'], { stdio: 'ignore' });
  spawnSync('fuser', ['-k', '8124/tcp'], { stdio: 'ignore' });
}
