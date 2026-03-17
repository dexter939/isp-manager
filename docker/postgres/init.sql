-- Enable PostGIS extension
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_topology;
CREATE EXTENSION IF NOT EXISTS fuzzystrmatch;
CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS unaccent;

-- Create Italian unaccent dictionary for toponym normalization
CREATE TEXT SEARCH CONFIGURATION italian_unaccent (COPY = pg_catalog.italian);
ALTER TEXT SEARCH CONFIGURATION italian_unaccent
    ALTER MAPPING FOR hword, hword_part, word
    WITH unaccent, italian_stem;
