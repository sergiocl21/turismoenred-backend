<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo Reservation — Reservas de TurismoEnRed.
 *
 * Entidad transaccional: registra el acuerdo entre un usuario
 * y un paquete en un momento dado, con los valores congelados
 * de esa operación.
 */
class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ── CONSTANTES DE ESTADO ─────────────────────────
     *
     * ¿Por qué constantes y no escribir 'pending' suelto?
     *
     * Porque 'pendng' mal escrito en un where() no falla:
     * simplemente no devuelve resultados, y el bug pasa
     * desapercibido. Con una constante, un error de tipeo
     * revienta de inmediato y el editor te autocompleta.
     *
     * A las cadenas sueltas repartidas por el código se les
     * llama "magic strings", y son una mala práctica conocida.
     */
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'user_id',
        'package_id',
        'travel_date',
        'persons',
        'unit_price',
        'total_price',
        'status',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        // Convierte la fecha a un objeto Carbon, que permite
        // operar con ella: ->format(), ->diffInDays(), etc.
        'travel_date'  => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',

        'unit_price'   => 'decimal:2',
        'total_price'  => 'decimal:2',
        'persons'      => 'integer',
    ];

    // ─────────────────────────────────────────────
    // RELACIONES
    // ─────────────────────────────────────────────

    /**
     * La reserva PERTENECE A un usuario.
     *
     * belongsTo se usa en el lado que GUARDA la clave foránea.
     * Como reservations tiene la columna user_id, este es el
     * lado "hijo" de la relación.
     *
     * Uso: $reserva->user->name
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La reserva PERTENECE A un paquete.
     * Uso: $reserva->package->name
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    // ─────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Reservas cuya fecha de viaje aún no ha llegado.
     * now() devuelve la fecha y hora actuales.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('travel_date', '>=', now()->toDateString());
    }

    // ─────────────────────────────────────────────
    // ATRIBUTOS CALCULADOS
    // ─────────────────────────────────────────────

    /**
     * Días que faltan para el viaje.
     * Carbon calcula la diferencia por nosotros.
     */
    public function getDaysUntilTripAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->travel_date, false);
    }

    /**
     * ¿Esta reserva todavía se puede cancelar?
     * Regla de negocio: solo si no está ya cancelada o completada.
     */
    public function getIsCancellableAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
        ]);
    }
}
