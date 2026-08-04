-- Port skonsolidowany (stan finalny) ze wszystkich migracji Laravela w
-- app/Modules/Storefront/database/migrations/, na branchu `main` (BEZ
-- short_description na job_positions — to pole istnieje tylko na
-- niepowiązanym branchu `kotlin-migration`, nie na `main`). Tabela `users`
-- jest we wspólnej migracji `common/V1` (potrzebna też na fizycznej bazie
-- Gateway — patrz FlywayMigrationConfig), NIE tutaj.

CREATE TABLE salespeople (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(255),
    voivodeships TEXT, -- JSON (lista województw)
    active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE products (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(255),
    purpose VARCHAR(255),
    slug VARCHAR(255) NOT NULL UNIQUE,
    description_html TEXT,
    pickup_instruction TEXT,
    price INTEGER NOT NULL CHECK (price >= 0), -- grosze
    tag_uid VARCHAR(255) NOT NULL UNIQUE,
    main_image VARCHAR(255),
    active BOOLEAN NOT NULL DEFAULT true,
    phone VARCHAR(255),
    website VARCHAR(255),
    voivodeship VARCHAR(255),
    status VARCHAR(20) NOT NULL DEFAULT 'kontakt',
    salesperson_id BIGINT REFERENCES salespeople (id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_products_status ON products (status);

CREATE TABLE product_images (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products (id) ON DELETE CASCADE,
    path VARCHAR(255) NOT NULL,
    sort INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE orders (
    id UUID PRIMARY KEY,
    product_id BIGINT REFERENCES products (id), -- NULL: koszyk/sklep firmowy (nie 1 produkt)
    transaction_id UUID, -- uuid z bramki Gateway, celowo bez FK (inna baza)
    amount INTEGER NOT NULL CHECK (amount >= 0), -- grosze
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'failed')),
    paid_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_orders_transaction_id ON orders (transaction_id);

CREATE TABLE events (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT REFERENCES products (id) ON DELETE SET NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('tag_open', 'page_view', 'buy_click', 'purchase')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_events_product_type_created ON events (product_id, type, created_at);

CREATE TABLE job_positions (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    employment_type VARCHAR(255),
    description_html TEXT,
    active BOOLEAN NOT NULL DEFAULT true,
    sort INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE job_applications (
    id BIGSERIAL PRIMARY KEY,
    job_position_id BIGINT REFERENCES job_positions (id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(255),
    message TEXT,
    cv_path VARCHAR(255),
    cv_original_name VARCHAR(255),
    is_read BOOLEAN NOT NULL DEFAULT false,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    future_recruitment_consent BOOLEAN NOT NULL DEFAULT false,
    future_recruitment_consent_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_job_applications_status ON job_applications (status);
CREATE INDEX idx_job_applications_future_consent ON job_applications (future_recruitment_consent);

CREATE TABLE contact_messages (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(255),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE parish_notes (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products (id) ON DELETE CASCADE,
    body TEXT NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'kontakt',
    author VARCHAR(255),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_parish_notes_product_id ON parish_notes (product_id);

CREATE TABLE categories (
    id BIGSERIAL PRIMARY KEY,
    parent_id BIGINT REFERENCES categories (id) ON DELETE SET NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    label_html TEXT,
    label_text VARCHAR(255) NOT NULL,
    intro TEXT,
    icon VARCHAR(255),
    source VARCHAR(20) NOT NULL DEFAULT 'none',
    position INTEGER NOT NULL DEFAULT 0,
    active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_categories_parent_id ON categories (parent_id);
CREATE INDEX idx_categories_position ON categories (position);

CREATE TABLE potential_parishes (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(255),
    address VARCHAR(255),
    voivodeship VARCHAR(255),
    denomination VARCHAR(255),
    phone VARCHAR(255),
    lat NUMERIC(10, 7) NOT NULL,
    lon NUMERIC(10, 7) NOT NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'nowa',
    salesperson_id BIGINT REFERENCES salespeople (id) ON DELETE SET NULL,
    note TEXT,
    called_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_potential_parishes_voivodeship ON potential_parishes (voivodeship);
CREATE INDEX idx_potential_parishes_city ON potential_parishes (city);
CREATE INDEX idx_potential_parishes_status ON potential_parishes (status);

CREATE TABLE shop_items (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users (id),
    slug VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    min_amount INTEGER NOT NULL CHECK (min_amount >= 0),
    price INTEGER CHECK (price >= 0),
    description TEXT,
    is_default BOOLEAN NOT NULL DEFAULT false,
    tag_uid VARCHAR(255) UNIQUE,
    active BOOLEAN NOT NULL DEFAULT true,
    sort INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (user_id, slug)
);

CREATE INDEX idx_shop_items_user_id ON shop_items (user_id);

CREATE TABLE beneficiary_nodes (
    id BIGSERIAL PRIMARY KEY,
    heading VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    image_side VARCHAR(10) NOT NULL DEFAULT 'left',
    image_scale INTEGER NOT NULL DEFAULT 100,
    image_x INTEGER NOT NULL DEFAULT 0,
    image_y INTEGER NOT NULL DEFAULT 0,
    text_align VARCHAR(10) NOT NULL DEFAULT 'left',
    body_html TEXT,
    position INTEGER NOT NULL DEFAULT 0,
    active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
