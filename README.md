# Hosts Manager

# Тестовое задание

[Описание тестового задание](#test-task-description)

Установка зависимостей

```bash
composer install
``` 

Запуск Docker-контейнеров

```bash
docker compose up -d
``` 

Применение миграций

```bash
./vendor/bin/sail artisan migrate
``` 

Наполнение БД тестовыми пользователями

```bash
php artisan db:seed
``` 

Запуск тестов

```bash
composer test
``` 

Запуск очередей для асинхронных задач

```bash
php artisan queue:work
``` 

# Роуты

Для удобства - коллекция для postman в корне проекта [hosts.postman_collection.json](./hosts.postman_collection.json) <a id="postman-collection"></a>


## Создать хост <br>

### POST - **/api/hosts** <br>
body
  ```json
 {
    "hostname": "app-01",
    "ip": "10.0.1.5",
    "tags": [
        "web",
        "prod"
    ]
}
 ```

## Получить список хостов <br>

### GET - /api/hosts
query
 ```json
 {
    "page": {
        "after": "eyJpZCI6ImExMjA5OWYzLWM4MGMtNDY0NS1iYzY1LTY5", // Указатель пагинации
        "size": 15 // Кол-во элементов на странице
    }, 
    "q": "app" // Поиск
}
 ```

## Запросить изменение хоста (только для admin`) <br>

### PATCH - **/api/hosts/{hostId}/rename**

body
```json
{
    "new_hostname": "new-app-1" // Новое имя хоста
}
```
headers
```json
{
  "Idempotency-Key": "00000000-0000-0000-0000-000000000000" // Уникальный ключ
}
```
> [!IMPORTANT]
> Idempotency-Key в заголовках обязателен!

## Получить информацию по операции <br>

### GET - **/api/operations/{operationId}**

## Получить токен <br>

### POST - **/api/auth**
body
 ```json
 {
    "name": "admin",
    "password": "password"
}
 ```
---
Тестовые пользователи

| Логин |   Пароль | доступ к `rename` |
|:-----:|---------:|------------------:|
| admin | password |                Да |
| user  | password |               Нет |

> [!IMPORTANT]
> Если использовать [коллекцию postman](#postman-collection), то `access-token` подставится автоматически

## Логи

Логи пишутся в файл `/storage/logs/operations-info.log`

---
<br>

# <a id="test-task-description">Описание тестового задание</a>

## Технические требования

- Laravel 10/11, PHP ≥ 8.2, PostgreSQL ≥ 14.
- Без фронта. Чистый REST + тесты.
- Документация в README.md, .env.example, скрипт запуска (Docker Compose).

### Бизнес-задача (упрощённо)

Храним список хостов и умеем переименовывать хост асинхронно.

- Создание хоста — синхронно.
- Переименование — асинхронно (через очередь), с идемпотентностью по заголовку Idempotency-Key.
- Поиск по хостам по части имени/по IP.
  Модель данных (миграции)

1. hosts
    - id (uuid, PK)
    - hostname (varchar, UNIQUE, простая проверка на формат)
    - ip (inet в Postgres)
    - tags (jsonb, опционально)
    - timestamps
      Индексы:
    - GIN pg_trgm на hostname (для LIKE/ILIKE)
    - (опц.) GIN на tags
      Окей, если: для inet сделан raw SQL в миграции, а не строка; есть check или валидация для hostname.
2. operations — трекинг асинхронных задач
    - id (uuid)
    - type (enum: rename)
    - status (enum: pending|processing|done|failed)
    - host_id (FK → hosts)
    - payload (jsonb) — например, {"new_hostname":"..."}
    - idempotency_key (varchar, UNIQUE, nullable)
    - error (text, nullable)
    - timestamps
    -

### Эндпойнты (контракты)

1. `POST /api/hosts` — создать хост

- Body:

```json
 {
    "hostname": "app-01",
    "ip": "10.0.1.5",
    "tags": [
        "web",
        "prod"
    ]
}
```

- Валидация:
    - `FormRequest` (hostname по простому regex, ip — ipv4/inet),
    - Уникальность hostname.
- Ответ: `201 Created` + объект хоста.

2. `GET /api/hosts?q=...&page[size]=...&page[after]=...` — список/поиск

- Фильтр: q ищет по части hostname и точному ip.
- Пагинация: можно обычную, бонус — keyset.
- Ответ: 200 OK + массив.

3. PATCH /api/hosts/{id}/rename — запросить переименование

- Headers: Idempotency-Key: <uuid> (обязателен)
- Body:

```json
{
    "new_hostname": "app-01-renamed"
}
```

- Действие: создаёт запись в operations и ставит Job в очередь.
- Ответ: `202 Accepted + { "operation_id": "..." }`
- Повтор с тем же Idempotency-Key → вернуть тот же operation_id (идемпотентность).

4. `GET /api/operations/{id}` — статус операции

- Ответ: `200 OK + { "status": "...", "error": null|"...", "host": {...} }`

### Поведение Job (асинхронно)

- Проверяет, что new_hostname уникален, «переименовывает» запись в БД.
- Метит операцию done или failed (c error).
- Идемпотентность: если done — повтор не меняет результат.
- Рейт-лимит: повесь `Throttle/RateLimiter` на rename-роут (любая разумная реализация).

### Тесты (минимум)

- Feature:
    - успешное создание хоста;
    - валидация на неверный ip/дубликат hostname;
    - rename → `202` → исполнение Job → done;
    - повтор rename с тем же `Idempotency-Key` возвращает тот же `operation_id`;
    - поиск q находит по части hostname.
    - (опц.) Unit: сервис/валидатор hostname.

#### Политика доступа (например, только роль admin может rename).

#### Нормальные коды ошибок: `400`/`401`/`403`/`409`/`422`/`500`.

#### Логи/метрики (да хоть события в laravel.log с correlation-id).


