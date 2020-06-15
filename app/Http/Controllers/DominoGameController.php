<?php

namespace App\Http\Controllers;

use App\Jobs\DominoGameJob;
use App\Models\GameProgress;
use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DominoGameController extends Controller
{
	/**
	 * Setup a game with the number of players 
	 * 
	 * @param Request $request
	 * 
	 * @return Response The created game session ID
	 */
	public function setup(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'players' => 'required|numeric|between:2,4'
		]);

		if ($validator->fails()) {
			return response(['message' => 'Number of players for a game must be between 2 and 4'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$gameSession = new GameSession();
		$gameSession->id = Str::uuid();
		$gameSession->players = $request->players;
		$gameSession->save();

		if (!$gameSession->exists) {
			return response(['message' => "Couldn't setup game."], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		return response(['game_session_id' => $gameSession->id], Response::HTTP_OK);
	}

	/**
	 * Return the game sessions from the database
	 * 
	 * @param Request $request
	 * 
	 * @return Response All game sessions
	 */
	public function list(Request $request)
	{
		return response(GameSession::all(), Response::HTTP_OK);
	}

	/**
	 * @param Request $request
	 * 
	 * @return Response
	 */
	public function start(Request $request)
	{
		$gameSession = GameSession::where('id', $request->game_session_id)->first();
		if (!$gameSession) {
			return response(['message' => 'No game found with the requested ID'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$sessionId = $request->game_session_id;
		$players = $gameSession->players;

		// Set the game status to 1 == ready
		$gameSession->game_status = 1;
		$gameSession->save();

		// Put the game into the queue for processing, if done it'll update the status to 3 == processed
		DominoGameJob::dispatch($players, $sessionId);

		// Check if the status is still (1 == ready)
		$gameSession->refresh();
		if ($gameSession->game_status < 3) {
			// Set the status to 2 == dispatched
			$gameSession->game_status = 2;
			$gameSession->save();
		}

		return response(['message' => 'Game ' . $sessionId . 'is in progress...'], Response::HTTP_OK);
	}

	/**
	 * @param Request $request
	 * 
	 * @return Response
	 */
	public function status(Request $request)
	{
		$gameSession = GameSession::where('id', $request->game_session_id)->first();
		if (!$gameSession) {
			return response(['message' => 'No game found with the requested ID'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		return response(['message' => 'The game status is ' . $gameSession->game_status], Response::HTTP_OK);
	}

	/**
	 * @param Request $request
	 * 
	 * @return Response
	 */
	public function result(Request $request)
	{
		$gameSession = GameSession::where('id', $request->game_session_id)->first();
		if (!$gameSession) {
			return response(['message' => 'No game found with the requested ID'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$gameResult = [];

		$gameResult['winner'] = $gameSession->winner_ids;
		$progress = GameProgress::where('session_id', $request->game_session_id)
			->orderBy('step', 'asc')
			->get();

		foreach ($progress as $gameStep) {
			$gameResult['steps'][$gameStep->step]  = [
				'player' => $gameStep->player_id,
				'hand' => $gameStep->hand,
				'table' => $gameStep->table,
				'boneyard' => $gameStep->boneyard
			];
		}

		return response(['result' => $gameResult], Response::HTTP_OK);
	}
}
