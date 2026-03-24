# Upgrade to Laravel 12

The codebase has been updated for **Laravel 12**. All structural changes are in place.

## Composer requirement

Laravel 12 requires **Composer 2.2 or higher**. Your current Composer is 2.1.x.

### Upgrade Composer (required before installing dependencies)

1. **Option A – Reinstall Composer (Windows)**  
   Download and run the latest installer from:  
   https://getcomposer.org/download/

2. **Option B – Self-update (run as Administrator)**  
   In an elevated PowerShell or Command Prompt:
   ```bash
   composer self-update
   ```

3. **Check version**
   ```bash
   composer --version
   ```
   You should see **2.2** or higher.

## Complete the upgrade

After Composer is 2.2+:

```bash
# Remove old vendor and lock file so Laravel 12 can resolve
Remove-Item -Recurse -Force vendor
Remove-Item composer.lock

# Install Laravel 12 dependencies
composer install

# Regenerate autoload and caches
composer dump-autoload
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## What was changed

- **composer.json** – PHP ^8.2, `laravel/framework` ^12.0, `laravel/sanctum` ^3.3, `laravel/tinker` ^2.10, and dev deps (Pint, Collision, PHPUnit 11, etc.).
- **bootstrap/app.php** – Laravel 11/12 style: `Application::configure()` with `withRouting()`, `withMiddleware()`, `withExceptions()`; no Kernel.
- **bootstrap/providers.php** – New file listing `AppServiceProvider`, `AuthServiceProvider`, `EventServiceProvider`.
- **public/index.php** – Uses `$app->handleRequest(Request::capture())` instead of the HTTP Kernel.
- **Removed** – `app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Providers/RouteServiceProvider.php`.
- **AppServiceProvider** – API rate limiting moved here from `RouteServiceProvider`.
- **Middleware** – `auth` and `guest` aliases registered in `bootstrap/app.php`; `Authenticate` redirects to `'/login'`; `RedirectIfAuthenticated` uses `'/home'`.
- **config/app.php** – `RouteServiceProvider` removed from `providers` array.

## PHP version

Laravel 12 supports **PHP 8.2 to 8.5**. Use PHP 8.2+ (e.g. Laragon’s PHP 8.2) when running `composer` and `php artisan`.
