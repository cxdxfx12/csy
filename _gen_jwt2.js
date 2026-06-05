// 用正确的JWT密钥测试
const crypto = require('crypto');

function base64url(str) {
  return Buffer.from(str).toString('base64url');
}

function signHS256(data, secret) {
  return crypto.createHmac('sha256', secret).update(data).digest('base64url');
}

// 正确的 JWT KEY 来自 .env
const secret = 'd5F8kL2mN9pQ3rT7wX1yA4bC6eH0jV5u';
const iss = 'dasheng-pms';
const aud = 'dasheng-pms-client';
const now = Math.floor(Date.now() / 1000);

const ownerId = process.argv[2] || '1';

const header = { alg: 'HS256', typ: 'JWT' };
const payload = {
  iss, aud,
  iat: now, nbf: now,
  exp: now + 86400,
  sub: parseInt(ownerId),
  type: 'owner',
};

const eh = base64url(JSON.stringify(header));
const ep = base64url(JSON.stringify(payload));
const sig = signHS256(`${eh}.${ep}`, secret);

const token = `${eh}.${ep}.${sig}`;
console.log(`OwnerID:${ownerId} Token:${token}`);
