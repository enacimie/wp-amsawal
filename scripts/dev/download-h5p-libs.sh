#!/bin/bash
# Download H5P libraries from official repository
echo "📦 Descargando librerías H5P desde repositorio oficial..."

H5P_URL="https://api.h5p.org/v2/contents"

# List of required H5P library IDs
declare -A LIBS=(
    ["H5P.DialogCards"]="dialog-cards"
    ["H5P.MultiChoice"]="multiple-choice"
    ["H5P.Blanks"]="fill-in-the-blanks"
    ["H5P.OpenEndedQuestion"]="essay"
    ["H5P.MarkTheWords"]="mark-the-words"
    ["H5P.MemoryGame"]="memory-game"
)

mkdir -p h5p-libraries

for lib_name in "${!LIBS[@]}"; do
    lib_slug="${LIBS[$lib_name]}"
    echo "  📥 $lib_name ($lib_slug)..."
    
    # Get example content from H5P.org
    curl -s "https://api.h5p.org/v2/content-types/$lib_slug" \
        -H "Accept: application/json" \
        > "h5p-libraries/$lib_name-info.json"
    
    echo "    ✓ Info guardada"
done

echo "✅ Descarga completa"
