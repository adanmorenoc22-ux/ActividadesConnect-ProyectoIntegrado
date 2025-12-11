# README - Funcionalidad Completa de ActividadesConnect

## 📋 Índice
1. [Descripción del Proyecto](#descripción-del-proyecto)
2. [Funcionalidades Principales](#funcionalidades-principales)
3. [Funcionalidades por Tipo de Usuario](#funcionalidades-por-tipo-de-usuario)
4. [Flujos de Trabajo](#flujos-de-trabajo)
5. [Sistemas Integrados](#sistemas-integrados)
6. [Características Técnicas](#características-técnicas)

---

## Descripción del Proyecto

**ActividadesConnect** es una plataforma web completa que conecta organizadores de actividades de ocio (ofertantes) con personas interesadas en vivir experiencias únicas (consumidores). La aplicación facilita el encuentro, la reserva y la gestión de actividades de ocio, entretenimiento, deporte aventura y turismo.

### Objetivo Principal
Crear un ecosistema donde los ofertantes puedan publicar y gestionar sus actividades, y los consumidores puedan descubrir, reservar y participar en experiencias que se adapten a sus preferencias.

---

## Funcionalidades Principales

### 🔐 Sistema de Autenticación y Usuarios

#### Registro de Usuarios
- **Registro dual**: Ofertantes y Consumidores
- **Validación completa**: Email único, contraseña segura, términos aceptados
- **Creación automática de perfiles**: Al registrarse se crea automáticamente el perfil específico
- **Verificación de email**: Sistema de tokens para verificación

#### Inicio de Sesión
- **Autenticación segura**: Hash de contraseñas con `password_hash()`
- **Gestión de sesiones**: Control de acceso basado en roles
- **Recordar sesión**: Opción de mantener sesión abierta
- **Recuperación de contraseña**: Sistema de tokens para reset

#### Gestión de Perfiles
- **Perfil completo**: Datos personales y específicos según tipo de usuario
- **Edición de perfil**: Actualización de toda la información
- **Visualización**: Vista propia y vista pública
- **Eliminación de cuenta**: Proceso seguro con confirmaciones

---

## Funcionalidades por Tipo de Usuario

### 👨‍💼 Para Ofertantes

#### 1. Gestión de Perfil
- **Datos personales**: Nombre, apellidos, email, teléfono, fecha de nacimiento
- **Datos profesionales**:
  - Descripción profesional
  - Experiencia y trayectoria
  - Certificaciones y títulos
  - Disponibilidad general
- **Estadísticas**: Total de actividades, reservas recibidas

#### 2. Gestión de Actividades
- **Crear actividades**:
  - Información básica (título, descripción, categoría)
  - Detalles técnicos (duración, dificultad, precio)
  - Ubicación (lugar inicio/fin, coordenadas)
  - Material (requerido e incluido)
  - Requisitos (edad, preparación física, restricciones)
  - Servicios incluidos (transporte, comida, seguro)
  
- **Editar actividades**: Modificación completa de todos los campos
- **Ver mis actividades**: Listado con estadísticas y acciones
- **Eliminar actividades**: Eliminación permanente con confirmación

#### 3. Gestión de Disponibilidad
- **Agregar fechas disponibles**:
  - Fecha y hora de inicio
  - Cálculo automático de hora fin (basado en duración)
  - Número de plazas disponibles
  - Precio especial (opcional)
  
- **Editar disponibilidad**: Modificar fechas, horarios y plazas
- **Cancelar disponibilidad**: Cancelar fechas específicas
- **Eliminar disponibilidad cancelada**: Eliminación permanente de fechas canceladas

#### 4. Gestión de Reservas
- **Ver todas las reservas**: Listado completo de reservas de sus actividades
- **Confirmar/Rechazar reservas**: Aceptar o rechazar solicitudes pendientes
- **Completar reservas**: Marcar actividades como completadas
- **Ver participantes**: Lista detallada de participantes por actividad y fecha
- **Información del cliente**: Datos de contacto del consumidor

#### 5. Sistema de Solicitudes
- **Buscar solicitudes**: Explorar solicitudes de consumidores
- **Mostrar interés**: Indicar interés en una solicitud
- **Crear propuestas**: Enviar propuestas personalizadas a consumidores
- **Gestionar propuestas**: Ver estado de propuestas enviadas

#### 6. Sistema de Mensajería
- **Bandeja de entrada**: Mensajes recibidos
- **Mensajes enviados**: Historial de mensajes enviados
- **Archivar mensajes**: Organizar mensajes importantes
- **Papelera**: Recuperar o eliminar permanentemente mensajes
- **Notificaciones automáticas**: Mensajes del sistema sobre reservas, confirmaciones, etc.

---

### 👤 Para Consumidores

#### 1. Gestión de Perfil
- **Datos personales**: Nombre, apellidos, email, teléfono, fecha de nacimiento
- **Preferencias y datos adicionales**:
  - Preferencias de actividades
  - Nivel de experiencia (principiante, intermedio, avanzado)
  - Restricciones médicas
  - Alergias conocidas
- **Estadísticas**: Total de reservas, solicitudes enviadas, intereses recibidos

#### 2. Búsqueda de Actividades
- **Catálogo completo**: Ver todas las actividades disponibles
- **Filtros avanzados**:
  - Por categoría
  - Por precio (rango)
  - Por dificultad
  - Por fecha disponible
  - Por ubicación
- **Búsqueda por texto**: Buscar en títulos y descripciones
- **Vista detallada**: Información completa de cada actividad

#### 3. Sistema de Reservas
- **Crear reservas**:
  - Seleccionar actividad
  - Elegir fecha disponible
  - Especificar número de participantes
  - **Ingresar nombres de participantes**: Lista completa de asistentes
  - Añadir notas especiales
  - Ver precio total calculado
  
- **Validaciones**:
  - Verificar plazas disponibles
  - Prevenir reservas duplicadas
  - Validar número de participantes
  
- **Gestionar reservas**:
  - Ver todas mis reservas
  - Ver estado (pendiente, confirmada, completada, cancelada)
  - Ver lista de participantes
  - Cancelar reservas pendientes o confirmadas
  - Eliminar reservas canceladas

#### 4. Sistema de Solicitudes
- **Crear solicitudes personalizadas**:
  - Título y descripción de lo que buscas
  - Categoría de actividad
  - Fecha deseada
  - Hora deseada
  - Duración estimada
  - Presupuesto máximo
  - Ubicación
  - Número de participantes estimados
  - Requisitos especiales
  
- **Gestionar solicitudes**:
  - Ver todas mis solicitudes
  - Ver intereses de ofertantes
  - Ver propuestas recibidas
  - Aceptar/rechazar propuestas
  - Editar solicitudes activas
  - Cancelar solicitudes

#### 5. Sistema de Mensajería
- **Bandeja de entrada**: Mensajes recibidos
- **Mensajes enviados**: Historial de mensajes enviados
- **Archivar mensajes**: Organizar mensajes importantes
- **Papelera**: Recuperar o eliminar permanentemente mensajes
- **Notificaciones automáticas**: Mensajes del sistema sobre reservas, confirmaciones, etc.

---

## Flujos de Trabajo

### Flujo: Ofertante Publica Actividad

```
1. Ofertante se registra
   ↓
2. Completa su perfil profesional
   ↓
3. Crea una nueva actividad
   - Define título, descripción, categoría
   - Establece precio, duración, dificultad
   - Configura ubicación y requisitos
   ↓
4. Gestiona disponibilidad
   - Agrega fechas y horarios disponibles
   - Define número de plazas por fecha
   - Establece precios especiales si aplica
   ↓
5. Actividad visible en catálogo
   ↓
6. Recibe reservas de consumidores
   ↓
7. Confirma o rechaza reservas
   ↓
8. Gestiona participantes
   - Ve lista de participantes por fecha
   - Contacta con clientes si es necesario
   ↓
9. Marca actividad como completada
```

### Flujo: Consumidor Reserva Actividad

```
1. Consumidor se registra
   ↓
2. Completa su perfil y preferencias
   ↓
3. Busca actividades
   - Usa filtros para encontrar lo que busca
   - Revisa detalles de actividades
   ↓
4. Selecciona actividad y fecha
   ↓
5. Crea reserva
   - Especifica número de participantes
   - Ingresa nombre de cada participante
   - Añade notas si es necesario
   ↓
6. Espera confirmación del ofertante
   ↓
7. Recibe mensaje de confirmación/rechazo
   ↓
8. Asiste a la actividad
   ↓
9. Ofertante marca como completada
```

### Flujo: Solicitud Personalizada

```
1. Consumidor crea solicitud
   - Describe lo que busca
   - Especifica fecha, presupuesto, ubicación
   ↓
2. Ofertantes muestran interés
   - Ofertantes ven la solicitud
   - Muestran interés si pueden ayudar
   ↓
3. Ofertantes envían propuestas
   - Crean propuesta personalizada
   - Establecen precio propuesto
   ↓
4. Consumidor revisa propuestas
   - Ve todas las propuestas recibidas
   - Compara ofertas
   ↓
5. Consumidor acepta/rechaza propuestas
   ↓
6. Si acepta: Se crea actividad y reserva automáticamente
```

---

## Sistemas Integrados

### 📧 Sistema de Mensajería

#### Características
- **Mensajería directa**: Comunicación entre usuarios
- **Bandeja organizada**: Entrada, Enviados, Archivados, Papelera
- **Mensajes del sistema**: Notificaciones automáticas sobre eventos importantes
- **Gestión completa**: Archivar, eliminar, restaurar mensajes

#### Eventos que Generan Mensajes Automáticos
- Nueva reserva recibida (ofertante)
- Reserva confirmada (consumidor)
- Reserva rechazada (consumidor)
- Reserva cancelada (consumidor → ofertante)
- Reserva completada (consumidor)
- Interés en solicitud (consumidor)
- Propuesta recibida (consumidor)

### 📅 Sistema de Reservas

#### Estados de Reserva
- **Pendiente**: Recién creada, esperando confirmación
- **Confirmada**: Aceptada por el ofertante
- **Completada**: Actividad realizada
- **Cancelada**: Cancelada por consumidor u ofertante
- **Rechazada**: Rechazada por el ofertante

#### Funcionalidades
- **Gestión de plazas**: Actualización automática de plazas disponibles
- **Lista de participantes**: Nombres de todos los asistentes
- **Cálculo de precios**: Precio total automático
- **Validaciones**: Prevención de sobre-reservas
- **Historial completo**: Todas las reservas pasadas y futuras

### 🎯 Sistema de Solicitudes

#### Estados de Solicitud
- **Activa**: Abierta a propuestas
- **En proceso**: Tiene intereses o propuestas
- **Completada**: Se aceptó una propuesta
- **Cancelada**: Cancelada por el consumidor

#### Funcionalidades
- **Búsqueda avanzada**: Filtros por categoría, fecha, presupuesto
- **Sistema de intereses**: Ofertantes muestran interés
- **Sistema de propuestas**: Ofertantes envían ofertas personalizadas
- **Gestión de propuestas**: Aceptar/rechazar propuestas

### 📊 Sistema de Disponibilidad

#### Características
- **Múltiples fechas**: Cada actividad puede tener múltiples fechas disponibles
- **Gestión flexible**: Agregar, editar, cancelar fechas
- **Plazas por fecha**: Número de plazas independiente por cada fecha
- **Precios especiales**: Precio diferente por fecha si es necesario
- **Estados**: Disponible, Completo, Cancelado

---

## Características Técnicas

### Seguridad
- ✅ **Prepared Statements (PDO)**: Prevención SQL injection
- ✅ **Password Hashing**: Contraseñas hasheadas con bcrypt
- ✅ **Sanitización de Inputs**: Prevención XSS
- ✅ **Validación Backend y Frontend**: Doble validación
- ✅ **Control de Sesiones**: Gestión segura de autenticación
- ✅ **Verificación de Permisos**: Control de acceso basado en roles

### Validaciones Implementadas
- **Email**: Formato válido y único
- **Teléfono**: 9 dígitos numéricos
- **Contraseña**: Mínimo 6 caracteres
- **Fechas**: Formato correcto y lógica de negocio
- **Precios**: Valores numéricos positivos
- **Plazas**: Números enteros positivos
- **Campos requeridos**: Validación de obligatoriedad

### Optimizaciones
- **Índices en BD**: Consultas rápidas
- **Consultas eficientes**: JOINs optimizados
- **Transacciones SQL**: Integridad de datos
- **Código reutilizable**: Funciones auxiliares
- **Diseño responsive**: Adaptable a todos los dispositivos

### Experiencia de Usuario
- **Interfaz intuitiva**: Navegación clara y sencilla
- **Feedback visual**: Alertas y mensajes informativos
- **Validación en tiempo real**: Feedback inmediato
- **Animaciones suaves**: Transiciones agradables
- **Iconografía clara**: Font Awesome para mejor comprensión

---

## Módulos Principales

### Módulo de Autenticación
- Registro de usuarios
- Inicio de sesión
- Cierre de sesión
- Recuperación de contraseña
- Gestión de sesiones

### Módulo de Perfiles
- Visualización de perfil
- Edición de perfil
- Cambio de contraseña
- Eliminación de cuenta

### Módulo de Actividades
- Creación de actividades
- Edición de actividades
- Visualización de actividades
- Eliminación de actividades
- Gestión de disponibilidad
- Búsqueda y filtrado

### Módulo de Reservas
- Creación de reservas
- Confirmación/rechazo
- Cancelación
- Completado
- Visualización de participantes
- Gestión de plazas

### Módulo de Solicitudes
- Creación de solicitudes
- Búsqueda de solicitudes
- Sistema de intereses
- Sistema de propuestas
- Gestión de propuestas

### Módulo de Mensajería
- Envío de mensajes
- Recepción de mensajes
- Archivo de mensajes
- Eliminación de mensajes
- Restauración de mensajes
- Mensajes automáticos del sistema

---

## Estadísticas y Reportes

### Para Ofertantes
- Total de actividades creadas
- Total de reservas recibidas
- Actividades por estado
- Reservas por estado

### Para Consumidores
- Total de reservas realizadas
- Total de solicitudes enviadas
- Intereses recibidos en solicitudes
- Reservas por estado

---

## Características Adicionales

### Dashboard Personalizado
- **Vista diferente según tipo de usuario**
- **Estadísticas relevantes**
- **Accesos rápidos a funcionalidades principales**
- **Mensajes recientes**
- **Actividad reciente**

### Sistema de Navegación
- **Menú contextual**: Se adapta según el tipo de usuario
- **Breadcrumbs**: Navegación clara
- **Enlaces rápidos**: Acceso directo a funciones comunes

### Diseño Responsive
- **Adaptable a móviles**: Diseño optimizado para smartphones
- **Tablets**: Experiencia completa en tablets
- **Desktop**: Aprovecha el espacio en pantallas grandes

---

## Integraciones y Comunicación

### Mensajería Automática
El sistema envía mensajes automáticos para:
- Nuevas reservas
- Confirmaciones de reservas
- Rechazos de reservas
- Cancelaciones
- Completado de actividades
- Intereses en solicitudes
- Propuestas recibidas

### Notificaciones Visuales
- **Contador de mensajes no leídos**: En el menú principal
- **Alertas de éxito/error**: Feedback inmediato de acciones
- **Estados visuales**: Iconos y colores para estados

---

## Resumen de Funcionalidades

### Total de Funcionalidades Implementadas
- ✅ **Sistema de usuarios completo** (3 tipos: ofertante, consumidor, admin)
- ✅ **CRUD completo de ofertantes** (Create, Read, Update, Delete)
- ✅ **CRUD completo de consumidores** (Create, Read, Update, Delete)
- ✅ **Gestión completa de actividades**
- ✅ **Sistema de reservas avanzado**
- ✅ **Sistema de solicitudes personalizadas**
- ✅ **Sistema de mensajería completo**
- ✅ **Sistema de disponibilidad flexible**
- ✅ **Gestión de participantes**
- ✅ **Búsqueda y filtrado avanzado**
- ✅ **Dashboard personalizado**
- ✅ **Sistema de autenticación seguro**
- ✅ **Gestión de perfiles completa**

---

## Conclusión

ActividadesConnect es una aplicación web completa y funcional que cubre todos los aspectos necesarios para conectar ofertantes y consumidores de actividades de ocio. La aplicación está diseñada para ser intuitiva, segura y eficiente, proporcionando una experiencia de usuario excepcional tanto para ofertantes como para consumidores.

