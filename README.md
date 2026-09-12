# Commerce Desk
E-commerce admin and order management with Laravel 12, React 19, TypeScript and MySQL.

A portfolio application for managing a small store: a protected workspace, catalogue, stock ledger, customers, transactional orders, simulated payments and a reviewed AI product-description workflow.

## Included
- Administrator sign-in/logout: password hashing, session rotation, HttpOnly cookies, CSRF protection and throttling. Create administrators through the CLI; no public registration.
- Products: create/edit, unique SKU, categories, price, verified facts, descriptions and archive/unarchive.
- Categories: create/edit/delete; categories containing products cannot be deleted.
- Inventory: signed adjustments with reasons, available stock, low-stock thresholds and movement history.
- Customers: create/edit name, email, phone and address. Orders preserve customer name/email snapshots.
- Orders: multiple items, server-calculated totals in integer cents, row locks, stock reservations and request-key deduplication.
- Payment simulation: success, failure and pre-fulfilment refunds. No payment gateway or card data.
- Dashboard: saved-data totals, recent orders, low-stock list and order counts.
- REST API plus a session-authenticated workspace aggregation endpoint.
- Description assistant: explicit template mode or OpenAI structured output, consent before external calls, human review and token usage records. Drafts never overwrite products automatically.

## Validation status
- React/TypeScript production build: passed.
- Frontend currency tests: 2 passed.
- Laravel feature tests: included, NOT executed here because PHP/Composer/MySQL are unavailable. Environment permissions blocked PHP installation.
- Live OpenAI call, Docker build and browser/end-to-end tests: NOT executed.
- Composer lock: not generated here. The first composer install resolves compatible dependencies. Commit backend/composer.lock after successful testing.
- This is source code with validation still required on your machine, not a claim of production certification.

## Windows / XAMPP setup
Extract into a NEW folder: C:\xampp\htdocs\commerce-desk

Requirements: PHP 8.2+, Composer, MySQL/MariaDB from XAMPP. Node.js 22 is recommended for development. A built frontend is included in backend/public in this download, so viewing it does not require Node.

1. Start MySQL in XAMPP. Start Apache too if using phpMyAdmin. Create the database commerce_desk.
2. Check command-line PHP:
~~~cmd
php -v
php --ini
~~~
If needed, add C:\xampp\php to PATH. Enable zip, mbstring, pdo_mysql, openssl and curl in php.ini as needed; tests also require XML/DOM and pdo_sqlite. Do not append Linux commands such as || true.

3. Install:
~~~cmd
cd C:\xampp\htdocs\commerce-desk\backend
if not exist .env copy .env.example .env
composer install --prefer-dist
~~~
4. Edit backend/.env. Defaults use local MySQL, database commerce_desk, root and a blank password. Update to your actual credentials. Keep APP_NAME="Commerce Desk" quoted.
5. Generate the key ONCE for a new installation, migrate and create your administrator:
~~~cmd
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan db:seed
php artisan admin:create your-email@example.com
~~~
The administrator command asks for a name and password of at least 12 characters. No default password is shipped. The optional seeder creates sample products/customers, never administrators or orders, and preserves existing sample stock on reruns.

6. Start:
~~~cmd
php artisan serve --host=127.0.0.1 --port=8080
~~~
Open http://127.0.0.1:8080 and sign in. Keep the terminal open. The download includes a built React interface.

### Frontend development
In a second terminal:
~~~cmd
cd C:\xampp\htdocs\commerce-desk\frontend
npm ci
npm run dev
~~~
Open http://127.0.0.1:5173. Vite proxies /api to Laravel on port 8080. Use the same hostname consistently.
Optional scripts/setup-windows.cmd installs dependencies and builds; start-backend.cmd and start-frontend.cmd launch servers. Database creation, migrations and administrator creation remain explicit.

After editing, rebuild the single-server interface:
~~~cmd
cd C:\xampp\htdocs\commerce-desk\frontend
npm run build
xcopy dist\* ..\backend\public\ /E /I /Y
~~~
This adds index.html, assets and favicon.svg without replacing Laravel's index.php.

## Try the workflow
1. Sign in. The sample catalogue has four products and zero orders.
2. Add a category and product; new products start with zero stock.
3. Add stock with a reason in Inventory. Check its history.
4. Add a customer and create an order with two different products.
5. Confirm the total and reduced available stock; refresh to check persistence.
6. Simulate failure: order stays pending, stock remains reserved. Simulate success: order becomes paid.
7. Fulfil the paid order, or refund it before fulfilment. Cancellation/refund releases stock once.
8. Edit a product, generate a draft, review it, choose Use this draft, then Save changes.
9. Sign out and confirm private API requests require authentication.

Business rules: AUD only; no separate taxes, shipping or discounts. Stock reservations do not expire; cancel abandoned pending orders. Fulfilled returns/refunds are outside this version. Archive products instead of deleting order history. Customers are editable but not deleted through the UI. Every authenticated account is an administrator.

## AI descriptions
Default AI_PROVIDER=template copies verified facts into a clearly labelled template; no model is called.

For live generation set backend/.env:
~~~dotenv
AI_PROVIDER=openai
OPENAI_API_KEY=your-private-key
OPENAI_MODEL=gpt-4o-mini
~~~
Run php artisan config:clear and restart Laravel. The model must support Chat Completions structured outputs in your API project. API access, available billing credits and internet access are required.

Only product name/facts are sent, after confirmation. Do not put customer information or secrets in them. The prompt prohibits invented claims, but human review remains necessary. The backend validates the description, records returned token counts in ai_runs, and reports failures without silently falling back. Token cost estimates are not included because pricing is not configured. API keys remain server-side.
Reference: https://developers.openai.com/api/docs/guides/structured-outputs

## REST API
Base: /api/v1. JSON responses. Session authentication, not bearer tokens.
GET /csrf returns a token and session cookie. Retain cookies and send X-CSRF-TOKEN for POST/PUT/DELETE. Refresh the token after login/logout.

| Endpoint | Purpose |
| --- | --- |
| POST /login, GET /me, POST /logout | Authentication |
| GET /health | Public health check |
| GET /data | Admin workspace data |
| GET/POST /products, PUT /products/{id} | Product catalogue |
| POST /products/{id}/stock | delta and reason |
| GET/POST /categories, PUT/DELETE /categories/{id} | Categories |
| GET/POST /customers, PUT /customers/{id} | Customers |
| GET/POST /orders, GET /orders/{id} | Orders |
| POST /orders/{id}/payment | request_key UUID and outcome success/failed |
| POST /orders/{id}/fulfill, /cancel, /refund | Order transitions |
| GET /payments | Simulation ledger |
| POST /ai/description | name, facts, confirm_external |

Collection GET routes are paginated. /data loads the small-store catalogue/customers/orders and latest 100 movements/payments. Large datasets need server-side UI pagination.

Order body:
~~~json
{"request_key":"59c477f9-3912-4ce9-b4dc-dd5f7016a663","customer_id":1,"items":[{"product_id":1,"quantity":2}]}
~~~
Reuse a request key for the SAME request after a network failure; use a new key for a new order. Duplicate product lines are rejected. Client prices/totals are ignored. Validation: 422. Unauthenticated: 401. CSRF failure: 419.

## Tests and GitHub Actions
~~~cmd
cd backend
php artisan test
cd ..\frontend
npm test
npm run build
~~~
Backend tests use in-memory SQLite and cover authentication, server totals, stock rollback, request deduplication, refund restoration, transitions and mocked AI. Laravel bypasses CSRF middleware in these framework tests: verify CSRF manually before deployment. Concurrent row-lock behaviour needs MySQL integration testing; SQLite tests cannot establish it.
CI runs both suites. No passing CI badge is claimed.

## Docker (optional)
Only use if Docker Desktop is installed and running. Copy docker.env.example to root .env and set a strong DB_PASSWORD. Generate an APP_KEY:
~~~cmd
docker run --rm php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
~~~
Copy the output into APP_KEY in root .env (separate from backend/.env used by XAMPP).
~~~cmd
docker compose up --build -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan admin:create your-email@example.com
~~~
Open http://127.0.0.1:8080. MySQL uses a named volume. Do not run docker compose down -v unless you intend to delete the database. Container recreation logs administrators out because file sessions are ephemeral.

## Deployment
Serve ONLY backend/public through PHP 8.2+ Apache/Nginx. Build the frontend there. Make storage and bootstrap/cache writable. Keep .env outside the web root. Configure HTTPS, APP_ENV=production, APP_DEBUG=false, SESSION_SECURE_COOKIE=true and the real APP_URL. Use a dedicated database account and backups.
The Docker example binds to loopback for local use. Before public deployment run backend/MySQL tests, verify authentication/CSRF, lock dependencies, and configure monitoring/backups. No remote infrastructure or deployment is created.

## GitHub
From the project root:
~~~cmd
git init
git add .
git status
git commit -m "Build Commerce Desk admin and order management"
git branch -M main
git remote add origin YOUR_ACTUAL_REPOSITORY_URL
git push -u origin main
~~~
Replace the URL before running. Review staged files: never include .env or private data. If origin exists, use git remote set-url origin YOUR_ACTUAL_REPOSITORY_URL. Commit composer.lock after resolving/testing dependencies. Fresh Git clones need a frontend build before using the single-server URL.

## Architecture
React → session/CSRF-protected Laravel routes → controllers → OrderService transactions → MySQL.
Product facts → AI endpoint → structured draft → human review → normal product save.
Payments only change a simulation ledger, never a processor.

MIT licence. All sample customers and products are fictional.

