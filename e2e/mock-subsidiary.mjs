import http from 'node:http';

const port = Number(process.env.MOCK_PORT ?? 8124);
const secret = process.env.MOCK_SECRET ?? 'e2e-subsidiary-secret';

const reports = {
  'balance-sheet': {
    sections: [
      { code: '1-1000', name: 'Kas dan Setara Kas', level: 1, balance: 750000, children: [] },
      { code: '1-2000', name: 'Piutang Usaha', level: 1, balance: 500000, children: [] },
      { code: '2-1000', name: 'Utang Usaha', level: 1, balance: 250000, children: [] },
    ],
    totals: { assets: 1250000, liabilities_equity: 1250000, net_income: 0 },
  },
  'income-statement': {
    sections: [
      { code: 'E2E-P1', name: 'PENDAPATAN MOCK', level: 1, balance: 900000, children: [] },
      { code: 'E2E-B1', name: 'BEBAN MOCK', level: 1, balance: 400000, children: [] },
      { code: 'E2E-L1', name: 'LABA BERSIH MOCK', level: 1, balance: 500000, children: [] },
    ],
    totals: { revenue: 900000, expenses: 400000, net_income: 500000 },
  },
  'cash-flow': {
    sections: [
      { code: 'E2E-OP', name: 'OPERASI MOCK', level: 1, balance: 610000, children: [] },
      { code: 'E2E-INV', name: 'INVESTASI MOCK', level: 1, balance: -120000, children: [] },
      { code: 'E2E-FIN', name: 'PENDANAAN MOCK', level: 1, balance: -50000, children: [] },
    ],
    totals: { cash_change: 440000 },
  },
  'equity-changes': {
    sections: [
      { code: 'E2E-EQ1', name: 'SALDO AWAL MOCK', level: 1, balance: 1000000, children: [] },
      { code: 'E2E-EQ2', name: 'PRIVE MOCK', level: 1, balance: -100000, children: [] },
      { code: 'E2E-EQ3', name: 'SALDO AKHIR MOCK', level: 1, balance: 1400000, children: [] },
    ],
    totals: { closing_equity: 1400000 },
  },
  calk: {
    sections: [
      { code: 'E2E-CALC-1', name: 'ANDI MOCK', level: 1, balance: 80000, children: [] },
      { code: 'E2E-CALC-2', name: 'BUDI MOCK', level: 1, balance: 90000, children: [] },
      { code: 'E2E-CALC-3', name: 'CITRA MOCK', level: 1, balance: 100000, children: [] },
    ],
    totals: { participants: 3 },
  },
};

function send(response, status, body) {
  response.writeHead(status, { 'Content-Type': 'application/json' });
  response.end(JSON.stringify(body));
}

const server = http.createServer((request, response) => {
  const url = new URL(request.url ?? '/', `http://127.0.0.1:${port}`);
  const authorization = request.headers.authorization ?? '';

  if (authorization !== `Bearer ${secret}`) {
    send(response, 401, { status: 'error', message: 'Unauthorized' });
    return;
  }

  if (request.method !== 'GET') {
    send(response, 405, { status: 'error', message: 'Method not allowed' });
    return;
  }

  if (url.pathname === '/api/v1/holding/ping') {
    send(response, 200, { status: 'success' });
    return;
  }

  const match = url.pathname.match(/^\/api\/v1\/holding\/reports\/([a-z-]+)$/);
  if (!match || !(match[1] in reports)) {
    send(response, 404, { status: 'error', message: 'Not found' });
    return;
  }

  const report = match[1];
  const year = Number(url.searchParams.get('year') ?? new Date().getFullYear());
  const month = url.searchParams.get('month');
  send(response, 200, {
    status: 'success',
    meta: { report, period: { year, month: month ? Number(month) : null } },
    data: reports[report],
  });
});

server.listen(port, '127.0.0.1');
