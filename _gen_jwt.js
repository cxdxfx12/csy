// 生成 owner JWT token 用于测试
const crypto = require('crypto');

function base64url(str) {
  return Buffer.from(str).toString('base64url');
}

function signHS256(data, secret) {
  return crypto.createHmac('sha256', secret).update(data).digest('base64url');
}

// JWT config from config/jwt.php
const secret = 'ds_property_manager_jwt_key_2026';
const iss = 'dasheng-pms';
const aud = 'dasheng-pms-client';
const exp = 86400;
const now = Math.floor(Date.now() / 1000);

// 使用 owner_id 1 试试（通常会有一个admin/test用户）
const ownerId = process.argv[2] || '1';

const header = { alg: 'HS256', typ: 'JWT' };
const payload = {
  iss,
  aud,
  iat: now,
  nbf: now,
  exp: now + exp,
  sub: ownerId,
  type: 'owner',
};

const encodedHeader = base64url(JSON.stringify(header));
const encodedPayload = base64url(JSON.stringify(payload));
const signature = signHS256(`${encodedHeader}.${encodedPayload}`, secret);

const token = `${encodedHeader}.${encodedPayload}.${signature}`;
console.log(`Owner ID: ${ownerId}`);
console.log(`JWT Token: ${token}`);
console.log(`Expires: ${new Date((now + exp) * 1000).toISOString()}`);
