<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // 1) CPF/CNPJ
            $table->string('cpf_cnpj')->unique();

            // 2) E-mail
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            // 3) Senha
            $table->string('password');

            // 4) Tipo (common ou merchant)
            $table->enum('type', ['common','merchant'])->default('common');

            // 5) Saldo
            $table->decimal('balance', 15, 2)->default(0);

            $table->rememberToken();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
