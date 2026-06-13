<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/config/config.php';

$isSqlite = ($dbDriver ?? '') === 'sqlite';

$sql = $isSqlite ? "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    phone TEXT,
    address TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    total REAL NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'pending',
    shipping_address TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_name TEXT NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    price REAL NOT NULL CHECK (price >= 0)
);

CREATE INDEX IF NOT EXISTS idx_orders_user_created ON orders(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id);
" : "
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
);

CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    total NUMERIC(10, 2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
);

CREATE TABLE IF NOT EXISTS order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_name VARCHAR(255) NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    price NUMERIC(10, 2) NOT NULL CHECK (price >= 0)
);

CREATE INDEX IF NOT EXISTS idx_orders_user_created ON orders(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id);
";

try {
    $pdo->exec($sql);
    if ($isSqlite) {
        $sqliteMigrations = [
            'users' => [
                'role' => "ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'user'",
                'phone' => "ALTER TABLE users ADD COLUMN phone TEXT",
                'address' => "ALTER TABLE users ADD COLUMN address TEXT",
                'created_at' => "ALTER TABLE users ADD COLUMN created_at TEXT",
            ],
            'orders' => [
                'user_id' => "ALTER TABLE orders ADD COLUMN user_id INTEGER",
                'total' => "ALTER TABLE orders ADD COLUMN total REAL NOT NULL DEFAULT 0",
                'status' => "ALTER TABLE orders ADD COLUMN status TEXT NOT NULL DEFAULT 'pending'",
                'shipping_address' => "ALTER TABLE orders ADD COLUMN shipping_address TEXT",
                'created_at' => "ALTER TABLE orders ADD COLUMN created_at TEXT",
            ],
            'order_items' => [
                'order_id' => "ALTER TABLE order_items ADD COLUMN order_id INTEGER",
                'product_name' => "ALTER TABLE order_items ADD COLUMN product_name TEXT NOT NULL DEFAULT ''",
                'quantity' => "ALTER TABLE order_items ADD COLUMN quantity INTEGER NOT NULL DEFAULT 1",
                'price' => "ALTER TABLE order_items ADD COLUMN price REAL NOT NULL DEFAULT 0",
            ],
        ];

        foreach ($sqliteMigrations as $table => $missingColumns) {
            $tableColumns = $pdo->query("PRAGMA table_info($table)")->fetchAll();
            $existingColumns = array_column($tableColumns, 'name');

            foreach ($missingColumns as $column => $alterSql) {
                if (!in_array($column, $existingColumns, true)) {
                    $pdo->exec($alterSql);
                }
            }
        }

        $pdo->exec("UPDATE users SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL");
        $pdo->exec("UPDATE orders SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL");
    } else {
        $pdo->exec("
            ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'user';
            ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20);
            ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT;
            ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'UTC');
            ALTER TABLE orders ADD COLUMN IF NOT EXISTS user_id INTEGER REFERENCES users(id) ON DELETE CASCADE;
            ALTER TABLE orders ADD COLUMN IF NOT EXISTS total NUMERIC(10, 2) NOT NULL DEFAULT 0;
            ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'pending';
            ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_address TEXT;
            ALTER TABLE orders ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'UTC');
            ALTER TABLE order_items ADD COLUMN IF NOT EXISTS order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE;
            ALTER TABLE order_items ADD COLUMN IF NOT EXISTS product_name VARCHAR(255) NOT NULL DEFAULT '';
            ALTER TABLE order_items ADD COLUMN IF NOT EXISTS quantity INTEGER NOT NULL DEFAULT 1;
            ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price NUMERIC(10, 2) NOT NULL DEFAULT 0;
        ");
    }

    echo "Tabelele au fost create sau exista deja. Driver: " . htmlspecialchars($dbDriver) . "\n";
} catch (PDOException $e) {
    echo "Eroare: " . htmlspecialchars($e->getMessage()) . "\n";
}
?>
