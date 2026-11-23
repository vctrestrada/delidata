<div align="center">

<img src="docs/logo-delidata.png" alt="Delidata" width="96">

# Delidata

**Software para restaurantes con una o varias sucursales:** punto de venta, control de gastos y un dashboard en tiempo real con velocímetro de gastos contra ventas.

![Laravel](https://img.shields.io/badge/Laravel-13-F97316?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4-F97316)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-F97316?logo=postgresql&logoColor=white)
![Reverb](https://img.shields.io/badge/Reverb-WebSockets-F97316)
![Tailwind](https://img.shields.io/badge/Tailwind-4-F97316?logo=tailwindcss&logoColor=white)

</div>

---

## 📸 Capturas

| Dashboard del dueño | Punto de venta |
|---|---|
| ![Dashboard](docs/capturas/dashboard.png) | ![Punto de venta](docs/capturas/punto-de-venta.png) |

| Recorrido guiado | Gastos |
|---|---|
| ![Recorrido guiado](docs/capturas/recorrido.png) | ![Gastos](docs/capturas/gastos.png) |

---

## ✨ Funcionalidades

### Jerarquía de roles

```
Super admin ──► administra las suscripciones (planes, vigencia, renovaciones)
   │
   └── Negocio (cliente que paga) ──► Dueño
          │                             • registra sus restaurantes (según su plan)
          │                             • arma el catálogo y los precios
          │                             • da de alta a su personal
          │                             • ve el dashboard de todo el negocio
          │
          └── Restaurante ──► Encargado
                 │              • administra a sus vendedores
                 │              • marca productos agotados
                 │              • revisa ventas, cancela con motivo, registra gastos
                 │
                 └──────────► Vendedor
                                • cobra en el punto de venta
                                • consulta su corte de caja
```

### Por módulo

| Módulo | Qué hace |
|---|---|
| **Suscripciones** | Planes Básico, Profesional y Empresarial con límite de restaurantes. Estados calculados por fecha (activa, por vencer, programada, vencida, suspendida). La renovación empieza al día siguiente de que termina la vigente. |
| **Restaurantes y personal** | Alta de locales con horario. Cuentas de encargados y vendedores; cada uno ve solo su restaurante. |
| **Catálogo** | Categorías ordenables, productos con foto, costo y margen calculado, precio especial y disponibilidad por restaurante. |
| **Punto de venta** | Catálogo táctil, cálculo de cambio con billetes sugeridos, descuentos, ticket térmico de 80 mm y folio consecutivo por restaurante. |
| **Ventas** | Historial con filtros, resumen por forma de pago y cancelación con motivo y auditoría. |
| **Gastos** | Seis categorías, comprobante en foto o PDF guardado de forma privada, y utilidad del periodo. |
| **Dashboard** | Velocímetro de gastos contra ventas, tendencia diaria, comparación entre restaurantes, top de productos, horas pico y formas de pago (ApexCharts). |
| **Tiempo real** | Con Reverb, cada venta aparece al instante en el panel del dueño. Si un producto se agota, desaparece del punto de venta. |
| **Recorridos guiados** | Cada pantalla explica su uso paso a paso la primera vez, para todos los roles (Driver.js). Se pueden saltar, desactivar para siempre o volver a activar desde el menú del usuario. |

---

## 🧱 Stack

- **Backend:** Laravel 13 · PHP 8.4+ · PostgreSQL
- **Frontend:** Livewire 4 · Flux UI · Tailwind CSS 4 · Alpine.js
- **Gráficas:** ApexCharts · **Recorridos:** Driver.js
- **Tiempo real:** Laravel Reverb + Laravel Echo
- **Roles y permisos:** spatie/laravel-permission
- **Pruebas:** PHPUnit (SQLite en memoria)

---

## 🔐 Decisiones de seguridad

- **Aislamiento entre negocios:** todas las consultas pasan por el *trait* `AlcanceDelNegocio`, así que un dueño nunca puede ver datos de otro negocio y un encargado solo ve su restaurante. Un recurso ajeno responde **404**, sin revelar que existe.
- **Precios del lado del servidor:** el punto de venta solo envía *qué productos y cuántos*. `RegistrarVenta` vuelve a leer los precios y la disponibilidad de la base de datos.
- **Folios sin duplicados:** se bloquea el restaurante (`lockForUpdate`) mientras se asigna el folio.
- **Historial inmutable:** el detalle de cada venta guarda nombre, precio y costo del producto al momento de vender, y las ventas canceladas no se borran.
- **Comprobantes privados:** se guardan fuera de `public/` y solo se entregan con un controlador que verifica permisos.
- **Canales privados de WebSockets:** autorizados por negocio y restaurante; los usuarios desactivados no pueden suscribirse.
- **Cuentas desactivadas:** su sesión se cierra en la siguiente petición (middleware `UsuarioActivo`).
- **Registro público desactivado:** las cuentas las crea el nivel superior de la jerarquía.

---

## 🚀 Instalación

Requisitos: PHP 8.4+ (con `pdo_pgsql`), Composer, Node 20+ y PostgreSQL.

```bash
git clone https://github.com/TU_USUARIO/delidata.git
cd delidata
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configura PostgreSQL en el `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=delidata
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña
```

Luego:

```bash
php artisan install:broadcasting --reverb   # configura Reverb y sus llaves
php artisan storage:link
php artisan migrate --seed
composer run dev                            # servidor, Vite, colas y Reverb
```

Abre <http://localhost:8000>.

### Usuarios de prueba

Contraseña para todos: **`password`**

| Rol | Correo | Entra a |
|---|---|---|
| Super admin | `superadmin@delidata.test` | Suscripciones |
| Dueño | `dueno@delidata.test` | La Esquina Taquería (2 restaurantes) |
| Encargado | `encargado@delidata.test` | La Esquina — Centro |
| Vendedor | `vendedor@delidata.test` | Punto de venta de Centro |

Los datos de ejemplo incluyen **45 días de ventas y gastos**. Centro deja buena utilidad y Plaza Neza tiene el margen apretado, así que el dashboard muestra contraste desde el primer momento.

---

## 🧪 Pruebas

```bash
php artisan test
```

Cubren suscripciones y licencias, límites del plan, aislamiento entre negocios y restaurantes, catálogo y disponibilidad, punto de venta (precios, folios, efectivo, agotados), cancelaciones, tickets, gastos y comprobantes privados, dashboard y autorización de canales en tiempo real.

---

## 📁 Estructura principal

```
app/
├── Actions/Ventas/          RegistrarVenta, CancelarVenta
├── Enums/                   Rol, PlanLicencia, EstadoLicencia, MetodoPago, CategoriaGasto…
├── Events/                  Eventos en tiempo real (Reverb)
├── Http/Middleware/         VerificarLicencia, UsuarioActivo
├── Livewire/
│   ├── Admin/               Suscripciones (super admin)
│   ├── Negocio/             Inicio, Restaurantes, Personal, Productos (dueño)
│   ├── Encargado/           Inicio y menú
│   ├── Ventas/              Punto de venta, mis ventas, historial
│   ├── Gastos/              Lista y formulario
│   ├── Dashboard/           Panel con gráficas
│   └── Concerns/            AlcanceDelNegocio, EscuchaTiempoReal
└── Models/                  Negocio, Licencia, Restaurante, Producto, Venta, Gasto…
resources/js/
├── recorridos.js            Recorridos guiados (Driver.js)
├── graficas.js              Componente de ApexCharts
└── tiempo-real.js           Avisos y estado de conexión
```

---

<div align="center">
Desarrollado por <strong>Victor</strong> · Universidad Tecnológica de Nezahualcóyotl
</div>
