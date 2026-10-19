<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Blade::directive('pesos', fn ($monto) => "<?php echo '$' . number_format((float) ($monto), 0, ',', '.'); ?>");
    }
}
