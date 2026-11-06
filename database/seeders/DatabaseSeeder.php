<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal del proyecto.
 *
 * Laravel ejecuta únicamente este archivo cuando corres
 * `php artisan db:seed`. Desde aquí llamamos a los demás
 * seeders en el orden en que deben correr.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * $this->call() recibe un array de clases seeder
         * y las ejecuta en ese orden, de arriba hacia abajo.
         *
         * El orden es importante cuando hay claves foráneas:
         * primero las tablas independientes (packages),
         * después las que dependen de ellas (reservations).
         */
        $this->call([
            PackageSeeder::class,
        ]);
    }
}
