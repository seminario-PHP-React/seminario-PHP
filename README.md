## Documentación de Instalación del Proyecto

### Requisitos Previos

Antes de comenzar, asegúrate de tener instalados los siguientes programas en tu máquina:

1. **PHP** (versión 8.0 o superior)
2. **Composer** (gestor de dependencias de PHP)

Puedes verificar si los tienes instalados con los siguientes comandos:

```
php -v
composer -v
```

### Pasos para la Instalación

### 1. Instalar las Dependencias ###
Una vez clonado el repositorio, navega hasta la carpeta del proyecto:
```
cd <nombre-del-repositorio>
```
Como la carpeta vendor no está incluida en el repositorio (se encuentra en .gitignore), deberás instalar las dependencias de Composer. Ejecuta el siguiente comando:

```
composer install
```
Este comando descargará e instalará todas las dependencias necesarias que están definidas en el archivo composer.json.

### 2. Configuración del Entorno ###
Asegúrate de tener un entorno de servidor local que pueda ejecutar el proyecto, como el servidor web incorporado de PHP. Si no tienes configurado un servidor específico, puedes usar el servidor local de PHP:

Navega hasta el directorio public (en donde se encuentra el archivo index.php):

```
cd public
```

Luego, ejecuta el servidor local con el siguiente comando:

```
php -S localhost:8000
```
Esto iniciará el servidor web en ***http://localhost:8000.*** Si todo está correctamente configurado, deberías ver el mensaje de "Hello world!" en el navegador.

### 3. Acceso al Proyecto ###
Una vez el servidor esté en ejecución, puedes acceder a la aplicación abriendo tu navegador y dirigiéndote a la siguiente URL:

```
http://localhost:8000
```
