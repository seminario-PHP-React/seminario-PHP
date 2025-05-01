# API RESTful con Slim Framework en PHP
## Descripción del Proyecto
### Este proyecto consiste en el desarrollo de una API RESTful utilizando Slim Framework en PHP. El objetivo principal es permitir la gestión y autenticación de usuarios de manera segura utilizando JSON Web Tokens (JWT). La aplicación proporciona funcionalidades como la obtención y actualización de los datos de los usuarios, validación de entradas y manejo de errores.

## **Rutas de la API**

Podes encontrar y probar todas las rutas de la API utilizando la colección de Postman en el siguiente enlace:

[Ver Rutas](https://www.postman.com/juanazabaleta/seminario-php-garro-dolores-zabaleta-juana/overview)

## Objetivos
- Crear una API que gestione usuarios de manera segura.
- Implementar autenticación basada en JWT para proteger las rutas.
- Usar Slim Framework para manejar las rutas y las solicitudes HTTP de manera eficiente.
- Utilizar herramientas como Valitron para la validación de datos de entrada.
- Asegurar el proyecto mediante buenas prácticas de desarrollo y el manejo adecuado de dependencias.

## Tecnologías Utilizadas
- <b>Slim Framework:</b>  Un framework minimalista de PHP que permite crear aplicaciones web y APIs de manera rápida.

- <b>PHP:</b> Lenguaje de programación utilizado para desarrollar el backend de la aplicación.

- <b>Firebase PHP-JWT:</b> Librería para la creación y validación de tokens JWT que proporcionan una capa de seguridad en la autenticación de usuarios.

- <b>Valitron:</b> Librería para la validación de datos de entrada, asegurando que los datos enviados por los usuarios sean correctos antes de ser procesados.

- <b>PHP-DI:</b> Implementación de inyección de dependencias que ayuda a gestionar la creación y la resolución de las clases de manera eficiente.

- <b>PHPDotenv:</b> Maneja las variables de entorno en el proyecto, proporcionando una forma segura de gestionar credenciales y configuraciones.

## Estructura del Proyecto
El proyecto está estructurado de la siguiente manera:
```
src/                    # Contiene todo el código fuente de la aplicación
  └── App/
      ├── Controllers/  # Controladores para manejar las rutas y la lógica de negocio
      ├── Model/        # Modelos para interactuar con la base de datos
      └── Middleware/   # Middleware para validación y autenticación

config/                 # Archivos de configuración
    ├── Controllers/    # Configuración relacionada a los controladores
    └── routes.php      # Definición de las rutas de la API

.env                    # Archivo de configuración del entorno (variables sensibles)
