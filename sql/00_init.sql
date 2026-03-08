-- ============================================================
-- Online Examination Management System - Master Init Script
-- File: 00_init.sql
-- Description: Runs first (alphabetical order).
--   Sets session configuration and, after all other scripts
--   have executed (01–06), resets demo user passwords to the
--   bcrypt hash of the plain-text value "password" so the
--   application works out-of-the-box for demo/presentation.
--
-- Execution order in docker-entrypoint-initdb.d:
--   00_init.sql  ← this file (session setup only at DB start)
--   01_schema.sql
--   02_triggers.sql
--   03_procedures.sql
--   04_views.sql
--   05_sample_data.sql  ← inserts users with "password" hashes
--   06_complex_queries.sql
--
-- Plain-text password for ALL demo accounts: password
-- ============================================================

-- Session-level character set / collation setup.
-- These settings apply to the connection used by the MySQL
-- docker-entrypoint when it imports this and subsequent files.
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Disable strict mode during bulk import to allow minor type
-- coercions in the sample data without aborting on every warning.
SET sql_mode = 'NO_ENGINE_SUBSTITUTION';
