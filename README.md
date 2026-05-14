# Student Record

Student Record — це вебзастосунок для обліку учнів, занять і користувачів.

Проєкт написаний на Symfony та запускається через Docker.  
Symfony-застосунок знаходиться у папці `app/`, а Docker-конфігурація знаходиться в корені проєкту.

## Структура проєкту

```text
student-record/
├── app/
│   ├── bin/
│   ├── config/
│   ├── migrations/
│   ├── public/
│   ├── src/
│   ├── templates/
│   ├── composer.json
│   ├── composer.lock
│   ├── package.json
│   └── package-lock.json
│
├── docker/
│   └── nginx/
│       └── default.conf
│
├── Dockerfile
├── docker-compose.yml
├── .gitignore
└── README.md
```

---

# 1. Вимоги

Перед запуском потрібно встановити:

- Git
- Docker
- Docker Compose

Перевірити, що все встановлено:

```bash
git --version
docker --version
docker compose version
```

---

# 2. Клонування проєкту

Склонуйте репозиторій:

```bash
git clone https://github.com/andreeykaa/student-record.git
```

Перейдіть у папку проєкту:

```bash
cd student-record
```

---

# 3. Налаштування `.env`

Symfony-застосунок знаходиться в папці `app/`.

У проєкті може бути файл:

```text
app/.env
```

Для локального запуску краще створити власний локальний файл:

```bash
cp app/.env app/.env.local
```

Відкрийте файл:

```bash
nano app/.env.local
```

Перевірте `DATABASE_URL`.

Для Docker важливо, щоб у `DATABASE_URL` використовувалась назва сервісу бази даних із `docker-compose.yml`, а не `localhost`.

Приклад:

```env
DATABASE_URL="mysql://app:app@database:3306/student_record?serverVersion=8.0"
```

Або, якщо сервіс бази даних у `docker-compose.yml` називається `db`, тоді:

```env
DATABASE_URL="mysql://app:app@db:3306/student_record?serverVersion=8.0"
```

Не потрібно писати так:

```env
DATABASE_URL="mysql://app:app@localhost:3306/student_record"
```

У Docker `localhost` всередині PHP-контейнера означає сам PHP-контейнер, а не контейнер бази даних.

---

# 4. Запуск Docker

У корені проєкту виконайте:

```bash
docker compose up -d --build
```

Ця команда:

- збирає Docker-образи;
- запускає контейнери;
- запускає вебсервер;
- запускає базу даних.

Перевірити, чи контейнери запущені:

```bash
docker compose ps
```

---

# 5. Встановлення PHP-залежностей

Після запуску контейнерів потрібно встановити Composer-залежності.

Якщо PHP/Symfony сервіс у `docker-compose.yml` називається `app`, виконайте:

```bash
docker compose exec app composer install
```

Якщо команда не працює через те, що `composer.json` лежить у папці `app/`, використайте:

```bash
docker compose exec app sh -c "cd app && composer install"
```

Якщо сервіс називається не `app`, подивіться назву сервісу командою:

```bash
docker compose ps
```

Потім замініть `app` у командах на правильну назву сервісу.

Наприклад, якщо сервіс називається `php`:

```bash
docker compose exec php composer install
```

або:

```bash
docker compose exec php sh -c "cd app && composer install"
```

---

# 6. Встановлення frontend-залежностей

Якщо у проєкті використовуються npm-залежності, виконайте:

```bash
docker compose exec app sh -c "cd app && npm install"
```

Якщо Node.js не встановлений у контейнері, можна виконати локально на комп’ютері:

```bash
cd app
npm install
cd ..
```

Якщо проєкт використовує тільки готові CSS/JS-файли з папки `public/`, цей крок можна пропустити.

---

# 7. Створення бази даних

Якщо база даних ще не створена, виконайте:

```bash
docker compose exec app sh -c "cd app && php bin/console doctrine:database:create"
```

Якщо база вже існує, Symfony може показати повідомлення, що база даних уже створена. Це нормально.

---

# 8. Запуск міграцій

Після створення бази потрібно застосувати міграції:

```bash
docker compose exec app sh -c "cd app && php bin/console doctrine:migrations:migrate"
```

Якщо потрібно виконати без підтвердження:

```bash
docker compose exec app sh -c "cd app && php bin/console doctrine:migrations:migrate --no-interaction"
```

---

# 9. Створення першого адміністратора

У проєкті є консольна команда:

```bash
app:create-user
```

Вона дозволяє створити користувача через термінал.

Формат команди:

```bash
php bin/console app:create-user username password
```

Команда приймає два обов’язкові аргументи:

```text
username — ім’я користувача
password — пароль у відкритому вигляді
```

Також команда має опцію:

```bash
--admin
```

Ця опція створює користувача з роллю адміністратора.

Щоб створити першого адміністратора, виконайте:

```bash
docker compose exec app sh -c "cd app && php bin/console app:create-user admin admin123 --admin"
```

Після цього буде створений користувач:

```text
Username: admin
Password: admin123
Role: ROLE_ADMIN
```

Рекомендується після першого входу змінити пароль на більш надійний.

Приклад з іншим логіном і паролем:

```bash
docker compose exec app sh -c "cd app && php bin/console app:create-user superadmin StrongPassword123 --admin"
```

---

# 10. Створення звичайного користувача через консоль

Щоб створити звичайного користувача без прав адміністратора, виконайте команду без опції `--admin`:

```bash
docker compose exec app sh -c "cd app && php bin/console app:create-user student student123"
```

Такий користувач буде створений як звичайний користувач.

---

# 11. Відкриття сайту

Після запуску Docker відкрийте сайт у браузері:

```text
http://localhost:8080
```

Якщо у `docker-compose.yml` вказаний інший порт, використовуйте його.

Наприклад, якщо у `docker-compose.yml` є:

```yaml
ports:
  - "8000:80"
```

тоді сайт буде доступний за адресою:

```text
http://localhost:8000
```

---

# 12. Авторизація

Після створення адміністратора відкрийте сайт у браузері та увійдіть у систему.

Наприклад, якщо створювали адміністратора командою:

```bash
docker compose exec app sh -c "cd app && php bin/console app:create-user admin admin123 --admin"
```

то для входу використовуйте:

```text
Username: admin
Password: admin123
```

---

# 13. Швидкий запуск з нуля

Повна послідовність команд для запуску після клонування:

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

Після цього відкрийте:

```text
http://localhost:8080
```

---

# 14. Корисні Docker-команди

Запустити контейнери:

```bash
docker compose up -d
```

Запустити контейнери з перебудовою образів:

```bash
docker compose up -d --build
```

Зупинити контейнери:

```bash
docker compose down
```

Зупинити контейнери і видалити volumes:

```bash
docker compose down -v
```

Увага: команда `docker compose down -v` видаляє дані бази, якщо база зберігається у Docker volume.

Переглянути список контейнерів:

```bash
docker compose ps
```

Переглянути логи:

```bash
docker compose logs
```

Переглянути логи конкретного сервісу:

```bash
docker compose logs app
```

Зайти всередину контейнера:

```bash
docker compose exec app sh
```

---

# 15. Корисні Symfony-команди

Усі команди виконуються всередині PHP/Symfony контейнера.

Очистити кеш:

```bash
docker compose exec app sh -c "cd app && php bin/console cache:clear"
```

Перевірити список маршрутів:

```bash
docker compose exec app sh -c "cd app && php bin/console debug:router"
```

Перевірити схему бази даних:

```bash
docker compose exec app sh -c "cd app && php bin/console doctrine:schema:validate"
```

Переглянути список доступних команд:

```bash
docker compose exec app sh -c "cd app && php bin/console list"
```

---

# 16. Повне очищення бази даних

Якщо потрібно повністю очистити локальну базу даних, можна видалити Docker volumes:

```bash
docker compose down -v
```

Після цього запустіть контейнери заново:

```bash
docker compose up -d --build
```

Потім знову виконайте міграції:

```bash
docker compose exec app sh -c "cd app && php bin/console doctrine:migrations:migrate --no-interaction"
```

І створіть першого адміністратора:

```bash
docker compose exec app sh -c "cd app && php bin/console app:create-user admin admin123 --admin"
```

---

# 17. Якщо потрібно оновити проєкт

Щоб отримати останні зміни з GitHub:

```bash
git pull
```

Після оновлення коду бажано виконати:

```bash
docker compose up -d --build
docker compose exec app sh -c "cd app && composer install"
docker compose exec app sh -c "cd app && php bin/console doctrine:migrations:migrate --no-interaction"
docker compose exec app sh -c "cd app && php bin/console cache:clear"
```

---

# 18. Можливі проблеми

## Помилка: `service app not found`

Якщо команда типу:

```bash
docker compose exec app ...
```

не працює, перевірте назви сервісів:

```bash
docker compose ps
```

Потім замініть `app` у командах на реальну назву сервісу.

Наприклад, якщо сервіс називається `php`, тоді команда буде:

```bash
docker compose exec php sh -c "cd app && php bin/console doctrine:migrations:migrate"
```

---

## Помилка: `bin/console not found`

Це означає, що команда виконується не з тієї директорії всередині контейнера.

Зайдіть у контейнер:

```bash
docker compose exec app sh
```

Перевірте файли:

```bash
ls
```

Якщо бачите папку `app`, перейдіть у неї:

```bash
cd app
```

Після цього перевірте:

```bash
php bin/console
```

---

## Помилка підключення до бази даних

Перевірте `DATABASE_URL` у файлі:

```text
app/.env.local
```

У Docker не потрібно використовувати `localhost`.

Неправильно:

```env
DATABASE_URL="mysql://app:app@localhost:3306/student_record"
```

Правильно:

```env
DATABASE_URL="mysql://app:app@database:3306/student_record"
```

або:

```env
DATABASE_URL="mysql://app:app@db:3306/student_record"
```

Назва `database` або `db` має відповідати назві сервісу бази даних у `docker-compose.yml`.

---

## Помилка з правами доступу до `var/`

Якщо Symfony не може записати кеш або логи, виконайте:

```bash
docker compose exec app sh -c "cd app && chmod -R 777 var"
```

Для локальної розробки це допустимо.

---

## Помилка: порт уже зайнятий

Якщо порт `8080` уже зайнятий, змініть порт у `docker-compose.yml`.

Наприклад, було:

```yaml
ports:
  - "8080:80"
```

Можна змінити на:

```yaml
ports:
  - "8081:80"
```

Після цього перезапустіть Docker:

```bash
docker compose down
docker compose up -d --build
```

І відкрийте:

```text
http://localhost:8081
```

---
