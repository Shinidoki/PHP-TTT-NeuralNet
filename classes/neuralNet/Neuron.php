<?php

/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 16.07.2015
 * Time: 12:50
 */
class Neuron
{
    protected $numInputs;
    /** @var Float[] */
    protected $weights = array();

    /**
     * @param $numInputs int
     */
    public function __construct($numInputs)
    {
        $this->numInputs = $numInputs;

        for ($i = 0; $i < $numInputs + 1; $i++) {
            $this->weights[] = Helper::randomClamped();
        }
    }

    public function getNumInputs()
    {
        return $this->numInputs;
    }

    /**
     * @param $index
     * @return Float
     */
    public function getWeight($index)
    {
        return $this->weights[$index];
    }

    /**
     * @return Float[]
     */
    public function getWeights()
    {
        return $this->weights;
    }

    public function setWeight($index, $value)
    {
        $this->weights[$index] = $value;
    }
}