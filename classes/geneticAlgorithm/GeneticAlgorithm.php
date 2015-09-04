<?php

/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 16.07.2015
 * Time: 15:07
 */
require_once "Genome.php";

class GeneticAlgorithm
{
    /** @var Genome[] $population */
    protected $population = array();
    protected $populationSize;
    protected $mutationRate;
    protected $crossRate;
    protected $chromoLength;
    protected $totalFitness = 0;
    protected $generation = 0;
    protected $fittestGenome = 0;
    protected $bestFitness = 0;
    protected $worstFitness = PHP_INT_MAX;
    protected $averageFitness = 0;

    public function __construct($popSize, $mutRate, $crossRate, $numWeights)
    {
        $this->populationSize = $popSize;
        $this->mutationRate = $mutRate;
        $this->crossRate = $crossRate;
        $this->chromoLength = $numWeights;
        for ($i = 0; $i < $popSize; $i++) {
            $this->population[] = new Genome(array(), 0);

            for ($j = 0; $j < $numWeights; $j++) {
                $this->population[$i]->addWeight(Helper::randomClamped());
            }
        }
    }

    /**
     * mutates a chromosome by perturbing its weights by an amount not greater than MAX_PERTURBATION
     *
     * @param Genome $chromo
     */
    public function mutate(&$chromo)
    {
        //Traverse the chormosome and mutate each weight dependent on the mutation rate
        $weights = $chromo->getWeights();
        foreach ($weights as $i => $weight) {
            //Do we perturb this weight?
            if (Helper::randomFloat() > $this->mutationRate) {
                $weight += (Helper::randomClamped() * MAX_PERTURBATION);
                $chromo->setWeight($i, $weight);
            }
        }
    }

    /**
     * returns a chromo based on roulette wheel sampling
     *
     * @return Genome|null
     */
    public function getChromoRoulette()
    {
        //Generate a random number between 0 & total fitness count
        $slice = (double)((Helper::randomFloat()) * $this->totalFitness);

        //This will be the chosen Chromosome
        $theChosenOne = NULL;

        //Go through the chromosomes adding up the fitness so far
        $fitnessSoFar = 0.0;
        foreach ($this->population as $genome) {
            $fitnessSoFar += $genome->getFitness();

            //if the fitness so far > random number return the chromo at this point
            if ($fitnessSoFar >= $slice) {
                $theChosenOne = $genome;
                break;
            }
        }

        //If the Roullette failed give at least some random genome
        if ($theChosenOne === NULL) {
            $theChosenOne = $this->population[mt_rand(0, count($this->population) - 1)];
        }
        return $theChosenOne;
    }

    /**
     * @param Genome $mum
     * @param Genome $dad
     * @param Genome $baby1
     * @param Genome $baby2
     */
    public function crossover(&$mum, &$dad, &$baby1, &$baby2)
    {
        //Just return parents as offspring dependent on the rate or if parents are the same
        if (Helper::randomFloat() > $this->crossRate || $mum === $dad) {
            $baby1 = $mum;
            $baby2 = $dad;
            return;
        }

        //determine a crossover point
        $crossPoint = mt_rand(0, $this->chromoLength - 1);

        //create the offspring

        $mumWeights = $mum->getWeights();
        $dadWeights = $dad->getWeights();
        for ($i = 0; $i < $crossPoint; $i++) {
            $baby1->setWeight($i, $mumWeights[$i]);
            $baby2->setWeight($i, $dadWeights[$i]);
        }

        for ($i = $crossPoint; $i < count($mumWeights); $i++) {
            $baby1->setWeight($i, $dadWeights[$i]);
            $baby2->setWeight($i, $mumWeights[$i]);
        }
    }

    public function epoch(&$oldPop)
    {
        //assign the given population to the classes population
        $this->population = $oldPop;
        //Reset current generation
        $this->reset();

        //Sort the population (for scaling and elitism)
        usort($oldPop, array("Genome", "sort"));
        //calculate best, worst, average and total fitness
        $this->calculateStats();

        //create a temporary array to store new genomes
        $newPop = array();

        //Now to add a little elitism we shall add in some copies of the fittest genomes. Make sure we add an EVEN number or the roulette wheel sampling will crash
        if (!(NUM_COPY_ELITES * NUM_ELITES % 2)) {
            $this->grabNBest(NUM_ELITES, NUM_COPY_ELITES, $newPop);
        }
        //now we enter the GA loop

        //repeat until a new population is generated
        while (count($newPop) < $this->populationSize) {
            //grab two genomes
            $mum = $this->getChromoRoulette();
            $dad = $this->getChromoRoulette();

            //create some offspring via crossover
            $baby1 = new Genome(array(), 0);
            $baby2 = new Genome(array(), 0);

            $this->crossover($mum, $dad, $baby1, $baby2);
            //now we mutate
            $this->mutate($baby1);
            $this->mutate($baby2);
            //now copy into new population
            $newPop[] = $baby1;
            $newPop[] = $baby2;

        }

        //finnished so assign new population back into class Population
        $this->population = $newPop;
        $this->generation++;
        return $this->population;
    }

    protected function grabNBest($numBest, $numCopies, &$population)
    {
        //add the required amount of copies of the n most fittest to the supplied array
        while ($numBest--) {
            for ($i = 0; $i < $numCopies; $i++) {
                $population[] = $this->population[($this->populationSize - 1) - $numBest];
            }
        }
    }

    /**
     * Updates the best, worst, average and total fitness
     */
    public function calculateStats()
    {
        $this->totalFitness = 0;

        $currentMax = 0;
        $currentMin = PHP_INT_MAX;

        foreach ($this->population as $index => $genome) {
            //update fittest if necessary
            if ($genome->getFitness() > $currentMax) {
                $currentMax = $genome->getFitness();
                $this->fittestGenome = $index;
                $this->bestFitness = $currentMax;
            }

            //update worst if necessary
            if ($genome->getFitness() < $currentMin) {
                $currentMin = $genome->getFitness();
                $this->worstFitness = $currentMin;
            }

            $this->totalFitness += $genome->getFitness();
        }

        $this->averageFitness = $this->totalFitness / $this->populationSize;
    }

    /**
     * resets all the relevant variables ready for a new generation
     */
    public function reset()
    {
        $this->totalFitness = 0;
        $this->bestFitness = 0;
        $this->worstFitness = PHP_INT_MAX;
        $this->averageFitness = 0;
    }

    /**
     * @return Genome[]
     */
    public function getChromos()
    {
        return $this->population;
    }

    public function averageFitness()
    {
        return $this->averageFitness;
    }

    public function bestFitness()
    {
        return $this->bestFitness;
    }

    public function getBestGenome()
    {
        return $this->population[$this->fittestGenome];
    }

    /**
     * @param Genome[] $population
     */
    public function setPopulation(&$population)
    {
        $this->population = $population;
    }

    /**
     * @return int
     */
    public function getGeneration()
    {
        return $this->generation;
    }
}