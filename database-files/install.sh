#!/bin/bash

# Stock Trading Bot - Database Setup Script
# This script installs all migrations, models, factories, and seeders

echo "🚀 Stock Trading Bot - Database Setup"
echo "======================================"
echo ""

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: This doesn't appear to be a Laravel project root directory."
    echo "Please run this script from your Laravel project root."
    exit 1
fi

echo "✓ Laravel project detected"
echo ""

# Get current timestamp for migrations
TIMESTAMP=$(date +"%Y_%m_%d_%H%M%S")

# Calculate sequential timestamps for migrations
TIMESTAMP_1="${TIMESTAMP}"
TIMESTAMP_2=$(date -d "+1 second" +"%Y_%m_%d_%H%M%S" 2>/dev/null || date -v+1S +"%Y_%m_%d_%H%M%S" 2>/dev/null)
TIMESTAMP_3=$(date -d "+2 seconds" +"%Y_%m_%d_%H%M%S" 2>/dev/null || date -v+2S +"%Y_%m_%d_%H%M%S" 2>/dev/null)
TIMESTAMP_4=$(date -d "+3 seconds" +"%Y_%m_%d_%H%M%S" 2>/dev/null || date -v+3S +"%Y_%m_%d_%H%M%S" 2>/dev/null)
TIMESTAMP_5=$(date -d "+4 seconds" +"%Y_%m_%d_%H%M%S" 2>/dev/null || date -v+4S +"%Y_%m_%d_%H%M%S" 2>/dev/null)
TIMESTAMP_6=$(date -d "+5 seconds" +"%Y_%m_%d_%H%M%S" 2>/dev/null || date -v+5S +"%Y_%m_%d_%H%M%S" 2>/dev/null)
TIMESTAMP_7=$(date -d "+6 seconds" +"%Y_%m_%d_%H%M%S" 2>/dev/null || date -v+6S +"%Y_%m_%d_%H%M%S" 2>/dev/null)

echo "📦 Installing files..."
echo ""

# Create temporary directory
TEMP_DIR=$(mktemp -d)
echo "Created temp directory: $TEMP_DIR"

# Copy files from source to temp (assuming files are in current directory)
if [ -d "database-files" ]; then
    cp -r database-files/* "$TEMP_DIR/"
    echo "✓ Files copied to temp directory"
else
    echo "❌ Error: database-files directory not found"
    echo "Please ensure you've extracted all files to a 'database-files' folder"
    exit 1
fi

# Install migrations
echo ""
echo "Installing migrations..."
cp "$TEMP_DIR/migrations/001_create_stocks_table.php" "database/migrations/${TIMESTAMP_1}_create_stocks_table.php"
cp "$TEMP_DIR/migrations/002_create_stock_prices_table.php" "database/migrations/${TIMESTAMP_2}_create_stock_prices_table.php"
cp "$TEMP_DIR/migrations/003_create_model_configurations_table.php" "database/migrations/${TIMESTAMP_3}_create_model_configurations_table.php"
cp "$TEMP_DIR/migrations/004_create_experiments_table.php" "database/migrations/${TIMESTAMP_4}_create_experiments_table.php"
cp "$TEMP_DIR/migrations/005_create_predictions_table.php" "database/migrations/${TIMESTAMP_5}_create_predictions_table.php"
cp "$TEMP_DIR/migrations/006_create_backtest_results_table.php" "database/migrations/${TIMESTAMP_6}_create_backtest_results_table.php"
cp "$TEMP_DIR/migrations/007_create_backtest_trades_table.php" "database/migrations/${TIMESTAMP_7}_create_backtest_trades_table.php"
echo "✓ 7 migrations installed"

# Install models
echo "Installing models..."
cp "$TEMP_DIR/models/"*.php "app/Models/"
echo "✓ 7 models installed"

# Install factories
echo "Installing factories..."
cp "$TEMP_DIR/factories/"*.php "database/factories/"
echo "✓ Factories installed"

# Install seeders
echo "Installing seeders..."
cp "$TEMP_DIR/seeders/"*.php "database/seeders/"
echo "✓ Seeders installed"

# Cleanup
rm -rf "$TEMP_DIR"

echo ""
echo "✅ Installation complete!"
echo ""
echo "Next steps:"
echo "1. Review the migrations in database/migrations/"
echo "2. Run: php artisan migrate"
echo "3. Run: php artisan db:seed"
echo ""
echo "📚 See README.md for more information"
