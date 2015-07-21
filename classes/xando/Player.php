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

    public function __construct($symbol, $random = false, $human = false)
    {
        if($random){
            $this->brain = NULL;
        }elseif($human) {
            $this->brain = 'human';
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

    protected function generatePossibleMoves($currentBoard)
    {
        $possibilities = array();
        $i = 0;
        foreach($currentBoard as $key => $fieldVal){
            if($fieldVal == 0){
                $possibilities[$i] = $currentBoard;
                $possibilities[$i][$key] = 1;
                $i++;
            }
        }
        return $possibilities;
    }

    public function getNumOfWeights()
    {
        if($this->brain != NULL && $this->brain != 'human')
            return $this->brain->getNumberOfWeights();
        return false;
    }

    public function putWeights(&$weights)
    {
        if($this->brain != NULL)
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