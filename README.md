# Gestión de concesionario y taller

Aplicación Laravel para administrar clientes, coches y sus revisiones técnicas. Incluye una interfaz web que consume la API REST del proyecto. Las fotos opcionales de los coches se suben a Cloudinary y se guardan junto con su identificador para poder reemplazarlas o eliminarlas.

## Requisitos

- PHP 8.1 o superior y Composer
- Node.js y npm
- MySQL/MariaDB o SQLite

## Puesta en marcha

1. Configura la conexión a la base de datos en `.env`.
2. Configura `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY` y `CLOUDINARY_API_SECRET` en `.env`. El folder predeterminado es `concesionario/coches`.
3. Ejecuta las migraciones:

   ```bash
   php artisan migrate
   ```

4. Instala y compila los recursos del frontend:

   ```bash
   npm install
   npm run build
   ```

5. Inicia Laravel:

   ```bash
   php artisan serve
   ```

6. Abre `http://127.0.0.1:8000`.

Durante el desarrollo puedes usar `npm run dev` en lugar de `npm run build`.

## Modelo de datos

- `clientes` tiene muchos `coches`; cada coche requiere `cliente_id`.
- `coches` tiene muchas `revisiones`; cada revisión requiere `coche_id`.
- Al eliminar un cliente o coche, sus registros dependientes se eliminan mediante las claves foráneas en cascada.
- Las revisiones guardan los indicadores booleanos `aceite`, `filtro` y `frenos`.
- Las fotos aceptan JPG y PNG de hasta 5 MB. Se envían como archivos, no como URL.
- Al reemplazar o eliminar un coche también se actualiza o elimina su foto en Cloudinary.

## API

La API devuelve JSON y valida los campos y las claves foráneas.
Para subir una foto de coche, envía el campo `foto` como archivo JPG o PNG en una solicitud `multipart/form-data`. Al actualizar desde el navegador, el formulario usa `POST` con `_method=PUT` para permitir el envío del archivo.

| Método | Ruta | Acción |
| --- | --- | --- |
| GET, POST | `/api/clientes` | Listar y crear clientes |
| GET, PUT/PATCH, DELETE | `/api/clientes/{id}` | Consultar, actualizar y eliminar un cliente |
| GET, POST | `/api/coches` | Listar y crear coches |
| GET, PUT/PATCH, DELETE | `/api/coches/{id}` | Consultar, actualizar y eliminar un coche |
| GET | `/api/coches?cliente_id={id}` | Filtrar coches por cliente |
| GET | `/api/coches?aceite=1&filtro=1&frenos=1` | Filtrar por servicios de una misma revisión |
| GET | `/api/coches/{id}/revisiones` | Consultar las revisiones de un coche |
| GET, POST | `/api/revisiones` | Listar y crear revisiones |
| GET, PUT/PATCH, DELETE | `/api/revisiones/{id}` | Consultar, actualizar y eliminar una revisión |
| GET | `/api/revisiones?coche_id={id}` | Filtrar revisiones por coche |

La interfaz web permite gestionar los tres tipos de registros, filtrar coches por cliente y servicios, y consultar revisiones por coche.

## Pruebas

```bash
php artisan test
```

En un entorno sin base de datos MySQL, las pruebas pueden ejecutarse con SQLite:

```bash
set DB_CONNECTION=sqlite
set DB_DATABASE=:memory:
php artisan test
```
