// The page is a thin client over the three endpoints. No framework, no build
// step: it shows the raw JSON the API returns, because that is the point.
(function () {
  'use strict';

  const CATEGORIES = ['Groceries', 'Dining Out', 'Utilities', 'Transportation', 'Entertainment',
    'Healthcare', 'Shopping', 'Housing', 'Education', 'Miscellaneous'];

  // A few rows of the supplied history, so a reviewer can post real descriptions in one click.
  const SAMPLES = [
    { reference: 'TXN00001', amount: '-87.18', timestamp: '2024-05-16 07:22:18', description: 'Municipal Tax Payment', type: 'debit', account_number: 'NLINGB1944573686' },
    { reference: 'TXN00006', amount: '238.92', timestamp: '2023-12-30 20:49:08', description: 'NS Train Ticket', type: 'credit', account_number: 'NLABNA1349001104' },
    { reference: 'TXN00009', amount: '-161.04', timestamp: '2023-10-23 22:17:57', description: 'Bol.com Purchase', type: 'debit', account_number: 'NLRABO9631379247' },
    { reference: 'TXN00013', amount: '42.60', timestamp: '2024-02-03 16:29:37', description: 'Eneco Energy Bill', type: 'debit', account_number: 'NLRABO5127825843' },
    { reference: 'TXN00021', amount: '317.62', timestamp: '2024-02-14 03:44:26', description: 'Albert Heijn Purchase', type: 'credit', account_number: 'NLABNA9387277578' },
    { reference: 'TXN00047', amount: '2950.00', timestamp: '2024-03-25 09:00:00', description: 'Salary from Employer', type: 'credit', account_number: 'NLINGB1944573686' },
    { reference: '', amount: '-12.99', timestamp: '2024-04-01 00:00:00', description: 'Netflix Subscription', type: 'debit', account_number: 'NLINGB1944573686' },
    { reference: '', amount: '-23.50', timestamp: '2024-04-02 19:30:00', description: 'Uber Eats order 4471', type: 'debit', account_number: 'NLINGB1944573686' },
  ];
  let sampleIndex = 0;

  const $ = (id) => document.getElementById(id);

  async function call(method, path, body) {
    const response = await fetch(path, {
      method,
      headers: body ? { 'Content-Type': 'application/json' } : {},
      body: body ? JSON.stringify(body) : undefined,
    });
    let json = null;
    try { json = await response.json(); } catch (_) { json = null; }
    return { status: response.status, json };
  }

  function render(target, result, summary) {
    const ok = result.status < 400;
    target.innerHTML = '';
    const status = document.createElement('p');
    status.className = 'status ' + (ok ? 'ok' : 'bad');
    status.textContent = 'HTTP ' + result.status;
    target.appendChild(status);
    if (summary) {
      const p = document.createElement('p');
      p.className = 'summary';
      p.append(...summary);
      target.appendChild(p);
    }
    const pre = document.createElement('pre');
    pre.textContent = JSON.stringify(result.json, null, 2);
    target.appendChild(pre);
  }

  function badge(category) {
    const b = document.createElement('span');
    b.className = 'category-badge';
    b.textContent = category;
    return b;
  }

  function transactionSummary(t) {
    return ['"' + t.description + '" is ', badge(t.category), ' (' + t.categorisation.source + ')'];
  }

  async function health() {
    const el = $('health');
    try {
      const r = await call('GET', '/health');
      el.textContent = r.status === 200 ? 'API up, database ' + r.json.data.database : 'API answered ' + r.status;
      el.className = 'health ' + (r.status === 200 ? 'ok' : 'bad');
    } catch (_) {
      el.textContent = 'API unreachable';
      el.className = 'health bad';
    }
  }

  function formBody(form) {
    const body = {};
    for (const [key, value] of new FormData(form).entries()) {
      if (key === 'reference' && value.trim() === '') continue;
      body[key] = value;
    }
    return body;
  }

  function fillSample() {
    const s = SAMPLES[sampleIndex++ % SAMPLES.length];
    const form = $('submit-form');
    for (const key of Object.keys(s)) form.elements[key].value = s[key];
  }

  async function submit(event) {
    event.preventDefault();
    const r = await call('POST', '/transactions', formBody($('submit-form')));
    render($('submit-result'), r, r.status === 201 ? transactionSummary(r.json.data) : null);
    if (r.status === 201) {
      $('detail-id').value = r.json.data.id;
      await list();
    }
  }

  async function list(event) {
    if (event) event.preventDefault();
    const category = $('category-filter').value;
    const r = await call('GET', '/transactions' + (category ? '?category=' + encodeURIComponent(category) : ''));
    const tbody = $('list').querySelector('tbody');
    tbody.innerHTML = '';
    if (r.status !== 200) {
      $('list-meta').textContent = 'HTTP ' + r.status;
      return;
    }
    $('list-meta').textContent = r.json.meta.count + ' shown' + (r.json.meta.count >= r.json.meta.cap ? ' (cap reached)' : '');
    $('list-empty').hidden = r.json.data.length > 0;
    for (const t of r.json.data) {
      const tr = document.createElement('tr');
      tr.dataset.id = t.id;
      tr.title = t.id;
      const cells = [t.description, null, t.amount, t.type, t.reference || '—'];
      cells.forEach((value, i) => {
        const td = document.createElement('td');
        if (i === 1) td.appendChild(badge(t.category)); else td.textContent = value;
        if (i === 2) td.className = 'num';
        tr.appendChild(td);
      });
      tr.addEventListener('click', () => { $('detail-id').value = t.id; detail(); });
      tbody.appendChild(tr);
    }
  }

  async function detail(event) {
    if (event) event.preventDefault();
    const id = $('detail-id').value.trim();
    if (!id) return;
    const r = await call('GET', '/transactions/' + encodeURIComponent(id));
    render($('detail-result'), r, r.status === 200 ? transactionSummary(r.json.data) : null);
  }

  const filter = $('category-filter');
  for (const c of CATEGORIES) {
    const o = document.createElement('option');
    o.value = c; o.textContent = c;
    filter.appendChild(o);
  }
  filter.addEventListener('change', list);
  $('submit-form').addEventListener('submit', submit);
  $('fill-sample').addEventListener('click', fillSample);
  $('list-form').addEventListener('submit', list);
  $('detail-form').addEventListener('submit', detail);

  health();
  list();
})();
