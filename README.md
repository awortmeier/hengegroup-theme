# hengegroup-theme

WordPress-/WooCommerce-Theme der Hengegroup (Tailwind, Vite, PHP-Komponenten nach shadcn/ui).

## Setup

```sh
composer install && pnpm install
cp .env.example .env   # FTP-Zugangsdaten
pnpm build             # bzw. pnpm deploy:changed
```

Checks: `composer lint`, `composer test`, `pnpm format:check`, `pnpm test`.

## Anforderungen

| Komponente | Minimum | Getestet |
| ---------- | ------- | -------- |
| WordPress  | 6.5     | 7.0      |
| PHP        | 8.2     | 8.3+     |
| Node.js    | —       | 22       |
| pnpm       | —       | 11.18.0  |

## Doku

- `CLAUDE.md`: Konventionen
- `docs/entscheidungen.md`: warum etwas so gebaut ist
- `docs/how-to.md`: Pflege und Erweiterung
- `docs/to-do.md`: offene Punkte
- `CHANGELOG.md`: Aenderungen
