<?php

namespace Game;

class Matrix {
    private $width;

    private $height;

    public $cells = [];

    /**
     * Constructor.
    */
    public function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Fills the matrix with cells.
    */
    public function generateCells($randomize, $rand_max = 10) {
        for ($i = 0; $i < $this->width; $i++) {
            for ($j = 0; $j < $this->height; $j++) {
                if ($randomize) {
                    $this->cells[$j][$i] = $this->getRandomState($rand_max);
                }
                else {
                    $this->cells[$j][$i] = 0;
                }
            }
        }
        return $this;
    }

    /**
     * all the live cells.
    */
    public function countLiveCells() {
        $count = 0;
        foreach ($this->cells as $y => $row) {
            foreach ($row as $x => $cell) {
                if ($cell) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Get the grid width.
    */
    public function getWidth() {
        return $this->width;
    }

    /**
     * Get the grid height.
    */
    public function getHeight() {
        return $this->height;
    }

    /**
     * Get a random state for a cell.
    */
    private function getRandomState($rand_max = 1) {
        return rand(0, $rand_max) === 0;
    }
}
?>