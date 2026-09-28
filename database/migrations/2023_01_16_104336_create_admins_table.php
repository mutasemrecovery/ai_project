<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->nullable();
            $table->string('username', 100);
            $table->string('password', 225);
            $table->boolean('is_super')->default(false);
            $table->timestamps();
        });

        DB::table('admins')->insert(
            [
                'name' => "Admin",
                'username' => "admin",
                'email' => "admin@demo.com",
                'password' => bcrypt('admin'), // password
                'is_super' => true, // all access
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admins');
    }
};
