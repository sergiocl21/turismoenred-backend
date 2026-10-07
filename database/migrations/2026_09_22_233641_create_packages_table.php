<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla `packages` (paquetes turísticos de TurismoEnRed).
     *
     * Esta tabla es el catálogo comercial del sistema: cada fila es un
     * producto vendible. Incluye campos de financiación porque el modelo
     * de negocio permite pagar a cuotas y por ahorro programado.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {

            // ─────────────────────────────────────────────
            // IDENTIFICACIÓN
            // ─────────────────────────────────────────────

            // Clave primaria autoincremental (BIGINT UNSIGNED).
            // Laravel la llama `id` por convención.
            $table->id();

            // Código comercial interno: PKG-001, PKG-002...
            // unique() → la BD rechaza duplicados a nivel de motor.
            $table->string('code', 20)->unique();

            // Nombre visible del paquete.
            $table->string('name');



            // URL amigable: "tour-cartagena-5-dias".
            // Se usa en el frontend: /destinos/tour-cartagena-5-dias
            // Mejor para SEO y no expone los IDs internos.
            $table->string('slug')->unique();

            // ─────────────────────────────────────────────
            // CONTENIDO DESCRIPTIVO
            // ─────────────────────────────────────────────

            // Resumen corto para las tarjetas del catálogo.
            $table->string('summary', 500)->nullable();

            // Descripción completa. TEXT permite miles de caracteres.
            $table->text('description');

            // Qué incluye y qué no incluye el paquete.
            $table->text('includes')->nullable();
            $table->text('not_includes')->nullable();

            // ─────────────────────────────────────────────
            // DATOS DEL VIAJE
            // ─────────────────────────────────────────────

            $table->string('origin', 120);
            $table->string('destination', 120);

            // Duración del viaje.
            // unsignedTinyInteger → entero de 0 a 255, ocupa 1 byte.
            // Un viaje nunca dura 300 días, así que no desperdiciamos espacio.
            $table->unsignedTinyInteger('days');
            $table->unsignedTinyInteger('nights');

            // ─────────────────────────────────────────────
            // PRECIO Y DISPONIBILIDAD
            // ─────────────────────────────────────────────

            // Precio por persona.
            // decimal(12,2) → hasta 9.999.999.999,99
            //
            // ⚠️ NUNCA uses float o double para dinero.
            // Los flotantes tienen errores de precisión binaria:
            // 0.1 + 0.2 no da exactamente 0.3 en punto flotante.
            // DECIMAL guarda el valor exacto.
            $table->decimal('price', 12, 2);

            // Cupos totales disponibles del paquete.
            $table->unsignedSmallInteger('capacity');

            // ─────────────────────────────────────────────
            // FINANCIACIÓN (el diferencial de TurismoEnRed)
            // ─────────────────────────────────────────────

            // ¿Este paquete se puede pagar a cuotas?
            $table->boolean('allows_installments')->default(true);

            // Porcentaje mínimo de abono para separar el cupo.
            // Ej: 20.00 significa que debe abonar el 20% del total.
            $table->decimal('min_deposit_percentage', 5, 2)->default(20.00);

            // Número máximo de cuotas permitidas para este paquete.
            $table->unsignedTinyInteger('max_installments')->default(12);

            // ─────────────────────────────────────────────
            // TEMPORADA Y ESTADO
            // ─────────────────────────────────────────────

            // Temporada comercial. Clave para la estrategia de
            // desestacionalización: en temporada baja se pueden
            // ofrecer más cuotas o mejores precios.
            $table->enum('season', ['low', 'mid', 'high'])->default('mid');

            // Imágenes del paquete guardadas como JSON.
            // Ej: ["packages/cartagena-1.jpg", "packages/cartagena-2.jpg"]
            $table->json('images')->nullable();

            // Estado de publicación.
            // enum → la BD solo acepta estos tres valores exactos.
            $table->enum('status', ['draft', 'active', 'inactive'])
                  ->default('draft');

            // ─────────────────────────────────────────────
            // CONTROL
            // ─────────────────────────────────────────────

            // created_at y updated_at. Laravel los mantiene solo.
            $table->timestamps();

            // deleted_at → borrado suave (SoftDeletes).
            // El registro no se elimina físicamente, solo se marca.
            // Crítico aquí: un paquete puede tener reservas históricas
            // que necesitan seguir apuntando a él.
            $table->softDeletes();

            // ─────────────────────────────────────────────
            // ÍNDICES
            // ─────────────────────────────────────────────

            // Un índice acelera las búsquedas por esas columnas.
            // Sin índice, MySQL recorre TODA la tabla fila por fila.
            // Con índice, salta directo a los resultados.
            //
            // Indexamos lo que el usuario va a filtrar en el catálogo.
            $table->index('destination');
            $table->index('status');
            $table->index(['status', 'season']); // índice compuesto
        });
    }

    /**
     * Revierte la migración eliminando la tabla.
     * Se ejecuta con: php artisan migrate:rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
