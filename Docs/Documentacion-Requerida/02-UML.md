# Diagrama UML - ActividadesConnect

## 📋 Índice
1. [Diagrama de Clases](#diagrama-de-clases)
2. [Diagrama de Casos de Uso](#diagrama-de-casos-de-uso)
3. [Diagrama de Secuencia](#diagrama-de-secuencia)
4. [Diagrama de Actividad](#diagrama-de-actividad)

---

## Diagrama de Clases

### Clase Principal: Database

```
┌─────────────────────────────────────┐
│           Database                  │
├─────────────────────────────────────┤
│ - host: string                      │
│ - db_name: string                   │
│ - username: string                  │
│ - password: string                  │
│ - conn: PDO                         │
├─────────────────────────────────────┤
│ + getConnection(): PDO              │
└─────────────────────────────────────┘
```

### Funciones Auxiliares (includes/functions.php)

```
┌─────────────────────────────────────┐
│         Funciones Globales          │
├─────────────────────────────────────┤
│ + sanitizeInput(data): string       │
│ + validateEmail(email): bool        │
│ + validatePhone(phone): bool        │
│ + hashPassword(password): string    │
│ + verifyPassword(pass, hash): bool  │
│ + generateToken(): string           │
│ + isLoggedIn(): bool                │
│ + isOfertante(): bool               │
│ + isConsumidor(): bool              │
│ + isAdmin(): bool                   │
│ + redirect(url): void               │
│ + showAlert(message, type): void    │
│ + displayAlert(): void              │
│ + formatDate(date): string          │
│ + formatDateTime(datetime): string  │
│ + formatPrice(price): string        │
│ + getActivityCategories(): array    │
│ + getDifficultyLevels(): array      │
│ + getRequestStatus(): array         │
│ + getMensajesNoLeidos(): int        │
└─────────────────────────────────────┘
```

### Entidades del Sistema

```
┌─────────────────────────────────────┐
│            Usuario                  │
├─────────────────────────────────────┤
│ - id: int                           │
│ - email: string                     │
│ - password: string                  │
│ - nombre: string                    │
│ - apellidos: string                 │
│ - telefono: string                  │
│ - fecha_nacimiento: date            │
│ - tipo: enum                        │
│ - activo: bool                      │
│ - fecha_registro: datetime          │
├─────────────────────────────────────┤
│ + login()                           │
│ + logout()                          │
│ + updateProfile()                   │
│ + deleteAccount()                   │
└─────────────────────────────────────┘
         │
         │ 1:1
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌─────────┐ ┌───────────────┐
│Ofertante│ │ Consumidor    │
├─────────┤ ├───────────────┤
│- id     │ │- id           │
│- usuario│ │- usuario_id   │
│  _id    │ │- preferencias │
│- descrip│ │- nivel_exp    │
│  cion   │ │- restricciones│
│- experi │ │- alergias     │
│  encia  │ └───────────────┘
│- certif │
│  icacion│
└─────────┘
    │
    │ 1:N
    │
    ▼
┌─────────────────────────────────────┐
│         Actividad                   │
├─────────────────────────────────────┤
│ - id: int                           │
│ - ofertante_id: int                 │
│ - titulo: string                    │
│ - descripcion: text                 │
│ - categoria: string                 │
│ - duracion_horas: decimal           │
│ - dificultad: enum                  │
│ - precio_persona: decimal           │
│ - lugar_inicio: string              │
│ - estado: enum                      │
├─────────────────────────────────────┤
│ + create()                          │
│ + update()                          │
│ + delete()                          │
│ + getDisponibilidades()             │
└─────────────────────────────────────┘
         │
         │ 1:N
         │
         ▼
┌─────────────────────────────────────┐
│   DisponibilidadActividad           │
├─────────────────────────────────────┤
│ - id: int                           │
│ - actividad_id: int                 │
│ - fecha_inicio: datetime            │
│ - fecha_fin: datetime               │
│ - plazas_disponibles: int           │
│ - precio_especial: decimal          │
│ - estado: enum                      │
├─────────────────────────────────────┤
│ + create()                          │
│ + update()                          │
│ + cancel()                          │
│ + delete()                          │
└─────────────────────────────────────┘
         │
         │ 1:N
         │
         ▼
┌─────────────────────────────────────┐
│           Reserva                   │
├─────────────────────────────────────┤
│ - id: int                           │
│ - consumidor_id: int                │
│ - actividad_id: int                 │
│ - disponibilidad_id: int            │
│ - fecha_actividad: datetime         │
│ - num_participantes: int            │
│ - precio_total: decimal             │
│ - estado: enum                      │
│ - notas: text                       │
├─────────────────────────────────────┤
│ + create()                          │
│ + confirm()                         │
│ + reject()                          │
│ + cancel()                          │
│ + complete()                        │
│ + getParticipantes()                │
└─────────────────────────────────────┘
         │
         │ 1:N
         │
         ▼
┌─────────────────────────────────────┐
│    ParticipanteReserva              │
├─────────────────────────────────────┤
│ - id: int                           │
│ - reserva_id: int                   │
│ - nombre: string                    │
│ - orden: int                        │
└─────────────────────────────────────┘
```

---

## Diagrama de Casos de Uso

### Actor: Ofertante

```
┌─────────────────────────────────────────────┐
│              OFERTANTE                      │
└─────────────────────────────────────────────┘
         │
         │
    ┌────┴────────────────────────────────────┐
    │                                          │
    ▼                                          ▼
┌──────────────┐                    ┌──────────────────┐
│ Registrar    │                    │ Iniciar Sesión   │
│ Cuenta       │                    │                  │
└──────────────┘                    └──────────────────┘
         │                                   │
         │                                   │
         ▼                                   ▼
┌─────────────────────────────────────────────────────┐
│                  GESTIÓN DE PERFIL                  │
│  - Ver Perfil                                       │
│  - Editar Perfil                                    │
│  - Cambiar Contraseña                               │
│  - Eliminar Cuenta                                  │
└─────────────────────────────────────────────────────┘
         │
         │
         ▼
┌─────────────────────────────────────────────────────┐
│              GESTIÓN DE ACTIVIDADES                 │
│  - Crear Actividad                                  │
│  - Editar Actividad                                 │
│  - Ver Mis Actividades                              │
│  - Eliminar Actividad                               │
│  - Gestionar Disponibilidad                         │
│    • Agregar Fecha                                  │
│    • Editar Fecha                                   │
│    • Cancelar Fecha                                 │
│    • Eliminar Fecha Cancelada                       │
└─────────────────────────────────────────────────────┘
         │
         │
         ▼
┌─────────────────────────────────────────────────────┐
│              GESTIÓN DE RESERVAS                    │
│  - Ver Reservas                                     │
│  - Confirmar Reserva                                │
│  - Rechazar Reserva                                 │
│  - Completar Reserva                                │
│  - Ver Participantes                                │
└─────────────────────────────────────────────────────┘
         │
         │
         ▼
┌─────────────────────────────────────────────────────┐
│            SISTEMA DE SOLICITUDES                   │
│  - Buscar Solicitudes                               │
│  - Mostrar Interés                                  │
│  - Crear Propuesta                                  │
│  - Gestionar Propuestas                             │
└─────────────────────────────────────────────────────┘
         │
         │
         ▼
┌─────────────────────────────────────────────────────┐
│              SISTEMA DE MENSAJERÍA                  │
│  - Ver Bandeja de Entrada                           │
│  - Ver Mensajes Enviados                            │
│  - Archivar Mensajes                                │
│  - Eliminar Mensajes                                │
│  - Enviar Mensaje                                   │
└─────────────────────────────────────────────────────┘
```

### Actor: Consumidor

```
┌─────────────────────────────────────────────┐
│              CONSUMIDOR                     │
└─────────────────────────────────────────────┘
         │
         │
    ┌────┴────────────────────────────────────┐
    │                                          │
    ▼                                          ▼
┌──────────────┐                    ┌──────────────────┐
│ Registrar    │                    │ Iniciar Sesión   │
│ Cuenta       │                    │                  │
└──────────────┘                    └──────────────────┘
         │                                   │
         │                                   │
         ▼                                   ▼
┌─────────────────────────────────────────────────────┐
│                  GESTIÓN DE PERFIL                  │
│  - Ver Perfil                                       │
│  - Editar Perfil                                    │
│  - Cambiar Contraseña                               │
│  - Eliminar Cuenta                                  │
└─────────────────────────────────────────────────────┘
         │
         │
         ▼
┌─────────────────────────────────────────────────────┐
│            BÚSQUEDA DE ACTIVIDADES                  │
│  - Ver Catálogo                                     │
│  - Filtrar Actividades                              │
│  - Ver Detalles                                     │
│  - Reservar Actividad                               │
│    • Seleccionar Fecha                              │
│    • Especificar Participantes                      │
│    • Ingresar Nombres                               │
│    • Confirmar Reserva                              │
└─────────────────────────────────────────────────────┘
         │
         │
         ▼
┌─────────────────────────────────────────────────────┐
│              GESTIÓN DE RESERVAS                    │
│  - Ver Mis Reservas                                 │
│  - Cancelar Reserva                                 │
│  - Eliminar Reserva Cancelada                       │
│  - Ver Detalles de Reserva                          │
└─────────────────────────────────────────────────────┘
         │
         │
         ▼
┌─────────────────────────────────────────────────────┐
│            SISTEMA DE SOLICITUDES                   │
│  - Crear Solicitud                                  │
│  - Ver Mis Solicitudes                              │
│  - Ver Intereses                                    │
│  - Ver Propuestas                                   │
│  - Aceptar/Rechazar Propuesta                       │
│  - Editar Solicitud                                 │
│  - Cancelar Solicitud                               │
└─────────────────────────────────────────────────────┘
         │
         │
         ▼
┌─────────────────────────────────────────────────────┐
│              SISTEMA DE MENSAJERÍA                  │
│  - Ver Bandeja de Entrada                           │
│  - Ver Mensajes Enviados                            │
│  - Archivar Mensajes                                │
│  - Eliminar Mensajes                                │
│  - Enviar Mensaje                                   │
└─────────────────────────────────────────────────────┘
```

---

## Diagrama de Secuencia

### Secuencia: Crear Reserva

```
Consumidor      Frontend      Backend       Base de Datos
    │              │             │                │
    │──Buscar─────>│             │                │
    │              │──GET───────>│                │
    │              │             │──SELECT───────>│
    │              │             │<──Resultados───│
    │              │<──HTML──────│                │
    │<──Página─────│             │                │
    │              │             │                │
    │──Seleccionar Actividad───> │                │
    │              │             │                │
    │──Ver Detalles────────────> │                │
    │              │──GET───────>│                │
    │              │             │──SELECT───────>│
    │              │             │<──Datos────────│
    │              │<──HTML──────│                │
    │<──Detalles───│             │                │
    │              │             │                │
    │──Completar Formulario────> │                │
    │              │             │                │
    │──Enviar Reserva──────────> │                │
    │              │──POST──────>│                │
    │              │             │──SELECT───────>│
    │              │             │  (verificar)   │
    │              │             │<──OK───────────│
    │              │             │                │
    │              │             │──BEGIN TRANS──>│
    │              │             │                │
    │              │             │──INSERT───────>│
    │              │             │  (reserva)     │
    │              │             │<──ID───────────│
    │              │             │                │
    │              │             │──INSERT───────>│
    │              │             │  (participantes)│
    │              │             │<──OK───────────│
    │              │             │                │
    │              │             │──INSERT───────>│
    │              │             │  (mensaje)     │
    │              │             │<──OK───────────│
    │              │             │                │
    │              │             │──UPDATE───────>│
    │              │             │  (plazas)      │
    │              │             │<──OK───────────│
    │              │             │                │
    │              │             │──COMMIT───────>│
    │              │             │<──OK───────────│
    │              │<──Success───│                │
    │<─Confirmación│             │                │
```

### Secuencia: Confirmar Reserva (Ofertante)

```
Ofertante        Frontend      Backend       Base de Datos
    │               │             │                │
    │──Ver Reservas──────────────>│                │
    │               │──GET───────>│                │
    │               │             │──SELECT───────>│
    │               │             │<──Reservas─────│
    │               │<──HTML──────│                │
    │<──Lista───────│             │                │
    │               │             │                │
    │──Confirmar─────────────────>│                │
    │               │──POST──────>│                │
    │               │             │                │
    │               │             │──BEGIN TRANS──>│
    │               │             │                │
    │               │             │──UPDATE───────>│
    │               │             │  (reserva)     │
    │               │             │<──OK───────────│
    │               │             │                │
    │               │             │──INSERT───────>│
    │               │             │  (mensaje)     │
    │               │             │<──OK───────────│
    │               │             │                │
    │               │             │──COMMIT───────>│
    │               │             │<──OK───────────│
    │               │<──Success───│                │
    │<──Confirmación│             │                │
```

---

## Diagrama de Actividad

### Actividad: Proceso de Reserva

```
[Inicio]
    │
    ▼
[Consumidor busca actividad]
    │
    ▼
[Selecciona actividad]
    │
    ▼
[Ver detalles y disponibilidad]
    │
    ▼
[¿Hay fechas disponibles?]
    │
    ├── NO ──> [Mostrar mensaje: Sin disponibilidad]
    │          │
    │          ▼
    │        [Fin]
    │
    └── SÍ ──> [Seleccionar fecha]
               │
               ▼
          [Especificar número participantes]
               │
               ▼
          [¿Plazas suficientes?]
               │
               ├── NO ──> [Mostrar error]
               │         │
               │         ▼
               │       [Fin]
               │
               └── SÍ ──> [Ingresar nombres participantes]
                          │
                          ▼
                     [¿Todos los nombres completos?]
                          │
                          ├── NO ──> [Solicitar completar]
                          │         │
                          │         └── [Volver a ingresar]
                          │
                          └── SÍ ──> [Calcular precio total]
                                     │
                                     ▼
                                [Mostrar resumen]
                                     │
                                     ▼
                                [¿Confirmar?]
                                     │
                                     ├── NO ──> [Cancelar]
                                     │         │
                                     │         ▼
                                     │       [Fin]
                                     │
                                     └── SÍ ──> [Crear reserva]
                                                │
                                                ▼
                                           [Actualizar plazas]
                                                │
                                                ▼
                                           [Enviar mensaje a ofertante]
                                                │
                                                ▼
                                           [Mostrar confirmación]
                                                │
                                                ▼
                                              [Fin]
```

### Actividad: Proceso de Crear Actividad

```
[Inicio]
    │
    ▼
[Ofertante accede a crear actividad]
    │
    ▼
[Completar formulario]
    │
    ├── Información básica
    ├── Detalles técnicos
    ├── Ubicación
    ├── Material y requisitos
    └── Servicios incluidos
    │
    ▼
[Validar datos]
    │
    ▼
[¿Datos válidos?]
    │
    ├── NO ──> [Mostrar errores]
    │         │
    │         └── [Corregir datos]
    │
    └── SÍ ──> [Guardar actividad]
               │
               ▼
          [¿Agregar disponibilidad?]
               │
               ├── NO ──> [Actividad creada]
               │         │
               │         ▼
               │       [Fin]
               │
               └── SÍ ──> [Gestionar disponibilidad]
                          │
                          ▼
                     [Agregar fechas]
                          │
                          ▼
                     [Actividad lista]
                          │
                          ▼
                        [Fin]
```

---

## Relaciones entre Entidades

### Relación Usuario - Ofertante/Consumidor
```
Usuario (1) ──────< (1) Ofertante
Usuario (1) ──────< (1) Consumidor
```
- **Tipo**: Relación 1:1 (uno a uno)
- **Cardinalidad**: Un usuario puede ser un ofertante O un consumidor
- **Implementación**: Foreign key con UNIQUE constraint

### Relación Ofertante - Actividad
```
Ofertante (1) ──────< (N) Actividad
```
- **Tipo**: Relación 1:N (uno a muchos)
- **Cardinalidad**: Un ofertante puede tener múltiples actividades
- **Implementación**: Foreign key `ofertante_id` en `actividades`

### Relación Actividad - Disponibilidad
```
Actividad (1) ──────< (N) DisponibilidadActividad
```
- **Tipo**: Relación 1:N
- **Cardinalidad**: Una actividad puede tener múltiples fechas disponibles
- **Implementación**: Foreign key `actividad_id` en `disponibilidad_actividades`

### Relación Disponibilidad - Reserva
```
DisponibilidadActividad (1) ──────< (N) Reserva
```
- **Tipo**: Relación 1:N
- **Cardinalidad**: Una fecha disponible puede tener múltiples reservas
- **Implementación**: Foreign key `disponibilidad_id` en `reservas`

### Relación Consumidor - Reserva
```
Consumidor (1) ──────< (N) Reserva
```
- **Tipo**: Relación 1:N
- **Cardinalidad**: Un consumidor puede tener múltiples reservas
- **Implementación**: Foreign key `consumidor_id` en `reservas`

### Relación Reserva - Participante
```
Reserva (1) ──────< (N) ParticipanteReserva
```
- **Tipo**: Relación 1:N
- **Cardinalidad**: Una reserva puede tener múltiples participantes
- **Implementación**: Foreign key `reserva_id` en `participantes_reservas`

### Relación Usuario - Mensaje (Remitente)
```
Usuario (1) ──────< (N) Mensaje (como remitente)
```
- **Tipo**: Relación 1:N
- **Cardinalidad**: Un usuario puede enviar múltiples mensajes
- **Implementación**: Foreign key `remitente_id` en `mensajes`

### Relación Usuario - Mensaje (Destinatario)
```
Usuario (1) ──────< (N) Mensaje (como destinatario)
```
- **Tipo**: Relación 1:N
- **Cardinalidad**: Un usuario puede recibir múltiples mensajes
- **Implementación**: Foreign key `destinatario_id` en `mensajes`

---

## Patrones de Diseño Utilizados

### 1. Singleton (Implícito)
- **Clase Database**: Una sola instancia de conexión
- **Uso**: Reutilización de conexión en toda la aplicación

### 2. Factory Pattern (Implícito)
- **Creación de usuarios**: Según el tipo se crea ofertante o consumidor
- **Uso**: `register.php` crea diferentes tipos de perfiles

### 3. MVC Simplificado
- **Model**: Base de datos y clases de acceso
- **View**: Archivos PHP con HTML
- **Controller**: Lógica de negocio en archivos PHP

---

## Flujos de Datos Principales

### Flujo de Autenticación
```
Usuario → Login Form → PHP (validar) → MySQL (verificar) → 
PHP (crear sesión) → Dashboard
```

### Flujo de Creación de Actividad
```
Ofertante → Form → PHP (validar) → MySQL (INSERT actividad) → 
PHP (redirigir) → Gestión Disponibilidad
```

### Flujo de Reserva
```
Consumidor → Seleccionar → Form → PHP (validar plazas) → 
MySQL (INSERT reserva + participantes) → MySQL (UPDATE plazas) → 
MySQL (INSERT mensaje) → PHP (redirigir) → Confirmación
```

---

## Conclusión

Los diagramas UML muestran la estructura completa del sistema ActividadesConnect, incluyendo:
- ✅ Relaciones entre entidades
- ✅ Flujos de trabajo principales
- ✅ Casos de uso por tipo de usuario
- ✅ Secuencias de operaciones críticas
- ✅ Patrones de diseño implementados

Esta documentación UML facilita la comprensión del sistema y su mantenimiento futuro.

