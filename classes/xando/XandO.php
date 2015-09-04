<?php

/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 16.07.2015
 * Time: 16:46
 */
class XandO
{
    protected $board;
    protected $currentTurn = 0;
    protected $currentPlayer = 0;
    /** @var  Player[] $players */
    protected $players;
    protected $boardSize;
    protected $gameResult;

    public function __construct($boardSize = 3, &$players)
    {
        $this->boardSize = $boardSize;
        $this->players = $players;
        //initialize empty board
        $this->initializeBoard();
    }

    public function drawBoard()
    {
        echo "\n";
        for ($i = 0; $i < $this->boardSize; $i++) {
            echo "-----";
        }
        foreach ($this->board as $row) {
            echo "\n";
            foreach ($row as $field) {
                echo "| " . $field . " |";
            }
            echo "\n";
            for ($i = 0; $i < $this->boardSize; $i++) {
                echo "-----";
            }
        }
        echo "\n";
    }

    public function setField($row, $column, $symbol)
    {
        $this->currentTurn++;
        if ($this->board[$row][$column] == ' ') {
            $this->board[$row][$column] = $symbol;
            if ($this->checkWin($row, $column, $symbol)) {
//                echo "Winner: ".$symbol."\n";
                return array('winner' => $symbol);
            }
            if ($this->checkDraw()) {
//                echo "Draw!\n";
                return array('winner' => 'draw');
            }
            return true;
        }
        return false;
    }


    public function letPlay($echo = false)
    {
        $gameEnd = false;
        while ($gameEnd === false) {
            $player = $this->players[$this->currentPlayer];
            $inputs = $this->getCurrentBoardValues();
            $output = $player->makeTurn($inputs);

            $turnResult = $this->setField($output[0], $output[1], $player->getSymbol());
            //Give 1 Point for each round so he learns that lasting longer gets him nearer to a draw
            $player->incrementFitness(4);

            if ($echo) {
                echo "Player " . $player->getSymbol() . " wants to set " . $output[0] . " | " . $output[1] . "\n";
                $this->drawBoard();
            }

            if (is_array($turnResult)) {
                $this->gameResult = $turnResult;

                if ($turnResult['winner'] != 'draw') {
                    $gameEnd = true;
                    //4 Points for winning
                    $player->incrementFitness(4);
                    if ($echo) {
                        echo "Player " . $player->getSymbol() . " won!\n";
                    }
                } else {
                    $gameEnd = true;
                    //2 Points for draw
                    $player->incrementFitness(2);
                    $this->switchPlayer();
                    $this->players[$this->currentPlayer]->incrementFitness(2);
                    $this->switchPlayer();
                    $this->gameResult = array('winner' => 'draw');
                    if ($echo) {
                        echo "Draw!\n";
                    }
                }
            }
            $this->switchPlayer();
        }
    }

    protected function switchPlayer()
    {
        if ($this->currentPlayer > 0) {
            $this->currentPlayer--;
        } else {
            $this->currentPlayer++;
        }
    }

    protected function checkWin($lastRow, $lastCol, $symbol)
    {
        $n = $this->boardSize;
        //check col
        for ($i = 0; $i < $n; $i++) {
            if ($this->board[$lastRow][$i] != $symbol) {
                break;
            }
            //check if we found n symbols in a row
            if ($i == $n - 1) {
                //win!
                return true;
            }
        }

        //check row
        for ($i = 0; $i < $n; $i++) {
            if ($this->board[$i][$lastCol] != $symbol) {
                break;
            }
            if ($i == $n - 1) {
                return true;
            }
        }

        //check diag
        if ($lastRow == $lastCol) {
            //we're on a diagonal
            for ($i = 0; $i < $n; $i++) {
                if ($this->board[$i][$i] != $symbol)
                    break;
                if ($i == $n - 1) {
                    return true;
                }
            }
        }

        //check anti diag
        for ($i = 0; $i < $n; $i++) {
            if ($this->board[$i][($n - 1) - $i] != $symbol)
                break;
            if ($i == $n - 1) {
                return true;
            }
        }
        return false;
    }

    protected function checkDraw()
    {
        //check draw
        if ($this->currentTurn > pow(BOARD_SIZE, 2)) {
            return true;
        }
        foreach ($this->board as $row) {
            foreach ($row as $symbol) {
                if ($symbol == ' ') {
                    return false;
                }
            }
        }
        return true;
    }

    protected function initializeBoard()
    {
        for ($i = 0; $i < $this->boardSize; $i++) {
            for ($j = 0; $j < $this->boardSize; $j++) {
                $this->board[$i][$j] = ' ';
            }
        }
    }

    public function reset()
    {
        $this->initializeBoard();
        $this->currentTurn = 0;
        $this->currentPlayer = 0;
    }

    public function getBoardSize()
    {
        return $this->boardSize;
    }

    public function getCurrentBoard()
    {
        return $this->board;
    }

    public function getCurrentBoardValues()
    {
        $result = array();
        foreach ($this->board as $row) {
            foreach ($row as $symbol) {
                $result[] = $this->convertSymbol($symbol);
            }
        }
        return $result;
    }

    protected function convertSymbol($symbol)
    {
        switch ($symbol) {
            case ' ':
                return 0;
            case $this->players[$this->currentPlayer]->getSymbol():
                return 1;
            default:
                return -1;
        }
    }

    /**
     * @return mixed
     */
    public function getGameResult()
    {
        return $this->gameResult;
    }

    public function testBoard($board)
    {
        $this->board = $board;
        var_dump($this->checkWin(0, 0, 'O'));
    }

}