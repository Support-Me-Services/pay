#!/bin/bash
# Postgres tworzy automatycznie TYLKO bazę z POSTGRES_DB (nfc_pay) — druga
# fizyczna baza tenantów Storefront (nfc_shop1) musi być dołożona tutaj.
set -euo pipefail

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-SQL
    SELECT 'CREATE DATABASE nfc_shop1'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'nfc_shop1')\gexec
SQL
