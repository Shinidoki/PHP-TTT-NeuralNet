<?php

/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 17.07.2015
 * Time: 14:11
 */

/** Max turn calculation for the minimax algorithm */
define('MAX_DEPTH', 5);

/**
 * Class Player
 */
class Player
{
    /** @var  NeuralNet $brain */
    protected $brain;
    protected $fitness = 0;
    protected $symbol;
    protected $illegalMoves = 0;
    protected $movesMade = 0;


    public function __construct($symbol, $random = false, $human = false, $algo = false)
    {
        if ($random) {
            $this->brain = NULL;
        } elseif ($human) {
            $this->brain = 'human';
        } elseif ($algo) {
            $this->brain = 'algo';
        } else {
            $this->brain = new NeuralNet(9, 1, NUM_HIDDEN_LAYERS, NEURONS_PER_LAYER);
        }

        $this->symbol = $symbol;
    }

    public function reset()
    {
        $this->fitness = 0;
        $this->movesMade = 0;
        $this->illegalMoves = 0;
    }

    public function makeTurn($inputs)
    {
        $this->movesMade++;
        if ($this->brain == NULL) {
            //Generate all possible moves and choose a random one
            $moves = $this->generatePossibleMoves($inputs);
            $fieldKey = array_diff_assoc($moves[mt_rand(0, count($moves) - 1)], $inputs);
            $fieldKey = array_search(1, $fieldKey);
            $output = array(
                (int)($fieldKey / BOARD_SIZE),
                $fieldKey % BOARD_SIZE
            );
        } elseif ($this->brain == 'human') {
            //This is the input for a human user
            do {
                echo "\nPlease enter the row (0-2): ";
                $row = trim(fgets(STDIN));
                echo "\nPlease enter the column (0-2): ";
                $col = trim(fgets(STDIN));
            } while ($row < 0 || $row > 2 ||
                $col < 0 || $col > 2);
            $output = array(
                (int)$row,
                (int)$col
            );

        } elseif ($this->brain == 'algo') {
            //Generate all possible moves and choose a random one

            $output = $this->getOptimalMove($inputs);

        } else {
            //Let the Neural net decide which one of the possible moves it thinks is the best
            $moves = $this->generatePossibleMoves($inputs);
            $bestMove = array('key' => 0, 'value' => 0);
            foreach ($moves as $key => $move) {
                $output = $this->brain->update($move);
                if ($output[0] > $bestMove['value']) {
                    $bestMove['key'] = $key;
                    $bestMove['value'] = $output[0];
                }
            }
            $fieldKey = array_diff_assoc($moves[$bestMove['key']], $inputs);
            $fieldKey = array_search(1, $fieldKey);

            $output = array(
                (int)($fieldKey / BOARD_SIZE),
                $fieldKey % BOARD_SIZE
            );
        }

        if (count($output) < 2) {
            return false;
        }

        return $output;
    }

    protected function generatePossibleMoves($currentBoard, $enemy = false)
    {
        $possibilities = array();
        $i = 0;
        foreach ($currentBoard as $key => $fieldVal) {
            if ($fieldVal == 0) {
                $possibilities[$i] = $currentBoard;
                if ($enemy) {
                    $possibilities[$i][$key] = -1;
                } else {
                    $possibilities[$i][$key] = 1;
                }
                $i++;
            }
        }
        return $possibilities;
    }

    protected function getOptimalMove($currentBoard)
    {
        if ($currentBoard[4] == 0) {
            $output = array(
                1, 1
            );
        } else {
            $bestScore = -PHP_INT_MAX;
            $moves = $this->generatePossibleMoves($currentBoard, false);
            $chosenMove = array();
            foreach ($moves as $move) {
                $score = $this->miniMax($move, 0, true);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $chosenMove = $move;
                }
            }
            $fieldKey = array_diff_assoc($chosenMove, $currentBoard);
            $fieldKey = array_search(1, $fieldKey);

            $output = array(
                (int)($fieldKey / BOARD_SIZE),
                $fieldKey % BOARD_SIZE
            );
        }

        return $output;
    }

    protected function miniMax($currentBoard, $depth, $enemy = false)
    {
        $gameStatus = $this->checkGame($currentBoard, $enemy, $depth);

        if ($gameStatus !== false) {
            return $gameStatus;
        }

        $depth++;

        if ($depth >= MAX_DEPTH) {
            return 0;
        }

        $scores = array();

        $moves = $this->generatePossibleMoves($currentBoard, $enemy);

        foreach ($moves as $move) {
            $scores[] = $this->miniMax($move, $depth, !$enemy);
        }

        if ($enemy) {
            return min($scores);
        } else {
            return max($scores);
        }
    }

    protected function checkGame($currentBoard, $enemy, $depth)
    {
        $combinations = array(
            array(
                0, 1, 2 //Row1
            ),
            array(
                3, 4, 5 //Row2
            ),
            array(
                6, 7, 8 //Row3
            ),
            array(
                0, 3, 6 //Col1
            ),
            array(
                1, 4, 7 //Col2
            ),
            array(
                2, 5, 8 //Col3
            ),
            array(
                0, 4, 8 //Diag
            ),
            array(
                2, 4, 6 //Anti-Diag
            ),
        );
        foreach ($combinations as $combination) {
            $score = $this->evaluateLine($currentBoard[$combination[0]], $currentBoard[$combination[1]], $currentBoard[$combination[2]], $depth);
            if ($score != 0) {
                return $score;
            }
        }
        foreach ($currentBoard as $boardVal) {
            if ($boardVal == 0) {
                return false;
            }
        }
        return 0;
    }

    protected function evaluateLine($x, $y, $z, $depth)
    {
        if ($x == 1 &&
            $y == 1 &&
            $z == 1
        ) {
            return 10 - $depth;
        } elseif ($x == (-1) &&
            $y == (-1) &&
            $z == (-1)
        ) {
            return $depth - 10;
        } else {
            return 0;
        }
    }

    public function getNumOfWeights()
    {
        if (is_object($this->brain))
            return $this->brain->getNumberOfWeights();
        return false;
    }

    public function putWeights(&$weights)
    {
        if (is_object($this->brain))
            $this->brain->putWeights($weights);
    }

    public function getFitness()
    {
        return $this->fitness;
    }

    public function incrementFitness($i)
    {
        $this->fitness += $i;
    }

    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @return int
     */
    public function getIllegalMoves()
    {
        return $this->illegalMoves;
    }

    /**
     * @return int
     */
    public function getMovesMade()
    {
        return $this->movesMade;
    }

    public function addIllegalMove()
    {
        $this->illegalMoves++;
    }

    public function getSymbolValue()
    {
        return $this->symbol == 'X' ? -1 : 1;
    }
}