# Skill: Коміт змін AoE2 Companion Web

## Мета
Створення комітів для проєкту aoe2-companion-web з урахуванням особливостей репозиторію.

## Зображення НЕ комітяться

Папка `web/images/` та всі підпапки прописані в `.gitignore`:

```
/web/images/units
/web/images/civs
/web/images/buildings
/web/images/techs
/web/images/stats
/web/images/resources
```

Зображення **копіюються на сервер вручну** через `scp` під час деплою (див. скіл `aoe2-companion-web-deploy`).

При коміті:
- **НЕ додавати** файли з `web/images/` — вони ігноруються git
- **НЕ намагатися** видалити їх з `.gitignore` для коміту
- Якщо зміни включають нові зображення — нагадати користувачу, що для деплою потрібно буде скопіювати їх через `scp`

## Стиль комітів

- Мова: англійська
- Формат: короткий опис змін (1-2 речення)
- Фокус на "що і навіщо", а не "які файли змінено"

## Що комітити

- PHP-код (controllers, models, views, commands, config)
- Міграції (`migrations/`)
- CSS (`web/css/`)
- JS (`web/js/`)
- Composer-файли (`composer.json`, `composer.lock`)

## Що НЕ комітити

- `web/images/*` — зображення (gitignored, копіюються вручну)
- `vendor/` — залежності (gitignored)
- `runtime/` — кеш Yii2 (gitignored)
- `web/assets/` — скомпільовані ассети (gitignored)
- `.env` — секрети (gitignored)
