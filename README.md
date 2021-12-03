## Deplyoment

- git clone https://github.com/azizbekvahidov/take_bot

- composer update

- rename .env.example => .env

- php artisan key:generate

- add BOT_TOKEN to .env
- configure database configurations in .env
- php artisan migrate
- Run the bot: php artisan bot:run
