#!/bin/bash

echo "🚀 Setting up E-commerce Platform Features..."
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ NPM is not installed. Please install Node.js and NPM first."
    exit 1
fi

echo "📦 Installing Composer dependencies..."
composer install

if [ $? -ne 0 ]; then
    echo "❌ Composer install failed!"
    exit 1
fi

echo "✅ Composer dependencies installed"
echo ""

echo "📦 Installing NPM dependencies..."
npm install

if [ $? -ne 0 ]; then
    echo "❌ NPM install failed!"
    exit 1
fi

echo "✅ NPM dependencies installed"
echo ""

echo "🗄️  Running database migrations..."
php artisan migrate

if [ $? -ne 0 ]; then
    echo "⚠️  Migration failed. Make sure your database is configured in .env"
else
    echo "✅ Database migrations completed"
fi

echo ""
echo "🎨 Building frontend assets..."
npm run build

if [ $? -ne 0 ]; then
    echo "❌ Build failed!"
    exit 1
fi

echo "✅ Frontend assets built"
echo ""

echo "✨ Setup complete!"
echo ""
echo "📋 Next steps:"
echo "1. Make sure your .env file is configured"
echo "2. Run 'php artisan serve' to start the development server"
echo "3. Run 'npm run dev' in another terminal for hot reload"
echo ""
echo "📧 Email notifications are configured with Mailtrap"
echo "🔗 Admin Dashboard: http://localhost:8000/admin"
echo "📊 Analytics: http://localhost:8000/admin/analytics"
echo ""
echo "📖 See FEATURES_IMPLEMENTATION.md for detailed documentation"

