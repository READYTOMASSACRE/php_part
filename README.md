# Requirements:

* Php 7.1.3 or higher;
* [Composer](https://getcomposer.org/ "Composer");
* [Symfony-cli](https://github.com/symfony/cli);
* MySQL 5.7 or higher;
* PDO-SQLite PHP extension enabled;
* php-intl PHP extension enabled;
* and the [usual Symfony application requirements](https://symfony.com/doc/current/reference/requirements.html);
* [Node.js 6.4.0](https://nodejs.org/en/) or higher;
* [Yarn](https://yarnpkg.com/lang/en/);

# Installation:

1. Clone repository
`$ git clone git@github.com:READYTOMASSACRE/php_part.git && cd php_part`

2. Install composer dependencies
`$ composer install`

3. Install frontend dependencies
`$ yarn install`

4. Set your connection to database in `.env.local`
For example:
`DATABASE_URL="mysql://root:root@0.0.0.0:3306/db_name"`

5. Set up migrations
`$ php bin/console doctrine:migrations:migrate`

6. Fill data from source
`$ php bin/console app:get-rbc-news`

7. Build frontend
`$ yarn encore production`

8. Start your server (by default on http://127.0.0.1:8000)
`$ symfony server:start`