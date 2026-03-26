# Skill: Деплой та робота з продом AoE2 Companion Web

## Мета
Підключення до продового сервера, деплой коду, запуск міграцій та робота з базою даних для проєкту aoe2-companion-web.

## Сервер

- **Хост**: DigitalOcean Droplet
- **IP**: 188.166.78.173
- **SSH**: `ssh root@188.166.78.173` (ключ налаштований)
- **ОС**: Ubuntu 24.04 LTS

## Структура на сервері

```
/opt/
├── aoe2-companion-web/        # Код додатку (git clone, branch: main)
├── aoe2-data/                 # Спарсені JSON-дані (цивілізації, юніти, техи)
├── docker-prod-webserver/     # Docker Compose інфраструктура
```

## Контейнери

| Контейнер | Призначення |
|-----------|------------|
| `prod_aoe2_nginx` | Nginx веб-сервер |
| `prod_aoe2_php` | PHP-FPM (виконує Yii2 додаток) |
| `prod_mysql` | MySQL 5.7 (спільний для всіх проєктів) |

## Деплой нового коду

```bash
# 1. Підтягнути код
ssh root@188.166.78.173 "cd /opt/aoe2-companion-web && git pull origin main"

# 2. Запустити міграції (якщо є нові)
ssh root@188.166.78.173 "docker exec prod_aoe2_php php /var/www/html/aoe2-companion-web/yii migrate --interactive=0"

# 3. Перезапустити PHP-FPM (скинути opcache, якщо потрібно)
ssh root@188.166.78.173 "cd /opt/docker-prod-webserver && docker compose restart aoe2-php"
```

## Копіювання зображень

Зображення (`web/images/`) **не комітяться в git** (є в `.gitignore`), тому їх потрібно копіювати на сервер окремо через `scp`.

```bash
# Всі іконки технологій (~4 MB, 199 файлів)
scp -r web/images/techs/ root@188.166.78.173:/opt/aoe2-companion-web/web/images/techs/

# Іконки юнітів
scp -r web/images/units/ root@188.166.78.173:/opt/aoe2-companion-web/web/images/units/

# Емблеми цивілізацій
scp -r web/images/civs/ root@188.166.78.173:/opt/aoe2-companion-web/web/images/civs/

# Або все разом
scp -r web/images/ root@188.166.78.173:/opt/aoe2-companion-web/web/images/
```

**Gitignored папки з зображеннями:**
- `/web/images/units` — іконки юнітів
- `/web/images/civs` — емблеми цивілізацій
- `/web/images/buildings` — будівлі
- `/web/images/techs` — іконки технологій
- `/web/images/stats` — іконки характеристик
- `/web/images/resources` — іконки ресурсів

## Yii2 консольні команди

```bash
# Загальний формат
ssh root@188.166.78.173 "docker exec prod_aoe2_php php /var/www/html/aoe2-companion-web/yii <command>"

# Приклади:
# Міграції
ssh root@188.166.78.173 "docker exec prod_aoe2_php php /var/www/html/aoe2-companion-web/yii migrate --interactive=0"
ssh root@188.166.78.173 "docker exec prod_aoe2_php php /var/www/html/aoe2-companion-web/yii migrate/history 10 --interactive=0"

# Імпорт даних
ssh root@188.166.78.173 "docker exec prod_aoe2_php php /var/www/html/aoe2-companion-web/yii import/all"
ssh root@188.166.78.173 "docker exec prod_aoe2_php php /var/www/html/aoe2-companion-web/yii import/civilizations"
ssh root@188.166.78.173 "docker exec prod_aoe2_php php /var/www/html/aoe2-companion-web/yii import/units"
ssh root@188.166.78.173 "docker exec prod_aoe2_php php /var/www/html/aoe2-companion-web/yii import/availability"
```

## База даних

### Продова БД
```bash
# Назва бази: aoe2-companion
# Користувач/пароль визначаються через env vars контейнера prod_aoe2_php

# Виконати SQL-запит
ssh root@188.166.78.173 "docker exec prod_aoe2_php php /var/www/html/aoe2-companion-web/yii sql 'SELECT ...'"

# Або через MySQL-контейнер напряму (потрібен пароль з .env):
ssh root@188.166.78.173 "cd /opt/docker-prod-webserver && docker exec prod_mysql mysql -uroot -p\$(grep MYSQL_ROOT_PASSWORD .env | cut -d= -f2) 'aoe2-companion' -e 'SELECT ...'"
```

### Локальна БД
```bash
docker exec webserver_mysql mysql -u user -p123456 aoe2-companion -e "..."
```

## Локальна розробка

### Шляхи
- **Код**: `/Users/nasa/_Dev/_projects/aoe2-companion-web/`
- **Дані**: `/Users/nasa/_Dev/_projects/aoe2-companion/data/`
- **Docker**: `/Users/nasa/_Dev/_projects/docker-dev-webserver/`

### Контейнери (локальні)
| Контейнер | Призначення |
|-----------|------------|
| `webserver_php` | PHP-FPM |
| `webserver_mysql` | MySQL |
| `webserver_nginx` | Nginx |

### Запуск Yii2 команд локально
```bash
docker exec webserver_php php /var/www/html/aoe2-companion/aoe2-companion-web/yii <command>
```

**Увага**: локально код монтується з `/Users/nasa/_Dev/_projects/aoe2-companion/aoe2-companion-web/` (вкладена папка всередині aoe2-companion), а git-репо знаходиться в `/Users/nasa/_Dev/_projects/aoe2-companion-web/`. Після змін в git-репо потрібно скопіювати файли в монтовану папку, або синхронізувати їх.

## Логи

```bash
# Nginx
ssh root@188.166.78.173 "docker logs prod_aoe2_nginx --tail 50"

# PHP-FPM
ssh root@188.166.78.173 "docker logs prod_aoe2_php --tail 50"

# MySQL
ssh root@188.166.78.173 "docker logs prod_mysql --tail 50"
```

## Важливі нюанси

- **Git branch**: `main` (і локально, і на проді)
- **JSON-дані на проді**: `/opt/aoe2-data/` — окрема папка, не всередині web-додатку
- **ImportController** шукає дані через `$this->dataDir = dirname(Yii::getAlias('@app')) . '/data'` — переконатися, що шлях резолвиться коректно на проді
- **Невідповідність назв цивілізацій**: В JSON юнітів використовуються "Mayans"/"Incas", а в JSON цивілізацій — "Maya"/"Inca". Маппінг aliases налаштований в `ImportController::$civAliases`
