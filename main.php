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
define ('NEURONS_PER_LAYER', 27);
define ('BOARD_SIZE', 3);

require_once "classes/neuralNet/NeuralNet.php";
require_once "classes/xando/XandO.php";
require_once "classes/xando/Player.php";
require_once "classes/geneticAlgorithm/GeneticAlgorithm.php";
require_once "classes/helper/helper.php";

ini_set('xdebug.var_display_max_depth', 10);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

/** @var Player[][] $players */
$players = array();

$pairs = 100;

for($i = 0; $i < $pairs; $i++){
    $players[] = array(
        0 => new Player('X'),
//        1 => new Player('O')
        1 => new Player('O', true,false)
    );
}
$numWeights = $players[0][0]->getNumOfWeights();
$genetics = new GeneticAlgorithm(($pairs),MUTATION_RATE,CROSS_RATE,$numWeights);
//$untrainedGenes = new GeneticAlgorithm(($pairs),MUTATION_RATE,CROSS_RATE,$numWeights);

/** @var Genome[] $untrainedPopulation */
//$untrainedPopulation = $untrainedGenes->getChromos();

/** @var Genome[] $trainedPopulation */
$trainedPopulation = $genetics->getChromos();
//$trainedPopulation = unserialize(file_get_contents("populations/trained-population ".$pairs."pop ".NUM_HIDDEN_LAYERS."x".NEURONS_PER_LAYER.".txt"));
//$genetics->epoch($trainedPopulation);

$stats = array();

/** @var XandO[] $boards */
$boards = array();

foreach($players as $pairId => $playerPair){
    $playerPair[0]->putWeights($trainedPopulation[$pairId]->getWeights());
//    $playerPair[1]->putWeights($untrainedPopulation[$pairId]->getWeights());
}

$results = array(
    'draw' => 0,
    'X' => 0,
    'O' => 0
);

$bestPopulationAvgTrained = $genetics->averageFitness();
//$bestPopulationAvgUntrained = $untrainedGenes->averageFitness();
$bestPopulationAvgUntrained = 0;

for($i = 0; $bestPopulationAvgUntrained < 6 && $bestPopulationAvgTrained < 6; $i++){
    $secondPlayer = 0;
    foreach($players as $pairId => $playerPair){
        $board = new XandO(BOARD_SIZE, $playerPair);
        $board->letPlay();
        $trainedPopulation[$pairId]->setFitness($players[$pairId][0]->getFitness());
//        $untrainedPopulation[$pairId]->setFitness($players[$pairId][1]->getFitness());
//        $board->drawBoard();
        $result = $board->getGameResult();
        $results[$result['winner']]++;
        unset($board);
    }

    $stats[$i]['trained']['averageFitness'] = $genetics->averageFitness();
    $stats[$i]['trained']['bestFitness'] = $genetics->bestFitness();
//    $stats[$i]['untrained']['averageFitness'] = $untrainedGenes->averageFitness();
//    $stats[$i]['untrained']['bestFitness'] = $untrainedGenes->bestFitness();

    $trainedPopulation = $genetics->epoch($trainedPopulation);
//    $untrainedPopulation = $untrainedGenes->epoch($untrainedPopulation);
    shuffle($trainedPopulation);
//    shuffle($untrainedPopulation);
    foreach($players as $pairId => $playerPair) {
        $playerPair[0]->putWeights($trainedPopulation[$pairId]->getWeights());
        $playerPair[0]->reset();
//        $playerPair[1]->putWeights($untrainedPopulation[$pairId]->getWeights());
        $playerPair[1]->reset();
    }
    echo "||Trained|| Generation: $i. Best: ".$stats[$i]['trained']['bestFitness']." Average: ".$stats[$i]['trained']['averageFitness']."\n";
//    echo "||Untrained|| Generation: $i. Best: ".$stats[$i]['untrained']['bestFitness']." Average: ".$stats[$i]['untrained']['averageFitness']."\n";
    if($stats[$i]['trained']['averageFitness'] > $bestPopulationAvgTrained){
        file_put_contents('populations/trained-population '.$pairs.'pop '.NUM_HIDDEN_LAYERS.'x'.NEURONS_PER_LAYER.'.txt', serialize($trainedPopulation));
        $bestPopulationAvgTrained = $stats[$i]['trained']['averageFitness'];
        echo "New Best Average!\n";
    }
//    if($stats[$i]['untrained']['averageFitness'] > $bestPopulationAvgUntrained){
//        file_put_contents('untrained-population.txt', serialize($untrainedPopulation));
//        $bestPopulationAvgUntrained = $stats[$i]['untrained']['averageFitness'];
//        echo "New Best Average!\n";
//    }

}



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