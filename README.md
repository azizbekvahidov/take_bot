## Deplyoment

- git clone https://github.com/azizbekvahidov/take_bot

- composer update

- rename .env.example => .env

- php artisan key:generate

- add BOT_TOKEN to .env
- configure database configurations in .env
- php artisan migrate
- Url for webhook: https://api.telegram.org/bot{token}/setWebhook?url={url}/api/telegram-bot-connect
- Or run the bot: php artisan bot:run (if webhook is set,this method does not work)
