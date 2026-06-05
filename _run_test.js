async (page) => {
  try {
    const token = await page.evaluate(() => localStorage.getItem('owner_token'));
    console.log('Token exists:', !!token, 'length:', token ? token.length : 0);
    
    const result = await page.evaluate(async () => {
      try {
        const token = localStorage.getItem('owner_token');
        const res = await fetch('/index.php/api/claimProperty', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
          body: JSON.stringify({ phone: '19171045360' })
        });
        const ct = res.headers.get('content-type');
        const data = await res.json();
        return { ok: res.ok, status: res.status, ct, data };
      } catch(e) {
        return { error: true, message: e.message, name: e.name };
      }
    });
    console.log('Result:', JSON.stringify(result, null, 2));
  } catch(e) {
    console.log('Outer error:', e.message);
  }
}
