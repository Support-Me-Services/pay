# Sprzątanie po cutoverze React (2026-07-21)

Podczas przełączenia produkcji z Blade na React/Inertia zostawiono na serwerze
prod (`34.118.46.252`) artefakty bezpieczeństwa umożliwiające szybki rollback.
**Usuń je dopiero po potwierdzeniu stabilności produkcji** (zalecane: kilka dni
bez incydentów). Po usunięciu `vendor.blade-bak` rollback jednym skryptem
przestaje działać — wróć wtedy do Blade ręcznie (`git reset --hard 7703492` +
`composer install`).

## Co posprzątać (na prod, jako sudo)

```bash
# 1. Worktree build-owy (świeży build robił się tam poza żywym katalogiem)
sudo git -C /var/www/support-me worktree remove --force /var/www/support-me-next
sudo git -C /var/www/support-me worktree prune

# 2. Backup vendora sprzed cutoveru (wersja Blade)
sudo rm -rf /var/www/support-me/vendor.blade-bak

# 3. Skrypty jednorazowe cutoveru/revertu
sudo rm -f /var/www/revert-to-blade.sh /var/www/cutover.sh
```

## Uwagi
- Kolumny `future_recruitment_consent[_at]` w bazie są **addytywne** — zostają
  nawet po ewentualnym rollbacku (Blade ich nie używa, nie przeszkadzają).
- Po sprzątnięciu przyszłe deploye idą standardowo przez `bin/deploy.sh`
  (buduje na serwerze, nie potrzebuje worktree).
- Node 20 i usługa systemd `pay-ssr` **zostają** — są potrzebne na stałe.
