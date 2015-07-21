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
            $this->brain = new NeuralNet(9,9,NUM_HIDDEN_LAYERS,NEURONS_PER_LAYER);
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
            return array(
                mt_rand(0,2),
                mt_rand(0,2)
            );
        } elseif($this->brain == 'human'){
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
            $output = $this->brain->update($inputs);
            foreach($inputs as $key => $value) {
                if ($value != 0 && isset($output[$key]) && count($output) > 1) {
                    unset($output[$key]);
                }
            }
            $fieldKey = array_keys($output, max($output));
            $fieldKey = $fieldKey[0];
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