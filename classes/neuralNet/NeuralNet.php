<?php
/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 16.07.2015
 * Time: 14:04
 */
require_once "NeuronLayer.php";


class NeuralNet{
    protected $numInputs;
    protected $numOutputs;
    protected $numHiddenLayers;
    protected $neuronsPerHiddenLayer;
    /** @var NeuronLayer[]  */
    protected $layers = array();

    public function __construct($numInputs, $numOutputs, $numHiddenLayers, $neuronsPerHidden)
    {
        $this->numInputs = $numInputs;
        $this->numOutputs = $numOutputs;
        $this->numHiddenLayers = $numHiddenLayers;
        $this->neuronsPerHiddenLayer = $neuronsPerHidden;

        if($numHiddenLayers > 0) {
            $this->layers[] = new NeuronLayer($neuronsPerHidden, $numInputs);

            for ($i = 0; $i < $numHiddenLayers; $i++) {
                $this->layers[] = new NeuronLayer($neuronsPerHidden, $neuronsPerHidden);
            }

            $this->layers[] = new NeuronLayer($numOutputs, $neuronsPerHidden);
        } else {
            $this->layers[] = new NeuronLayer($numOutputs, $numInputs);
        }
    }

    /**
     * Returns an array with the weights
     *
     * @return Float[]
     */
    public function getWeights()
    {
        /** @var Float[] $weights */
        $weights = array();

        foreach($this->layers as $layer){
            foreach($layer->getNeurons() as $neuron){
                foreach($neuron->getWeights() as $weight){
                    $weights[] = $weight;
                }
            }
        }

        return $weights;
    }

    /**
     * Replaces the weights of the net with the new values
     *
     * @param $weights
     */
    public function putWeights(&$weights)
    {
        $weightIndex = 0;
        foreach($this->layers as $layer){
            $neurons = $layer->getNeurons();
            foreach($neurons as $neuron){
                $neuronWeights = $neuron->getWeights();
                foreach($neuronWeights as $key => $weight){
                    $neuron->setWeight($key, $weights[$weightIndex]);
                    $weightIndex++;
                }
            }
        }
    }

    /**
     * Returns the total number of weights needed for the net
     *
     * @return int
     */
    public function getNumberOfWeights()
    {
        $weights = 0;
        foreach($this->layers as $layer){
            foreach($layer->getNeurons() as $neuron){
                $weights += count($neuron->getWeights());
            }
        }
        return $weights;
    }

    /**
     * Calculates the output array for an input array
     *
     * @param $inputs
     * @return array
     */
    public function update($inputs)
    {
        $outputs = array();
        if(count($inputs) != $this->numInputs){
            return $outputs;
        }

        foreach($this->layers as $lIndex => $layer){
            if($lIndex > 0){
                $inputs = $outputs;
            }
            $outputs = array();

            foreach($layer->getNeurons() as $nIndex => $neuron){
                $netInput = 0;

                $numInputs = $neuron->getNumInputs();

                foreach($neuron->getWeights() as $wIndex => $weight){
                    if($wIndex < $numInputs -1){
                        $netInput += $weight * $inputs[$wIndex];
                    }
                }

                $netInput += $neuron->getWeight($numInputs-1) * BIAS;
                $outputs[] = Helper::sigmoid($netInput, ACTIVATION_RESPONSE);
//                $outputs[] = Helper::step($netInput, 2);
            }
        }

        return $outputs;
    }
}