// 一键构建4个Android APK
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const APPS = [
  {
    name: 'admin-app',
    displayName: '大圣物业管理平台',
    appId: 'com.dasheng.admin',
    url: 'http://www.hbdxm.com/admin/',
    iconDir: 'admin'
  },
  {
    name: 'owner-app',
    displayName: '业主端',
    appId: 'com.dasheng.owner',
    url: 'http://www.hbdxm.com/owner.html',
    iconDir: 'owner'
  },
  {
    name: 'staff-app',
    displayName: '物业员工端',
    appId: 'com.dasheng.staff',
    url: 'http://www.hbdxm.com/staff.html',
    iconDir: 'staff'
  },
  {
    name: 'manager-app',
    displayName: '小区经理工作台',
    appId: 'com.dasheng.manager',
    url: 'http://www.hbdxm.com/manager.html',
    iconDir: 'manager'
  }
];

const BASE = 'e:\\ds\\apps';
const PUBLIC = 'e:\\ds\\public';
const JAVA_HOME = 'C:\\jdk11\\jdk';
const ANDROID_HOME = 'C:\\android-sdk';

const env = {
  ...process.env,
  JAVA_HOME,
  ANDROID_HOME,
  ANDROID_SDK_ROOT: ANDROID_HOME,
  PATH: `${JAVA_HOME}\\bin;${ANDROID_HOME}\\platform-tools;${ANDROID_HOME}\\cmdline-tools\\latest\\bin;${process.env.PATH}`
};

const run = (cmd, cwd) => {
  console.log('>', cmd);
  try {
    const out = execSync(cmd, { cwd, env, stdio: 'pipe', encoding: 'utf8' });
    console.log(out.slice(-500));
    return out;
  } catch (e) {
    console.error('ERROR:', e.stderr || e.message);
    throw e;
  }
};

// Step 1: Create 4 capacitor app projects
for (const app of APPS) {
  const appDir = path.join(BASE, app.name);
  console.log(`\n\n========== ${app.displayName} ==========\n`);
  
  // package.json
  const pkg = {
    name: app.name,
    version: '1.0.0',
    description: app.displayName + ' - 大圣智慧物业',
    scripts: {
      'build:android': 'npx cap sync android && cd android && gradlew assembleDebug'
    },
    dependencies: {
      '@capacitor/core': '^6.0.0'
    },
    devDependencies: {
      '@capacitor/cli': '^6.0.0',
      '@capacitor/android': '^6.0.0'
    }
  };
  
  fs.mkdirSync(appDir, { recursive: true });
  fs.writeFileSync(path.join(appDir, 'package.json'), JSON.stringify(pkg, null, 2));
  
  // capacitor.config.ts
  const config = `
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: '${app.appId}',
  appName: '${app.displayName}',
  webDir: '.',
  server: {
    url: '${app.url}',
    cleartext: true,
    androidScheme: 'http'
  },
  android: {
    allowMixedContent: true
  }
};

export default config;
`;
  fs.writeFileSync(path.join(appDir, 'capacitor.config.ts'), config.trim());
  
  // index.html (placeholder)
  fs.writeFileSync(path.join(appDir, 'index.html'), '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' + app.displayName + '</title></head><body><p>Loading...</p></body></html>');
  
  // Install deps
  console.log('Installing npm packages...');
  run(`cmd /c "set PATH=C:\\Program Files\\nodejs;%PATH% && cd /d ${appDir} && npm install --prefer-offline"`, appDir);
  
  // Init Android platform
  console.log('Adding Android platform...');
  run(`cmd /c "set PATH=C:\\Program Files\\nodejs;%PATH% && cd /d ${appDir} && npx cap add android"`, appDir);
  
  // Copy icon
  const androidRes = path.join(appDir, 'android', 'app', 'src', 'main', 'res');
  const iconDir = app.name === 'admin-app' ? 'admin' : app.iconDir;
  const iconAssetDir = app.name === 'admin-app' ? path.join(PUBLIC, 'admin') : PUBLIC;
  
  // Copy icons to Android resource folders
  const mipmapDirs = ['mipmap-mdpi','mipmap-hdpi','mipmap-xhdpi','mipmap-xxhdpi','mipmap-xxxhdpi'];
  const sizes = [48, 72, 96, 144, 192];
  
  for (let i = 0; i < mipmapDirs.length; i++) {
    const targetDir = path.join(androidRes, mipmapDirs[i]);
    fs.mkdirSync(targetDir, { recursive: true });
    // Copy the 192x192 icon and it'll scale
    const src = path.join(iconAssetDir, iconDir === 'admin' ? 'icon-192.png' : `${iconDir}-icon-192.png`);
    const dest = path.join(targetDir, 'ic_launcher.png');
    if (fs.existsSync(src)) {
      fs.copyFileSync(src, dest);
    }
  }
  
  console.log(`${app.displayName} setup complete!`);
}

console.log('\n\n========== ALL 4 APPS SETUP DONE ==========');
console.log('Next: Build APKs with _build_apks.js');
