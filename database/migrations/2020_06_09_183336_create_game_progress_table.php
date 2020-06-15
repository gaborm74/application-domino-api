<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameProgressTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('game_progress', function (Blueprint $table) {

			$table->uuid('session_id');
			$table->unsignedTinyInteger('player_id');
			$table->unsignedTinyInteger('step');
			$table->string('hand', 512)->nullable();
			$table->string('table', 512)->nullable();
			$table->string('boneyard', 512)->nullable();

			$table->foreign('session_id')->references('id')->on('game_sessions')->onDelete('cascade');
			$table->primary(['session_id', 'player_id', 'step']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('game_progress');
	}
}
