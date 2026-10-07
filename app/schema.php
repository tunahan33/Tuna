<?php
/** Veritabanı tabloları */

function create_schema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    phone TEXT,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'uye',
    active INTEGER NOT NULL DEFAULT 1,
    last_login TEXT,
    created_at TEXT NOT NULL
);
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT,
    sort INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL REFERENCES categories(id),
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    short_desc TEXT NOT NULL,
    description TEXT NOT NULL,
    features TEXT,
    price REAL NOT NULL,
    old_price REAL,
    stock INTEGER NOT NULL DEFAULT 0,
    sizes TEXT,
    art TEXT NOT NULL DEFAULT 'forma',
    color TEXT NOT NULL DEFAULT '#C8102E',
    featured INTEGER NOT NULL DEFAULT 0,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_no TEXT NOT NULL UNIQUE,
    user_id INTEGER REFERENCES users(id),
    customer_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    city TEXT NOT NULL,
    district TEXT NOT NULL,
    address TEXT NOT NULL,
    note TEXT,
    subtotal REAL NOT NULL,
    shipping REAL NOT NULL,
    total REAL NOT NULL,
    status TEXT NOT NULL DEFAULT 'yeni',
    cargo_company TEXT,
    tracking_no TEXT,
    assigned_to INTEGER REFERENCES users(id),
    card_last4 TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER,
    name TEXT NOT NULL,
    size TEXT,
    price REAL NOT NULL,
    qty INTEGER NOT NULL
);
CREATE TABLE order_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    user_name TEXT NOT NULL,
    text TEXT NOT NULL,
    created_at TEXT NOT NULL
);
CREATE TABLE requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_no TEXT NOT NULL UNIQUE,
    type TEXT NOT NULL,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    extra TEXT,
    status TEXT NOT NULL DEFAULT 'yeni',
    reply TEXT,
    assigned_to INTEGER REFERENCES users(id),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    updated_by TEXT,
    updated_at TEXT NOT NULL
);
CREATE TABLE activity (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    user_name TEXT NOT NULL,
    role TEXT NOT NULL,
    action TEXT NOT NULL,
    details TEXT,
    link TEXT,
    ip TEXT,
    created_at TEXT NOT NULL
);
CREATE TABLE settings (
    skey TEXT PRIMARY KEY,
    svalue TEXT
);
CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_activity_created ON activity(created_at);
CREATE INDEX idx_items_order ON order_items(order_id);
SQL);
}
