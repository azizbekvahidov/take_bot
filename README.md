## Deplyoment

```shell
git clone https://github.com/azizbekvahidov/take_bot
```

### Run:

```shell
composer install --optimize-autoloader --no-dev
```

- rename `.env.example` to `.env`

### Generate application key

```shell
php artisan key:generate
```

- add `BOT_TOKEN` to `.env`
- add `CAFE_CLIENT_URL` to `.env`
- add `BOT_ADMINS` to `.env`

### Configure database configurations

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

### Run migrations

```shell
php artisan migrate
```

- Url for webhook: `https://api.telegram.org/bot{token}/setWebhook?url={url}/api/telegram-bot-connect`

### Or run the bot:

```shell
php artisan bot:run
```

> if webhook is set,this method does not work
