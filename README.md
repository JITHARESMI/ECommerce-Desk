# Commerce Desk

### E-Commerce Admin & Order Management System

Commerce Desk is a full-stack portfolio project for managing an online store’s operations: products, categories, inventory, customers, orders and simulated payments. It also includes an AI-assisted product-description workflow with human review before saving.

Built with **Laravel 12, React 19, TypeScript and MySQL**.

> This project is the administrative side of e-commerce. It does not include a customer-facing storefront, shopping cart or customer checkout. All payments are simulated; no real money is processed.

## Features

| Module | Functionality |
| --- | --- |
| Admin access | Sign in and out, hashed passwords, session authentication, CSRF protection and rate limiting |
| Products | Create and edit products, manage SKUs and prices, archive products and maintain descriptions |
| Categories | Create, edit and delete unused categories |
| Inventory | Adjust stock with a reason, view movement history and identify low-stock products |
| Customers | Create and update customer contact records |
| Orders | Create multi-item orders, reserve stock and manage order status |
| Payment simulation | Simulate successful or failed payments and pre-fulfilment refunds |
| Dashboard | View saved-data sales totals, order counts, recent orders and low-stock alerts |
| REST API | Authenticated endpoints for catalogue, customer and order operations |
| Description assistant | Generate a template or AI draft, review it and explicitly save approved content |

## Engineering highlights

- **Server-controlled pricing:** order totals are calculated from database prices using integer cents.
- **Transactional inventory:** order creation checks available stock and reserves it within a database transaction, using row locks.
- **Retry handling:** request keys prevent the same order or simulated payment from being recorded twice during normal retries.
- **Order history:** product names, SKUs and prices are saved with order items; customer names and emails are also captured at order creation.
- **Controlled transitions:** unpaid orders cannot be fulfilled. Pending cancellations and pre-fulfilment refunds release reserved stock.
- **Reviewed AI output:** structured descriptions are validated by the backend and returned as drafts, without automatically changing product content.

## Technology

| Layer | Stack |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | React 19, TypeScript, Vite |
| Database | MySQL; SQLite for automated backend tests |
| AI | Optional OpenAI integration with structured JSON output |
| Testing | PHPUnit and Vitest |
| Tooling | Docker configuration and GitHub Actions |

## Run locally

The instructions below use **Windows and XAMPP**. Docker is not required.

### 1. Requirements

- PHP 8.2 or newer, with the required Laravel extensions
- Composer
- MySQL/MariaDB running through XAMPP
- Node.js 22 and npm for building or developing the frontend

Extract or clone the project into `C:\xampp\htdocs\commerce-desk`. Start MySQL and create a database named `commerce_desk`. Start Apache if you use phpMyAdmin to create it.

### 2. Configure the backend

```cmd
cd C:\xampp\htdocs\commerce-desk\backend
if not exist .env copy .env.example .env
composer install --prefer-dist
```

Edit `backend/.env` to match your database credentials:

```dotenv
APP_NAME="Commerce Desk"
APP_URL=http://127.0.0.1:8080
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=commerce_desk
DB_USERNAME=root
DB_PASSWORD=
```

Generate the application key once for a new installation, create the tables and add an administrator:

```cmd
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan db:seed
php artisan admin:create your-email@example.com
```

The administrator command asks for your name and a password of at least 12 characters. There is no default administrator password. Seeding is optional and adds fictional products and customers.

### 3. Start the application

Start Laravel and leave its terminal open:

```cmd
php artisan serve --host=127.0.0.1 --port=8080
```

In a second terminal:

```cmd
cd C:\xampp\htdocs\commerce-desk\frontend
npm ci
npm run dev
```

Open **http://127.0.0.1:5173** and sign in with your administrator account. Vite forwards API requests to Laravel on port 8080.

For a single-server setup, build and copy the frontend into Laravel’s public directory:

```cmd
npm run build
xcopy dist\* ..\backend\public\ /E /I /Y
```

Then open **http://127.0.0.1:8080** while Laravel is running. The downloadable project archive already includes a built frontend; a fresh Git clone may require this build step.

## Try the order workflow

1. Create a category and product.
2. Add opening stock in **Inventory**, recording a reason.
3. Create a customer and an order containing one or more products.
4. Check the calculated total and reduced available stock.
5. Simulate a failed payment, then a successful payment.
6. Fulfil the paid order or simulate a refund before fulfilment.
7. Refresh the page to confirm the records persist.

## AI product-description generator

The default mode is `AI_PROVIDER=template`. It creates a clearly labelled template from supplied facts without calling an AI model.

To enable OpenAI, update `backend/.env`:

```dotenv
AI_PROVIDER=openai
OPENAI_API_KEY=your-private-api-key
OPENAI_MODEL=gpt-4o-mini
```

Run `php artisan config:clear` and restart Laravel. Your API project needs access to a model that supports the implemented structured-output request, plus available API billing credits.

In the product editor, enter the product name and verified facts, generate a draft, review its claims and choose **Use this draft**. Save the product separately to apply the description.

The application requests confirmation before sending product facts to OpenAI. Keys stay on the backend, and returned input/output token counts are recorded in `ai_runs`. Provider failures are shown as errors rather than silently replaced with template results. Generated content still requires human verification.

## API overview

Base path: `/api/v1`.

The API uses session cookies and CSRF protection. Obtain a token from `GET /csrf`, retain the session cookie and send `X-CSRF-TOKEN` with modifying requests. Refresh the token after authentication changes.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | `/login`, `/logout` | Authentication |
| GET | `/me` | Current administrator |
| GET | `/health` | Public health check |
| GET | `/data` | Admin workspace data |
| GET / POST | `/products` | List or create products |
| PUT | `/products/{id}` | Update a product |
| POST | `/products/{id}/stock` | Adjust stock |
| GET / POST | `/categories` | List or create categories |
| PUT / DELETE | `/categories/{id}` | Update or remove a category |
| GET / POST | `/customers` | List or create customers |
| PUT | `/customers/{id}` | Update a customer |
| GET / POST | `/orders` | List or create orders |
| GET | `/orders/{id}` | Order details |
| POST | `/orders/{id}/payment` | Simulate a payment |
| POST | `/orders/{id}/fulfill`, `/cancel`, `/refund` | Change order state; each action uses the `/orders/{id}` prefix |
| GET | `/payments` | Simulated payment history |
| POST | `/ai/description` | Generate a description draft |

For order creation, send a UUID `request_key`, a `customer_id` and an `items` array containing `product_id` and `quantity`. Reuse the same key only when retrying the same request. Prices and totals supplied by a client are not trusted.

## Testing

Backend:

```cmd
cd backend
php artisan test
```

Frontend:

```cmd
cd frontend
npm test
npm run build
```

The included backend suite covers authentication, totals, stock rollback, retries, payment transitions, refunds and mocked AI responses. GitHub Actions is configured to run both suites.

**Recorded validation:** the frontend production build and two frontend currency tests passed during preparation. Backend tests, MySQL concurrency behaviour, Docker, browser workflows and live OpenAI requests have not yet been verified. SQLite tests alone cannot establish MySQL row-lock behaviour. No passing CI status is claimed.

## Scope and limitations

- Admin application only; no customer storefront, customer accounts or checkout.
- Every authenticated account is an administrator; granular staff roles are not implemented.
- Payments are simulations. Currency is AUD, with no separate tax, discount or shipping calculation.
- Reservations do not expire automatically; cancel abandoned pending orders to release stock.
- Refunds after fulfilment and physical returns are outside this version.
- The admin aggregation endpoint is intended for a small dataset; larger stores need paginated UI queries.
- A Composer lock file was not generated during preparation. Resolve dependencies, run the backend tests and commit the resulting lock file before treating the build as reproducible.

## Deployment and security

Serve only `backend/public`, use HTTPS, and keep `.env` outside the web root. Configure `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` and the correct application URL. Keep `storage` and `bootstrap/cache` writable.

An optional Docker configuration is included. Copy `docker.env.example` to the root `.env`, supply an application key and a strong database password, then build the containers. The Docker configuration binds to local loopback by default. Run migrations and create an administrator explicitly before use.

Validate backend behaviour, authentication, CSRF protection and deployment configuration before public hosting. Never commit actual `.env` files, API keys, passwords or customer data. Keep `.env.example` as the setup template.

## Licence

MIT — see [LICENSE](LICENSE).
