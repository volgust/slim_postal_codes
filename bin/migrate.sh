#!/bin/sh

# ----------------------------
# Load environment variables from .env
# ----------------------------
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR/.."

if [ ! -f "$PROJECT_ROOT/.env" ]; then
  echo ".env file not found in project root ($PROJECT_ROOT/.env)"
  exit 1
fi

# Export all .env variables
set -a
. "$PROJECT_ROOT/.env"
set +a

# ----------------------------
# Migrations variables
# ----------------------------
: "${DB_HOST:?Need to set DB_HOST in .env}"
: "${DB_USER:?Need to set DB_USER in .env}"
: "${DB_PASS:?Need to set DB_PASS in .env}"
: "${DB_NAME:?Need to set DB_NAME in .env}"

echo "Checking migrations table..."

# Create migrations table if not exists
docker exec -i "$DB_HOST" sh -c "MYSQL_PWD=$DB_PASS mysql -u$DB_USER $DB_NAME" <<'EOF'
CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
EOF

echo "Using DB_HOST=$DB_HOST"

# Loop through migration files
for file in "$PROJECT_ROOT"/database/migrations/*.sql
do
  filename=$(basename "$file")
  echo "Checking $filename..."

  executed=$(docker exec -i "$DB_HOST" \
    env MYSQL_PWD=$DB_PASS \
    mysql -s -N -u"$DB_USER" "$DB_NAME" \
    -e "SELECT COUNT(*) FROM migrations WHERE filename='$filename';")

  echo "Executed value: '$executed'"

  if [ "$executed" -eq 0 ]; then
    echo "Running $filename..."

    docker exec -i "$DB_HOST" sh -c "MYSQL_PWD=$DB_PASS mysql -u$DB_USER $DB_NAME" < "$file"

    if [ $? -eq 0 ]; then
      docker exec -i "$DB_HOST" sh -c \
      "MYSQL_PWD=$DB_PASS mysql -u$DB_USER $DB_NAME -e \"INSERT INTO migrations (filename) VALUES ('$filename');\""

      echo "Saved $filename to migrations table."
    else
      echo "Error running $filename. Stopping."
      exit 1
    fi
  else
    echo "Skipping $filename (already executed)."
  fi
done

echo "All migrations processed."