<?php

namespace App\Domino;

use App\Models\GameProgress;
use App\Models\GameSession;

class DominoGame
{

	/**
	 * Players playing the game
	 *
	 * @var DominoPlayer[]
	 */
	private $dominoPlayers = [];

	/**
	 * Tiles on the table
	 *
	 * @var DominoTile[]
	 */
	private $playedTiles = [];

	/**
	 * The key of the player next to play from $dominoPlayers
	 *
	 * @var integer
	 */
	private $activePlayerKey = null;

	/**
	 * Values from the extremities of the played tiles on the table
	 *
	 * @var array
	 */
	private $playableValues = [];

	/**
	 * The remaining stack after dealing
	 *
	 * @var DominoStack
	 */
	private $boneYard = null;

	/**
	 * The UUID for the game
	 * @var string UUID
	 */
	private $gameId = null;

	/**
	 * The step counter for progress saving
	 * 
	 * @var int
	 */
	private $step = 1;

	/**
	 * Stores the status of the game
	 * 
	 * @var bool
	 */
	private $isEnded = false;

	/**
	 * Entry point to start the game
	 *
	 * @param integer $numberOfPlayers
	 *        	Number of players
	 */
	public function startGame(int $numberOfPlayers, string $gameId)
	{
		$this->gameId = $gameId;

		if ($numberOfPlayers < 2 || $numberOfPlayers > 4) {
			throw new \Exception('Number of players must be between 2 and 4 inclusive');
		}
		// Init the stack
		$this->boneYard = new DominoStack();
		$this->boneYard->initBoneyard();

		// Add players to the game
		for ($count = 1; $count <= $numberOfPlayers; $count++) {
			$this->dominoPlayers[] = new DominoPlayer();
		}

		// Deal players
		/* @var DominoTile $startingTile */
		$startingTile = null;
		foreach ($this->dominoPlayers as $playerKey => $player) {

			for ($count = 0; $count < 7; $count++) {
				$tile = $this->boneYard->getFromStack();
				$player->addToHand($tile);

				// Select the biggest double for start
				if ($tile->isDouble() && (!isset($startingTile) || (isset($startingTile) && ($startingTile->getValue('L') < $tile->getValue('L'))))) {
					$startingTile = $tile;
					$this->activePlayerKey = $playerKey;
				}
			}
			// Save each hand after dealing
			$this->saveProgress($playerKey);
		}

		// if no starting double tile can be found, pick a random player's random tile to start
		if (!isset($startingTile)) {
			$this->activePlayerKey = array_rand($this->dominoPlayers);
			$startingTile = $this->dominoPlayers[$this->activePlayerKey]->playStarterTile();
		}

		// Update the playable values witht he new tile value just played
		$this->playableValues['L'] = $startingTile->getValue('L');
		$this->playableValues['R'] = $startingTile->getValue('R');

		// add the first played tile to the table stack
		$this->playedTiles[] = $startingTile;

		// update the player's hand
		$this->dominoPlayers[$this->activePlayerKey]->removeFromHand($startingTile);

		// Save the start of the first round
		$this->saveProgress($this->activePlayerKey);
	}

	/**
	 * Main logic of the game
	 */
	public function playTheGame()
	{
		// if game already ended get out of recursion
		if ($this->isEnded) {
			return;
		}
		// If every player passed and boneyard is empty, get out of recursion
		if ($this->boneYard->isEmpty() && $this->checkPlayersStatus()) {
			$this->isEnded = true;
			$this->saveEndGame(98, $this->getWinners());
			return;
		}

		$this->activePlayerKey = $this->getNextPlayer($this->activePlayerKey);

		// If player does not have any playable tile, pick from boneyard
		while (!$this->dominoPlayers[$this->activePlayerKey]->checkHand($this->playableValues['L'], $this->playableValues['R'])) {
			$newTile = $this->boneYard->getFromStack();
			// If there's no more tile left in the boneyard, player passes
			if (!$newTile) break;
			$this->dominoPlayers[$this->activePlayerKey]->addToHand($newTile);

			// Player picked a tile from the boneyard
			$this->saveProgress($this->activePlayerKey);
		}

		if ($this->dominoPlayers[$this->activePlayerKey]->canPlay()) {
			$nextTile = $this->dominoPlayers[$this->activePlayerKey]->playHand();

			// Position the tile correctly to match the tile on the table
			if ($nextTile->getValue('L') == $this->playableValues['L'] || $nextTile->getValue('R') == $this->playableValues['R']) {
				$nextTile->flipValues();
			}

			// Prepend or append the new tile to the played tiles to match values
			if ($nextTile->getValue('R') == $this->playableValues['L']) {
				array_unshift($this->playedTiles, $nextTile);
				$this->playableValues['L'] = $nextTile->getValue('L');
			} else {
				array_push($this->playedTiles, $nextTile);
				$this->playableValues['R'] = $nextTile->getValue('R');
			}

			// Player played a tile
			$this->saveProgress($this->activePlayerKey);

			if ($this->dominoPlayers[$this->activePlayerKey]->isEmptyHand()) {
				$this->dominoPlayers[$this->activePlayerKey]->setAsWinner();
				// Player won with empty hand
				$this->saveEndGame(99, json_encode([$this->activePlayerKey]));
				$this->isEnded = true;
				return;
			}
			$this->dominoPlayers[$this->activePlayerKey]->setInactive(false);
		} else {
			$this->dominoPlayers[$this->activePlayerKey]->setInactive(true);
			// Player passed
			$this->saveProgress($this->activePlayerKey);
		}

		// Next
		$this->playTheGame();
	}

	/**
	 * Return the next player to play
	 *
	 * @param integer $activePlayerKey
	 * @return integer
	 */
	private function getNextPlayer($activePlayerKey)
	{
		$numberOfPlayers = count($this->dominoPlayers);
		if ($activePlayerKey < $numberOfPlayers - 1) {
			return ($activePlayerKey + 1);
		} else {
			return 0;
		}
	}

	/**
	 * Returns if there are any player left to be able to play
	 * (no tiles in hand to place on table, boneyard is empty...)
	 *
	 * @return boolean
	 */
	private function checkPlayersStatus()
	{
		$allPassed = true;
		foreach ($this->dominoPlayers as $player) {
			$allPassed &= $player->isInactive(); // && $allPassed
		}
		return $allPassed;
	}

	/**
	 * Persist game progress record
	 * 
	 * @return null
	 */
	private function saveProgress($playerKey)
	{
		If (is_null($playerKey)) {
			$playerKey = $this->activePlayerKey;
		}
		$dominoPlayer = new GameProgress();
		$dominoPlayer->player_id = $playerKey;
		$dominoPlayer->session_id = $this->gameId;
		$dominoPlayer->step = $this->step++;
		$dominoPlayer->hand = print_r($this->dominoPlayers[$playerKey]->getHandHTML(), true); //->getHandHTML();
		$dominoPlayer->table = $this->getPlayedTilesHTML();
		$dominoPlayer->boneyard = $this->boneYard->getStackHTML();
		$dominoPlayer->save();
	}

	/**
	 * Update game session record wiht winners
	 * 
	 * @param int $status
	 * @param string $winners
	 * 
	 * @return [type]
	 */
	private function saveEndGame(int $status, string $winners)
	{
		$gameSession = GameSession::where('id', $this->gameId)->first();
		$gameSession->winner_ids = $winners;
		$gameSession->game_status = $status; // game ended
		$gameSession->save();
	}

	/**
	 * Get the winner(s)
	 * 
	 * @return string
	 */
	private function getWinners()
	{
		$winners = [];

		// Get the points in each player's hand
		foreach ($this->dominoPlayers as $playerKey => $player) {
			$result[$playerKey] = $player->getHandPoints();
		}

		// look for the player with the least points in hand
		asort($result, SORT_NUMERIC);
		$prevValue = null;
		foreach ($result as $key => $value) {
			if (count($winners) && ($prevValue != $value)) {
				break;
			}
			if ($prevValue === null || ($prevValue == $value)) {
				$winners[] = $key;
			}
			$prevValue = $value;
		}

		return json_encode($winners);
	}

	/**
	 * Return the UTF8 HTML codes for the tiles on the table
	 * 
	 * @return string 
	 */
	private function getPlayedTilesHTML()
	{
		$tiles = '';
		foreach ($this->playedTiles as $tile) {
			$tiles .= $tile->getHexValue('horizontal');
		}
		return $tiles;
	}
}
