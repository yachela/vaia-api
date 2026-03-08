# VAIA API

Backend API para la aplicación móvil de viajes VAIA.

## Stack

- Laravel 12
- PHP 8.2+
- PostgreSQL 16
- Redis
- Laravel Sanctum

## Screenshots

<!-- Add screenshots here -->
<!-- ![API Screenshot](docs/screenshot.png) -->

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Desarrollo

```bash
# Servidor completo (server + queue + logs + vite)
composer dev

# Solo servidor
php artisan serve

# Tests
composer test
```

## API Endpoints

### Autenticación
- `POST /api/register` - Registro de usuario
- `POST /api/login` - Inicio de sesión
- `POST /api/logout` - Cerrar sesión
- `GET /api/user` - Obtener perfil
- `PUT /api/user` - Actualizar perfil
- `POST /api/user/avatar` - Subir avatar

### Viajes (CRUD)
- `GET /api/trips` - Listar viajes
- `POST /api/trips` - Crear viaje
- `GET /api/trips/{trip}` - Ver viaje
- `PUT /api/trips/{trip}` - Actualizar viaje
- `DELETE /api/trips/{trip}` - Eliminar viaje

### Actividades
- `GET /api/activities` - Listar todas las actividades del usuario
- `GET /api/trips/{trip}/activities` - Listar actividades
- `POST /api/trips/{trip}/activities` - Crear actividad
- `PUT /api/trips/{trip}/activities/{activity}` - Actualizar
- `DELETE /api/trips/{trip}/activities/{activity}` - Eliminar

### Gastos
- `GET /api/trips/{trip}/expenses` - Listar gastos
- `POST /api/trips/{trip}/expenses` - Crear gasto
- `GET /api/trips/{trip}/expenses/{expense}/receipt` - Descargar recibo

### Documentos
- `GET /api/trips/{trip}/documents` - Listar documentos
- `POST /api/trips/{trip}/documents` - Subir documento
- `DELETE /api/documents/{document}` - Eliminar documento

### Checklist
- `GET /api/trips/{trip}/checklist` - Ver checklist
- `POST /api/trips/{trip}/checklist/items` - Agregar item
- `PATCH /api/checklist/items/{item}/complete` - Toggle completado
- `DELETE /api/checklist/items/{item}` - Eliminar item

### Exportación
- `GET /api/trips/{trip}/export/itinerary.pdf` - PDF del itinerario
- `GET /api/trips/{trip}/export/expenses.csv` - CSV de gastos

### IA
- `POST /api/trips/{trip}/suggestions` - Obtener sugerencias de actividades

## Licencia

MIT
