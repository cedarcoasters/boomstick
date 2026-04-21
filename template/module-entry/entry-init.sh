<?='#!/bin/bash';?>

# Script: entry-init.sh
# Inserts/updates the nginx server block for entry-xxxxx module
# Uses unique markers to avoid overwriting other server blocks

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
NGINX_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")/docker-config/nginx"
TARGET_FILE="${NGINX_DIR}/nginx.conf"
TEMPLATE_FILE="${NGINX_DIR}/template.nginx.conf"

MODULE_NAME="entry-<?=O::$moduleName;?>"
START_MARKER="# START Server Block: ${MODULE_NAME}"
END_MARKER="# END Server Block: ${MODULE_NAME}"

echo "Updating nginx server block for ${MODULE_NAME} in ${TARGET_FILE}..."

# If target file doesn't exist, copy from template
if [ ! -f "$TARGET_FILE" ]; then
    if [ ! -f "$TEMPLATE_FILE" ]; then
        echo "Error: Neither '${TARGET_FILE}' nor '${TEMPLATE_FILE}' exist."
        exit 1
    fi
    echo "nginx.conf not found. Copying from template..."
    cp "$TEMPLATE_FILE" "$TARGET_FILE"
fi

# Check if http block exists
if ! grep -q "^http {" "$TARGET_FILE"; then
    echo "Error: No 'http {' block found in ${TARGET_FILE}"
    exit 1
fi

# Create a temporary file for safe editing
TEMP_FILE=$(mktemp)
trap "rm -f '$TEMP_FILE'" EXIT

# Check if this module's server block already exists
if grep -q "$START_MARKER" "$TARGET_FILE"; then
    echo "Existing server block for ${MODULE_NAME} found. Updating..."

    # Remove existing block for this module, then re-add it
    awk -v start="$START_MARKER" -v end="$END_MARKER" '
        $0 ~ start { skip=1; next }
        $0 ~ end { skip=0; next }
        !skip { print }
    ' "$TARGET_FILE" > "$TEMP_FILE"

    mv "$TEMP_FILE" "$TARGET_FILE"
    TEMP_FILE=$(mktemp)
    trap "rm -f '$TEMP_FILE'" EXIT
fi

# Insert server block before the closing brace of the http block
# Find the last } in the file (closing http block) and insert before it
awk -v module="$MODULE_NAME" '
BEGIN {
    server_block = "\t" "# START Server Block: " module "\n"
    server_block = server_block "\tserver {\n"
    server_block = server_block "\t\tlisten 80;\n"
    server_block = server_block "\t\tserver_name localhost;\n"
    server_block = server_block "\t\troot /var/www/html/module/" module "/public;\n"
    server_block = server_block "\t\tindex index.php;\n"
    server_block = server_block "\n"
    server_block = server_block "\t\trewrite ^(/css/.*)$ $1 last;\n"
    server_block = server_block "\t\trewrite ^(/js/.*)$ $1 last;\n"
    server_block = server_block "\t\trewrite ^(/images/.*)$ $1 last;\n"
    server_block = server_block "\t\trewrite ^(/favicon.ico)$ $1 last;\n"
    server_block = server_block "\t\trewrite ^(/pdf/.*)$ $1 last;\n"
    server_block = server_block "\t\trewrite ^/(.*)$ /index.php?__requested_path=$1&$args last;\n"
    server_block = server_block "\n"
    server_block = server_block "\t\t# PHP handling: Proxy to PHP-FPM\n"
    server_block = server_block "\t\tlocation ~ \\.php$ {\n"
    server_block = server_block "\t\t\tfastcgi_pass php:9000;\n"
    server_block = server_block "\t\t\tfastcgi_index index.php;\n"
    server_block = server_block "\t\t\tfastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n"
    server_block = server_block "\t\t\tinclude fastcgi_params;\n"
    server_block = server_block "\t\t}\n"
    server_block = server_block "\n"
    server_block = server_block "\t\t# Deny access to hidden files\n"
    server_block = server_block "\t\tlocation ~ /\\. {\n"
    server_block = server_block "\t\t\tdeny all;\n"
    server_block = server_block "\t\t}\n"
    server_block = server_block "\t}\n"
    server_block = server_block "\t# END Server Block: " module
}
{
    lines[NR] = $0
    last = NR
}
END {
    for (i = 1; i < last; i++) {
        print lines[i]
    }
    # Insert server block before last line (closing brace of http)
    if (lines[last] == "}") {
        print ""
        printf "%s", server_block
        print ""
        print lines[last]
    } else {
        print lines[last]
    }
}
' "$TARGET_FILE" > "$TEMP_FILE"

# Replace the original file
mv "$TEMP_FILE" "$TARGET_FILE"

echo "Successfully updated the server block for ${MODULE_NAME} in ${TARGET_FILE}"