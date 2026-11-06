<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder de paquetes turísticos.
 *
 * Inserta dos tipos de datos:
 *  1. Paquetes "ancla": fijos, controlados, siempre iguales.
 *     Sirven para demos, pruebas manuales y capturas de pantalla.
 *  2. Paquetes aleatorios generados por la factory, para tener
 *     volumen suficiente y probar paginación y filtros.
 */
class PackageSeeder extends Seeder
{
    /**
     * Laravel ejecuta este método al correr el seeder.
     */
    public function run(): void
    {
        // ─────────────────────────────────────────────
        // 1. PAQUETES ANCLA (datos controlados)
        // ─────────────────────────────────────────────

        $paquetesFijos = [
            [
                'code' => 'PKG-0001',
                'name' => 'Cartagena Colonial 4 días',
                'summary' => 'Ciudad amurallada, islas del Rosario y atardecer en Café del Mar.',
                'description' => 'Un recorrido por el centro histórico de Cartagena, con visita a las Islas del Rosario, city tour por el Castillo San Felipe y tiempo libre para disfrutar la gastronomía caribeña.',
                'origin' => 'Medellín',
                'destination' => 'Cartagena',
                'days' => 4,
                'nights' => 3,
                'price' => 1250000,
                'capacity' => 40,
                'season' => 'high',
                'allows_installments' => true,
                'min_deposit_percentage' => 20,
                'max_installments' => 12,
                'status' => 'active',
            ],
            [
                'code' => 'PKG-0002',
                'name' => 'Eje Cafetero 5 días',
                'summary' => 'Fincas cafeteras, Valle de Cocora y Parque del Café.',
                'description' => 'Inmersión en la cultura cafetera colombiana: recorrido por fincas productoras, caminata en el Valle de Cocora entre palmas de cera, y día completo en el Parque Nacional del Café.',
                'origin' => 'Medellín',
                'destination' => 'Eje Cafetero',
                'days' => 5,
                'nights' => 4,
                'price' => 980000,
                'capacity' => 30,
                'season' => 'low',
                'allows_installments' => true,
                'min_deposit_percentage' => 15,
                'max_installments' => 18,
                'status' => 'active',
            ],
            [
                'code' => 'PKG-0003',
                'name' => 'San Andrés Todo Incluido 6 días',
                'summary' => 'Mar de siete colores, Johnny Cay y Acuario.',
                'description' => 'Plan todo incluido en San Andrés isla: alojamiento frente al mar, tour a Johnny Cay y Acuario, vuelta a la isla y tiempo libre para buceo o snorkel.',
                'origin' => 'Medellín',
                'destination' => 'San Andrés',
                'days' => 6,
                'nights' => 5,
                'price' => 2450000,
                'capacity' => 25,
                'season' => 'high',
                'allows_installments' => true,
                'min_deposit_percentage' => 25,
                'max_installments' => 12,
                'status' => 'active',
            ],
        ];

        // Recorremos el array e insertamos cada paquete.
        foreach ($paquetesFijos as $datos) {

            // Generamos el slug a partir del nombre.
            $datos['slug'] = Str::slug($datos['name']);

            // Campos comunes a todos los paquetes ancla.
            $datos['includes'] = 'Transporte aéreo ida y regreso, alojamiento, desayunos, traslados y seguro de viaje.';
            $datos['not_includes'] = 'Gastos personales, propinas, almuerzos y cenas no especificados.';

            /**
             * updateOrCreate(buscar_por, valores)
             *
             * Si ya existe un paquete con ese `code`, lo actualiza.
             * Si no existe, lo crea.
             *
             * ¿Por qué esto y no create()?
             * Porque hace el seeder IDEMPOTENTE: puedes ejecutarlo
             * diez veces y el resultado es el mismo. Con create()
             * fallaría al segundo intento por el índice UNIQUE.
             */
            Package::updateOrCreate(
                ['code' => $datos['code']],
                $datos
            );
        }

        // ─────────────────────────────────────────────
        // 2. PAQUETES ALEATORIOS (volumen de prueba)
        // ─────────────────────────────────────────────

        // Genera 15 paquetes variados usando la factory.
        Package::factory()->count(15)->create();

        // Genera 5 específicamente de temporada baja,
        // usando el state que definimos en la factory.
        Package::factory()->lowSeason()->count(5)->create();

        // Mensaje en consola al terminar.
        $this->command->info('Paquetes turísticos creados correctamente.');
    }
}
