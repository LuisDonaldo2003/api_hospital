# Proyecto SISMEG

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://github.com/LuisDonaldo2003/admin_hospital/blob/main/src/assets/img/login-logo.png" width="400" alt="Laravel Logo"></a></p>

Este proyecto fue desarrollado por estudiantes del Instituto Tecnológico de Ciudad Altamirano, Guerrero, durante su estancia en el Hospital IMSS-Bienestar Coyuca de Catalán “Dr. Guillermo Soberón Acevedo”, con el propósito de ser implementado en el ámbito hospitalario, conforme a los requerimientos establecidos por el Director General, Eric Aburto Álvarez, en el marco del programa de Educación Dual.
## Estudiantes a cargo del proyecto

- Luis Donaldo López Martínez (Full Stack Developer & UI/UX Designer)
- Alejandro Vidal Pérez (Software Developer)
- Enrique Ruiz Peralta (Software Developer & Product Strategist.)
- Julián Reynoso Zavaleta (Product Strategist & Theoretical Concept Developer)
- Jose Antonio Herrera Chamu  (Product Strategist & Theoretical Concept Developer)

# Pasos para clonar el repositorio

- Crea una carpeta donde vaya a clonar dicho repositorio
- Ejecuta un cmd en dirección a la carpeta de destino
- Inicializar `git init` para que el destino sea apto para la clonación
- Ejecutar `git clone https://github.com/LuisDonaldo2003/api_hospital.git` 

# Pasos para construir Laravel API

## 1. Configuración del archivo `php.ini`

Dependiendo de la versión de PHP instalada en el sistema, debe habilitarse la carga de las siguientes extensiones:

- extension=sodium
- extension=zip
  
Se desmarcarán dichas extensiones para que permita realizar la siguiente instalación

## 2. Instalar Composer

Para instalar Composer en nuestro Laravel API, se necesita el siguiente comando

- composer install

## Revisar la conexión del `.env`

Cuando se clona el repositorio, veremos unicamente un `.env example`, dicho archivo, editaremos su nombre y pondremos unicamente el `.env`

.env original

```javascript
DB_CONNECTION=sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

.env sugerencia

```javascript
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=2679
DB_DATABASE=api_hospital
DB_USERNAME=postgres
DB_PASSWORD=
```

Se establece como referencia el valor `pgsql`, dado que la plataforma utiliza PostgreSQL como gestor de base de datos. Este parámetro puede modificarse según el gestor que se emplee, como MySQL, entre otros.

## 3. Ejecución de `php artisan storage:link`

Con dicho comando se crea un enlace simbólico (symlink) entre la carpeta `storage/app/public` y la carpeta `public/storage` del proyecto

## 4. Ejecución de `php artisan key:generate`

Se genera una nueva clave de aplicación y la asigna automáticamente a la variable APP_KEY en el archivo `.env`

## 5. Creación de `oauth-private`

Dirigete a la carpeta `api_hospital/storage` y crea una carpeta llamada `oauth-private`, el cual es un directorio de almacenamiento interno de Laravel destinado a guardar las claves privadas y públicas utilizadas para el sistema de autenticación basado en JWT con algoritmo RS256.

## 6. Instalar OpenSSL en Windows

Con el siguiente [enlace](https://slproweb.com/products/Win32OpenSSL.html "enlace") encontrarás el instalador de OpenSSL

## 7. Editar variable de entorno para OpenSSL

- Escribe en el recuadro de búsqueda de Windows `Editar variables de entorno del sistema`
- Dirígete a `variables de entorno`
- Dirígete a `PATH` en las variables del sistema
- Si al instalar OpenSSL usaste su dirección predeterminada, se encuentra en `C:\Program Files\OpenSSL-Win64\bin`, el cual será puesto en dicho apartado
- Para verificar que realmente esté instalado, abre un CMD y escribe el siguiente comando `openssl version`, el cual, debe mostrar la versión

## 8. Generación de pem (privado y público)

Para generar un par de claves RSA (privada y pública) usando OpenSSL, las cuales, se guardarán en la carpeta storage/oauth-private para que el sistema Laravel las use con JWT (algoritmo RS256).

- Privado
```javascript
openssl genrsa -out storage/oauth-private/private.pem 4096
```

- Público
```javascript
openssl rsa -in storage/oauth-private/private.pem -pubout -out storage/oauth-private/public.pem
```

## 9. Generación de Key para JWT

Con el siguiente comando
```javascript
php artisan jwt:secret
```

Genera una clave secreta única para JWT y la guarda en el archivo .env dentro de la variable JWT_SECRET. En donde al final del .env se mostrará dicha clave, para agilizar las cosas, copia lo siguiente y pégalo debajo de `JWT_SECRET` lo siguiente:

```javascript
JWT_ALGO=RS256
JWT_PRIVATE_KEY=storage/oauth-private/private.pem
JWT_PUBLIC_KEY=storage/oauth-private/public.pem
JWT_TTL=480
JWT_REFRESH_TTL=20160
JWT_LEEWAY=60
JWT_BLACKLIST_ENABLED=true
JWT_BLACKLIST_GRACE_PERIOD=30
```

## 10. Optimizar la subida de información por parte de PHP

Paraa optimizar el que PHP responda a subida grande de datos, se tiene que realizar lo siguiente:

- .env
Agrega las siguientes líneas dentro del archivo

```javascript
UPLOAD_MAX_FILESIZE=1000M
POST_MAX_SIZE=1000M
```

- php.ini
 Verifica las siguientes lineas y edita su valor correspondiente
```javascript
post_max_size = 1000M
upload_max_filesize = 1000M
```

##11. Creación de servidor SMTP para verificación de cuentas

La plataforma incorpora un sistema de verificación de cuentas que permite mantener un control sobre los registros y garantizar que los usuarios sean auténticos. Para este propósito, se utiliza un servidor SMTP, en este caso, el de Google.

En el siguiente [enlace](https://www.youtube.com/watch?v=ShM8ufqsGlY "enlace"). Encontrarás un pequeño tutorial de como crear tu aplicación para Servidor SMTP, aqui te muestro un ejemplo de como debe quedar configurado en el `.env`

```javascript
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=correodeprueba@gmail.com
MAIL_ENCRYPTION=ssl
MAIL_PASSWORD=opdimfjhenklskb
MAIL_FROM_ADDRESS=correodeprueba@gmail.com
MAIL_FROM_NAME="Hospital IMSS-Bienestar"
```
## 11. Ejecución de la migración

Ejecuta el siguiente comando
```javascript
php artisan migrate --seed
```
Los que realizará es que los seeders se ejecutarán para iniciar con el llenado de la información

## 12. Resultado esperado del .env

En las siguientes líneas de código, se puede observar como tiene que estar configurado el .env

```javascript
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://127.0.0.1:8000/

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=2679
DB_DATABASE=api_hospital
DB_USERNAME=postgres
DB_PASSWORD=


UPLOAD_MAX_FILESIZE=1000M
POST_MAX_SIZE=1000M

SESSION_DRIVER=database
SESSION_LIFETIME=480
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_DRIVER=redis
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=correodeprueba@gmail.com
MAIL_ENCRYPTION=ssl
MAIL_PASSWORD=opdimfjhenklskb
MAIL_FROM_ADDRESS=correodeprueba@gmail.com
MAIL_FROM_NAME="Hospital IMSS-Bienestar"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

JWT_SECRET=
JWT_ALGO=RS256
JWT_PRIVATE_KEY=storage/oauth-private/private.pem
JWT_PUBLIC_KEY=storage/oauth-private/public.pem
JWT_TTL=480
JWT_REFRESH_TTL=20160
JWT_LEEWAY=60
JWT_BLACKLIST_ENABLED=true
JWT_BLACKLIST_GRACE_PERIOD=30
```
## 12. Inicializar el proyecto

Para inicializar el proyecto, abre un cmd o donde tengas abierto dicha parte del proyecto y ejecuta

```javascript
php artisan serve
```