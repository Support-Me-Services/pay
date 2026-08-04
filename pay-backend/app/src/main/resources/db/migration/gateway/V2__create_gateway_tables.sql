-- Port 1:1 z app/Modules/Gateway/database/migrations/2026_06_11_000001_create_gateway_tables.php
-- (MySQL -> Postgres). BEZ antitheft_checks — moduł uznany za fikcyjny i wycięty
-- z portu (decyzja produktowa, patrz plan migracji).

CREATE TABLE shops (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    base_url VARCHAR(255) NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    payment_mode VARCHAR(20) NOT NULL DEFAULT 'classic' CHECK (payment_mode IN ('classic', 'app2app')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE tags (
    id BIGSERIAL PRIMARY KEY,
    shop_id BIGINT NOT NULL REFERENCES shops (id) ON DELETE CASCADE,
    tag_uid VARCHAR(255) NOT NULL UNIQUE,
    target_url VARCHAR(255) NOT NULL,
    label VARCHAR(255),
    active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE transactions (
    id UUID PRIMARY KEY,
    shop_id BIGINT NOT NULL REFERENCES shops (id),
    tag_id BIGINT REFERENCES tags (id) ON DELETE SET NULL,
    product_external_id VARCHAR(255) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    amount INTEGER NOT NULL CHECK (amount >= 0), -- grosze
    currency VARCHAR(3) NOT NULL DEFAULT 'PLN',
    status VARCHAR(20) NOT NULL DEFAULT 'created'
        CHECK (status IN ('created', 'pending', 'paid', 'failed', 'abandoned')),
    mode VARCHAR(20) NOT NULL CHECK (mode IN ('classic', 'app2app')),
    return_url VARCHAR(500) NOT NULL,
    notify_url VARCHAR(500),
    provider_order_id VARCHAR(255),
    provider_redirect_url VARCHAR(1000),
    paid_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_transactions_shop_status ON transactions (shop_id, status);
CREATE INDEX idx_transactions_created_at ON transactions (created_at);

CREATE TABLE events (
    id BIGSERIAL PRIMARY KEY,
    shop_id BIGINT NOT NULL REFERENCES shops (id) ON DELETE CASCADE,
    tag_id BIGINT REFERENCES tags (id) ON DELETE SET NULL,
    transaction_id UUID, -- celowo BEZ FK, jak w oryginale (luźne powiązanie)
    type VARCHAR(30) NOT NULL
        CHECK (type IN ('tag_open', 'payment_started', 'payment_success', 'payment_failed')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_events_shop_type_created ON events (shop_id, type, created_at);

CREATE TABLE leads (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(255) NOT NULL,
    company VARCHAR(255),
    message TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
