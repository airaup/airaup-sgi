# AIRAUP - SGI
[![License: MIT](https://img.shields.io/badge/License-MIT-brightgreen.svg)](LICENSE.md)

Este es el Sistema de Gestión Integral de AIRAUP.
Es útilizado principalmente para la gestión de la membresía de los clubes de Rotaract en la AIRAUP.

Puedes conocer más acerca de que es la AIRAUP en [AIRAUP.org](http://airaup.org).

## Instrucciones

Las siguientes instrucciones permiten que puedas correr SGI localmente en tu computadora para poder desarrollar.

### Prerequisitos

Es necesario instalar [Docker Compose](https://docs.docker.com/compose/install/#install-compose) para poder correr el proyecto localmente. Utilizar Docker nos permite que todos los desarrolladores tengamos un entorno con las mismas versiones de las tecnologías que utilizamos para desarrollar.

### Instalación

Una vez descargado el repositorio simplemente es necesario correr el próximo comando desde dentro de la carpeta.

```
$ git clone https://github.com/airaup/airaup-sgi.git
$ cd airaup-sgi
$ docker-compose up --build
```

Luego podrás ver el proyecto en el puerto 8000 de tu [localhost](http://localhost:8000).

#### Ingresar

Los datos personales han sido ofuscados para seguridad de los usuarios.

Para ingresar con un usuario puedes probar con:

```
user: 66socio@airaup.org
passwd: airaup01
```

### Tooling

#### Adminer

Adminer te permite explorar la base MySQL, similar a phpMyAdmin.

Lo podrás ver el proyecto en el puerto 8080 de tu [localhost](http://localhost:8080).

Podes utilizar las siguientes credenciales:

```
user: root
passwd: changeme
```

## Licencia

Este proyecto está bajo la licencia MIT - Ver el archivo [LICENSE.md](LICENSE.md) para más detalles.
