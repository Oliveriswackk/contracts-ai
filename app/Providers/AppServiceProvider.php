<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Mappers\MapperRegistry;
use App\Services\Mappers\PrestacionServiciosMapper;
use App\Services\Mappers\AdquisicionBienesMapper;
use App\Services\Mappers\AdquisicionBienesBISMapper;
use App\Services\Mappers\ContractMapper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        MapperRegistry::clear();

        MapperRegistry::register(
            PrestacionServiciosMapper::class,
            fn($text) =>
                str_contains($text, 'SESEA/AD/')
                || str_contains($text, 'Grupo Mae')
                || str_contains($text, 'PRESTACIÓN DE SERVICIOS')
        );

        MapperRegistry::register(
            AdquisicionBienesBISMapper::class,
            fn($text) =>
                preg_match('/SESEA\/LPE\/\d+\/\d+BIS/', $text)
        );

        MapperRegistry::register(
            AdquisicionBienesMapper::class,
            fn($text) =>
                preg_match('/SESEA\/LPE\/\d+\/\d+/', $text)
                && !preg_match('/BIS/', $text)
        );

        MapperRegistry::register(
            ContractMapper::class,
            fn($text) =>
                str_contains($text, 'SESEA/')
        );
    }
}
