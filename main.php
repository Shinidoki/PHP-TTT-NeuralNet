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
define ('NUM_COPY_ELITES', 2);
define ('NUM_ELITES', 2);
define ('MUTATION_RATE', 0.2);
define ('CROSS_RATE', 0.7);
define ('NUM_HIDDEN_LAYERS', 3);
define ('NEURONS_PER_LAYER', 9);
define ('BOARD_SIZE', 3);
define ('PAIRS', 20);

require_once "classes/neuralNet/NeuralNet.php";
require_once "classes/xando/XandO.php";
require_once "classes/xando/Player.php";
require_once "classes/xando/Training.php";
require_once "classes/geneticAlgorithm/GeneticAlgorithm.php";
require_once "classes/helper/helper.php";

ini_set('xdebug.var_display_max_depth', 10);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

date_default_timezone_set('Europe/Berlin');

/** @var Player[][] $players */
$players = array();


// Generate Players-Pairs for the specified amount of pairs
for ($i = 0; $i < PAIRS; $i++) {
    $players[] = array(
        0 => new Player('X'), //Genetic Player
        1 => new Player('O', false, false, true) //Perfect Algorithm Player
    );
}


$file = "populations/trained-population " . PAIRS . "pop " . NUM_HIDDEN_LAYERS . "x" . NEURONS_PER_LAYER . ".txt";

if (file_exists("populations/trained-population " . PAIRS . "pop " . NUM_HIDDEN_LAYERS . "x" . NEURONS_PER_LAYER . ".txt")) {
    // Load "brains" for the players from file
    echo "Loaded Population from file '" . $file . "'\n";
    $genetics = unserialize(file_get_contents($file));
} else {
    // Create a new "brains" for our players
    $numWeights = $players[0][0]->getNumOfWeights();
    $genetics = new GeneticAlgorithm((PAIRS), MUTATION_RATE, CROSS_RATE, $numWeights);
    echo "Generated random Population\n";
}
$trainedPopulation = $genetics->getChromos();

$geneticArray = array(&$genetics);

$traning = new Training($players, $geneticArray, array($file));

// Let them train until the average fitness reaches the given value
$results = $traning->trainVsAlgorithm(23);

/** @var Player[] $endplayers */
$endplayers = array(
    0 => new Player('X'),
    1 => new Player('O', false, true, true)
);


$endplayers[0]->putWeights($genetics->getChromoRoulette()->getWeights());
//$endplayers[1]->putWeights($untrainedGenes->getBestGenome()->getWeights());
$endgame = new XandO(3, $endplayers);

$endgame->letPlay(true);
$endgame->drawBoard();


