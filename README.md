# AE Exercise

## Installation
1. Install software requirements:
    * PHP, PHP-MYSQL, PHP-CURL (developed on version 7.1)
    * MYSQL (developed on version 5.4)
    * Symfony (http://symfony.com/doc/current/setup.html, developed on 3.2.2)
    * Composer (https://getcomposer.org/doc/00-intro.md)
2. Change to the project directory
3. Update composer (run: "composer update")
4. Create MYSQL database / user / grant user permissions
5. Udate Symfony project MYSQL parameters (app/config/parameters.yml)
6. Update MYSQL schema (run: "php bin/console doctrine:schema:update --force")
7. Start server (run: "php bin/console server:run")
8. Access via browser (localhost:8000)