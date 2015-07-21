<?php
/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 16.07.2015
 * Time: 14:50
 */

define ('BIAS', -1);
define ('ACTIVATION_RESPONSE', 1);
define ('MAX_PERTURBATION', 0.3);
define ('NUM_COPY_ELITES', 1);
define ('NUM_ELITES', 4);
define ('GENERATIONS', 100);
define ('MUTATION_RATE', 0.1);
define ('CROSS_RATE', 0.7);
define ('NUM_HIDDEN_LAYERS', 3);
define ('NEURONS_PER_LAYER', 9);
define ('BOARD_SIZE', 3);

require_once "classes/neuralNet/NeuralNet.php";
require_once "classes/xando/XandO.php";
require_once "classes/xando/Player.php";
require_once "classes/geneticAlgorithm/GeneticAlgorithm.php";
require_once "classes/helper/helper.php";

ini_set('xdebug.var_display_max_depth', 10);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

date_default_timezone_set('Europe/Berlin');

/** @var Player[][] $players */
$players = array();

$pairs = 100;

for($i = 0; $i < $pairs; $i++){
    $players[] = array(
        0 => new Player('X'),
        1 => new Player('O')
//        1 => new Player('O', true,true)
    );
}
$numWeights = $players[0][0]->getNumOfWeights();

$untrainedGenes = new GeneticAlgorithm(($pairs),MUTATION_RATE,CROSS_RATE,$numWeights);

echo "Generated Random Population as other Player\n";
/** @var Genome[] $untrainedPopulation */
$untrainedPopulation = $untrainedGenes->getChromos();

$untrainedFile = "populations/untrained-".date('Y-m-d H-i-s')."-population ".$pairs."pop ".NUM_HIDDEN_LAYERS."x".NEURONS_PER_LAYER.".txt";
$file = "populations/trained-population ".$pairs."pop ".NUM_HIDDEN_LAYERS."x".NEURONS_PER_LAYER.".txt";

/** @var Genome[] $trainedPopulation */
if(file_exists("populations/trained-population ".$pairs."pop ".NUM_HIDDEN_LAYERS."x".NEURONS_PER_LAYER.".txt")){
    echo "Loaded Population from file '".$file."'\n";
    $genetics = unserialize(file_get_contents($file));
}else {
    $genetics = new GeneticAlgorithm(($pairs),MUTATION_RATE,CROSS_RATE,$numWeights);
    echo "Generated random Population\n";
}
$trainedPopulation = $genetics->getChromos();


$results = trainNet($players,$trainedPopulation,$genetics,$file,$pairs,11.5);



/** @var Player[] $endplayers */
$endplayers = array(
    0 => new Player('X'),
//    1 => new Player('O')
    1 => new Player('O', false,true)
);


$endplayers[0]->putWeights($genetics->getBestGenome()->getWeights());
//$endplayers[1]->putWeights($untrainedGenes->getBestGenome()->getWeights());
$endgame = new XandO(3, $endplayers);

//
$endgame->letPlay(true);
$endgame->drawBoard();

var_dump($results);

/**
 * Trains 2 different Nets/Genetics by letting them play against each other
 *
 * @param Player[][] $players
 * @param Genome[] $trainedPopulation
 * @param Genome[] $untrainedPopulation
 * @param GeneticAlgorithm $genetics
 * @param GeneticAlgorithm $untrainedGenes
 * @param String $file
 * @param String $untrainedFile
 * @param Integer $minAvg
 * @return array
 */
function train2Nets(&$players,&$trainedPopulation,&$untrainedPopulation,&$genetics,&$untrainedGenes,$file,$untrainedFile,$minAvg)
{
    $stats = array();

    foreach($players as $pairId => $playerPair){
        $playerPair[0]->putWeights($trainedPopulation[$pairId]->getWeights());
        $playerPair[1]->putWeights($untrainedPopulation[$pairId]->getWeights());
    }

    $results = array(
        'draw' => 0,
        'X' => 0,
        'O' => 0
    );

    $bestPopulationAvgTrained = $genetics->averageFitness();
    $bestPopulationAvgUntrained = $untrainedGenes->averageFitness();

    for($i = 0; $bestPopulationAvgUntrained < $minAvg && $bestPopulationAvgTrained < $minAvg; $i++){
        foreach($players as $pairId => $playerPair){
            $board = new XandO(BOARD_SIZE, $playerPair);
            $board->letPlay();
            $trainedPopulation[$pairId]->setFitness($players[$pairId][0]->getFitness());
            $untrainedPopulation[$pairId]->setFitness($players[$pairId][1]->getFitness());
//        $board->drawBoard();
            $result = $board->getGameResult();
            $results[$result['winner']]++;
            unset($board);
        }

        $stats[$i]['trained']['averageFitness'] = $genetics->averageFitness();
        $stats[$i]['trained']['bestFitness'] = $genetics->bestFitness();

        if($stats[$i]['trained']['averageFitness'] > $bestPopulationAvgTrained){
            saveGenes($file,$genetics,$bestPopulationAvgTrained,$stats[$i]['trained']['averageFitness']);
        }
        $stats[$i]['untrained']['averageFitness'] = $untrainedGenes->averageFitness();
        $stats[$i]['untrained']['bestFitness'] = $untrainedGenes->bestFitness();

        if($stats[$i]['untrained']['averageFitness'] > $bestPopulationAvgUntrained){
            saveGenes($untrainedFile,$untrainedGenes,$bestPopulationAvgUntrained,$stats[$i]['untrained']['averageFitness']);
        }

        $trainedPopulation = $genetics->epoch($trainedPopulation);
        $untrainedPopulation = $untrainedGenes->epoch($untrainedPopulation);
        shuffle($trainedPopulation);
        shuffle($untrainedPopulation);
        foreach($players as $pairId => $playerPair) {
            $playerPair[0]->putWeights($trainedPopulation[$pairId]->getWeights());
            $playerPair[0]->reset();
            $playerPair[1]->putWeights($untrainedPopulation[$pairId]->getWeights());
            $playerPair[1]->reset();
        }
        echo "||Trained|| Generation: ".$genetics->getGeneration().". Best: ".$stats[$i]['trained']['bestFitness']." Average: ".$stats[$i]['trained']['averageFitness']."\n";
        echo "||Untrained|| Generation: ".$untrainedGenes->getGeneration().". Best: ".$stats[$i]['untrained']['bestFitness']." Average: ".$stats[$i]['untrained']['averageFitness']."\n";
    }
    return $results;
}

/**
 * Trains a single net by letting it play against its own population randomly
 *
 * @param Player[][] $players
 * @param Genome[] $population
 * @param GeneticAlgorithm $genetics
 * @param String $file
 * @param Integer $pairs
 * @param Integer $minAvg
 * @return array
 */
function trainNet(&$players,&$population,&$genetics,$file,$pairs, $minAvg)
{
    $stats = array();

    foreach($players as $pairId => $playerPair){
        $playerPair[0]->putWeights($population[$pairId]->getWeights());
        $playerPair[1]->putWeights($population[$pairId%$pairs]->getWeights());
    }

    $results = array(
        'draw' => 0,
        'X' => 0,
        'O' => 0
    );

    $bestPopulationAvg = $genetics->averageFitness();

    for($i = 0; $bestPopulationAvg < $minAvg; $i++){
        foreach($players as $pairId => $playerPair){
            $board = new XandO(BOARD_SIZE, $playerPair);
            $board->letPlay();
            $population[$pairId]->setFitness($players[$pairId][0]->getFitness());
            $population[$pairId%$pairs]->setFitness($players[$pairId][1]->getFitness());
//        $board->drawBoard();
            $result = $board->getGameResult();
            $results[$result['winner']]++;
            unset($board);
        }

        $stats[$i]['trained']['averageFitness'] = $genetics->averageFitness();
        $stats[$i]['trained']['bestFitness'] = $genetics->bestFitness();

        if($stats[$i]['trained']['averageFitness'] > $bestPopulationAvg){
            saveGenes($file,$genetics,$bestPopulationAvg,$stats[$i]['trained']['averageFitness']);
        }

        $population = $genetics->epoch($population);
        shuffle($population);
        foreach($players as $pairId => $playerPair) {
            $playerPair[0]->putWeights($population[$pairId]->getWeights());
            $playerPair[0]->reset();
            $playerPair[1]->putWeights($population[$pairId%$pairs]->getWeights());
            $playerPair[1]->reset();
        }
        echo "Generation: ".$genetics->getGeneration().". Best: ".$stats[$i]['trained']['bestFitness']." Average: ".$stats[$i]['trained']['averageFitness']."\n";
    }
    return $results;
}

function saveGenes($file,$genetics,&$bestPopulationAvg,$fitness)
{
    file_put_contents($file, serialize($genetics));
    $bestPopulationAvg = $fitness;
    echo "New Best Average!\n";
}