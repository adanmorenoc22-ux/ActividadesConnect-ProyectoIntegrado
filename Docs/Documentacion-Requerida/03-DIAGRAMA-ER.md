# Diagrama Entidad-Relación - ActividadesConnect

## 📋 Índice
1. [Descripción del Modelo](#descripción-del-modelo)
2. [Diagrama E/R Completo](#diagrama-er-completo)
3. [Descripción de Entidades](#descripción-de-entidades)
4. [Relaciones](#relaciones)
5. [Integridad Referencial](#integridad-referencial)
6. [Índices y Optimización](#índices-y-optimización)

---

## Descripción del Modelo

El sistema ActividadesConnect utiliza un modelo de datos relacional normalizado que permite la gestión completa de ofertantes, consumidores, actividades, reservas, solicitudes y mensajería. El diseño sigue las reglas de normalización hasta 3NF y utiliza claves foráneas con `ON DELETE CASCADE` para mantener la integridad referencial.

---

## Diagrama E/R Completo

```
┌────────────────────────────────────────┐
│            USUARIOS                    │
├────────────────────────────────────────┤
│ id (PK, AUTO_INCREMENT)                │
│ email (UK)                             │
│ password                               │
│ nombre                                 │
│ apellidos                              │
│ telefono                               │
│ fecha_nacimiento                       │
│ tipo (ENUM: ofertante/consumidor/admin)│
│ activo (TINYINT)                       │
│ fecha_registro (DATETIME)              │
│ ultimo_acceso (DATETIME)               │
│ token_reset                            │
│ token_expires                          │
└────────────────────────────────────────┘
         │
         │ 1:1 (UNIQUE)
         │
    ┌────┴────────────────────────────┐
    │                                  │
    ▼                                  ▼
┌───────────────────────┐    ┌──────────────────────┐
│     OFERTANTES        │    │    CONSUMIDORES      │
├───────────────────────┤    ├──────────────────────┤
│ id (PK)               │    │ id (PK)              │
│ usuario_id (FK, UK)   │    │ usuario_id (FK, UK)  │
│ descripcion (TEXT)    │    │ preferencias (TEXT)  │
│ experiencia (TEXT)    │    │ nivel_experiencia    │
│ certificaciones       │    │ (ENUM)               │
│ disponibilidad_general│    │ restricciones_medicas│
│ verificado (TINYINT)  │    │ alergias (TEXT)      │
│ fecha_verificacion    │    └──────────────────────┘
└───────────────────────┘
         │
         │ 1:N
         │
         ▼
┌──────────────────────────────────────┐
│         ACTIVIDADES                  │
├──────────────────────────────────────┤
│ id (PK)                              │
│ ofertante_id (FK)                    │
│ titulo                               │
│ descripcion                          │
│ categoria                            │
│ subcategoria                         │
│ duracion_horas                       │
│ dificultad (ENUM)                    │
│ precio_persona                       │
│ precio_grupo                         │
│ min_participantes                    │
│ max_participantes                    │
│ lugar_inicio                         │
│ lugar_fin                            │
│ coordenadas_inicio                   │
│ coordenadas_fin                      │
│ material_requerido                   │
│ material_incluido                    │
│ preparacion_fisica                   │
│ requisitos_edad_min                  │
│ requisitos_edad_max                  │
│ restricciones                        │
│ incluye_transporte                   │
│ incluye_comida                       │
│ incluye_seguro                       │
│ estado (ENUM)                        │
│ fecha_creacion                       │
│ fecha_actualizacion                  │
└──────────────────────────────────────┘
         │
         │ 1:N
         │
         ▼
┌─────────────────────────────────────────────┐
│   DISPONIBILIDAD_ACTIVIDADES                │
├─────────────────────────────────────────────┤
│ id (PK)                                     │
│ actividad_id (FK)                           │
│ fecha_inicio (DATETIME)                     │
│ fecha_fin (DATETIME)                        │
│ plazas_disponibles                          │
│ precio_especial                             │
│ notas                                       │
│ estado (ENUM: disponible/completo/cancelado)│
└─────────────────────────────────────────────┘
         │
         │ 1:N
         │
         ▼
┌──────────────────────────────────────┐
│           RESERVAS                   │
├──────────────────────────────────────┤
│ id (PK)                              │
│ consumidor_id (FK)                   │
│ actividad_id (FK)                    │
│ disponibilidad_id (FK)               │
│ fecha_reserva                        │
│ fecha_actividad                      │
│ num_participantes                    │
│ precio_total                         │
│ estado (ENUM: pendiente/confirmada/  │
│          cancelada/completada)       │
│ notas                                │
│ fecha_confirmacion                   │
└──────────────────────────────────────┘
         │
         │ 1:N
         │
         ▼
┌──────────────────────────────────────┐
│    PARTICIPANTES_RESERVAS            │
├──────────────────────────────────────┤
│ id (PK)                              │
│ reserva_id (FK)                      │
│ nombre                               │
│ orden                                │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│    CONSUMIDORES                      │
├──────────────────────────────────────┤
│ id (PK)                              │
│ usuario_id (FK, UK)                  │
│ ...                                  │
└──────────────────────────────────────┘
         │
         │ 1:N
         │
         ▼
┌──────────────────────────────────────┐
│   SOLICITUDES_CONSUMIDORES           │
├──────────────────────────────────────┤
│ id (PK)                              │
│ consumidor_id (FK)                   │
│ titulo                               │
│ descripcion                          │
│ categoria                            │
│ fecha_deseada                        │
│ hora_deseada                         │
│ duracion_estimada                    │
│ presupuesto_max                      │
│ ubicacion                            │
│ participantes_estimados              │
│ requisitos_especiales                │
│ estado (ENUM)                        │
│ fecha_creacion                       │
│ fecha_actualizacion                  │
└──────────────────────────────────────┘
         │
         │ 1:N
         │
         ▼
┌──────────────────────────────────────┐
│   INTERESES_OFERTANTES               │
├──────────────────────────────────────┤
│ id (PK)                              │
│ ofertante_id (FK)                    │
│ solicitud_id (FK)                    │
│ fecha_interes                        │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│   PROPUESTAS_OFERTANTES              │
├──────────────────────────────────────┤
│ id (PK)                              │
│ ofertante_id (FK)                    │
│ solicitud_id (FK)                    │
│ mensaje                              │
│ precio_propuesto                     │
│ fecha_propuesta                      │
│ estado (ENUM)                        │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│           MENSAJES                   │
├──────────────────────────────────────┤
│ id (PK)                              │
│ remitente_id (FK)                    │
│ destinatario_id (FK)                 │
│ asunto                               │
│ mensaje                              │
│ leido (TINYINT)                      │
│ fecha_envio                          │
│ archivado_remitente                  │
│ archivado_destinatario               │
│ eliminado_remitente                  │
│ eliminado_destinatario               │
└──────────────────────────────────────┘
    ▲                              ▲
    │                              │
    │ 1:N                          │ 1:N
    │                              │
┌─────────────────────────────────────┐
│            USUARIOS                 │
│  (como remitente y destinatario)    │
└─────────────────────────────────────┘
```

---

## Descripción de Entidades

### 1. USUARIOS
**Descripción**: Tabla base que almacena información común de todos los usuarios del sistema.

**Atributos Clave**:
- `id`: Identificador único (PK, AUTO_INCREMENT)
- `email`: Email único (UK) para autenticación
- `password`: Contraseña hasheada con bcrypt
- `tipo`: Enum que define el rol (ofertante, consumidor, admin)
- `activo`: Flag para activar/desactivar cuenta

**Relaciones**:
- 1:1 con OFERTANTES (si tipo = 'ofertante')
- 1:1 con CONSUMIDORES (si tipo = 'consumidor')
- 1:N con MENSAJES (como remitente)
- 1:N con MENSAJES (como destinatario)

---

### 2. OFERTANTES
**Descripción**: Perfil específico de usuarios que ofrecen actividades.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `usuario_id`: Foreign key a USUARIOS (UNIQUE, 1:1)
- `descripcion`: Descripción profesional del ofertante
- `experiencia`: Experiencia y trayectoria
- `certificaciones`: Certificaciones y títulos
- `verificado`: Flag de verificación (0/1)

**Relaciones**:
- N:1 con USUARIOS
- 1:N con ACTIVIDADES
- 1:N con INTERESES_OFERTANTES
- 1:N con PROPUESTAS_OFERTANTES

---

### 3. CONSUMIDORES
**Descripción**: Perfil específico de usuarios que buscan y reservan actividades.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `usuario_id`: Foreign key a USUARIOS (UNIQUE, 1:1)
- `preferencias`: Preferencias de actividades
- `nivel_experiencia`: Enum (principiante, intermedio, avanzado)
- `restricciones_medicas`: Restricciones de salud
- `alergias`: Alergias conocidas

**Relaciones**:
- N:1 con USUARIOS
- 1:N con RESERVAS
- 1:N con SOLICITUDES_CONSUMIDORES

---

### 4. ACTIVIDADES
**Descripción**: Catálogo de actividades ofertadas por los ofertantes.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `ofertante_id`: Foreign key a OFERTANTES
- `titulo`: Título de la actividad
- `categoria`: Categoría principal
- `duracion_horas`: Duración en horas
- `dificultad`: Enum (fácil, media, difícil, muy_difícil)
- `precio_persona`: Precio por persona
- `estado`: Enum (activa, cancelada, pausada)

**Relaciones**:
- N:1 con OFERTANTES
- 1:N con DISPONIBILIDAD_ACTIVIDADES
- 1:N con RESERVAS

---

### 5. DISPONIBILIDAD_ACTIVIDADES
**Descripción**: Fechas y horarios disponibles para cada actividad.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `actividad_id`: Foreign key a ACTIVIDADES
- `fecha_inicio`: Fecha y hora de inicio
- `fecha_fin`: Fecha y hora de fin (calculada)
- `plazas_disponibles`: Número de plazas disponibles
- `precio_especial`: Precio especial para esta fecha (opcional)
- `estado`: Enum (disponible, completo, cancelado)

**Relaciones**:
- N:1 con ACTIVIDADES
- 1:N con RESERVAS

---

### 6. RESERVAS
**Descripción**: Reservas realizadas por consumidores para actividades específicas.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `consumidor_id`: Foreign key a CONSUMIDORES
- `actividad_id`: Foreign key a ACTIVIDADES
- `disponibilidad_id`: Foreign key a DISPONIBILIDAD_ACTIVIDADES
- `num_participantes`: Número de participantes
- `precio_total`: Precio total calculado
- `estado`: Enum (pendiente, confirmada, cancelada, completada)
- `fecha_confirmacion`: Fecha de confirmación por ofertante

**Relaciones**:
- N:1 con CONSUMIDORES
- N:1 con ACTIVIDADES
- N:1 con DISPONIBILIDAD_ACTIVIDADES
- 1:N con PARTICIPANTES_RESERVAS

---

### 7. PARTICIPANTES_RESERVAS
**Descripción**: Nombres de los participantes de cada reserva.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `reserva_id`: Foreign key a RESERVAS
- `nombre`: Nombre del participante
- `orden`: Orden del participante en la lista

**Relaciones**:
- N:1 con RESERVAS

---

### 8. SOLICITUDES_CONSUMIDORES
**Descripción**: Solicitudes personalizadas creadas por consumidores.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `consumidor_id`: Foreign key a CONSUMIDORES
- `titulo`: Título de la solicitud
- `descripcion`: Descripción detallada
- `categoria`: Categoría de actividad buscada
- `fecha_deseada`: Fecha deseada
- `presupuesto_max`: Presupuesto máximo
- `estado`: Enum (activa, en_proceso, completada, cancelada)

**Relaciones**:
- N:1 con CONSUMIDORES
- 1:N con INTERESES_OFERTANTES
- 1:N con PROPUESTAS_OFERTANTES

---

### 9. INTERESES_OFERTANTES
**Descripción**: Intereses mostrados por ofertantes en solicitudes.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `ofertante_id`: Foreign key a OFERTANTES
- `solicitud_id`: Foreign key a SOLICITUDES_CONSUMIDORES
- `fecha_interes`: Fecha en que se mostró interés

**Relaciones**:
- N:1 con OFERTANTES
- N:1 con SOLICITUDES_CONSUMIDORES

---

### 10. PROPUESTAS_OFERTANTES
**Descripción**: Propuestas enviadas por ofertantes a solicitudes.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `ofertante_id`: Foreign key a OFERTANTES
- `solicitud_id`: Foreign key a SOLICITUDES_CONSUMIDORES
- `mensaje`: Mensaje de la propuesta
- `precio_propuesto`: Precio propuesto
- `fecha_propuesta`: Fecha propuesta
- `estado`: Enum (pendiente, aceptada, rechazada)

**Relaciones**:
- N:1 con OFERTANTES
- N:1 con SOLICITUDES_CONSUMIDORES

---

### 11. MENSAJES
**Descripción**: Sistema de mensajería entre usuarios y mensajes automáticos del sistema.

**Atributos Clave**:
- `id`: Identificador único (PK)
- `remitente_id`: Foreign key a USUARIOS (remitente)
- `destinatario_id`: Foreign key a USUARIOS (destinatario)
- `asunto`: Asunto del mensaje
- `mensaje`: Contenido del mensaje
- `leido`: Flag de lectura (0/1)
- `archivado_remitente`: Flag de archivo para remitente
- `archivado_destinatario`: Flag de archivo para destinatario
- `eliminado_remitente`: Flag de eliminación para remitente
- `eliminado_destinatario`: Flag de eliminación para destinatario

**Relaciones**:
- N:1 con USUARIOS (como remitente)
- N:1 con USUARIOS (como destinatario)

---

## Relaciones

### Relación 1: USUARIOS → OFERTANTES
- **Tipo**: 1:1 (UNIQUE)
- **Cardinalidad**: Un usuario puede ser un ofertante (si tipo = 'ofertante')
- **Foreign Key**: `ofertantes.usuario_id` → `usuarios.id`
- **CASCADE**: `ON DELETE CASCADE` - Si se elimina usuario, se elimina ofertante

### Relación 2: USUARIOS → CONSUMIDORES
- **Tipo**: 1:1 (UNIQUE)
- **Cardinalidad**: Un usuario puede ser un consumidor (si tipo = 'consumidor')
- **Foreign Key**: `consumidores.usuario_id` → `usuarios.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 3: OFERTANTES → ACTIVIDADES
- **Tipo**: 1:N
- **Cardinalidad**: Un ofertante puede tener múltiples actividades
- **Foreign Key**: `actividades.ofertante_id` → `ofertantes.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 4: ACTIVIDADES → DISPONIBILIDAD_ACTIVIDADES
- **Tipo**: 1:N
- **Cardinalidad**: Una actividad puede tener múltiples fechas disponibles
- **Foreign Key**: `disponibilidad_actividades.actividad_id` → `actividades.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 5: DISPONIBILIDAD_ACTIVIDADES → RESERVAS
- **Tipo**: 1:N
- **Cardinalidad**: Una fecha disponible puede tener múltiples reservas
- **Foreign Key**: `reservas.disponibilidad_id` → `disponibilidad_actividades.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 6: CONSUMIDORES → RESERVAS
- **Tipo**: 1:N
- **Cardinalidad**: Un consumidor puede tener múltiples reservas
- **Foreign Key**: `reservas.consumidor_id` → `consumidores.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 7: RESERVAS → PARTICIPANTES_RESERVAS
- **Tipo**: 1:N
- **Cardinalidad**: Una reserva puede tener múltiples participantes
- **Foreign Key**: `participantes_reservas.reserva_id` → `reservas.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 8: CONSUMIDORES → SOLICITUDES_CONSUMIDORES
- **Tipo**: 1:N
- **Cardinalidad**: Un consumidor puede crear múltiples solicitudes
- **Foreign Key**: `solicitudes_consumidores.consumidor_id` → `consumidores.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 9: SOLICITUDES_CONSUMIDORES → INTERESES_OFERTANTES
- **Tipo**: 1:N
- **Cardinalidad**: Una solicitud puede recibir múltiples intereses
- **Foreign Key**: `intereses_ofertantes.solicitud_id` → `solicitudes_consumidores.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 10: SOLICITUDES_CONSUMIDORES → PROPUESTAS_OFERTANTES
- **Tipo**: 1:N
- **Cardinalidad**: Una solicitud puede recibir múltiples propuestas
- **Foreign Key**: `propuestas_ofertantes.solicitud_id` → `solicitudes_consumidores.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 11: USUARIOS → MENSAJES (Remitente)
- **Tipo**: 1:N
- **Cardinalidad**: Un usuario puede enviar múltiples mensajes
- **Foreign Key**: `mensajes.remitente_id` → `usuarios.id`
- **CASCADE**: `ON DELETE CASCADE`

### Relación 12: USUARIOS → MENSAJES (Destinatario)
- **Tipo**: 1:N
- **Cardinalidad**: Un usuario puede recibir múltiples mensajes
- **Foreign Key**: `mensajes.destinatario_id` → `usuarios.id`
- **CASCADE**: `ON DELETE CASCADE`

---

## Integridad Referencial

### Política de Eliminación en Cascada

Todas las relaciones utilizan `ON DELETE CASCADE`, lo que significa que:

1. **Eliminar Usuario** → Elimina automáticamente:
   - Perfil de ofertante o consumidor
   - Todas las actividades (si es ofertante)
   - Todas las reservas (si es consumidor)
   - Todos los mensajes (como remitente y destinatario)
   - Todas las solicitudes (si es consumidor)
   - Todas las propuestas (si es ofertante)

2. **Eliminar Actividad** → Elimina automáticamente:
   - Todas las fechas de disponibilidad
   - Todas las reservas asociadas
   - Todos los participantes de esas reservas

3. **Eliminar Disponibilidad** → Elimina automáticamente:
   - Todas las reservas para esa fecha
   - Todos los participantes de esas reservas

4. **Eliminar Reserva** → Elimina automáticamente:
   - Todos los participantes de esa reserva

### Ventajas del CASCADE
- ✅ Mantiene la integridad de datos
- ✅ Evita registros huérfanos
- ✅ Simplifica la eliminación de datos
- ✅ Garantiza consistencia

---

## Índices y Optimización

### Índices Primarios (PK)
- `usuarios.id`
- `ofertantes.id`
- `consumidores.id`
- `actividades.id`
- `disponibilidad_actividades.id`
- `reservas.id`
- `participantes_reservas.id`
- `solicitudes_consumidores.id`
- `intereses_ofertantes.id`
- `propuestas_ofertantes.id`
- `mensajes.id`

### Índices Únicos (UK)
- `usuarios.email`
- `ofertantes.usuario_id`
- `consumidores.usuario_id`

### Índices Secundarios (KEY)
```sql
-- Usuarios
KEY idx_tipo (tipo)
KEY idx_activo (activo)

-- Actividades
KEY idx_ofertante (ofertante_id)
KEY idx_categoria (categoria)
KEY idx_estado (estado)

-- Disponibilidad
KEY idx_actividad (actividad_id)
KEY idx_fecha_inicio (fecha_inicio)
KEY idx_estado (estado)

-- Reservas
KEY idx_consumidor (consumidor_id)
KEY idx_actividad (actividad_id)
KEY idx_disponibilidad (disponibilidad_id)
KEY idx_estado (estado)

-- Mensajes
KEY idx_remitente (remitente_id)
KEY idx_destinatario (destinatario_id)
KEY idx_leido (leido)
```

### Optimizaciones Implementadas
- ✅ Índices en claves foráneas para JOINs rápidos
- ✅ Índices en campos de búsqueda frecuente (categoría, estado, fecha)
- ✅ Índices en campos de filtrado (tipo, activo)
- ✅ Uso de ENUM para campos con valores limitados
- ✅ Tipos de datos apropiados (DECIMAL para precios, DATETIME para fechas)

---

## Normalización

### Primera Forma Normal (1NF)
- ✅ Todos los atributos son atómicos
- ✅ No hay grupos repetitivos
- ✅ Cada fila es única

### Segunda Forma Normal (2NF)
- ✅ Cumple 1NF
- ✅ Todos los atributos no clave dependen completamente de la clave primaria

### Tercera Forma Normal (3NF)
- ✅ Cumple 2NF
- ✅ No hay dependencias transitivas
- ✅ Los atributos no clave son independientes entre sí

---

## Consideraciones de Diseño

### Escalabilidad
- El diseño soporta grandes volúmenes de datos
- Los índices optimizan las consultas más frecuentes
- La estructura permite agregar nuevas funcionalidades sin cambios mayores

### Seguridad
- Contraseñas hasheadas (no se almacenan en texto plano)
- Tokens para recuperación de contraseña
- Campos sensibles protegidos

### Flexibilidad
- Campos TEXT para descripciones extensas
- ENUM para valores predefinidos pero extensibles
- Campos opcionales (NULL) donde es apropiado

### Mantenibilidad
- Nombres descriptivos de tablas y columnas
- Comentarios en el esquema SQL
- Estructura lógica y organizada

---

## Conclusión

El diagrama E/R de ActividadesConnect representa un modelo de datos robusto, normalizado y optimizado que:
- ✅ Mantiene la integridad referencial
- ✅ Facilita consultas eficientes
- ✅ Soporta todas las funcionalidades del sistema
- ✅ Permite escalabilidad futura
- ✅ Garantiza consistencia de datos

