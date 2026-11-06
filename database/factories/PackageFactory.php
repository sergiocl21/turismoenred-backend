<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory del modelo Package.
 *
 * Genera paquetes turísticos sintéticos pero verosímiles:
 * destinos colombianos reales, precios dentro de rangos del
 * mercado y condiciones de financiación coherentes.
 */
class PackageFactory extends Factory
{
    /**
     * Modelo que esta factory construye.
     * Laravel lo infiere por el nombre, pero declararlo
     * explícitamente hace el código más legible y evita
     * problemas si algún día renombras la clase.
     */
    protected $model = Package::class;

    /**
     * Define los valores por defecto de cada registro generado.
     */
    public function definition(): array
    {
        // ── Destinos reales de Colombia ──────────────────
        // Un array con los destinos que maneja TurismoEnRed.
        // Usamos datos reales en lugar de ciudades aleatorias
        // de Faker porque así las pruebas se parecen al negocio.
        $destinos = [
            'Cartagena', 'Santa Marta', 'San Andrés', 'Medellín',
            'Eje Cafetero', 'Guatapé', 'Villa de Leyva', 'Barichara',
            'Tayrona', 'Amazonas', 'Caño Cristales', 'Nuquí',
        ];

        $origenes = ['Medellín', 'Bogotá', 'Cali', 'Barranquilla', 'Bucaramanga'];

        // ── Duración del viaje ───────────────────────────
        // Generamos los días primero porque las noches
        // dependen de ellos: siempre es una noche menos.
        // numberBetween(min, max) devuelve un entero aleatorio.
        $dias = $this->faker->numberBetween(3, 10);
        $noches = $dias - 1;

        // ── Destino y nombre comercial ───────────────────
        // randomElement() escoge un valor al azar del array.
        $destino = $this->faker->randomElement($destinos);

        // Construimos un nombre como "Tour Cartagena 5 días"
        $nombre = "Tour {$destino} {$dias} días";

        // ── Precio ───────────────────────────────────────
        // randomFloat(decimales, min, max)
        // Rango realista para paquetes nacionales en pesos.
        $precio = $this->faker->randomFloat(2, 450000, 3500000);

        // ── Temporada ────────────────────────────────────
        // randomElement con pesos implícitos: repetimos 'mid'
        // para que aparezca con más frecuencia, igual que
        // ocurre en el mercado real.
        $temporada = $this->faker->randomElement(['low', 'mid', 'mid', 'high']);

        return [
            // Código comercial único: PKG-0001, PKG-0002...
            // unique() garantiza que Faker no repita el valor.
            // str_pad rellena con ceros a la izquierda hasta 4 dígitos.
            'code' => 'PKG-' . str_pad(
                $this->faker->unique()->numberBetween(1, 9999),
                4,
                '0',
                STR_PAD_LEFT
            ),

            'name' => $nombre,

            // Str::slug convierte "Tour Cartagena 5 días"
            // en "tour-cartagena-5-dias".
            // Le añadimos un sufijo aleatorio para garantizar
            // unicidad, ya que dos paquetes podrían coincidir.
            'slug' => Str::slug($nombre) . '-' . $this->faker->unique()->numberBetween(100, 999),

            // Resumen corto para las tarjetas del catálogo.
            'summary' => $this->faker->sentence(12),

            // Descripción larga: 3 párrafos unidos en un texto.
            'description' => $this->faker->paragraphs(3, true),

            'includes' => "Transporte aéreo ida y regreso, alojamiento {$noches} noches, desayunos, traslados aeropuerto-hotel, seguro de viaje.",

            'not_includes' => "Gastos personales, propinas, almuerzos y cenas no especificados, actividades opcionales.",

            'origin' => $this->faker->randomElement($origenes),
            'destination' => $destino,

            'days' => $dias,
            'nights' => $noches,

            'price' => $precio,

            // Cupos disponibles del paquete.
            'capacity' => $this->faker->numberBetween(10, 60),

            // ── Financiación ─────────────────────────────
            // boolean(80) devuelve true el 80% de las veces.
            // La mayoría de paquetes son financiables, que es
            // la propuesta de valor de TurismoEnRed.
            'allows_installments' => $this->faker->boolean(80),

            // Abono mínimo entre 15% y 30% del valor total.
            'min_deposit_percentage' => $this->faker->randomElement([15, 20, 25, 30]),

            // En temporada baja ofrecemos más cuotas como
            // incentivo para llenar esos meses.
            'max_installments' => $temporada === 'low' ? 18 : 12,

            'season' => $temporada,

            // Por ahora sin imágenes reales.
            // Las cargaremos cuando montemos el módulo de archivos.
            'images' => null,

            // La mayoría activos, algunos en borrador.
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'draft']),
        ];
    }

    /**
     * ── ESTADOS (states) ─────────────────────────────────
     *
     * Un "state" es una variación de la plantilla base.
     * Permite generar casos específicos sin duplicar código:
     *
     *   Package::factory()->active()->create();
     *   Package::factory()->lowSeason()->count(5)->create();
     *
     * Internamente fusiona el array que devuelves con el
     * de definition(), sobrescribiendo solo esas claves.
     */

    /** Fuerza el paquete a estado activo. */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /** Paquete de temporada baja con financiación extendida. */
    public function lowSeason(): static
    {
        return $this->state(fn (array $attributes) => [
            'season' => 'low',
            'allows_installments' => true,
            'max_installments' => 18,
        ]);
    }

    /** Paquete que solo acepta pago de contado. */
    public function cashOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'allows_installments' => false,
            'min_deposit_percentage' => 100,
            'max_installments' => 1,
        ]);
    }
}
