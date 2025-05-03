-- database.sql
-- SQL commands to create and manage the database schema

-- Create the 'urls' table if it doesn't exist
CREATE TABLE IF NOT EXISTS urls (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Create the 'url_checks' table if it doesn't exist
CREATE TABLE IF NOT EXISTS url_checks (
    id SERIAL PRIMARY KEY,
    url_id INTEGER REFERENCES urls(id),
    status_code INTEGER,
    h1 TEXT,
    title TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Create index on urls name column for faster lookup
CREATE INDEX IF NOT EXISTS urls_name_idx ON urls (name);

-- Create index on url_checks url_id column for faster foreign key lookups
CREATE INDEX IF NOT EXISTS url_checks_url_id_idx ON url_checks (url_id); 