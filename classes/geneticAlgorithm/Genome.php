<?php

/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 16.07.2015
 * Time: 15:13
 */
class Genome
{
    protected $weights = array();
    protected $fitness = 0;

    public function __construct($weights, $fitness)
    {
        $this->weights = $weights;
        $this->fitness = $fitness;
    }

    public function addWeight($weight)
    {
        $this->weights[] = $weight;
    }

    public function getFitness()
    {
        return $this->fitness;
    }

    /**
     * @param Genome $a
     * @param Genome $b
     * @return int
     */
    static function sort($a, $b)
    {
        if ($a->fitness == $b->fitness) {
            return 0;
        }
        return ($a->fitness < $b->fitness) ? -1 : 1;
    }

    public function getWeights()
    {
        return $this->weights;
    }

    public function setFitness($newFitness)
    {
        $this->fitness = $newFitness;
    }

    public function setWeight($index, $value)
    {
        $this->weights[$index] = $value;
    }
}