<?php

namespace App\Domino;

class DominoPlayer
{

	/**
	 * Array of DominoTiles in the player's hand
	 *
	 * @var DominoStack
	 */
	private $hand = null;

	/**
	 * Keys matching the playable tiles from $hand
	 *
	 * @var DominoTile[]
	 */
	private $playableTiles = [];

	/**
	 * Does the player have anything in hand to play
	 *
	 * @var boolean
	 */
	private $isInactive = false;

	/**
	 * True if the player is the winner by empty hand
	 *
	 * @var boolean
	 */
	private $isWinner = false;


	/**
	 */
	public function __construct()
	{
		$this->hand = new DominoStack();
	}

	/**
	 * Add a domino tile to the player's hand
	 *
	 * @param DominoTile $tile
	 */
	public function addToHand(DominoTile $tile = null)
	{
		if ($tile) {
			$this->hand->addToStack($tile);
		}
	}

	/**
	 * Remove a played tile from the player's hand
	 *
	 * @param DominoTile $tile
	 */
	public function removeFromHand(DominoTile $tile = null)
	{
		if ($tile) {
			$this->hand->removeTile($tile);
		}
	}

	/**
	 * Check if the player has any tile on hand for the round
	 *
	 * @param integer $playValue1
	 * @param integer $playValue2
	 * @return boolean
	 */
	public function checkHand(int $playValue1, int $playValue2)
	{
		foreach ($this->getHand() as $key => $tile) {
			if ($tile->isPlayable($playValue1, $playValue2)) {
				// save the key of any playable tile
				$this->playableTiles[] = $key;
			}
		}
		return count($this->playableTiles) ? true : false;
	}

	/**
	 * Return a random tile from the playable stack for the round
	 *
	 * @return DominoTile
	 */
	public function playHand()
	{
		// get a random element (key in $hand) from the playable pile
		$tileKeyToPlay = array_rand($this->playableTiles);
		// get the tile object itself to play
		$tileToPlay = $this->getHand()[$this->playableTiles[$tileKeyToPlay]];
		// remove the tile from the hand
		$this->removeFromHand($tileToPlay);
		// reset playable pile
		$this->playableTiles = [];

		return $tileToPlay;
	}

	/**
	 * Start the game with the first random tile if no doubles are in hands
	 *
	 * @return DominoTile
	 */
	public function playStarterTile()
	{
		// get a random element from hand
		return $this->hand->getRandomTile();
	}

	/**
	 * Set's the user status based on ability to play a tile
	 *
	 * @param boolean $gameStatus
	 */
	public function setInactive($gameStatus)
	{
		$this->isInactive = $gameStatus;
	}

	/**
	 * Return the player status of ability to play a tile
	 *
	 * @return boolean
	 */
	public function isInactive()
	{
		return $this->isInactive;
	}

	/**
	 * Tell if the user can play from hand or not
	 *
	 * @return boolean
	 */
	public function canPlay()
	{
		return count($this->playableTiles) ? true : false;
	}

	/**
	 * Return the sum of points in the player's hand
	 *
	 * @return integer
	 */
	public function getHandPoints()
	{
		if ($this->isEmptyHand())
			return -1;

		$points = 0;
		foreach ($this->getHand() as $tile) {
			$points += $tile->getValue('L');
			$points += $tile->getValue('R');
		}
		return $points;
	}

	/**
	 * Return TRUE if the user has no tiles left in hand
	 *
	 * @return boolean
	 */
	public function isEmptyHand()
	{
		return (!count($this->getHand()));
	}

	/**
	 * Set the user as the winner
	 */
	public function setAsWinner()
	{
		$this->isWinner = true;
	}

	/**
	 * Return the winner status
	 *
	 * @return boolean
	 */
	public function isWinner()
	{
		return $this->isWinner;
	}

	/**
	 * Return the user hand
	 *
	 * @return DominoTile[]
	 */
	public function getHand()
	{
		return $this->hand->getStack();
	}

	/**
	 * Return the user hand as a HTML coded UTF8 string
	 *
	 * @return string
	 */
	public function getHandHTML()
	{
		return $this->hand->getStackHTML();
	}
}
