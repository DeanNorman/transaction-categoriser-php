-- A second database for the integration tests, with the same table, so the
-- suite never touches the application's rows. Created once, on first start,
-- alongside the application database. The schema is copied with LIKE so
-- there is exactly one definition of the table (10-schema.sql).
CREATE DATABASE IF NOT EXISTS transactions_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS transactions_test.transactions LIKE transactions.transactions;
GRANT ALL PRIVILEGES ON transactions_test.* TO 'app'@'%';
FLUSH PRIVILEGES;
