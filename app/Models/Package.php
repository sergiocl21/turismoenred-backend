<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * Modelo Package — Paquetes turísticos de TurismoEnRed.
 *
 * Representa la tabla `packages`. Cada instancia de esta clase
 * equivale a una fila: un paquete vendible del catálogo.
 */
class Package extends Model
{
    /**
     * HasFactory  → permite generar datos de prueba con factories.
     * SoftDeletes → activa el borrado lógico usando la columna deleted_at.
     *
     * `use` dentro de una clase inserta traits: bloques de métodos
     * reutilizables que se "pegan" a la clase. PHP no permite herencia
     * múltiple, los traits resuelven ese límite.
     */
    use HasFactory, SoftDeletes;

    /**
     * Campos que SE PUEDEN asignar masivamente.
     *
     * Asignación masiva es cuando haces Package::create($datos)
     * pasando un array completo en lugar de campo por campo.
     *
     * 🔐 SEGURIDAD: Laravel bloquea por defecto esta operación.
     * Sin $fillable, un atacante podría enviar un campo extra en el
     * formulario (por ejemplo "status": "active") y publicar un paquete
     * sin autorización. Esto se llama Mass Assignment Vulnerability.
     * Solo lo que está en esta lista puede llenarse así.
     */
    protected $fillable = [
        'code',
        'name',
        'slug',
        'summary',
        'description',
        'includes',
        'not_includes',
        'origin',
        'destination',
        'days',
        'nights',
        'price',
        'capacity',
        'allows_installments',
        'min_deposit_percentage',
        'max_installments',
        'season',
        'images',
        'status',
    ];

    /**
     * Conversión automática de tipos al leer y escribir.
     *
     * MySQL devuelve casi todo como texto. Estos casts hacen que
     * PHP reciba el tipo correcto sin que tú conviertas manualmente.
     */
    protected $casts = [
        // En la BD es JSON; en PHP lo recibes como array nativo.
        'images' => 'array',

        // En la BD es TINYINT (0/1); en PHP lo recibes como true/false.
        'allows_installments' => 'boolean',

        // decimal:2 conserva la precisión monetaria exacta.
        'price' => 'decimal:2',
        'min_deposit_percentage' => 'decimal:2',

        'days' => 'integer',
        'nights' => 'integer',
        'capacity' => 'integer',
        'max_installments' => 'integer',
    ];

    // ─────────────────────────────────────────────
    // SCOPES — filtros reutilizables
    // ─────────────────────────────────────────────

    /**
     * Un scope es un filtro con nombre que se encadena en las consultas.
     *
     * Laravel reconoce el prefijo "scope" y te permite llamarlo
     * sin él: Package::active()
     *
     * Ventaja: si mañana "activo" pasa a significar algo más complejo,
     * lo cambias aquí una vez y todo el sistema queda actualizado.
     * Esto es el principio DRY: Don't Repeat Yourself.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Filtra por temporada: Package::season('low')->get()
     */
    public function scopeSeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    /**
     * Filtra solo los paquetes financiables.
     */
    public function scopeFinanceable($query)
    {
        return $query->where('allows_installments', true);
    }

    // ─────────────────────────────────────────────
    // ACCESSORS — atributos calculados
    // ─────────────────────────────────────────────

    /**
     * Calcula el abono mínimo en pesos a partir del porcentaje.
     *
     * Laravel reconoce el patrón get{Nombre}Attribute y lo expone
     * como si fuera una propiedad real: $paquete->minimum_deposit
     *
     * No existe en la base de datos, se calcula al vuelo.
     */
    public function getMinimumDepositAttribute(): float
    {
        return round($this->price * ($this->min_deposit_percentage / 100), 2);
    }

    /**
     * Devuelve la duración legible: "5 días / 4 noches"
     */
    public function getDurationAttribute(): string
    {
        return "{$this->days} días / {$this->nights} noches";
    }

    // ─────────────────────────────────────────────
    // CONFIGURACIÓN DE RUTAS
    // ─────────────────────────────────────────────

    /**
     * Indica a Laravel que use `slug` en lugar de `id` en las URLs.
     *
     * Así las rutas quedan /paquetes/tour-cartagena-5-dias
     * en vez de /paquetes/17
     *
     * 🔐 Beneficio de seguridad: no expone los IDs secuenciales,
     * que revelan cuántos registros tienes y permiten enumerarlos.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
