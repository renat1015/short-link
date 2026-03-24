<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Короткие ссылки с QR-кодами</h1>
</p>

Сервис для создания коротких ссылок с автоматической генерацией QR-кодов. Проект реализован на Yii2 Basic с использованием MariaDB, jQuery и Bootstrap.

ИСПОЛЬЗУЕМЫЕ ПАКЕТЫ
-------------------
- chillerlan/php-qrcode

УСТАНОВКА И ЗАПУСК
-------------------
### Используя Docker

1. Склонируйте репозиторий:
```
git clone https://github.com/renat1015/short-link.git

cd short-link
```
2.  Скопируйте файл окружения:
```
cp .env.example .env
```
3.  Запустите контейнеры:
```
docker-compose up -d
```
4.  Выполните инициализацию:
```
docker-compose exec php-fpm bash docker/scripts/init.sh
```
5.  Приложение будет доступно по адресу:

      http://localhost:8080

### Без Docker (Локальная разработка)

1. Склонируйте репозиторий:
```
git clone https://github.com/renat1015/short-link.git

cd short-link
```
2.  Установите зависимости:
```
composer install
```
3. Если MySQL не установлен:
```
# Установите MySQL
# Ubuntu/Debian:
sudo apt-get update
sudo apt-get install mysql-server

# macOS:
brew install mysql

# Windows:
# Скачайте установщик с https://dev.mysql.com/downloads/installer/
```
4. Запустите MySQL:
```
# Запустите MySQL
sudo systemctl start mysql  # Linux
# или
sudo service mysql start    # Linux (старые версии)

# Для macOS
brew services start mysql

# Для Windows
net start MySQL
```
5.  Создаем базу данных и пользователя:
```
sudo mysql <<EOF
CREATE DATABASE IF NOT EXISTS short_link_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'your_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_password';
GRANT ALL PRIVILEGES ON short_link_db.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```
4.  Настройте базу данных в файле config/db.php
    
5.  Примените миграции:
```
php yii migrate
```
6.  Запустите встроенный сервер:
```
php yii serve
```
7.  Приложение будет доступно по адресу:

      http://localhost:8080

КОНФИГУРАЦИЯ
-------------

### Database

Настройте файл `config/db.php` с вашими данными, по примеру:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=short_link_db',
    'username' => 'your_user',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
];
```