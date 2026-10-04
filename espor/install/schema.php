<?php
/** Veritabanı tabloları (MySQL ve SQLite uyumlu) */

function schema_sql(string $driver): array
{
    $mysql = $driver === 'mysql';
    $pk = $mysql ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $int = $mysql ? 'INT UNSIGNED' : 'INTEGER';
    $txt = $mysql ? 'MEDIUMTEXT' : 'TEXT';
    $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    return [
        "CREATE TABLE IF NOT EXISTS users (
            id $pk,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(30) NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'member',
            status VARCHAR(10) NOT NULL DEFAULT 'active',
            created_by $int NULL,
            last_login_at DATETIME NULL,
            reset_token VARCHAR(64) NULL,
            reset_expires DATETIME NULL,
            created_at DATETIME NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS services (
            id $pk,
            slug VARCHAR(160) NOT NULL UNIQUE,
            title VARCHAR(160) NOT NULL,
            short_desc VARCHAR(400) NOT NULL,
            description $txt NOT NULL,
            highlights TEXT NULL,
            icon VARCHAR(30) NOT NULL DEFAULT 'default',
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS packages (
            id $pk,
            service_id $int NOT NULL,
            name VARCHAR(160) NOT NULL,
            price DECIMAL(12,2) NOT NULL,
            old_price DECIMAL(12,2) NULL,
            duration VARCHAR(80) NOT NULL,
            sessions VARCHAR(80) NULL,
            short_desc VARCHAR(400) NOT NULL,
            description $txt NOT NULL,
            features TEXT NULL,
            is_featured TINYINT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS orders (
            id $pk,
            order_no VARCHAR(40) NOT NULL UNIQUE,
            user_id $int NULL,
            package_id $int NULL,
            service_title VARCHAR(160) NOT NULL,
            package_name VARCHAR(160) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            customer_name VARCHAR(120) NOT NULL,
            customer_email VARCHAR(190) NOT NULL,
            customer_phone VARCHAR(30) NOT NULL,
            customer_address VARCHAR(500) NOT NULL,
            customer_city VARCHAR(60) NULL,
            identity_no VARCHAR(20) NULL,
            invoice_type VARCHAR(12) NOT NULL DEFAULT 'bireysel',
            company_name VARCHAR(200) NULL,
            tax_office VARCHAR(100) NULL,
            tax_number VARCHAR(20) NULL,
            customer_note TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payment_method VARCHAR(30) NULL,
            payment_ref VARCHAR(100) NULL,
            payment_message VARCHAR(500) NULL,
            payment_raw TEXT NULL,
            assigned_to $int NULL,
            contract_accepted_at DATETIME NULL,
            ip VARCHAR(45) NULL,
            paid_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS order_notes (
            id $pk,
            order_id $int NOT NULL,
            user_id $int NULL,
            user_name VARCHAR(120) NOT NULL,
            note TEXT NOT NULL,
            created_at DATETIME NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS pages (
            id $pk,
            slug VARCHAR(120) NOT NULL UNIQUE,
            title VARCHAR(200) NOT NULL,
            content $txt NOT NULL,
            show_in_footer TINYINT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            updated_by $int NULL,
            updated_at DATETIME NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS messages (
            id $pk,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(30) NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'iletisim',
            ticket_no VARCHAR(20) NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(12) NOT NULL DEFAULT 'new',
            reply TEXT NULL,
            replied_at DATETIME NULL,
            assigned_to $int NULL,
            ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS activity_log (
            id $pk,
            user_id $int NULL,
            user_name VARCHAR(120) NOT NULL,
            user_role VARCHAR(20) NOT NULL,
            action VARCHAR(120) NOT NULL,
            entity VARCHAR(40) NULL,
            entity_id $int NULL,
            details TEXT NULL,
            ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS settings (
            skey VARCHAR(80) NOT NULL PRIMARY KEY,
            svalue TEXT NULL
        )$tail",
        "CREATE INDEX idx_orders_created ON orders (created_at)",
        "CREATE INDEX idx_orders_status ON orders (status)",
        "CREATE INDEX idx_activity_created ON activity_log (created_at)",
        "CREATE INDEX idx_packages_service ON packages (service_id)",
        "CREATE INDEX idx_messages_ticket ON messages (ticket_no)",
    ];
}
