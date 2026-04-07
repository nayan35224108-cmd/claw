require('dotenv').config({ path: require('path').join(__dirname, '..', '.env') });

const express = require('express');
const cors = require('cors');
const nodemailer = require('nodemailer');
const fs = require('fs');
const { execFile } = require('child_process');
const db = require('./db');

const app = express();
const PORT = process.env.INVOICE_API_PORT || 3001;

const mailTransporter = nodemailer.createTransport({
  host: process.env.MAIL_HOST,
  port: Number(process.env.MAIL_PORT || 465),
  secure: String(process.env.MAIL_ENCRYPTION || 'ssl').toLowerCase() === 'ssl',
  auth: {
    user: process.env.MAIL_USERNAME,
    pass: process.env.MAIL_PASSWORD,
  },
});

app.use(cors());
app.use(express.json({ limit: '1mb' }));

function execFileAsync(file, args, options = {}) {
  return new Promise((resolve, reject) => {
    execFile(file, args, { timeout: 10000, maxBuffer: 1024 * 1024, ...options }, (error, stdout, stderr) => {
      if (error) {
        error.stdout = stdout;
        error.stderr = stderr;
        reject(error);
        return;
      }
      resolve({ stdout, stderr });
    });
  });
}

function getMonitorToken(req) {
  const auth = req.headers.authorization || '';
  if (auth.startsWith('Bearer ')) return auth.slice(7).trim();
  const headerToken = req.headers['x-monitor-token'];
  if (typeof headerToken === 'string' && headerToken.trim()) return headerToken.trim();
  if (typeof req.query.token === 'string' && req.query.token.trim()) return req.query.token.trim();
  return '';
}

function requireMonitorAuth(req, res, next) {
  const expectedToken = String(process.env.FAIL2BAN_MONITOR_TOKEN || '').trim();
  if (!expectedToken) {
    return res.status(503).json({ message: 'FAIL2BAN_MONITOR_TOKEN belum dikonfigurasi.' });
  }

  const providedToken = getMonitorToken(req);
  if (!providedToken || providedToken !== expectedToken) {
    return res.status(401).json({ message: 'Token monitoring tidak valid.' });
  }

  res.set('Cache-Control', 'no-store');
  return next();
}

function parseJailList(output) {
  const match = output.match(/Jail list:\s*(.+)/);
  if (!match) return [];
  return match[1]
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
}

function parseSectionValue(output, label) {
  const pattern = new RegExp(`${label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*:\\s*(.+)`);
  const match = output.match(pattern);
  return match ? match[1].trim() : '';
}

function parseJailStatus(name, output) {
  const failedCurrent = Number.parseInt(parseSectionValue(output, 'Currently failed') || '0', 10);
  const failedTotal = Number.parseInt(parseSectionValue(output, 'Total failed') || '0', 10);
  const bannedCurrent = Number.parseInt(parseSectionValue(output, 'Currently banned') || '0', 10);
  const bannedTotal = Number.parseInt(parseSectionValue(output, 'Total banned') || '0', 10);
  const files = parseSectionValue(output, 'File list')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
  const bannedIPs = parseSectionValue(output, 'Banned IP list')
    .split(/\s+/)
    .map((item) => item.trim())
    .filter(Boolean);

  return {
    name,
    failedCurrent: Number.isNaN(failedCurrent) ? 0 : failedCurrent,
    failedTotal: Number.isNaN(failedTotal) ? 0 : failedTotal,
    bannedCurrent: Number.isNaN(bannedCurrent) ? 0 : bannedCurrent,
    bannedTotal: Number.isNaN(bannedTotal) ? 0 : bannedTotal,
    files,
    bannedIPs,
  };
}

function readLogTail(filePath, maxLines = 80) {
  try {
    const content = fs.readFileSync(filePath, 'utf8');
    return content.split('\n').filter(Boolean).slice(-maxLines);
  } catch (_err) {
    return [];
  }
}

app.get('/api/admin/fail2ban/status', requireMonitorAuth, async (_req, res) => {
  try {
    const { stdout: overallRaw } = await execFileAsync('fail2ban-client', ['status']);
    const overall = overallRaw.trim();
    const jailNames = parseJailList(overall);
    const jailDetails = await Promise.all(
      jailNames.map(async (name) => {
        const { stdout } = await execFileAsync('fail2ban-client', ['status', name]);
        return parseJailStatus(name, stdout.trim());
      })
    );

    const logLines = readLogTail('/var/log/fail2ban.log', 120);
    const recentEvents = logLines
      .filter((line) => /\b(Ban|Unban)\b/.test(line))
      .slice(-30)
      .reverse();

    res.json({
      ok: true,
      generatedAt: new Date().toISOString(),
      service: 'fail2ban-monitor',
      jailCount: jailNames.length,
      jails: jailDetails,
      recentEvents,
      logTail: logLines.slice(-80),
    });
  } catch (err) {
    res.status(500).json({
      message: 'Gagal membaca status fail2ban.',
      error: err.message,
      stderr: err.stderr || '',
    });
  }
});

function getTodayYYYYMMDD() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}${m}${d}`;
}

function generateInvoiceNumber() {
  const ymd = getTodayYYYYMMDD();
  const prefix = `INV-${ymd}-`;
  const last = db
    .prepare('SELECT no_invoice FROM invoices WHERE no_invoice LIKE ? ORDER BY no_invoice DESC LIMIT 1')
    .get(`${prefix}%`);

  let seq = 1;
  if (last && last.no_invoice) {
    const lastSeq = Number(String(last.no_invoice).slice(-3));
    if (!Number.isNaN(lastSeq)) seq = lastSeq + 1;
  }

  return `${prefix}${String(seq).padStart(3, '0')}`;
}

app.get('/api/health', (_req, res) => {
  res.json({ ok: true, service: 'invoice-api' });
});

app.post('/api/invoice', (req, res) => {
  try {
    const {
      tanggal,
      pengirim,
      klien,
      items,
      subtotal = 0,
      pajak = 0,
      diskon = 0,
      total = 0,
      status = 'draft',
    } = req.body || {};

    const no_invoice = generateInvoiceNumber();

    const stmt = db.prepare(`
      INSERT INTO invoices (no_invoice, tanggal, pengirim, klien, items, subtotal, pajak, diskon, total, status)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `);

    let result;
    let generatedNo = no_invoice;
    for (let i = 0; i < 3; i += 1) {
      try {
        generatedNo = i === 0 ? no_invoice : generateInvoiceNumber();
        result = stmt.run(
          generatedNo,
          tanggal || null,
          JSON.stringify(pengirim || {}),
          JSON.stringify(klien || {}),
          JSON.stringify(items || []),
          Number(subtotal || 0),
          Number(pajak || 0),
          Number(diskon || 0),
          Number(total || 0),
          status || 'draft'
        );
        break;
      } catch (e) {
        if (!String(e.message || '').includes('UNIQUE constraint failed: invoices.no_invoice') || i === 2) {
          throw e;
        }
      }
    }

    const row = db.prepare('SELECT * FROM invoices WHERE id = ?').get(result.lastInsertRowid);
    res.status(201).json(row);
  } catch (err) {
    if (String(err.message || '').includes('UNIQUE constraint failed: invoices.no_invoice')) {
      return res.status(409).json({ message: 'Nomor invoice sudah ada.' });
    }
    res.status(500).json({ message: 'Gagal menyimpan invoice.', error: err.message });
  }
});

app.get('/api/invoices', (_req, res) => {
  try {
    const rows = db.prepare('SELECT * FROM invoices ORDER BY id DESC').all();
    res.json(rows);
  } catch (err) {
    res.status(500).json({ message: 'Gagal mengambil riwayat invoice.', error: err.message });
  }
});

app.get('/api/invoice/:id', (req, res) => {
  try {
    const id = Number(req.params.id);
    const row = db.prepare('SELECT * FROM invoices WHERE id = ?').get(id);
    if (!row) return res.status(404).json({ message: 'Invoice tidak ditemukan.' });
    res.json(row);
  } catch (err) {
    res.status(500).json({ message: 'Gagal mengambil detail invoice.', error: err.message });
  }
});

app.post('/api/invoice/:id/send-email', async (req, res) => {
  try {
    const id = Number(req.params.id);
    const row = db.prepare('SELECT * FROM invoices WHERE id = ?').get(id);
    if (!row) return res.status(404).json({ message: 'Invoice tidak ditemukan.' });

    const klien = JSON.parse(row.klien || '{}');
    const pengirim = JSON.parse(row.pengirim || '{}');
    const items = JSON.parse(row.items || '[]');

    const toEmail = (req.body?.to || klien.email || '').trim();
    if (!toEmail) return res.status(400).json({ message: 'Email klien belum diisi.' });

    const itemLines = Array.isArray(items)
      ? items.map((it, i) => `- ${i + 1}. ${(it.desc || it.description || 'Item')} | Qty: ${it.qty || 0} | Harga: ${it.price || 0}`).join('\n')
      : '-';

    const subject = `Invoice ${row.no_invoice}`;
    const text = [
      `Halo ${klien.name || 'Klien'},`,
      '',
      `Berikut invoice Anda: ${row.no_invoice}`,
      `Tanggal: ${row.tanggal || '-'}`,
      `Subtotal: ${row.subtotal || 0}`,
      `Pajak: ${row.pajak || 0}`,
      `Diskon: ${row.diskon || 0}`,
      `Total: ${row.total || 0}`,
      '',
      'Rincian Item:',
      itemLines,
      '',
      `Pengirim: ${pengirim.name || process.env.MAIL_FROM_NAME || 'Invoice Generator'}`,
      '',
      'Terima kasih.',
    ].join('\n');

    await mailTransporter.sendMail({
      from: `${process.env.MAIL_FROM_NAME || 'Invoice Generator'} <${process.env.MAIL_FROM_ADDRESS || process.env.MAIL_USERNAME}>`,
      to: toEmail,
      subject,
      text,
    });

    res.json({ success: true, message: 'Email invoice berhasil dikirim.' });
  } catch (err) {
    res.status(500).json({ message: 'Gagal kirim email invoice.', error: err.message });
  }
});

app.delete('/api/invoice/:id', (req, res) => {
  try {
    const id = Number(req.params.id);
    const result = db.prepare('DELETE FROM invoices WHERE id = ?').run(id);
    if (result.changes === 0) return res.status(404).json({ message: 'Invoice tidak ditemukan.' });
    res.json({ success: true, deletedId: id });
  } catch (err) {
    res.status(500).json({ message: 'Gagal menghapus invoice.', error: err.message });
  }
});

app.listen(PORT, () => {
  console.log(`Invoice API running on http://localhost:${PORT}`);
});
