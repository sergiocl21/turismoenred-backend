<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla `reservations` (reservas de TurismoEnRed).
     *
     * Es una tabla transaccional: registra un acuerdo comercial
     * entre un usuario y un paquete en un momento determinado.
     * Por eso guarda copia del precio en vez de consultarlo.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {

            // ─────────────────────────────────────────────
            // IDENTIFICACIÓN
            // ─────────────────────────────────────────────

            $table->id();

            // Código visible para el cliente: RES-2026-0001
            // Es el número que aparece en su correo y su factura.
            $table->string('code', 25)->unique();

            // ─────────────────────────────────────────────
            // RELACIONES (claves foráneas)
            // ─────────────────────────────────────────────

            /**
             * foreignId('user_id') crea una columna BIGINT UNSIGNED
             * con ese nombre.
             *
             * constrained() le dice a Laravel: "convierte esto en
             * clave foránea". Por convención deduce la tabla destino
             * quitando el sufijo _id y pluralizando:
             *     user_id    → tabla users
             *     package_id → tabla packages
             *
             * restrictOnDelete() define qué pasa si alguien intenta
             * borrar el registro referenciado: la base de datos LO
             * IMPIDE mientras existan reservas asociadas.
             */
            $table->foreignId('user_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('package_id')
                  ->constrained()
                  ->restrictOnDelete();

            // ─────────────────────────────────────────────
            // DATOS DEL VIAJE
            // ─────────────────────────────────────────────

            // Fecha en que el cliente viaja.
            // date → solo fecha, sin hora.
            $table->date('travel_date');

            // Cuántas personas viajan en esta reserva.
            $table->unsignedTinyInteger('persons')->default(1);

            // ─────────────────────────────────────────────
            // VALORES CONGELADOS
            // ─────────────────────────────────────────────

            /**
             * Precio por persona AL MOMENTO DE RESERVAR.
             *
             * ⚠️ Esta es la columna más importante de la tabla.
             * NO se consulta desde packages.price: se copia aquí
             * cuando se crea la reserva y nunca más cambia.
             *
             * Si mañana sube el precio del paquete, esta reserva
             * conserva lo que el cliente aceptó pagar.
             */
            $table->decimal('unit_price', 12, 2);

            // Total del acuerdo: unit_price × persons.
            // También se guarda calculado, no se recalcula al vuelo.
            $table->decimal('total_price', 12, 2);

            // ─────────────────────────────────────────────
            // ESTADO Y TRAZABILIDAD
            // ─────────────────────────────────────────────

            /**
             * Ciclo de vida de la reserva.
             * El enum restringe los valores a nivel de motor:
             * ningún bug ni inyección puede escribir otra cosa.
             */
            $table->enum('status', [
                'pending',    // creada, esperando el abono
                'confirmed',  // abono recibido, cupo asegurado
                'completed',  // el viaje ya ocurrió
                'cancelled',  // desistida o vencida
            ])->default('pending');

            /**
             * Marcas de tiempo de los cambios de estado.
             *
             * ¿Por qué columnas separadas y no solo updated_at?
             * Porque updated_at cambia con CUALQUIER modificación.
             * Si necesito saber cuándo se confirmó exactamente una
             * reserva —para un reporte, una auditoría o un reclamo—
             * necesito ese dato aislado y que no se sobrescriba.
             */
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Motivo de cancelación, para auditoría y servicio al cliente.
            $table->string('cancellation_reason', 255)->nullable();

            // Observaciones del cliente o del asesor.
            $table->text('notes')->nullable();

            // ─────────────────────────────────────────────
            // CONTROL
            // ─────────────────────────────────────────────

            $table->timestamps();
            $table->softDeletes();

            // ─────────────────────────────────────────────
            // ÍNDICES
            // ─────────────────────────────────────────────

            // "Mis reservas" del cliente, filtradas por estado.
            $table->index(['user_id', 'status']);

            // Cupos ocupados de un paquete en una fecha concreta.
            // Será la consulta clave para controlar disponibilidad.
            $table->index(['package_id', 'travel_date']);

            // Listados del panel administrativo por estado.
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
