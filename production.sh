#!/bin/bash

# TMW Product Manager - Production Build Script
# Creates a clean zip file ready for WordPress upload

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_SLUG="tmw-product-manager"
BUILD_DIR="dist"
ZIP_NAME="${PLUGIN_SLUG}.zip"

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}TMW Product Manager - Production Build${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Clean up previous builds and old zip files
if [ -d "$BUILD_DIR" ]; then
    echo -e "${BLUE}Cleaning up previous builds...${NC}"
    rm -rf "$BUILD_DIR"
fi

# Remove old timestamped zip files
echo -e "${BLUE}Removing old zip files...${NC}"
rm -f ${PLUGIN_SLUG}-*.zip

# Remove existing production zip if it exists
if [ -f "$ZIP_NAME" ]; then
    rm -f "$ZIP_NAME"
fi

# Create build directory
echo -e "${BLUE}Creating build directory...${NC}"
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

# Copy files (exclude dev files)
echo -e "${BLUE}Copying plugin files...${NC}"
rsync -av \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='node_modules' \
    --exclude='dist' \
    --exclude='_ref' \
    --exclude='*.sh' \
    --exclude='.DS_Store' \
    --exclude='*.log' \
    --exclude='.env' \
    ./ "$BUILD_DIR/$PLUGIN_SLUG/"

# Create zip file
echo -e "${BLUE}Creating zip file...${NC}"
cd "$BUILD_DIR"
zip -r "../$ZIP_NAME" "$PLUGIN_SLUG" -q

# Clean up build directory
cd ..
rm -rf "$BUILD_DIR"

# Success message
echo ""
echo -e "${GREEN}================================================${NC}"
echo -e "${GREEN}Build complete!${NC}"
echo -e "${GREEN}================================================${NC}"
echo -e "File: ${GREEN}${ZIP_NAME}${NC}"
echo -e "Location: ${GREEN}$(pwd)/${ZIP_NAME}${NC}"
echo -e "Size: ${GREEN}$(du -h "$ZIP_NAME" | cut -f1)${NC}"
echo ""
echo -e "${BLUE}Ready to upload to WordPress!${NC}"
echo ""
