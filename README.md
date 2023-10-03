See it online at [https://moxie.dootic.com](https://moxie.dootic.com)

[![Build Status](http://integration.dootic.com:8080/job/moxie.dootic.com/badge/icon?style=plastic)](http://integration.dootic.com:8080/job/moxie.dootic.com/)

### Moxie

Moxie is an application that will allow you to control home expenses, incomes and to elaborate a home budget. You will also be able to adapt the categories and subcategories to your own needs.

If you need more than a spreadsheet or you have never controlled your home economy, this application can help you to have a better control of it.

### Requirements:

[Composer](https://getcomposer.org/)

Once installed, execute composer install from Moxie folder.

After installation, copy application/configs/application.ini.example to application/configs/application.ini. Edit
and set your database username and password that Moxie will use.

### Dependencies

#### installed with composer

[Zend Framework](http://framework.zend.com/)

[Phinx](https://phinx.org/)

After that, install phinx. Once installed, edit phinx.yml to match your database and then execute vendor/bin/phinx to
execute the migrations and update the database to the latest version.

[PHPUnit](https://phpunit.de/)

#### Installed using git submodules

[Simple PHP captcha](https://github.com/claviska/simple-php-captcha)

## Migration to Django

### Insert into...

> insert into moxie_user(id, first_name, last_name, is_staff, is_active, date_joined, login, password, email, language, created_at, updated_at, last_login, is_superuser) select id, '', '', 0, 1, created_at, login, password, email, language, created_at, updated_at, last_login, 0 from users;
> SET foreign_key_checks = 0;
> insert into moxie_category(name, description, type, parent, user_owner, `order`) select name, description, type, parent, user_owner, `order` from categories order by id asc;
> insert into moxie_transaction(user_owner, amount, note, date, in_sum, income_update, category) select user_owner, amount, note, date, in_sum, income_update, category from transactions;
> SET foreign_key_checks = 1;