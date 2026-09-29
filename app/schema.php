<?php
/** Veritabanı tablolarını oluşturur ve başlangıç içeriğini yükler. */
function install_schema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL DEFAULT '');

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE COLLATE NOCASE,
    phone TEXT DEFAULT '',
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'member',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    last_login_at TEXT
);

CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    short TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    icon TEXT NOT NULL DEFAULT 'target',
    sort INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    price INTEGER NOT NULL,
    duration TEXT NOT NULL DEFAULT '',
    short TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    features TEXT NOT NULL DEFAULT '',
    is_featured INTEGER NOT NULL DEFAULT 0,
    sort INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_no TEXT NOT NULL UNIQUE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    package_id INTEGER,
    package_name TEXT NOT NULL,
    service_title TEXT NOT NULL,
    amount INTEGER NOT NULL,
    customer_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    identity_no TEXT DEFAULT '',
    invoice_type TEXT NOT NULL DEFAULT 'bireysel',
    company_name TEXT DEFAULT '',
    tax_office TEXT DEFAULT '',
    tax_no TEXT DEFAULT '',
    address TEXT NOT NULL,
    city TEXT NOT NULL,
    note TEXT DEFAULT '',
    status TEXT NOT NULL DEFAULT 'pending',
    payment_ref TEXT DEFAULT '',
    payment_message TEXT DEFAULT '',
    contract_html TEXT DEFAULT '',
    ip TEXT DEFAULT '',
    created_at TEXT NOT NULL,
    paid_at TEXT,
    updated_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);

CREATE TABLE IF NOT EXISTS order_notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    user_id INTEGER,
    user_name TEXT NOT NULL,
    note TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    user_name TEXT NOT NULL,
    role TEXT NOT NULL,
    action TEXT NOT NULL,
    details TEXT DEFAULT '',
    ip TEXT DEFAULT '',
    created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_activity_created ON activity_log(created_at);

CREATE TABLE IF NOT EXISTS pages (
    slug TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    updated_at TEXT,
    updated_by TEXT
);

CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT DEFAULT '',
    subject TEXT DEFAULT '',
    message TEXT NOT NULL,
    extra TEXT DEFAULT '',
    status TEXT NOT NULL DEFAULT 'new',
    ip TEXT DEFAULT '',
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_no TEXT NOT NULL UNIQUE,
    order_no TEXT DEFAULT '',
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'open',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS ticket_replies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    author TEXT NOT NULL,
    is_staff INTEGER NOT NULL DEFAULT 0,
    message TEXT NOT NULL,
    created_at TEXT NOT NULL
);
SQL);

    $st = $pdo->prepare('INSERT OR IGNORE INTO settings(key, value) VALUES(?, ?)');
    foreach (DEFAULT_SETTINGS as $k => $v) $st->execute([$k, $v]);

    require __DIR__ . '/seed.php';
    $seed = seed_data();

    $sSt = $pdo->prepare('INSERT INTO services(slug, title, short, description, icon, sort) VALUES(?,?,?,?,?,?)');
    $pSt = $pdo->prepare('INSERT INTO packages(service_id, name, price, duration, short, description, features, is_featured, sort) VALUES(?,?,?,?,?,?,?,?,?)');
    foreach ($seed['services'] as $i => $s) {
        $sSt->execute([$s['slug'], $s['title'], $s['short'], $s['description'], $s['icon'], $i + 1]);
        $sid = (int)$pdo->lastInsertId();
        foreach ($s['packages'] as $j => $p) {
            $pSt->execute([$sid, $p['name'], $p['price'] * 100, $p['duration'], $p['short'], $p['description'], implode("\n", $p['features']), $j === 1 ? 1 : 0, $j + 1]);
        }
    }

    $pgSt = $pdo->prepare('INSERT OR IGNORE INTO pages(slug, title, content, updated_at, updated_by) VALUES(?,?,?,?,?)');
    foreach ($seed['pages'] as $slug => [$title, $content]) {
        $pgSt->execute([$slug, $title, trim($content), date('Y-m-d H:i:s'), 'Sistem']);
    }
}
