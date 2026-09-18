CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'user',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at VARCHAR(40) NOT NULL
);

CREATE TABLE IF NOT EXISTS rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key_name VARCHAR(255) NOT NULL,
    attempted_at INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS rate_limits_lookup ON rate_limits (key_name, attempted_at);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL,
    action VARCHAR(120) NOT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    metadata TEXT NULL,
    created_at VARCHAR(40) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS audit_logs_user_id ON audit_logs (user_id);
CREATE INDEX IF NOT EXISTS audit_logs_action ON audit_logs (action);
