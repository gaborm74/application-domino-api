<?php

namespace App\Domino;

class DominoStack
{

	/**
	 * Stack of available tiles
	 *
	 * @var DominoTile[]
	 */
	private $stack = [];

	/**
	 * Initialize the stack
	 */
	public function __construct()
	{
	}

	/**
	 * @return [type]
	 */
	public function initBoneyard()
	{
		$this->initStack();
		shuffle($this->stack);
	}

	/**
	 * Get one tile from the stack
	 *
	 * @return DominoTile
	 */
	public function getFromStack()
	{
		return array_shift($this->stack);
	}

	/**
	 * Add one tile to the stack
	 *
	 * @return DominoTile
	 */
	public function addToStack(DominoTile $tile)
	{
		return array_push($this->stack, $tile);
	}

	/**
	 * Tell if there's any tile left in the stack
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
		return (count($this->stack) == 0);
	}

	/**
	 * Return the HTML string of all tiles in the stack
	 * 
	 * @return string
	 */
	public function getStackHTML()
	{
		$stack = '';
		foreach ($this->stack as $tile) {
			$stack .= $tile->getHexValue('horizontal');
		}
		return $stack;
	}

	public function getStack() {
		return $this->stack;
	}
	/**
	 * Remove a random tile from the stack and return it 
	 * 
	 * @return DominoTile
	 */
	public function getRandomTile()
	{
		$tile = $this->stack[array_rand($this->stack)];
		$this->removeTile($tile);
		return $tile;
	}

	/**
	 * Remove a specific tile from the stack
	 * 
	 * @param DominoTile $tile
	 * 
	 * @return bool
	 */
	public function removeTile(DominoTile $tile)
	{
		$key = array_search($tile, $this->stack);
		if ($key !== false) {
			unset($this->stack[$key]);
			return true;
		}
		return false;
	}

	/**
	 * Fill the stack with the initial 28 tiles
	 */
	private function initStack()
	{
		for ($leftValue = 0; $leftValue <= 6; $leftValue++) {
			for ($rightValue = 0; $rightValue <= $leftValue; $rightValue++) {
				$this->stack[] = new DominoTile($leftValue, $rightValue);
			}
		}
	}
}
