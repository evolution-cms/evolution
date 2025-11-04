-- Initialize PostgreSQL database for Evolution CMS
-- This script runs automatically on first database creation

-- Enable pgvector extension
CREATE EXTENSION IF NOT EXISTS vector;

-- Set UTF-8 encoding
SET client_encoding = 'UTF8';

-- Optional: Create additional extensions if needed
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Grant permissions
GRANT ALL PRIVILEGES ON DATABASE evolution TO evo;

