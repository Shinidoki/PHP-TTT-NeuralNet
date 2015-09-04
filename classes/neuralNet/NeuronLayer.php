<?php
/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 16.07.2015
 * Time: 13:00
 */
require_once "Neuron.php";

class NeuronLayer
{
    protected $numNeurons;
    /** @var Neuron[] */
    protected $neurons = array();

    public function __construct($numNeurons, $inputsPerNeuron)
    {
        $this->numNeurons = $numNeurons;
        for ($i = 0; $i < $numNeurons; $i++) {
            $this->neurons[] = new Neuron($inputsPerNeuron);
        }
    }

    public function getNumNeurons()
    {
        return $this->numNeurons;
    }

    /**
     * @return Neuron[]
     */
    public function getNeurons()
    {
        return $this->neurons;
    }

    /**
     * @param $index
     * @return Neuron
     */
    public function getNeuron($index)
    {
        return $this->neurons[$index];
    }
}