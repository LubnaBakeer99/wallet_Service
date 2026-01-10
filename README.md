# Wallet Service API

## Overview

This is a **RESTful Wallet Service** implemented in Laravel.  
It supports **wallet management**, **deposits**, **withdrawals**, **transfers**, and **transaction history**.  
The service emphasizes **data integrity**, **atomic operations**, and **idempotency**.

---

## Features

1. **Wallets**
   - Create, list, and retrieve wallets
   - Each wallet has an owner, currency, and balance

2. **Deposits & Withdrawals**
   - Idempotent via `Idempotency-Key` header
   - Deposits add funds, withdrawals reduce funds
   - Row-level locking ensures concurrent safety
   - Transactions are recorded

3. **Transfers**
   - Move funds atomically between two wallets
   - Reject self-transfers and insufficient balance
   - Idempotent using `Idempotency-Key`
   - Records two transactions: `transfer_out` and `transfer_in`
   - Related wallet recorded for each transaction

4. **Transaction History**
   - Returns chronological list of wallet transactions
   - Filter by type (`deposit`, `withdraw`, `transfer_in`, `transfer_out`) and date range
   - Paginated
   - Each transaction includes:
     - ID
     - Type
     - Amount
     - Wallet
     - Related wallet (for transfers)
     - Timestamp

---

## Database Design

### Wallets Table
| Column      | Type      | Notes                       |
|------------|----------|----------------------------|
| id         | bigint   | Primary key                |
| owner_name | string   | Owner of the wallet        |
| currency   | string   | Currency code (USD, EUR)   |
| balance    | decimal  | Current balance            |
| created_at | datetime | Laravel timestamps         |
| updated_at | datetime | Laravel timestamps         |

### Transactions Table
| Column             | Type      | Notes                                    |
|-------------------|----------|------------------------------------------|
| id                 | bigint   | Primary key                              |
| wallet_id          | bigint   | FK to wallets.id                         |
| related_wallet_id  | bigint   | FK to wallets.id, nullable (for transfers) |
| type               | string   | deposit, withdraw, transfer_in, transfer_out |
| amount             | decimal  | Transaction amount                       |
| idempotency_key    | string   | Ensures idempotent operations           |
| created_at         | datetime | Laravel timestamps                       |
| updated_at         | datetime | Laravel timestamps                       |

**Constraints**
- Unique: `(wallet_id, idempotency_key)` → prevents duplicate operations
- Wallet balance updated atomically within transactions

---

## API Endpoints

| Method | Endpoint | Description |
|--------|---------|-------------|
| POST   | /api/v1/wallets | Create a wallet |
| GET    | /api/v1/wallets | List wallets (optional filters: owner, currency) |
| GET    | /api/v1/wallets/{id} | Get wallet details |
| POST   | /api/v1/wallets/{id}/deposit | Deposit funds (idempotent) |
| POST   | /api/v1/wallets/{id}/withdraw | Withdraw funds (idempotent, checks balance) |
| POST   | /api/v1/transfers | Transfer funds between wallets (idempotent) |
| GET    | /api/v1/wallets/{id}/transactions | Transaction history (filters: type, date range, pagination) |

---

## Idempotency

- All write operations (deposit, withdraw, transfer) require `Idempotency-Key` header.
- Duplicate requests with the same key will **return the same transaction** and **do not affect wallet balance**.
- Enforced via:
  - Unique constraint in database
  - Service-layer idempotency checks

---

## Concurrency & Data Integrity

- Wallet operations use **row-level locking (`lockForUpdate`)** to prevent race conditions
- All balance changes occur **inside DB transactions**
- Transfers are **atomic**, either both wallets are updated, or none are

---

## Example JSON Response (Transaction History)

```json
{
  "data": [
    {
      "id": 20,
      "type": "transfer_out",
      "amount": "50.00",
      "wallet": { "id": 1 },
      "related_wallet": { "id": 2 },
      "created_at": "2026-01-10T12:00:00Z"
    },
    {
      "id": 21,
      "type": "deposit",
      "amount": "100.00",
      "wallet": { "id": 1 },
      "related_wallet": null,
      "created_at": "2026-01-10T12:05:00Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
