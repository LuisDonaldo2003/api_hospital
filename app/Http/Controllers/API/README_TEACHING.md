# Módulo de Enseñanzas (Teaching) - Backend API

## Descripción General
Este módulo gestiona el registro de actividades de enseñanza y evaluaciones para el personal médico, enfermería y otros profesionales de la salud. Corresponde a las hojas Excel: LISTA DESPEGABLE, LIBRO10 y Hoja3.

## Tablas de Base de Datos

### `modalidades`
- `id` (PK)
- `codigo` (UNIQUE, ej: "CL-MIP")
- `nombre` (ej: "CLASES.MIP")
- `activo` (BOOLEAN)
- `created_at`, `updated_at`

### `participaciones`
- `id` (PK)
- `nombre` (UNIQUE, ej: "ASESOR", "PONENTE", "ASISTENTE")
- `activo` (BOOLEAN)
- `created_at`, `updated_at`

### `teachings`
- `id` (PK)
- `correo`, `ei`, `ef`, `profesion`, `nombre`, `area`, `adscripcion`, `nombre_evento`, `tema`, `fecha`, `horas`, `foja`
- `modalidad_id` (FK nullable)
- `participacion_id` (FK nullable)
- `created_at`, `updated_at`

### `evaluaciones`
- `id` (PK)
- `teaching_id` (FK nullable)
- `fecha_inicio`, `fecha_limite`, `especialidad`, `nombre`, `estado` (PENDIENTE/APROBADO/REPROBADO), `observaciones`
- `created_at`, `updated_at`

## Endpoints API

**Base URL:** `/api/teachings` (requiere autenticación: `Bearer token`)

### Teachings CRUD

#### 1. Listar Enseñanzas (con paginación y filtros)
```http
GET /api/teachings?page=1&per_page=10&search=<term>&especialidad=<area>&modalidad_id=<id>&participacion_id=<id>&fecha_inicio=<date>&fecha_fin=<date>&nombre_evento=<event>
```
**Response:**
```json
{
  "success": true,
  "data": [...],
  "total": 100,
  "per_page": 10,
  "current_page": 1,
  "last_page": 10,
  "from": 1,
  "to": 10
}
```

#### 2. Obtener un Teaching
```http
GET /api/teachings/{id}
```
**Response:**
```json
{
  "success": true,
  "data": { "id": 1, "nombre": "...", ... }
}
```

#### 3. Crear Teaching
```http
POST /api/teachings
Content-Type: application/json

{
  "correo": "test@example.com",
  "ei": "12/20",
  "ef": "20/20",
  "profesion": "DR.",
  "nombre": "John Doe",
  "area": "MEDICINA",
  "adscripcion": "HOSPITAL GRAL. REG.",
  "nombre_evento": "Curso XYZ",
  "tema": "Cardiología",
  "fecha": "2025-01-13",
  "horas": "2 HRS",
  "foja": "123",
  "modalidad_id": 1,
  "participacion_id": 2
}
```
**Response:**
```json
{
  "success": true,
  "data": { "id": 1, ... }
}
```

#### 4. Actualizar Teaching
```http
PUT /api/teachings/{id}
Content-Type: application/json

{ ... campos a actualizar ... }
```

#### 5. Eliminar Teaching
```http
DELETE /api/teachings/{id}
```
**Response:**
```json
{
  "success": true,
  "message": "Eliminado correctamente"
}
```

### Estadísticas

#### 6. Estadísticas Generales
```http
GET /api/teachings/stats
```
**Response:**
```json
{
  "success": true,
  "data": {
    "total": 150,
    "por_modalidad": { "1": 50, "2": 100 },
    "por_participacion": { "1": 75, "2": 75 },
    "total_horas": 0,
    "evaluaciones_pendientes": 25
  }
}
```

### Exportación e Importación

#### 7. Exportar a Excel (CSV)
```http
GET /api/teachings/export/excel?search=<term>&modalidad_id=<id>...
```
**Response:** CSV file download

#### 8. Importar desde Excel (CSV)
```http
POST /api/teachings/import/excel
Content-Type: multipart/form-data

file: [CSV file with header: id,correo,ei,ef,profesion,nombre,area,adscripcion,nombre_evento,tema,fecha,horas,foja,modalidad_id,participacion_id]
```
**Response:**
```json
{
  "success": true,
  "message": "Importadas: 50"
}
```

### Evaluaciones

#### 9. Listar Evaluaciones
```http
GET /api/teachings/evaluaciones?page=1&per_page=10
```
**Response:** (paginado como teachings)

#### 10. Evaluaciones Pendientes
```http
GET /api/teachings/evaluaciones/pendientes
```
**Response:** (paginado, filtrado por estado='PENDIENTE')

#### 11. Crear Evaluación
```http
POST /api/teachings/evaluaciones
Content-Type: application/json

{
  "teaching_id": 1,
  "fecha_inicio": "2025-01-01",
  "fecha_limite": "2025-03-01",
  "especialidad": "MEDICINA",
  "nombre": "John Doe",
  "estado": "PENDIENTE",
  "observaciones": "..."
}
```

#### 12. Actualizar Evaluación
```http
PUT /api/teachings/evaluaciones/{id}
```

#### 13. Eliminar Evaluación
```http
DELETE /api/teachings/evaluaciones/{id}
```

## Modelos Laravel

### Teaching
- Relación: `hasMany(Evaluacion::class)`
- Cast: `fecha` => date

### Evaluacion
- Relación: `belongsTo(Teaching::class)`
- Cast: `fecha_inicio`, `fecha_limite` => date

## Seeders

- `TeachingModalidadesSeeder`: Inserta modalidades básicas (CLASES.MIP, SESION EPSS, etc.)
- `TeachingParticipacionesSeeder`: Inserta participaciones (ASESOR, PONENTE, ASISTENTE)

## Validación (Requests)

- `StoreTeachingRequest`: valida campos requeridos (nombre, fecha, etc.)
- `UpdateTeachingRequest`: validación parcial para actualización
- `StoreEvaluacionRequest`: valida evaluación (nombre, estado, etc.)

## Migración y Ejecución

1. Ejecutar migraciones:
   ```bash
   php artisan migrate
   ```

2. Ejecutar seeders:
   ```bash
   php artisan db:seed --class=TeachingModalidadesSeeder
   php artisan db:seed --class=TeachingParticipacionesSeeder
   ```

3. Las rutas están registradas en `routes/api.php` bajo el prefijo `teachings` con middleware `auth:api`.

## Integración con Frontend Angular

El servicio `TeachingService` en el frontend Angular apunta a `${URL_SERVICIOS}/teachings` y espera:
- Headers: `Authorization: Bearer <token>`
- Respuestas JSON con `success`, `data`, `message`
- Paginación con estructura: `{ data: [...], total, per_page, current_page, last_page, from, to }`

## Notas

- La exportación genera CSV con todas las columnas del modelo Teaching.
- La importación espera un CSV con header y actualiza/crea registros usando `updateOrCreate` (basado en nombre, nombre_evento y fecha).
- Las evaluaciones están vinculadas opcionalmente a un teaching_id (puede ser NULL).
- Estado de evaluaciones: "PENDIENTE", "APROBADO", "REPROBADO".
