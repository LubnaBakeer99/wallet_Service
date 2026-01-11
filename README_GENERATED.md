# Wallet Service

A small, production-minded Laravel service implementing wallet management, idempotent deposits/withdrawals, atomic transfers, and a transaction audit trail.

This repository implements the backend logic (models, services, migrations, and API) for a wallet system that ensures data integrity and safe concurrent updates.

**Key features**
- Wallet CRUD and balance tracking
- Idempotent deposit, withdraw and transfer operations (via `Idempotency-Key`)
- Atomic transfers using DB transactions and row-level locking
- Full transaction history with before/after balances

**Repository layout (important files)**
- [app](app) — application code
  - [app/Models/Wallet.php](app/Models/Wallet.php)
  - [app/Models/Transaction.php](app/Models/Transaction.php)
  - [app/Services/WalletService.php](app/Services/WalletService.php)
  - [app/Exceptions/InsufficientBalanceException.php](app/Exceptions/InsufficientBalanceException.php)
- [database/migrations](database/migrations) — migrations for wallets & transactions
- [routes/api.php](routes/api.php) — API route definitions
- [phpunit.xml](phpunit.xml) — tests configuration

## Requirements

- PHP 8.0+ (check composer.json for exact constraint)
- Composer
- MySQL / PostgreSQL / SQLite (configured in `.env`)
- Node.js & npm (for frontend assets if used)

## Installation

1. Clone the repo:

```bash
git clone <repo-url> walletService
cd walletService
```

2. Install PHP dependencies:

```bash
composer install
```

3. Copy `.env` and configure database and app settings:

```bash
cp .env.example .env
# then update DB_* and other env vars
php artisan key:generate
```

4. Run migrations and seeders:

```bash
php artisan migrate --seed
```

5. (Optional) Install frontend tools and build assets:

```bash
npm install
npm run build
```

6. Start the application (local):

```bash
php artisan serve
```

## Database

Two primary tables are used:

- `wallets` — stores wallet owner, currency and current balance
- `transactions` — audit records with `wallet_id`, `related_wallet_id` (nullable), `type` (`deposit`, `withdraw`, `transfer_in`, `transfer_out`), `amount`, `balance_before`, `balance_after`, and `idempotency_key`

Important constraints and behavior:
- Unique `(wallet_id, idempotency_key)` to prevent duplicate processing
- Balance updates occur within database transactions and use `SELECT ... FOR UPDATE` (Laravel's `lockForUpdate()`)

## API (overview)

The app exposes RESTful endpoints (see `routes/api.php`). Typical endpoints include:

- `POST /api/v1/wallets` — create wallet
- `GET /api/v1/wallets` — list wallets
- `GET /api/v1/wallets/{id}` — wallet details
- `POST /api/v1/wallets/{id}/deposit` — deposit funds (idempotent)
- `POST /api/v1/wallets/{id}/withdraw` — withdraw funds (idempotent)
- `POST /api/v1/transfers` — transfer between wallets (idempotent)
- `GET /api/v1/wallets/{id}/transactions` — transaction history

Note: endpoints and exact payloads are defined in the controllers under `app/Http/Controllers`.

### Idempotency

All write operations support idempotency via the `Idempotency-Key` HTTP header. Repeating the same request with the same key will not double-process the operation; instead it returns the original result.

## Usage examples

Example deposit via curl (idempotent):

```bash
curl -X POST http://localhost:8000/api/v1/wallets/1/deposit \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: my-unique-key-123" \
  -d '{"amount": "100.00"}'
```

Example transfer:

```bash
curl -X POST http://localhost:8000/api/v1/transfers \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: transfer-key-456" \
  -d '{"from_wallet_id":1, "to_wallet_id":2, "amount":"50.00"}'
```

Example using the `WalletService` in code:

```php
use App\\Services\\WalletService;

$walletService = app(WalletService::class);

// deposit
$walletService->deposit(walletId: 1, amount: 100.00, idempotencyKey: 'key-123');

// withdraw
$walletService->withdraw(walletId: 1, amount: 25.00, idempotencyKey: 'key-456');

// transfer
$walletService->transfer(fromWalletId: 1, toWalletId: 2, amount: 50.00, idempotencyKey: 'key-789');
```

The service throws `App\\Exceptions\\InsufficientBalanceException` when a withdrawal or transfer would overdraw the wallet.

## Tests

Run the test suite with PHPUnit:

```bash
./vendor/bin/phpunit
```

Ensure your testing DB is configured in `.env.testing` or in the `phpunit.xml` configuration.

## Contributing

Contributions are welcome. Typical workflow:

1. Fork the repo
2. Create a feature branch
3. Add tests for new behavior
4. Open a pull request

Please follow PSR coding standards and run tests locally before submitting.

## Troubleshooting

- If migrations fail, check DB connection settings in `.env`.
- If idempotency behaves unexpectedly, ensure the `idempotency_key` column is present and the unique index exists.

## License

This project is provided as-is. Add a license file if you plan to publish or share the code publicly.

---

If you'd like, I can also:
- Generate an OpenAPI / Swagger spec for the API
- Add example Postman collection
- Add CI workflow to run tests and linting
