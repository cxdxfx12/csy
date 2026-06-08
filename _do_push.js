// Push to GitHub using the GitHub API
const https = require('https');
const fs = require('fs');
const path = require('path');

// Use git bundle approach: create a patch and apply via GitHub API
// Actually, let's just try spawning git push and waiting for it
const { spawn } = require('child_process');

const git = spawn('git', ['push', 'origin', 'master'], { cwd: 'e:\\ds' });

git.stdout.on('data', d => process.stdout.write(d));
git.stderr.on('data', d => process.stderr.write(d));
git.on('close', code => {
  console.log('Exit code:', code);
  process.exit(code);
});
git.on('error', e => {
  console.error('Spawn error:', e.message);
  process.exit(1);
});

// Timeout after 2 minutes
setTimeout(() => {
  console.log('Push timed out after 2 minutes');
  git.kill();
  process.exit(1);
}, 120000);
