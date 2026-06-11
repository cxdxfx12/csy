const fs = require('fs');
const d = 'e:/ds/public/readdy-local';
['index','product_full','solutions_full','pricing_full','about_full','contact_full','features_full','scenes_full'].forEach(f => {
  const fp = d + '/' + f + '.html';
  let h = fs.readFileSync(fp, 'utf-8');
  const before = h.length;
  // Remove any remaining readdy watermark related elements
  h = h.replace(/<div[^>]*class="[^"]*readdy-watermark[\s\S]*?<\/div>/g, '');
  // Remove watermark container with close button
  h = h.replace(/<div[^>]*>[^<]*<button[^>]*aria-label="关闭"[\s\S]*?<\/div>\s*<\/body>/g, '</body>');
  h = h.replace(/<button[^>]*aria-label="关闭"[^>]*><\/button>\s*<\/div>\s*<\/body>/g, '');
  if (h.length !== before) {
    fs.writeFileSync(fp, h);
    console.log('FIXED: ' + f + ' (-' + (before - h.length) + ')');
  } else { console.log('SKIP: ' + f); }
});
