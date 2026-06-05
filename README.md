# Notification Service

REST API сервис для отправки уведомлений через несколько каналов с гарантией доставки и генерацией отчётов.

**Стек:** Laravel 11 · PHP 8.2 · PostgreSQL · Redis · Docker

---

## Быстрый старт

```bash
# 1. Клонировать репозиторий
git clone <repo-url> && cd notification-service

# 2. Скопировать .env
cp .env.example .env

# 3. Поднять контейнеры
docker compose up -d --build

# 4. Установить зависимости
docker compose exec app composer install

# 5. После composer install добавить:
docker compose exec app bash -c "cp /tmp/laravel/routes/web.php routes/web.php && cp /tmp/laravel/routes/console.php routes/console.php"

# 6. Сгенерировать ключ приложения
docker compose exec app php artisan key:generate

# 7. Прогнать миграции + сиды
docker compose exec app php artisan migrate --seed

# API доступен на http://localhost:8080/api/v1
```

# Особенности

Проект написан с учётом Laravel 11 API. При запуске на Laravel 12 (устанавливается по умолчанию) необходимо вручную создать bootstrap/providers.php и app/Http/Controllers/Controller.php — это сделано в инструкции выше.

---

## API Reference

### Уведомления

| Метод | URL | Описание |
|-------|-----|----------|
| `POST` | `/api/v1/notifications` | Создать уведомление |
| `GET` | `/api/v1/notifications/{id}` | Статус уведомления |
| `GET` | `/api/v1/users/{userId}/notifications` | История уведомлений пользователя |

**POST /api/v1/notifications**
```json
{
  "user_id": 1,
  "message": "Привет, мир!",
  "channel": "email"
}
```
Каналы: `email`, `telegram`

**GET /api/v1/users/{userId}/notifications**

Query-параметры:
- `status` — `pending` | `sent` | `failed`
- `channel` — `email` | `telegram`
- `per_page` — число (1–100, по умолчанию 15)

---

### Отчёты (бонус)

| Метод | URL | Описание |
|-------|-----|----------|
| `POST` | `/api/v1/reports` | Запросить генерацию отчёта |
| `GET` | `/api/v1/reports/{id}` | Статус отчёта |
| `GET` | `/api/v1/reports/{id}/download` | Скачать готовый отчёт (Не вышло:  эндпоинт есть, но из-за особенностей Laravel 12 не отдаёт файл корректно)|

**POST /api/v1/reports**
```json
{
  "user_id": 1,
  "period_from": "2024-01-01",
  "period_to": "2024-01-31"
}
```

---

## Запуск тестов

```bash
# Все тесты
docker compose exec app php artisan test

# Unit отдельно
docker compose exec app vendor/bin/phpunit --testsuite=Unit

# Feature отдельно
docker compose exec app vendor/bin/phpunit --testsuite=Feature
```

## Статический анализ (PHPStan level 5)

```bash
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M
```

## Code style (Laravel Pint)

```bash
# Проверить
docker compose exec app vendor/bin/pint --test

# Применить
docker compose exec app vendor/bin/pint
```

---

## Архитектурные решения

### 1. Расширяемость каналов через интерфейс + теги

Центральный контракт — `NotificationChannelContract`. Каждый канал реализует два метода: `send()` и `channel()`.

`ChannelRouter` получает все реализации через IoC-контейнер Laravel (`app->tag()`), а `AppServiceProvider` регистрирует их. **Для добавления нового канала нужно только:**
1. Создать класс, реализующий `NotificationChannelContract`
2. Добавить его в массив тегов в `AppServiceProvider`

Существующий код не трогается. Это классический Open/Closed Principle.

```
NotificationChannelContract
  ├── EmailChannel
  ├── TelegramChannel
  └── (SmsChannel, PushChannel, ...)  ← добавить сюда
```

### 2. Гарантия доставки через Laravel Queue + retry

`SendNotificationJob` реализует `ShouldQueue` с конфигурацией:
- `tries = 3` — три попытки
- `backoff = [30, 60, 120]` — экспоненциальная задержка в секундах
- `failed()` — хук: при исчерпании попыток записывает статус `failed` и причину

Redis выбран как брокер: надёжнее database-queue, проще чем RabbitMQ для данного масштаба.

### 3. Атомарное управление статусом отчёта

`GenerateReportJob` при старте делает `UPDATE ... WHERE status IN ('pending', 'failed')` и проверяет количество обновлённых строк. Если `= 0` — другой воркер уже взял задачу, выходим. Это предотвращает двойную генерацию при параллельных воркерах.

Статус `processing` + хук `failed()` обеспечивают детектирование незавершённой генерации: если воркер упал в середине — статус остаётся `failed`, и retries сделают новую попытку.

### 4. Индексы БД

На таблице `notifications` созданы составные индексы:
- `(user_id, status)` — для фильтрации по статусу
- `(user_id, channel)` — для фильтрации по каналу
- `(user_id, status, channel)` — для комбинированной фильтрации

### 5. FormRequest для валидации

Вся входящая валидация вынесена в `FormRequest`-классы. Контроллер получает уже провалидированные данные через `$request->validated()`.

### 6. Enum-driven дизайн

`NotificationStatus`, `NotificationChannel`, `ReportStatus` — PHP 8.1 backed enums. Это типобезопасность на уровне компилятора: нельзя передать неверный статус без явной ошибки.

---

## Что улучшить в продакшне

1. **Аутентификация** — сейчас API открытый. В реальной системе: Sanctum/Passport, или API-key middleware. Пользователь должен видеть только свои уведомления.

2. **Real-time статусы** — Laravel Echo + WebSockets (Reverb) или SSE, чтобы клиент получал обновления статусов без поллинга.

3. **Реальные каналы** — `EmailChannel` через Mailable + SES/Mailgun, `TelegramChannel` через Bot API с rate-limit handling.

4. **Dead Letter Queue** — отдельная очередь для уведомлений, исчерпавших все попытки, с алертингом (Sentry, PagerDuty).

5. **Горизонтальное масштабирование** — несколько воркеров с Horizon для мониторинга очередей, автоскейлинг воркеров по размеру очереди.

6. **Observability** — структурированные логи (JSON), метрики (Prometheus + Grafana), трейсинг (OpenTelemetry).

7. **Отчёты в S3** — хранить сгенерированные файлы в S3/MinIO вместо локального диска, возвращать pre-signed URL вместо download-эндпоинта.

8. **Rate limiting** — ограничения на создание уведомлений на пользователя / IP.

9. **API versioning** — уже есть `/v1`, дальше поддерживать backward compatibility при изменениях.

10. **E2E тесты** — добавить тесты с реальной БД (TestContainers или отдельная тестовая compose-конфигурация).
