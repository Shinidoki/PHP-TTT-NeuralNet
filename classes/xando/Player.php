<?php

/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 17.07.2015
 * Time: 14:11
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
        if($random){
            $this->brain = NULL;
        }elseif($human) {
            $this->brain = 'human';
        }elseif($algo) {
            $this->brain = 'algo';
        }else {
            $this->brain = new NeuralNet(9,1,NUM_HIDDEN_LAYERS,NEURONS_PER_LAYER);
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
        if($this->brain == NULL){
            //Generate all possible moves and choose a random one
            $moves = $this->generatePossibleMoves($inputs);
            $fieldKey = array_diff_assoc($moves[mt_rand(0,count($moves)-1)],$inputs);
            $fieldKey = array_search(1,$fieldKey);
            $output = array(
                (int)($fieldKey/BOARD_SIZE),
                $fieldKey%BOARD_SIZE
            );
        } elseif($this->brain == 'human'){
            //This is the input for a human user
            do{
                echo "\nPlease enter the row (0-2): ";
                $row = trim(fgets(STDIN));
                echo "\nPlease enter the column (0-2): ";
                $col = trim(fgets(STDIN));
            }while($row < 0 || $row > 2 ||
                $col < 0 || $col > 2);
            $output = array(
                (int)$row,
                (int)$col
            );

        } elseif($this->brain == 'algo'){
            //Generate all possible moves and choose a random one

            $output = $this->getOptimalMove($inputs);

        } else {
            //Let the Neural net decide which one of the possible moves it thinks is the best
            $moves = $this->generatePossibleMoves($inputs);
            $bestMove = array('key' => 0, 'value' => 0);
            foreach($moves as $key => $move){
                $output = $this->brain->update($move);
                if($output[0] > $bestMove['value']){
                    $bestMove['key'] = $key;
                    $bestMove['value'] = $output[0];
                }
            }
            $fieldKey = array_diff_assoc($moves[$bestMove['key']],$inputs);
            $fieldKey = array_search(1,$fieldKey);

            $output = array(
                (int)($fieldKey/BOARD_SIZE),
                $fieldKey%BOARD_SIZE
            );
        }

        if(count($output) < 2){
            return false;
        }

        return $output;
    }

    protected function generatePossibleMoves($currentBoard, $enemy = false)
    {
        $possibilities = array();
        $i = 0;
        foreach($currentBoard as $key => $fieldVal){
            if($fieldVal == 0){
                $possibilities[$i] = $currentBoard;
                if($enemy){
                    $possibilities[$i][$key] = -1;
                }else {
                    $possibilities[$i][$key] = 1;
                }
                $i++;
            }
        }
        return $possibilities;
    }

    protected function getOptimalMove($currentBoard)
    {
        if($currentBoard[4] == 0){
            $output = array(
                1,1
            );
        }else {
            $bestScore = -PHP_INT_MAX;
            $moves = $this->generatePossibleMoves($currentBoard, false);
            $choices = array();
            foreach($moves as $move){
                $score = $this->minimaxPruning($move,9,true,-PHP_INT_MAX,PHP_INT_MAX);
                if($score > $bestScore){
                    $bestScore = $score;
                    $choices = array($move);
                }elseif($score == $bestScore){
                    $choices[] = $move;
                }
            }
            $chosenMove = $choices[mt_rand(0,count($choices)-1)];
            $fieldKey = array_diff_assoc($chosenMove,$currentBoard);
            $fieldKey = array_search(1,$fieldKey);

            $output = array(
                (int)($fieldKey/BOARD_SIZE),
                $fieldKey%BOARD_SIZE
            );
        }

        return $output;
    }

    protected function minimax($currentBoard, $enemy = false)
    {
        $gameStatus = $this->checkGame($currentBoard,$enemy);

        if($gameStatus !== false){
            return array($gameStatus);
        }
        $bestScore = $enemy ? PHP_INT_MAX : -PHP_INT_MAX;
        $bestMove = array();

        $moves = $this->generatePossibleMoves($currentBoard, $enemy);
        foreach($moves as $move){
            $score = $this->minimax($move,!$enemy);
            if($enemy == false){
                if($score[0] > $bestScore){
                    $bestScore = $score[0];
                    $bestMove = $move;
                }
            }else{
                if($score[0] < $bestScore){
                    $bestScore = $score[0];
                    $bestMove = $move;
                }
            }
        }
        return array($bestScore,$bestMove);
    }

    protected function minimaxPruning($currentBoard, $level, $enemy = false, $alpha, $beta)
    {
        $moves = $this->generatePossibleMoves($currentBoard, $enemy);
        $bestMove = array();
        $gameStatus = $this->checkGame($currentBoard,$enemy);

        if($gameStatus !== false){
            return array($gameStatus);
        }
        if($level == 0){
            return 0;
        }

        foreach($moves as $move){
            if($enemy == false){
                $score = $this->minimaxPruning($move,$level-1,!$enemy,$alpha,$beta);
                if($score[0] > $alpha){
                    $alpha = $score[0];
                    $bestMove = $move;
                }
            }else{
                $score = $this->minimaxPruning($move,$level-1,!$enemy,$alpha,$beta);
                if($score[0] < $beta){
                    $beta = $score[0];
                    $bestMove = $move;
                }
            }
            if($alpha >= $beta){
                break;
            }
        }

        return array($enemy == false ? $alpha : $beta,$bestMove);
    }

    protected function checkGame($currentBoard,$enemy)
    {
        $playSymbol = $enemy ? -1 : 1;
        $combinations = array(
            array(
                0,1,2
            ),
            array(
                3,4,5
            ),
            array(
                6,7,8
            ),
            array(
                0,3,6
            ),
            array(
                1,4,7
            ),
            array(
                2,5,8
            ),
            array(
                0,4,8
            ),
            array(
                2,4,6
            ),
        );
        foreach($combinations as $combination){
            $score = $this->evaluateLine($currentBoard[$combination[0]],$currentBoard[$combination[1]],$currentBoard[$combination[2]],$playSymbol);
            if($score != 0){
                return $score;
            }
        }
        foreach($currentBoard as $boardVal){
            if($boardVal == 0){
                return false;
            }
        }
        return 0;
    }

//    protected function minimax($currentBoard, $level, $enemy = false)
//    {
//        $currentBoardScore = $this->calculateBoardScore($currentBoard,$enemy);
//        if($currentBoardScore >= 100){
//            echo "Someone Won: $enemy = $currentBoardScore\n";
//            $moves = array();
//        }else {
//            $moves = $this->generatePossibleMoves($currentBoard, $enemy);
//        }
//        $bestScore = $enemy ? PHP_INT_MAX : -PHP_INT_MAX;
//        $bestMove = array();
//        if(empty($moves) || $level == 0){
//            $bestScore = $currentBoardScore;
//        }else {
//            foreach($moves as $key => $move){
//                if(!$enemy){
//                    $currentScore = $this->minimax($move,$level-1,true);
//                    if($currentScore['bestScore'] > $bestScore){
//                        $bestScore = $currentScore['bestScore'];
//                        $bestMove = $move;
//                    }
//                }else {
//                    $currentScore = $this->minimax($move,$level-1,false);
//                    if($currentScore['bestScore'] < $bestScore){
//                        $bestScore = $currentScore['bestScore'];
//                        $bestMove = $move;
//                    }
//                }
//            }
//        }
//        return array('bestScore' => $bestScore, 'move' => $bestMove);
//    }

    protected function switchBoard($board){
        $result = array();
        foreach($board as $key => $value){
            if($value != 0){
                $result[$key] = $value*(-1);
            }else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected function calculateBoardScore($board,$player)
    {
        $player = $player ? -1 : 1;
        $score = 0;

        $score += $this->evaluateCorners(array($board[0],$board[2],$board[6],$board[8]),$player);

        // Evaluate score for each of the 8 lines (3 rows, 3 columns, 2 diagonals)
        $score += $this->evaluateLine($board[0],$board[1],$board[2],$player);   //row 0

        $score += $this->evaluateLine($board[3],$board[4],$board[5],$player);   //row 1

        $score += $this->evaluateLine($board[6],$board[7],$board[8],$player);   //row 2

        $score += $this->evaluateLine($board[0],$board[3],$board[6],$player);   //col 0

        $score += $this->evaluateLine($board[1],$board[4],$board[7],$player);   //col 1

        $score += $this->evaluateLine($board[2],$board[5],$board[8],$player);   //col 2

        $score += $this->evaluateLine($board[0],$board[4],$board[8],$player);   //diagonal

        $score += $this->evaluateLine($board[2],$board[4],$board[6],$player);   //antidiagonal
        return $score;
    }

    protected function evaluateCorners($values, $player)
    {
        $score = 0;
        foreach($values as $val){
            if($val == $player){
                $score +=1;
            }
        }
        return $score;
    }

    protected function evaluateLine($x,$y,$z,$player)
    {
        if($x == 1 &&
            $y == 1 &&
            $z == 1)
        {
            return 5;
        }elseif($x == (-1) &&
            $y == (-1) &&
            $z == (-1))
        {
            return -1;
        }else {
            return 0;
        }

        //first Cell
        $score = $x;
        //Second Cell
        if ($y == 1) {
            if ($score == 1) {          //first cell is my symbol
                $score = 10;
            } elseif ($score == (-1)) { //first cell is enemy
                return 0;
            } else {                    //first cell is empty
                $score = 1;
            }
        }elseif($y == -1){
            if ($score == -1) {         //first cell is enemy symbol
                $score = (-10);
            } elseif ($score == 1) {    //first cell is my symbol
                return 0;
            } else {                    //first cell is empty
                $score = (-1);
            }
        }

        //third Cell
        if ($z == 1) {
            if ($score > 0) {           //cell1 and/or cell2 are mine
                $score *= 10;
            } elseif ($score < 0) {     //cell1 and/or cell2 are enemies
                return 0;
            } else {                    //cell1 and cell2 are empty
                $score = 1;
            }
        }elseif($z == -1){
            if ($score < 0) {           //cell1 and/or cell2 are enemy
                $score *= 10;
            } elseif ($score > 1) {     //cell1 and/or cell2 are mine
                return 0;
            } else {                    //cell1 and cell2 are empty
                $score = (-1);
            }
        }
        return $score;
    }

    public function getNumOfWeights()
    {
        if(is_object($this->brain))
            return $this->brain->getNumberOfWeights();
        return false;
    }

    public function putWeights(&$weights)
    {
        if(is_object($this->brain))
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
}