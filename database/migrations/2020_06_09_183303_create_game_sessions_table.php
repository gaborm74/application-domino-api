<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameSessionsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('game_sessions', function (Blueprint $table) {
			$table->uuid('id');
			$table->unsignedTinyInteger('game_status')->default(0);
			$table->string('winner_ids')->nullable();
			$table->unsignedTinyInteger('players')->nullable();
			$table->timestamps();

			$table->primary('id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('game_sessions');
	}
}
