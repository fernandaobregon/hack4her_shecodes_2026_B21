# Order Rescue — Hack4Her SheCodes 2026 B21

## Descripción general

**Order Rescue** es un prototipo web desarrollado para Arca Continental con el objetivo de prevenir sustituciones inesperadas en pedidos B2B.
La solución busca transformar un proceso reactivo en un proceso predictivo, conectado y automatizado mediante dashboards operativos, análisis de datos, reglas de negocio y un módulo de Machine Learning.

El sistema permite conectar información de pedidos, productos, clientes, CEDIS, rutas logísticas, validaciones de inventario y alertas de riesgo para mejorar la toma de decisiones antes del despacho.

---

## Problema detectado

Actualmente, un cliente puede realizar un pedido desde la aplicación y esperar recibir exactamente los productos solicitados. Sin embargo, durante la preparación o entrega, algunos productos pueden ser sustituidos sin que el cliente haya sido consultado previamente.

Esto genera:

* Pérdida de confianza del cliente.
* Aumento de reclamaciones.
* Mayor carga operativa para CEDIS y logística.
* Falta de trazabilidad en las sustituciones.
* Desconexión entre inventario, operación y cliente.

El problema no es únicamente que exista una sustitución, sino que muchas veces se detecta demasiado tarde.

---

## Propuesta de solución

Order Rescue funciona como un módulo adicional conectado a la app actual de pedidos y a dashboards internos.
La solución permite anticipar posibles faltantes, generar alertas, sugerir alternativas y permitir que el cliente participe en la decisión antes de que el pedido sea preparado o entregado.

La solución contempla:

* Visualización inteligente de inventario.
* Detección de productos en riesgo.
* Alertas para CEDIS, logística y gerencia.
* Notificación anticipada al cliente.
* Gestión de sustituciones.
* Validación SAP vs conteo físico.
* Dashboards por rol.
* Modelo de Machine Learning para generar Risk Score.
* API para conexión con módulo móvil.

---

## Tecnologías utilizadas

* **PHP** — backend y lógica del sistema.
* **MySQL** — base de datos relacional.
* **phpMyAdmin** — administración de base de datos.
* **HTML / CSS** — estructura visual de dashboards.
* **JavaScript** — interacción básica del frontend.
* **Python** — módulo de Machine Learning.
* **Scikit-learn** — entrenamiento del modelo predictivo.
* **XAMPP** — entorno local de desarrollo.
* **GitHub** — control de versiones del proyecto.

---

## Módulos del sistema

### 1. Login y control de acceso

El sistema cuenta con inicio de sesión por roles.
Cada usuario tiene permisos específicos según su perfil.

Roles contemplados:

* Administrador
* Supervisor CEDIS
* Coordinador Logística
* Analista de Datos / ML
* Gerente Regional

El acceso a cada vista y acción depende de la tabla de permisos del sistema.

---

### 2. Vista CEDIS / Supervisor de Almacén

Permite al encargado del almacén monitorear los pedidos pendientes de preparación.

Funciones principales:

* Visualizar pedidos en riesgo.
* Ver SKUs con stock crítico.
* Consultar déficit estimado de producto.
* Revisar nivel de riesgo: crítico, medio o bajo.
* Cambiar el estatus de una alerta.
* Marcar pedidos como listos para despacho o pendientes de respuesta del cliente.

Tablas relacionadas:

* `risk_alerts`
* `orders`
* `order_lines`
* `products`
* `customers`
* `cedis`

---

### 3. Vista Logística

Permite al equipo logístico controlar cortes de inventario y rutas de reparto.

Funciones principales:

* Confirmar el corte de inventario.
* Registrar cortes por CEDIS.
* Visualizar rutas de reparto.
* Identificar rutas con pedidos incompletos o en riesgo.
* Crear, editar y eliminar rutas según permisos.
* Monitorear progreso de carga.

Tablas relacionadas:

* `inventory_cuts`
* `delivery_routes`
* `route_orders`
* `risk_alerts`
* `orders`

---

### 4. Vista Equipo de Datos / Machine Learning

Permite validar la calidad de los datos y monitorear el modelo predictivo.

Funciones principales:

* Validar inventario SAP vs conteo físico.
* Registrar diferencias de inventario.
* Guardar inconsistencias en la base.
* Consultar métricas del modelo de Machine Learning.
* Revisar variables importantes del modelo.
* Ejecutar entrenamiento y generación de predicciones.

Tablas relacionadas:

* `inventory_validations`
* `data_quality_issues`
* `ml_model_versions`
* `ml_predictions`
* `ml_feature_importance`
* `products`
* `cedis`

---

### 5. Vista Gerente Regional

Permite visualizar indicadores ejecutivos para la toma de decisiones.

Funciones principales:

* Consultar tasa de sustitución general.
* Visualizar impacto económico estimado.
* Identificar clientes en riesgo.
* Consultar productos más sustituidos.
* Consultar CEDIS con mayor incidencia.
* Registrar notificaciones prioritarias a KAMs.

Tablas relacionadas:

* `substitution_events`
* `risk_alerts`
* `orders`
* `customers`
* `notification_log`

---

### 6. Vista Administrador

Permite configurar reglas generales del sistema y permisos por rol.

Funciones principales:

* Definir horario límite de respuesta del cliente.
* Definir hora sugerida de corte de inventario.
* Configurar umbral de riesgo.
* Configurar margen de tolerancia.
* Activar o desactivar notificaciones automáticas.
* Modificar matriz de permisos por rol.

Tablas relacionadas:

* `system_settings`
* `permissions`
* `role_permissions`
* `roles`
* `users`

---

### 7. API para módulo móvil

El sistema incluye endpoints para que una app móvil pueda conectarse al mismo backend y a la misma base de datos.

Endpoints principales:

* `api/get_alerts.php`
* `api/get_alert_detail.php`
* `api/respond_alert.php`
* `api/get_customer_preferences.php`
* `api/save_customer_preferences.php`
* `api/get_order_summary.php`
* `api/get_customer_metrics.php`

La app móvil no debe conectarse directamente a MySQL.
Debe consumir la API PHP para mantener seguridad, consistencia y trazabilidad.

---

## Machine Learning

El módulo de Machine Learning utiliza datos históricos para calcular un **Risk Score** por línea de pedido.

Flujo del modelo:

1. Se toman datos desde la vista `vw_ml_training_dataset`.
2. Se entrena un modelo con Python y Scikit-learn.
3. El modelo aprende patrones de sustitución histórica.
4. Se generan predicciones por línea de pedido.
5. Las predicciones se guardan en `ml_predictions`.
6. Si el riesgo supera el umbral definido, se genera una alerta en `risk_alerts`.
7. La alerta aparece en los dashboards de CEDIS y Gerencia.

Archivos principales del módulo ML:

* `ml/train_model.py`
* `ml/predict_risk.py`
* `ml/config.py`
* `ml/requirements.txt`

Nota: el modelo actual aprende de datos históricos. Para operación real, debe integrarse inventario actualizado después del corte logístico, ya que ese es el momento donde se tiene información más confiable del stock disponible.

---

## Estructura del proyecto

```text
order_rescue_web/
│
├── actions/
│   ├── confirm_inventory_cut.php
│   ├── delete_inventory_validation.php
│   ├── delete_route.php
│   ├── login_action.php
│   ├── notify_priority_alerts.php
│   ├── run_ml_predictions.php
│   ├── run_ml_training.php
│   ├── save_inventory_validation.php
│   ├── save_route.php
│   ├── save_system_settings.php
│   ├── update_alert_status.php
│   └── update_role_permissions.php
│
├── api/
│   ├── bootstrap.php
│   ├── get_alert_detail.php
│   ├── get_alerts.php
│   ├── get_customer_metrics.php
│   ├── get_customer_preferences.php
│   ├── get_order_summary.php
│   ├── respond_alert.php
│   └── save_customer_preferences.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── img/
│       └── arca_logo.png
│
├── config/
│   └── db.php
│
├── includes/
│   ├── auth.php
│   ├── footer.php
│   ├── header.php
│   └── helpers.php
│
├── ml/
│   ├── config.py
│   ├── predict_risk.py
│   ├── requirements.txt
│   └── train_model.py
│
├── database/
│   └── schema.sql
│
├── dashboard_admin.php
├── dashboard_cedis.php
├── dashboard_datos.php
├── dashboard_gerente.php
├── dashboard_logistica.php
├── index.php
├── login.php
├── logout.php
└── README.md
```

---

## Base de datos

La base de datos utilizada se llama:

```sql
order_rescue
```

El repositorio incluye únicamente la estructura de la base de datos:

```text
database/schema.sql
```

La base completa con datos reales no se incluye en GitHub debido a su peso.
Los archivos CSV originales y/o el respaldo completo de la base se encuentran en Drive

Tablas principales:

* `orders`
* `order_lines`
* `products`
* `customers`
* `cedis`
* `business_units`
* `substitution_events`
* `risk_alerts`
* `inventory_cuts`
* `inventory_validations`
* `delivery_routes`
* `route_orders`
* `users`
* `roles`
* `permissions`
* `role_permissions`
* `system_settings`
* `ml_model_versions`
* `ml_predictions`
* `ml_feature_importance`
* `notification_log`
* `audit_log`

Vistas principales:

* `vw_productos_mas_sustituidos`
* `vw_reemplazos_mas_usados`
* `vw_cedis_mas_sustituciones`
* `vw_cedis_dashboard_alerts`
* `vw_cedis_stock_critical`
* `vw_logistics_routes_summary`
* `vw_data_validation_dashboard`
* `vw_manager_kpis`
* `vw_ml_training_dataset`
* `vw_admin_permission_matrix`

---

## Instalación local

### 1. Clonar el repositorio

```bash
git clone https://github.com/fernandaobregon/hack4her_shecodes_2026_B21.git
```

### 2. Copiar el proyecto en XAMPP

Mover la carpeta del proyecto a:

```text
C:\xampp\htdocs\order_rescue_web
```

### 3. Encender servicios

Abrir XAMPP y encender:

* Apache
* MySQL

### 4. Crear base de datos

Entrar a phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Crear la base:

```sql
CREATE DATABASE order_rescue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Importar estructura

Importar el archivo:

```text
database/schema.sql
```

en la base `order_rescue`.

### 6. Configurar conexión

Revisar el archivo:

```text
config/db.php
```

Configuración esperada en XAMPP:

```php
$host = "localhost";
$dbname = "order_rescue";
$user = "root";
$pass = "";
```

### 7. Abrir el sistema

```text
http://localhost/order_rescue_web/login.php
```

---

## Importante sobre los datos

Este repositorio contiene la estructura del sistema, pero no incluye la base completa con datos reales.

Para que los dashboards muestren información real, se requiere importar:

* respaldo completo de la base `order_rescue`, o
* archivos CSV originales, o
* datos de prueba creados manualmente.

La base completa no se sube a GitHub por límite de tamaño y buenas prácticas.

---

## Usuarios demo

Los usuarios demo se crean en la base de datos durante la configuración inicial del sistema.

Usuarios contemplados:

```text
admin@orderrescue.local
supervisor@orderrescue.local
logistica@orderrescue.local
datos@orderrescue.local
gerente@orderrescue.local
```

Contraseña utilizada en el prototipo:

```text
Order123!
```

Si se importa únicamente `schema.sql`, estos usuarios pueden no existir porque el archivo contiene solo estructura.
En ese caso, se deben insertar manualmente o importar el respaldo completo de la base.

---

## Instalación del módulo Machine Learning

Entrar a la carpeta del proyecto:

```bash
cd C:\xampp\htdocs\order_rescue_web
```

Instalar dependencias:

```bash
py -m pip install -r ml\requirements.txt
```

Entrenar el modelo:

```bash
py ml\train_model.py
```

Generar predicciones:

```bash
py ml\predict_risk.py --limit 20000 --alert-threshold 70
```

Las predicciones se guardan en:

```text
ml_predictions
```

Y las alertas generadas se guardan en:

```text
risk_alerts
```

---

## Flujo general del sistema

```text
Cliente realiza pedido
        ↓
Sistema registra pedido y líneas
        ↓
Se analiza historial de sustituciones
        ↓
Modelo ML calcula Risk Score
        ↓
Se genera alerta si el riesgo es alto
        ↓
CEDIS visualiza pedido en riesgo
        ↓
Cliente recibe notificación en app
        ↓
Cliente acepta, cambia, elimina o solicita crédito
        ↓
CEDIS prepara pedido actualizado
        ↓
Gerencia monitorea impacto y KPIs
```

---

## Seguridad y permisos

El sistema utiliza:

* sesiones PHP
* roles
* permisos por rol
* validación CSRF en formularios
* auditoría de cambios

Cada acción crítica requiere permisos específicos.

Ejemplos:

* `update_alert_status`
* `confirm_inventory_cut`
* `manage_routes`
* `validate_inventory`
* `manage_settings`
* `manage_permissions`
* `run_ml_training`
* `run_ml_predictions`

Los cambios importantes se registran en:

```text
audit_log
```

---

## Limitaciones actuales

* El repositorio no incluye la base completa por peso.
* El modelo de Machine Learning aprende de datos históricos.
* Para predicción operativa real se requiere inventario actualizado después del corte logístico.
* Algunos IDs exportados desde Excel pueden aparecer en notación científica.
* Para producción, la integración debería realizarse directamente con SAP o con la app mediante API.

---

## Mejoras futuras

* Conexión directa con SAP.
* Integración con inventario en tiempo real.
* Entrenamiento automático programado del modelo ML.
* Notificaciones reales por WhatsApp, correo o push notification.
* Dashboard con gráficas interactivas.
* Despliegue en servidor web.
* Manejo avanzado de autenticación.
* API segura con tokens.
* App móvil conectada al backend.

---

## Equipo

Proyecto desarrollado para Hack4Her SheCodes 2026 B21.

Integrantes:

* Arantza Gutierrez Dominguez
* Camila Keilany Carrrillo Fuentes
* María Fernanda Obregón Ramírez
* Dulce María Gutierrez Dominguez

---

## Autoría técnica

La parte backend contempla:

* diseño de base de datos relacional
* normalización de datos
* dashboards conectados a MySQL
* acciones CRUD
* permisos por rol
* API para móvil
* trazabilidad
* módulo de Machine Learning

---

## Estado del proyecto

Prototipo funcional local en XAMPP.

El sistema demuestra la viabilidad de Order Rescue como solución integral para anticipar sustituciones, mejorar comunicación operativa y fortalecer la experiencia del cliente B2B.
