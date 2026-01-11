## 📚 Table of Contents

- [📌 Project Goals](#-project-goals)
- [⚙️ Tech Stack](#️-tech-stack)
- [🧱 System Architecture](#-system-architecture)
- [📂 Project Folder Structure](#-project-folder-structure)
- [🗄️ Database Design](#️-database-design)
  - [🪪 Wallets Table](#-wallets-table)
  - [🧾 Transactions Table](#-transactions-table)
- [🔁 Idempotency](#-idempotency)
- [🔐 Data Integrity & Concurrency](#-data-integrity--concurrency)
- [🌐 API Endpoints](#-api-endpoints)
  - [🪪 Wallet Management](#-wallet-management)
  - [💰 Deposits](#-deposits)
  - [💸 Withdrawals](#-withdrawals)
  - [🔄 Transfers](#-transfers)
  - [📜 Transaction History](#-transaction-history)
- [❤️ Health Check](#️-health-check)
- [🧪 Testing](#-testing)
- [🧠 Notes for Interviewers](#-notes-for-interviewers)
- [🚀 Conclusion](#-conclusion)

---

## 📌 Project Goals

- Demonstrate production-ready backend design
- Ensure atomic financial operations
- Prevent duplicate processing using idempotency
- Handle concurrent requests safely
- Provide clear, well-documented APIs

---

## ⚙️ Tech Stack

- PHP 8+
- Laravel
- MySQL / PostgreSQL
- REST API

---
## 📂 Project Folder Structure
my-wallet-service/
├─ app/
│ ├─ Http/
│ │ ├─ Controllers/
│ │ │ ├─ WalletController.php
│ │ │ ├─ TransactionController.php
│ │ │ └─ TransferController.php
│ │ ├─ Middleware/
│ │ │ └─ RequireIdempotencyKey.php
│ │ └─ Requests/
│ │ ├─ DepositRequest.php
│ │ ├─ WithdrawRequest.php
│ │ └─ TransferRequest.php
│ ├─ Models/
│ │ ├─ Wallet.php
│ │ └─ Transaction.php
│ └─ Services/
│ └─ WalletService.php
├─ database/
│ ├─ migrations/
│ │ ├─ create_wallets_table.php
│ │ └─ create_transactions_table.php
│ └─ seeders/
├─ routes/
│ └─ api.php
├─ assets/
│ └─ erd.png
├─ tests/
│ ├─ Feature/
│ └─ Unit/
├─ README.md
├─ composer.json
└─ .env.example

## 🧱 System Architecture
Controller → Service → Model → Database
- Controllers handle HTTP requests
- Services contain business logic
- Models define relationships and scopes
- Database transactions ensure data integrity

---

## 🗄️ Database Design
## 🧱 ER Diagram

![Wallet Service ERD](assets/erd.png)

### 🪪 Wallets Table

| Column | Type | Description |
|------|------|------------|
| id | bigint | Primary key |
| owner_name | string | Wallet owner |
| currency | string | Currency code |
| balance | decimal(15,2) | Current balance |
| created_at | datetime | Timestamp |
| updated_at | datetime | Timestamp |

---

### 🧾 Transactions Table

| Column | Type | Description |
|------|------|------------|
| id | bigint | Primary key |
| wallet_id | bigint | FK → wallets.id |
| related_wallet_id | bigint | FK → wallets.id (nullable) |
| type | string | deposit, withdraw, transfer_in, transfer_out |
| amount | decimal(15,2) | Transaction amount |
| balance_before | decimal(15,2) | Balance before transaction |
| balance_after | decimal(15,2) | Balance after transaction |
| idempotency_key | string | Prevents duplicate processing |
| created_at | datetime | Timestamp |
| updated_at | datetime | Timestamp |

---

## 🔁 Idempotency

All money-changing operations require an `Idempotency-Key` header.

- The client generates a unique key
- Repeated requests with the same key are processed only once
- Prevents duplicate deposits, withdrawals, and transfers
## 🔐 Data Integrity & Concurrency

- All financial operations run inside database transactions
- Wallet rows are locked to prevent race conditions
- Transfers debit and credit wallets atomically
- Wallet balances never go negative
## 🌐 API Endpoints

Below are the main API endpoints. See the controllers in `app/Http/Controllers` for exact request/response shapes and validation rules.

### Wallets

- Create wallet
  - **Endpoint:** `POST /api/wallets`
  - **Description:** Create a new wallet
  - **Request body**:

```json
{
  "owner_name": "John Doe",
  "currency": "USD"
}
```

- List wallets
  - **Endpoint:** `GET /api/wallets`
  - **Query params (optional):** `owner_name`, `currency`, `page`, `per_page`

- Get wallet
  - **Endpoint:** `GET /api/wallets/{id}`

### Deposits

- Deposit funds (idempotent)
  - **Endpoint:** `POST /api/wallets/{id}/deposit`
  - **Headers:** `Idempotency-Key: <unique-key>`
  - **Request body**:

```json
{
  "amount": "100.00"
}
```

### Withdrawals

- Withdraw funds (idempotent)
  - **Endpoint:** `POST /api/wallets/{id}/withdraw`
  - **Headers:** `Idempotency-Key: <unique-key>`
  - **Request body**:

```json
{
  "amount": "50.00"
}
```

### Transfers

- Transfer between wallets (idempotent)
  - **Endpoint:** `POST /api/transfers`
  - **Headers:** `Idempotency-Key: <unique-key>`
  - **Request body**:

```json
{
  "from_wallet_id": 1,
  "to_wallet_id": 2,
  "amount": "25.00"
}
```

Notes:
- Transfers must be between wallets with the same currency.
- Self-transfers are rejected.

### Transaction history

- Get transactions for a wallet
  - **Endpoint:** `GET /api/wallets/{id}/transactions`
  - **Query params (optional):** `type` (deposit, withdraw, transfer_in, transfer_out), `from` (ISO date), `to` (ISO date), `page`, `per_page`

Example transaction response snippet (paginated):

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
    }
  ],
  "links": { /* pagination links */ },
  "meta": { /* pagination meta */ }
}
```

