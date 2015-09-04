<?php

/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 04.09.15
 * Time: 12:09
 */
class Training
{

    /** @var  Player[][] $this->players */
    protected $players;

    /** @var GeneticAlgorithm[] $this ->genetics */
    protected $genetics;

    /** @var  String[] $this ->saveFiles */
    protected $saveFiles;

    /**
     * @param Player [][] $players
     * @param GeneticAlgorithm[] $genetics
     * @param String[] $saveFiles
     * @throws Exception
     */
    public function __construct(&$players, &$genetics, $saveFiles)
    {
        if (count($genetics) > 2) {
            throw(new Exception('More than 2 genetic algorithms passed to Training class'));
        }
        $this->players = $players;
        $this->genetics = $genetics;
        $this->saveFiles = $saveFiles;
    }

    /**
     * Fetches the population of a genetic algorithm
     *
     * @param int $i
     * @return bool|Genome[]
     */
    protected function getPopulation($i)
    {
        if (!isset($this->genetics[$i])) {
            return false;
        }
        return $this->genetics[$i]->getChromos();
    }

    /**
     * Trains 2 different Nets/Genetics by letting them play against each other
     *
     * @param $minAvg
     * @return array
     */
    public function train2Nets($minAvg)
    {
        if (!isset($this->genetics[1])) {
            $numWeights = $this->players[0][0]->getNumOfWeights();
            $this->genetics[1] = new GeneticAlgorithm((PAIRS), MUTATION_RATE, CROSS_RATE, $numWeights);

            echo "Generated Random Population as other Player\n";

            $this->saveFiles[1] = "populations/untrained-" . date('Y-m-d H-i-s') . "-population " . PAIRS . "pop " . NUM_HIDDEN_LAYERS . "x" . NEURONS_PER_LAYER . ".txt";
        }

        $population1 = $this->getPopulation(0);
        $population2 = $this->getPopulation(1);

        $stats = array();

        foreach ($this->players as $pairId => $playerPair) {
            /** @var Player[] $playerPair */
            $playerPair[0]->putWeights($population1[$pairId]->getWeights());
            $playerPair[1]->putWeights($population2[$pairId]->getWeights());
        }

        $results = array(
            'draw' => 0,
            'X' => 0,
            'O' => 0
        );

        $population1Avg = $this->genetics[0]->averageFitness();
        $population2Avg = $this->genetics[1]->averageFitness();

        for ($i = 0; $population2Avg < $minAvg && $population1Avg < $minAvg; $i++) {
            foreach ($this->players as $pairId => $playerPair) {
                $board = new XandO(BOARD_SIZE, $playerPair);
                $board->letPlay();
                $population1[$pairId]->setFitness($this->players[$pairId][0]->getFitness());
                $population2[$pairId]->setFitness($this->players[$pairId][1]->getFitness());
                $result = $board->getGameResult();
                $results[$result['winner']]++;
                unset($board);
            }

            $stats[$i]['population1']['averageFitness'] = $this->genetics[0]->averageFitness();
            $stats[$i]['population1']['bestFitness'] = $this->genetics[0]->bestFitness();

            if ($stats[$i]['population1']['averageFitness'] > $population1Avg) {
                $this->saveGenes(0, $population1Avg, $stats[$i]['population1']['averageFitness']);
            }
            $stats[$i]['population2']['averageFitness'] = $this->genetics[1]->averageFitness();
            $stats[$i]['population2']['bestFitness'] = $this->genetics[1]->bestFitness();

            if ($stats[$i]['population2']['averageFitness'] > $population2Avg) {
                $this->saveGenes(1, $bestPopulationAvgUntrained, $stats[$i]['population2']['averageFitness']);
            }

            $population1 = $this->genetics[0]->epoch($population1);
            $population2 = $this->genetics[1]->epoch($population2);
            shuffle($population1);
            shuffle($population2);
            foreach ($this->players as $pairId => $playerPair) {
                $playerPair[0]->putWeights($population1[$pairId]->getWeights());
                $playerPair[0]->reset();
                $playerPair[1]->putWeights($population2[$pairId]->getWeights());
                $playerPair[1]->reset();
            }
            echo "||Population 1|| Generation: " . $this->genetics[0]->getGeneration() . ". Best: " . $stats[$i]['population1']['bestFitness'] . " Average: " . $stats[$i]['population1']['averageFitness'] . "\n";
            echo "||Population 2|| Generation: " . $this->genetics[1]->getGeneration() . ". Best: " . $stats[$i]['population2']['bestFitness'] . " Average: " . $stats[$i]['population2']['averageFitness'] . "\n";
        }
        return $results;
    }


    /**
     * Trains a single net by letting it play against its own population randomly
     *
     * @param $pairs
     * @param $minAvg
     * @return array|bool
     */
    function trainNet($pairs, $minAvg)
    {
        $stats = array();

        /** @var Genome[] $population */
        $population = $this->getPopulation(0);

        if (!$population) {
            return false;
        }

        foreach ($this->players as $pairId => $playerPair) {
            /** @var Player[] $playerPair */
            $playerPair[0]->putWeights($population[$pairId]->getWeights());
            $playerPair[1]->putWeights($population[$pairId % $pairs]->getWeights());
        }

        $results = array(
            'draw' => 0,
            'X' => 0,
            'O' => 0
        );

        $bestPopulationAvg = $this->genetics[0]->averageFitness();

        for ($i = 0; $bestPopulationAvg < $minAvg; $i++) {
            foreach ($this->players as $pairId => $playerPair) {
                $board = new XandO(BOARD_SIZE, $playerPair);
                $board->letPlay();
                $population[$pairId]->setFitness($this->players[$pairId][0]->getFitness());
                $population[$pairId % $pairs]->setFitness($this->players[$pairId][1]->getFitness());
                $result = $board->getGameResult();
                $results[$result['winner']]++;
                unset($board);
            }

            $stats[$i]['trained']['averageFitness'] = $this->genetics[0]->averageFitness();
            $stats[$i]['trained']['bestFitness'] = $this->genetics[0]->bestFitness();

            if ($stats[$i]['trained']['averageFitness'] > $bestPopulationAvg) {
                $this->saveGenes(0, $bestPopulationAvg, $stats[$i]['trained']['averageFitness']);
            }

            $population = $this->genetics[0]->epoch($population);
            shuffle($population);
            foreach ($this->players as $pairId => $playerPair) {
                $playerPair[0]->putWeights($population[$pairId]->getWeights());
                $playerPair[0]->reset();
                $playerPair[1]->putWeights($population[$pairId % $pairs]->getWeights());
                $playerPair[1]->reset();
            }
            echo "Generation: " . $this->genetics[0]->getGeneration() . ". Best: " . $stats[$i]['trained']['bestFitness'] . " Average: " . $stats[$i]['trained']['averageFitness'] . "\n";
        }
        return $results;
    }

    /**
     * Trains a single net by playing against a random or perfect algorithm
     *
     * @param float $minAvg
     * @return array
     */
    public function trainVsAlgorithm($minAvg)
    {
        $stats = array();
        /** @var Genome[] $population */
        $population = $this->getPopulation(0);

        foreach ($this->players as $pairId => $playerPair) {
            /** @var Player[] $playerPair */
            $playerPair[0]->putWeights($population[$pairId]->getWeights());
        }

        $results = array(
            'draw' => 0,
            'X' => 0,
            'O' => 0
        );

        $bestPopulationAvg = $this->genetics[0]->averageFitness();

        for ($i = 0; $bestPopulationAvg < $minAvg; $i++) {
            foreach ($this->players as $pairId => $playerPair) {
                $board = new XandO(BOARD_SIZE, $playerPair);
                $board->letPlay();
                $population[$pairId]->setFitness($this->players[$pairId][0]->getFitness());
                $result = $board->getGameResult();
                $results[$result['winner']]++;
                unset($board);
            }

            $stats[$i]['trained']['averageFitness'] = $this->genetics[0]->averageFitness();
            $stats[$i]['trained']['bestFitness'] = $this->genetics[0]->bestFitness();

            if ($stats[$i]['trained']['averageFitness'] > $bestPopulationAvg) {
                $this->saveGenes(0, $bestPopulationAvg, $stats[$i]['trained']['averageFitness']);
            }

            $population = $this->genetics[0]->epoch($population);
            foreach ($this->players as $pairId => $playerPair) {
                $playerPair[0]->putWeights($population[$pairId]->getWeights());
                $playerPair[0]->reset();
                $playerPair[1]->reset();
            }
            echo "Generation: " . $this->genetics[0]->getGeneration() . ". Best: " . $stats[$i]['trained']['bestFitness'] . " Average: " . $stats[$i]['trained']['averageFitness'] . "\n";
        }
        return $results;
    }

    /**
     * Saves the genetic algorithm with the index $i in a file
     *
     * @param int $index
     * @param float $bestPopulationAvg
     * @param float $fitness
     */
    function saveGenes($index, &$bestPopulationAvg, $fitness)
    {
        $file = $this->saveFiles[$index];
        $genetics = $this->genetics[$index];
        $path = dirname($file);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($file, serialize($genetics));
        $bestPopulationAvg = $fitness;
        echo "New Best Average!\n";
    }

} 