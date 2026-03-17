FROM postgis/postgis:16-3.4

# Tool pg_basebackup è già incluso nell'immagine.
# Copiamo solo il custom entrypoint.
COPY entrypoint.sh /usr/local/bin/replica-entrypoint.sh
RUN chmod +x /usr/local/bin/replica-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/replica-entrypoint.sh"]
