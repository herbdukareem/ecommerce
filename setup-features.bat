@echo off
echo.
echo ========================================
echo  E-commerce Platform Features Setup
echo ========================================
echo.

REM Check if composer exists
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Composer is not installed. Please install Composer first.
    pause
    exit /b 1
)

REM Check if npm exists
where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] NPM is not installed. Please install Node.js and NPM first.
    pause
    exit /b 1
)

echo [1/5] Installing Composer dependencies...
call composer install
if %errorlevel% neq 0 (
    echo [ERROR] Composer install failed!
    pause
    exit /b 1
)
echo [SUCCESS] Composer dependencies installed
echo.

echo [2/5] Installing NPM dependencies...
call npm install
if %errorlevel% neq 0 (
    echo [ERROR] NPM install failed!
    pause
    exit /b 1
)
echo [SUCCESS] NPM dependencies installed
echo.

echo [3/5] Running database migrations...
call php artisan migrate
if %errorlevel% neq 0 (
    echo [WARNING] Migration failed. Make sure your database is configured in .env
) else (
    echo [SUCCESS] Database migrations completed
)
echo.

echo [4/5] Building frontend assets...
call npm run build
if %errorlevel% neq 0 (
    echo [ERROR] Build failed!
    pause
    exit /b 1
)
echo [SUCCESS] Frontend assets built
echo.

echo ========================================
echo  Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Make sure your .env file is configured
echo 2. Run 'php artisan serve' to start the development server
echo 3. Run 'npm run dev' in another terminal for hot reload
echo.
echo Email notifications are configured with Mailtrap
echo Admin Dashboard: http://localhost:8000/admin
echo Analytics: http://localhost:8000/admin/analytics
echo.
echo See FEATURES_IMPLEMENTATION.md for detailed documentation
echo.
pause

