See it online at http://moxie.dootic.com

### Moxie

Moxie is an application that will allow you to control home expenses, incomes and to elaborate a home budget. You will also be able to adapt the categories and subcategories to your own needs.

If you need more than a spreadsheet or you have never controlled your home economy, this application can help you to have a better control of it.

### Requirements:

[Composer](https://getcomposer.org/)

Once installed, execute composer install from Moxie folder.

After installation, copy application/configs/application.ini.example to application/configs/application.ini. Edit
and set your database username and password that Moxie will use.

[Phinx](https://phinx.org/)

After that, install phinx. Once installed, edit phinx.yml to match your database and then execute vendor/bin/phinx to
execute the migrations and update the database to the latest version.

### Dependencies (installed with composer)

[Zend Framework](http://framework.zend.com/)

[Simple PHP captcha](https://github.com/claviska/simple-php-captcha)