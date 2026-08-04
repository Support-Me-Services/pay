-- Wspólne dla KAŻDEJ fizycznej bazy tenanta (Storefront I Gateway) — panel
-- admina loguje się przez ten sam `users` niezależnie od hosta/modułu,
-- dokładnie jak w Laravelu (ResolveTenant przełącza połączenie domyślne,
-- ale `App\Models\User` nie ma nadpisanego `$connection`). Sesje idą przez
-- Spring Session + Redis (decyzja z planu), więc `sessions`/
-- `password_reset_tokens` z oryginału celowo pominięte.

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    handle VARCHAR(255) UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMPTZ,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
