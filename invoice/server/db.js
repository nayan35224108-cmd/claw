const path = require('path');
const Database = require('better-sqlite3');

const dbPath = path.join(__dirname, 'invoice.db');
const db = new Database(dbPath);

db.pragma('journal_mode = WAL');

db.exec(`
CREATE TABLE IF NOT EXISTS invoices (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  no_invoice TEXT UNIQUE,
  tanggal TEXT,
  pengirim TEXT,
  klien TEXT,
  items TEXT,
  subtotal REAL DEFAULT 0,
  pajak REAL DEFAULT 0,
  diskon REAL DEFAULT 0,
  total REAL DEFAULT 0,
  status TEXT DEFAULT 'draft',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
`);

module.exports = db;
