<?php

namespace Game;

class Conway {
    private $options = [];

    private $start = 0;
  
    private $counter = 0;
  
    private $gen_hashes = [];

    /**
     * constructor.
    */
    public function __construct(array $options) {
        $this->setDefaultValue($options);
        $this->start = time();
        $this->matrix = new Matrix($this->options['width'], $this->options['height']);
        $this->matrix->generateCells($this->options['random'], $this->options['rand_max']);

        if (!empty($this->options['template'])) {
            $this->setTemplate($this->options['template']);
        }
    }

    /**
     * Set default vaue.
    */
    private function setDefaultValue(array $options) {
        $defaults = [
        'timeout' => 5000,
        'rand_max' => 5,
        'realtime' => TRUE,
        'max_frame_count' => 0,
        'template' => NULL,
        'keep_alive' => 0,
        'random' => TRUE,
        'width' => exec('tput cols'),
        'height' => exec('tput lines') - 3,
        'cell' => 'O',
        'empty' => ' ',
        ];

        if (isset($options['template']) && !isset($options['random'])) {
            // Disable random when template is set.
            $options['random'] = FALSE;
        }

        $options += $defaults;
        $this->options += $options;
    }

    /**
     * Game start
    */
    public function start() {
        while (TRUE) {
            $this->counter++;
            if ($this->options['realtime']) {
                $this->renderMatrix();
                $this->footer();
                usleep($this->options['timeout']);
                $this->moveCursorBack();
            }
            $this->genrateCell();
            if ($this->options['max_frame_count'] && $this->counter >= $this->options['max_frame_count']) {
                break;
            }
            if (!$this->options['keep_alive'] && $this->compareHashes()) {
                break;
            }
        }

        if (!$this->options['realtime']) {
            // Draw the last frame.
            $this->moveCursorBack();
            $this->renderMatrix();
        }
    }

    /**
     * Set a template
    */
    public function setTemplate($name) {
        $template = $name . '.txt';
        $path = 'temp/' . $template;
        $file = fopen($path, 'r');
        $centerX = (int) floor($this->matrix->getWidth() / 2) / 2;
        $centerY = (int) floor($this->matrix->getHeight() / 2) / 2;
        $x = $centerX;
        $y = $centerY;
        while ($c = fgetc($file)) {
        if ($c == 'O') {
            $this->matrix->cells[$y][$x] = 1;
        }
        if ($c == "\n") {
            $y++;
            $x = $centerX;
        }
        else {
            $x++;
        }
        }
        fclose($file);
    }

    /**
     * Genrate cells on the base of game rules:
    */
    private function genrateCell() {
        $cells = &$this->matrix->cells;
        $kill_cell = $born_cell = [];

        for ($i = 0; $i < $this->matrix->getHeight(); $i++) {
            for ($j = 0; $j < $this->matrix->getWidth(); $j++) {
                $neighbor_count = $this->aliveNeighbors($j, $i);

                if ($cells[$i][$j] && ($neighbor_count < 2 || $neighbor_count > 3)) {
                    $kill_cell[] = [$i, $j];
                }
                if (!$cells[$i][$j] && $neighbor_count === 3) {
                    $born_cell[] = [$i, $j];
                }
            }
        }

        foreach ($kill_cell as $killed) {
            $cells[$killed[0]][$killed[1]] = 0;
        }

        foreach ($born_cell as $new) {
            $cells[$new[0]][$new[1]] = 1;
        }

        if (!$this->options['keep_alive']) {
            $this->saveCells();
        }
    }

    /**
     * comparing the past few hashes.
    */
    private function compareHashes() {
        foreach ($this->gen_hashes as $hash) {
            $found = -1;
            foreach ($this->gen_hashes as $hash2) {
                if ($hash === $hash2) {
                    $found++;
                }
            }
            if ($found >= 3) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * save cells and looping later on.
    */
    private function saveCells() {
        static $count;

        if (!isset($count)) {
            $count = 0;
        }

        $hash = md5(json_encode($this->matrix->cells));
        $this->gen_hashes[$count] = $hash;
        $count++;

        if ($count > 20) {
            $count = 0;
        }
    }

    /**
     * Alive neighbors.
    */
    private function aliveNeighbors($a, $b) {
        $alive_counter = 0;
        for ($i = $b - 1; $i <= $b + 1; $i++) {
            if ($i < 0 || $i >= $this->matrix->getHeight()) {
                continue;
            }
            for ($j = $a - 1; $j <= $a + 1; $j++) {
                if ($j == $a && $i == $b) {
                    continue;
                }
                if ($j < 0 || $j >= $this->matrix->getWidth()) {
                    continue;
                }
                if ($this->matrix->cells[$i][$j]) {
                    $alive_counter += 1;
                }
            }
        }
        return $alive_counter;
    }

    /**
     * Move to (0,0).
    */
    private function moveCursorBack() {
        echo "\033[0;0H";
    }

    /**
     * Render matrix.
    */
    private function renderMatrix() {
        foreach ($this->matrix->cells as $key => $row) {
            $show_row = '';
            foreach ($row as $key => $cell) {
                $show_row .= ($cell ? $this->options['cell'] : $this->options['empty']);
            }
            print $show_row . "\n";
        }
    }

    /**
     * footer
    */
    private function footer() {
        print str_repeat('_', $this->options['width']) . "\n";
        echo "\r";
        echo "\033[K";
        print $this->states() . "\n";
    }

    /**
     * Gets a status string with various attributes.
    */
    private function states() {
        $live_cells = $this->matrix->countLiveCells();
        $elapsed_time = time() - $this->start;
        if ($elapsed_time > 0) {
        $fps = number_format($this->counter / $elapsed_time, 1);
        }
        else {
        $fps = 'Calculating...';
        }
        return " Gen: {$this->counter} | Cells: $live_cells | Elapsed Time: {$elapsed_time}s | FPS: {$fps}";
    }

}
?>