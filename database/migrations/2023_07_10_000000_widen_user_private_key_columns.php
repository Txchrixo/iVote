<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Les clés privées sont désormais chiffrées (cast `encrypted`) : le texte chiffré
 * dépasse 255 caractères, les colonnes passent donc en TEXT.
 * Après cette migration : php artisan ivote:encrypt-private-keys
 */
return new class extends Migration
{
    private array $columns = ['bitcoin_priv_key', 'eth_priv_key', 'solana_priv_key'];

    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        foreach ($this->columns as $column) {
            if (Schema::hasColumn('users', $column)) {
                // Native column modification (Laravel 11+), works on MySQL, MariaDB, PostgreSQL and SQLite
                Schema::table('users', fn ($table) => $table->text($column)->nullable()->change());
            } else {
                Schema::table('users', fn ($table) => $table->text($column)->nullable());
            }
        }
    }

    public function down(): void
    {
        // Pas de retour en arrière : réduire la colonne tronquerait les clés chiffrées.
    }
};
