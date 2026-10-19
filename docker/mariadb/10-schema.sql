-- Schema for the application database. MariaDB runs this once, on first start.
-- A transaction and its categorisation live on one row: POST /transactions
-- writes both in a single insert (see docs/journey/04-persistence.md).
USE transactions;

CREATE TABLE IF NOT EXISTS transactions (
    id                    CHAR(36)       NOT NULL,            -- server-minted UUID
    reference             VARCHAR(64)    NULL,                -- the bank's own id (e.g. TXN00001), optional, unique
    amount                DECIMAL(14,2)  NOT NULL,            -- stored exactly as submitted, sign included
    occurred_at           DATETIME(6)    NOT NULL,            -- the bank's timestamp
    description           VARCHAR(255)   NOT NULL,            -- the text the categoriser reads
    type                  ENUM('debit','credit') NOT NULL,    -- stored as submitted, never reconciled with the sign
    account_number        VARCHAR(34)    NOT NULL,            -- IBAN length is at most 34
    category              VARCHAR(32)    NOT NULL,            -- one of the ten predefined categories
    categorisation_source VARCHAR(64)    NOT NULL,            -- the id of the rule that matched, or 'fallback'
    categorised_at        DATETIME(6)    NOT NULL,
    created_at            DATETIME(6)    NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_transactions_reference (reference),
    KEY ix_transactions_category_created (category, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
