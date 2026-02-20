<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\UserSocials;

return new class extends Migration
{
    private const TABLE_NAME = 'user_socials';
    private const UK_PROVIDER_PROVIDER_ID = 'uk_user_socials_provider_provider_id';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(self::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->enum('provider', array_keys(UserSocials::fGetProviders()));
            $table->string('provider_id');
            $table->timestamps();

            $table->unique(
                ['provider', 'provider_id'],
                self::UK_PROVIDER_PROVIDER_ID
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE " . self::TABLE_NAME . " DROP FOREIGN KEY " . self::TABLE_NAME . "_user_id_foreign;
        ");
        Schema::table(self::TABLE_NAME, function (Blueprint $table) {
            $table->dropUnique(self::UK_PROVIDER_PROVIDER_ID);
        });
        Schema::dropIfExists(self::TABLE_NAME);
    }
};
