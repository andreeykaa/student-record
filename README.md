# Student Record

Student Record — вебзастосунок для обліку учнів, занять і користувачів.

Стек:
- PHP / Symfony
- MySQL
- Docker

Symfony-застосунок знаходиться в `app/`.

---

# Requirements

- Git
- Docker
- Docker Compose

Перевірка:

```bash
git --version
docker --version
docker compose version
```

---

# Installation

## 1. Clone repository

```bash
git clone https://github.com/andreeykaa/student-record.git
cd student-record
```

---

## 2. Configure environment

Створіть локальний env-файл:

```bash
cp app/.env app/.env.local
```

Перевірте `DATABASE_URL` у `app/.env.local`.

Для Docker використовуйте назву сервісу бази даних, а не `localhost`.

Приклад:

```env
DATABASE_URL="mysql://app:app@database:3306/student_record?serverVersion=8.0"
```

---

## 3. Start containers

```bash
docker compose up -d --build
```

Перевірка:

```bash
docker compose ps
```

---

## 4. Install dependencies

```bash
docker compose exec app sh -c "cd app && composer install"
```

---

## 5. Create database and run migrations

```bash
docker compose exec app sh -c "cd app && php bin/console doctrine:database:create"

docker compose exec app sh -c "cd app && php bin/console doctrine:migrations:migrate --no-interaction"
```

---

## 6. Create admin user

```bash
docker compose exec app sh -c "cd app && php bin/console app:create-user admin admin123 --admin"
```

---

## 7. Open application

```text
http://localhost:8080
```

---

# Quick Start

```bash
git clone https://github.com/andreeykaa/student-record.git
cd student-record

cp app/.env app/.env.local

docker compose up -d --build

docker compose exec app sh -c "cd app && composer install"

docker compose exec app sh -c "cd app && php bin/console doctrine:database:create"

docker compose exec app sh -c "cd app && php bin/console doctrine:migrations:migrate --no-interaction"

docker compose exec app sh -c "cd app && php bin/console app:create-user admin admin123 --admin"
```

---

# Useful Commands

## Docker

```bash
docker compose up -d
docker compose down
docker compose logs
docker compose ps
```

## Symfony

```bash
docker compose exec app sh -c "cd app && php bin/console cache:clear"

docker compose exec app sh -c "cd app && php bin/console debug:router"

docker compose exec app sh -c "cd app && php bin/console list"
```

---

# Troubleshooting

## Database connection error

Перевірте `DATABASE_URL` у `app/.env.local`.

У Docker не використовуйте `localhost`.

---

## Service not found

Перевірте назви сервісів:

```bash
docker compose ps
```

---

## Permission issues with `var/`

```bash
docker compose exec app sh -c "cd app && chmod -R 777 var"
```

---

## Port already in use

Змініть порт у `docker-compose.yml`.

Наприклад:

```yaml
ports:
  - "8081:80"
```
