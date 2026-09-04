-- Pierwsza prawdziwa tabela core-svc (Faza 5). Odpowiednik Laravelowej
-- app/Modules/Init/database/migrations/2026_09_02_000001_create_init_codes.php
-- — bigint PK, osobna unikalna kolumna uuid (business key do publicznego
-- skanu). CHECK wymusza dokładnie jedno z organization_id/owner_user_id —
-- w Laravelu ten niezmiennik był pilnowany tylko w kontrolerze (komentarz w
-- oryginalnej migracji to potwierdza), tu wreszcie egzekwowany przez bazę.
create table init_codes (
    id                      bigint generated always as identity primary key,
    uuid                    varchar(36) not null unique,
    label                   varchar(255) not null,
    organization_id         bigint,
    owner_user_id           bigint,
    shop_item_id            bigint,
    target_organization_id  bigint,
    active                  boolean not null default true,
    created_at              timestamptz not null default now(),
    updated_at              timestamptz not null default now(),

    constraint init_codes_exactly_one_owner check (
        (organization_id is not null and owner_user_id is null)
        or (organization_id is null and owner_user_id is not null)
    )
);

create index init_codes_organization_id_idx on init_codes (organization_id);
create index init_codes_owner_user_id_idx on init_codes (owner_user_id);
