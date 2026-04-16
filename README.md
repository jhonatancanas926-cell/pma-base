# 🧠 Sistema de Automatización de Evaluaciones Psicométricas — PMA-R

> **Uniempresarial** · API RESTful + Interfaz Web en **Laravel 12** para administrar la batería PMA-R (Aptitudes Mentales Primarias), importar preguntas desde Excel, aplicar pruebas en línea, calcular resultados psicométricos y generar reportes PDF individuales.

---

## 📋 Tabla de Contenidos

1. [Requisitos del sistema](#-requisitos-del-sistema)
2. [Instalación paso a paso](#-instalación-paso-a-paso)
3. [Estructura del proyecto](#-estructura-del-proyecto)
4. [Importar preguntas desde Excel](#-importar-preguntas-desde-excel)
5. [Factor E — Imágenes espaciales](#-factor-e--imágenes-espaciales)
6. [Cómo funciona la prueba](#-cómo-funciona-la-prueba)
7. [Calificación y puntajes](#-calificación-y-puntajes)
8. [Reporte PDF individual](#-reporte-pdf-individual)
9. [API Reference](#-api-reference)
10. [Roles y permisos](#-roles-y-permisos)
11. [Subir cambios a GitHub](#-subir-cambios-a-github)
12. [Usuarios de prueba](#-usuarios-de-prueba)
13. [Solución de problemas frecuentes](#-solución-de-problemas-frecuentes)

---

## ✅ Requisitos del sistema

| Componente | Versión mínima | Notas |
|---|---|---|
| PHP | 8.2+ | Con extensiones: `mbstring`, `xml`, `zip`, `pdo_mysql`, `fileinfo`, `gd` |
| MySQL | 8.0+ | También funciona con MariaDB 10.6+ |
| Composer | 2.x | Gestor de dependencias PHP |
| XAMPP | 8.2+ | Recomendado para desarrollo local en Windows |
| Node.js | 18+ | Solo si se usan assets con Vite |

**Extensiones PHP requeridas** — verificar en `C:\xampp\php\php.ini`:
```ini
extension=gd
extension=zip
extension=pdo_mysql
extension=mbstring
extension=fileinfo
```

---

## 🚀 Instalación paso a paso

### 1. Crear el proyecto Laravel base

```bash
cd C:\xampp\htdocs\Laravel
composer create-project laravel/laravel pma-base
cd pma-base
```

### 2. Copiar los archivos del sistema

Descomprime el ZIP del proyecto y copia con estos comandos:

```bash
cd C:\xampp\htdocs\Laravel

xcopy /E /Y pma-sistema\app\*        pma-base\app\
xcopy /E /Y pma-sistema\database\*   pma-base\database\
xcopy /E /Y pma-sistema\routes\*     pma-base\routes\
xcopy /E /Y pma-sistema\bootstrap\*  pma-base\bootstrap\
xcopy /E /Y pma-sistema\resources\*  pma-base\resources\
```

### 3. Instalar dependencias

```bash
cd pma-base
composer require laravel/sanctum
composer require maatwebsite/excel
composer require phpoffice/phpword
composer require tecnickcom/tcpdf
```

> ⚠️ Si falla `ext-gd`, actívala en `C:\xampp\php\php.ini`:
> Busca `;extension=gd` y quítale el punto y coma → `extension=gd`
> Luego reinicia Apache en el panel XAMPP.

### 4. Configurar el entorno

```bash
copy .env.example .env
php artisan key:generate
```

Abre `.env` con el Bloc de notas y configura:

```env
APP_NAME="PMA Sistema"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pma_sistema
DB_USERNAME=root
DB_PASSWORD=
```

> En XAMPP la contraseña de root es **vacía** por defecto.

### 5. Crear la base de datos

Abre **phpMyAdmin** en `http://localhost/phpmyadmin` y crea:
- Nombre: `pma_sistema`
- Cotejamiento: `utf8mb4_unicode_ci`

O desde CMD:
```bash
mysql -u root -e "CREATE DATABASE pma_sistema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 6. Ejecutar migraciones

```bash
# Eliminar migraciones duplicadas de Laravel base
del database\migrations\2024_01_01_000001_create_users_table.php
del database\migrations\2024_01_01_000002_create_personal_access_tokens_table.php

# Ejecutar todas las migraciones
php artisan migrate

# Crear usuarios y datos base
php artisan db:seed
```

### 7. Publicar Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 8. Crear carpetas necesarias

```bash
mkdir storage\app\reportes
mkdir storage\app\importaciones
mkdir public\imagenes\factor_e
php artisan storage:link
```

### 9. Importar preguntas desde Excel

Copia `PMA_R_Preguntas.xlsx` a la raíz del proyecto, luego:

```bash
php artisan tinker
```

```php
$test = \App\Models\Test::first();
$import = new \App\Imports\PmaImport(1, $test->id);
$r = $import->importar('C:/xampp/htdocs/Laravel/pma-base/PMA_R_Preguntas.xlsx');
print_r($r);
exit
```

Resultado esperado:
```
[estado] => completado
[filas_exitosas] => 170
[filas_con_error] => 0
```

### 10. Iniciar el servidor

```bash
php artisan serve
```

Abre el navegador en: **http://127.0.0.1:8000**

Debe aparecer la pantalla de **login del sistema PMA-R**.

---

## 📁 Estructura del proyecto

```
pma-base/
├── app/
│   ├── Console/Commands/
│   │   └── ImportarPma.php              ← Comando CLI para importar Excel
│   ├── Http/Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php       ← Registro, login, logout (API)
│   │   │   ├── TestController.php       ← Gestión de pruebas (API)
│   │   │   ├── SesionController.php     ← Sesiones de evaluación (API)
│   │   │   ├── ImportarController.php   ← Subir Excel via API
│   │   │   ├── ReporteController.php    ← Generar PDF (API)
│   │   │   └── EstadisticasController.php
│   │   └── Web/
│   │       └── WebController.php        ← Controlador de vistas Blade
│   ├── Imports/
│   │   └── PmaImport.php                ← Motor de importación Excel
│   ├── Models/
│   │   ├── User.php                     ← campos: name,email,rol,edad,sexo
│   │   ├── Test.php                     ← Baterías de prueba
│   │   ├── Categoria.php                ← Factores V,E,R,N
│   │   ├── Pregunta.php                 ← Preguntas con metadatos
│   │   ├── Opcion.php                   ← Opciones de respuesta
│   │   ├── SesionPrueba.php             ← Instancia de prueba por usuario
│   │   ├── RespuestaUsuario.php         ← Respuestas individuales
│   │   ├── Resultado.php                ← Puntajes por categoría
│   │   └── Importacion.php              ← Log de importaciones
│   └── Services/
│       ├── PmaService.php               ← Cálculo de puntajes y percentiles
│       ├── PdfReporteService.php        ← Generador de reportes PDF (TCPDF)
│       └── DocumentoService.php         ← Generador de documentación Word
├── database/
│   ├── migrations/                      ← 7 migraciones completas
│   └── seeders/
│       └── DatabaseSeeder.php           ← 3 usuarios + test PMA-R
├── public/
│   └── imagenes/
│       └── factor_e/                    ← Imágenes del factor espacial
│           ├── 1-0.png                  ← Imagen principal pregunta 1
│           ├── 1-1.png ... 1-6.png      ← Opciones A-F pregunta 1
│           └── ...
├── resources/views/
│   ├── layouts/app.blade.php            ← Layout principal con nav
│   ├── auth/
│   │   ├── login.blade.php              ← Pantalla de login
│   │   └── register.blade.php          ← Registro con edad y sexo
│   ├── dashboard/index.blade.php        ← Panel principal
│   ├── prueba/
│   │   ├── show.blade.php               ← Detalle de prueba
│   │   └── responder.blade.php          ← Interfaz de evaluación
│   ├── resultados/show.blade.php        ← Pantalla de resultados
│   ├── sesiones/index.blade.php         ← Historial de sesiones
│   └── estadisticas/index.blade.php     ← Panel estadístico
└── routes/
    ├── api.php                          ← Rutas API REST v1
    └── web.php                          ← Rutas de vistas Blade
```

---

## 📊 Importar preguntas desde Excel

### Formato del archivo Excel (`PMA_R_Preguntas.xlsx`)

| Hoja | Columnas | Ítems | Descripción |
|------|----------|-------|-------------|
| `FACTOR V` | Nº, Palabra, Opción 1, 2, 3, 4 | 50 | Sinónimos — 4 opciones |
| `FACTOR E` | Nº, [imagen] | 20 | Espacial — requiere imágenes separadas |
| `FACTOR R` | Nº, Serie de letras | 30 | Series — 6 opciones cargadas automáticamente |
| `FACTOR N` | Nº, Sumando 1-4, Total | 70 | Numérico — V/F calculado automáticamente |

### Vía API (Postman)

```
POST http://127.0.0.1:8000/api/v1/importar
Authorization: Bearer {token_admin}
Body: form-data → archivo = PMA_R_Preguntas.xlsx
```

### Vía Artisan CLI

```bash
php artisan pma:importar C:/ruta/PMA_R_Preguntas.xlsx
```

---

## 🖼️ Factor E — Imágenes espaciales

### Estructura de archivos de imagen

```
public/imagenes/factor_e/
├── 1-0.png    ← Figura principal pregunta 1
├── 1-1.png    ← Opción A
├── 1-2.png    ← Opción B
├── 1-3.png    ← Opción C
├── 1-4.png    ← Opción D
├── 1-5.png    ← Opción E
├── 1-6.png    ← Opción F
├── 2-0.png
...
└── 20-6.png
```

### Cargar imágenes al sistema

```bash
# Copia todas las imágenes a la carpeta pública
xcopy /Y "C:\ruta\tus\imagenes\*" "C:\xampp\htdocs\Laravel\pma-base\public\imagenes\factor_e\"
```

### Activar preguntas del Factor E en Tinker

Una vez copiadas las imágenes y configuradas las respuestas correctas:

```bash
php artisan tinker
```

```php
// Activar todas las preguntas del Factor E
\App\Models\Pregunta::whereHas('categoria', fn($q) =>
    $q->where('codigo','FACTOR_E')
)->update(['activo' => true]);

// Verificar
\App\Models\Pregunta::whereHas('categoria', fn($q) =>
    $q->where('codigo','FACTOR_E')
)->count();
// debe mostrar: 20
```

> ⚠️ Las preguntas del Factor E se importan como **inactivas** hasta que se configuren las respuestas correctas y se suban las imágenes.

---

## 🔄 Cómo funciona la prueba

```
1. Registro/Login del evaluado
        ↓
2. Selecciona prueba PMA-R
        ↓
3. Inicia sesión → se crea registro en sesiones_prueba
        ↓
4. Responde factor por factor (V → E → R → N)
        ↓
5. Finaliza la prueba
        ↓
6. Sistema calcula puntajes automáticamente
        ↓
7. Ve resultados con PT, percentiles y gráfica
        ↓
8. Descarga reporte PDF individual
```

---

## 🧮 Calificación y puntajes

### Fórmula de corrección por azar (oficial PMA-R)

```
Puntaje Bruto (PB) = Aciertos − (Errores × Penalización)
PB = max(0, PB)   ← Los valores negativos se reemplazan por cero
```

### Penalizaciones oficiales por factor

| Factor | Ítems | Opciones | Penalización/error | Tiempo |
|--------|-------|----------|--------------------|--------|
| V — Verbal | 50 | 4 (A-D) | **−0.33** | 5 min |
| E — Espacial | 20 | 6 (A-F) | **−1.00** | 5 min |
| R — Razonamiento | 30 | 6 (A-F) | **−0.20** | 5 min |
| N — Numérico | 70 | 2 (V/F) | **−1.00** | 10 min |

### Puntuación Típica (PT) — Escala estandarizada

```
PT = 50 + 20 × [ (PB − Media_PB) / DT_PB ]

Media = 50  |  Desviación Típica = 20
```

| PT | Nivel | Percentil |
|----|-------|-----------|
| ≥ 70 | Muy Alto | P90 – P99 |
| 60 – 69 | Alto | P70 – P89 |
| 40 – 59 | Medio | P30 – P69 |
| 30 – 39 | Bajo | P10 – P29 |
| < 30 | Muy Bajo | P1 – P9 |

### Índice Global (IG)

```
IG = Promedio de PT de los 4 factores (V + E + R + N) / 4
```

---

## 📄 Reporte PDF individual

El reporte se genera automáticamente con **TCPDF** (100% PHP) e incluye:

- ✅ Datos del evaluado: nombre, documento, edad, sexo, programa, fecha
- ✅ ID de sesión y tiempo empleado
- ✅ Tabla de resultados: PB, PT, percentil y nivel por factor
- ✅ Penalización aplicada por factor
- ✅ Índice Global (IG) en PT con interpretación
- ✅ Gráfica de perfil de dispersión (línea por puntos)
- ✅ Tabla de escala de referencia PT
- ✅ Interpretación narrativa por factor
- ✅ Nota de confidencialidad

**Descargar desde el navegador:**
```
GET http://127.0.0.1:8000/sesiones/{id}/reporte
```

**Descargar desde Postman (API):**
```
GET http://127.0.0.1:8000/api/v1/sesiones/{id}/reporte
Authorization: Bearer {token}
```

---

## 🌐 API Reference

Todas las rutas protegidas requieren:
```
Authorization: Bearer {token}
```

### Autenticación

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| POST | `/api/v1/auth/register` | ❌ | Registrar nuevo usuario |
| POST | `/api/v1/auth/login` | ❌ | Iniciar sesión → token |
| POST | `/api/v1/auth/logout` | ✅ | Cerrar sesión |
| GET | `/api/v1/auth/me` | ✅ | Ver perfil propio |

### Pruebas

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/tests` | ✅ | Listar pruebas activas |
| GET | `/api/v1/tests/{id}` | ✅ | Detalle con factores |
| GET | `/api/v1/tests/{id}/preguntas` | ✅ | Preguntas sin respuesta correcta |
| POST | `/api/v1/tests` | Admin | Crear prueba |

### Sesiones de evaluación

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/sesiones` | ✅ | Mis sesiones |
| POST | `/api/v1/sesiones` | ✅ | Iniciar sesión de prueba |
| POST | `/api/v1/sesiones/{id}/responder` | ✅ | Registrar respuesta |
| POST | `/api/v1/sesiones/{id}/finalizar` | ✅ | Finalizar y calcular |
| GET | `/api/v1/sesiones/{id}/resultados` | ✅ | Ver resultados |
| GET | `/api/v1/sesiones/{id}/reporte` | ✅ | Descargar PDF |

### Administración

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| POST | `/api/v1/importar` | Admin | Subir Excel PMA-R |
| GET | `/api/v1/estadisticas` | Evaluador | Panel estadístico |
| GET | `/api/v1/documentacion` | Admin | Documentación Word |

---

## 👥 Roles y permisos

| Rol | Acceso |
|-----|--------|
| `admin` | Todo: importar Excel, gestionar pruebas, ver estadísticas globales, generar documentación Word, descargar cualquier reporte PDF |
| `evaluador` | Ver resultados de todos los evaluados, descargar reportes PDF, ver estadísticas |
| `evaluado` | Tomar pruebas, ver sus propios resultados, descargar su propio reporte PDF |

---

## 🐙 Subir cambios a GitHub

```bash
cd C:\xampp\htdocs\Laravel\pma-base

# Ver qué cambió
git status

# Agregar cambios
git add .

# Confirmar cambios con mensaje descriptivo
git commit -m "feat: agregar generación de reporte PDF con TCPDF"

# Subir a GitHub
git push origin main
```

> ⚠️ Asegúrate de que `.env` **nunca** aparezca en `git status` como archivo a subir.
> Si aparece, ejecuta: `echo ".env" >> .gitignore`

---

## 🔑 Usuarios de prueba

| Email | Contraseña | Rol | Acceso |
|-------|-----------|-----|--------|
| `admin@pma.test` | `Admin1234!` | admin | Acceso total |
| `evaluador@pma.test` | `Evaluador1234!` | evaluador | Ver resultados y estadísticas |
| `evaluado@pma.test` | `Evaluado1234!` | evaluado | Tomar pruebas |

---

## 🔧 Solución de problemas frecuentes

### ❌ `Could not open input file: artisan`
Estás en la carpeta equivocada. Debes estar en:
```bash
cd C:\xampp\htdocs\Laravel\pma-base
```

### ❌ `ext-gd missing`
Activa la extensión en `C:\xampp\php\php.ini`:
```ini
extension=gd   ← quitar el ; del inicio
```
Reinicia Apache en el panel XAMPP.

### ❌ `Table already exists`
```bash
php artisan migrate:fresh --seed --force
```

### ❌ `Personal access tokens table not found`
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
php artisan migrate
```

### ❌ Error 500 en login
Verificar que Sanctum está configurado en `bootstrap/app.php` y que la tabla `personal_access_tokens` existe en la base de datos.

### ❌ Las imágenes del Factor E no aparecen
Verificar que los archivos están en:
```
public\imagenes\factor_e\1-0.png ... 20-6.png
```
Y que las preguntas están activas en la base de datos:
```bash
php artisan tinker
\App\Models\Pregunta::whereHas('categoria', fn($q) => $q->where('codigo','FACTOR_E'))->where('activo',1)->count();
```

### ❌ El PDF no se genera
Verificar que TCPDF está instalado:
```bash
composer show | findstr tcpdf
```
Si no aparece:
```bash
composer require tecnickcom/tcpdf
```

### ❌ Error al importar Excel (`ext-gd`)
```bash
composer require maatwebsite/excel --ignore-platform-req=ext-gd
composer require phpoffice/phpspreadsheet --ignore-platform-req=ext-gd
```

---

## 📦 Dependencias principales

```json
{
    "laravel/framework": "^12.0",
    "laravel/sanctum": "^4.0",
    "maatwebsite/excel": "^3.1",
    "phpoffice/phpword": "^1.2",
    "tecnickcom/tcpdf": "^6.6"
}
```

---

## 📄 Licencia

MIT — Uniempresarial · 2026

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
