async () => {
  try {
    const token = localStorage.getItem('owner_token');
    const res = await fetch('/index.php/api/claimProperty', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({ phone: '19171045360' })
    });
    const ct = res.headers.get('content-type');
    const data = await res.json();
    return JSON.stringify({ ok: res.ok, status: res.status, ct, data });
  } catch(e) {
    return 'ERR:' + e.message + '|' + e.name;
  }
}
