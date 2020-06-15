<?php

namespace App\Jobs;

use App\Domino\DominoGame;
use App\Models\GameProgress;
use App\Models\GameSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DominoGameJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	private $players;
	private $sessionId;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($players, $sessionId)
	{
		$this->players = $players;
		$this->sessionId = $sessionId;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		GameProgress::where('session_id', $this->sessionId)->delete();
		$gameSession = GameSession::where('id', $this->sessionId)->first();

		// Reset session record
		$gameSession->winner_ids = null;
		$gameSession->game_status = 0;
		$gameSession->save();

		$gameSession->game_status = 3; // starting process
		try {
			$game = new DominoGame();
			$gameSession->game_status = 4; // game created
			$game->startGame($this->players, $this->sessionId);
			$gameSession->game_status = 5; // player dealt
			$game->playTheGame();
			$gameSession->game_status = 6; // game ended
		} catch (\Throwable $th) {
			file_put_contents('/var/tmp/game_result_' . $this->sessionId, json_encode('Game execution stopped with error ' . $th->getMessage() . "\n" . 'File: ' . $th->getFile() . "\n" . 'Line: ' . $th->getLine() . "\n"));
			$gameSession->game_status = 101; // error
		} finally {
			// Save the last set status for debug purposes
			$gameSession->save();
		}
	}
}
