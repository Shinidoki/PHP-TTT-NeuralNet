<?php

/**
 * Created by PhpStorm.
 * User: dennis.schnitzmeier
 * Date: 16.07.2015
 * Time: 15:24
 */
class helper
{
    /**
     * returns a random value between -1 and 1
     *
     * @return float
     */
    static public function randomClamped()
    {
        $number = (float)mt_rand() / (float)mt_getrandmax();
        $negative = mt_rand(0, 1);
        if ($negative == 1) {
            $number = $number * -1;
        }
        return $number;
    }

    /**
     * returns a random float between 0 and 1
     *
     * @return float
     */
    static public function randomFloat()
    {
        return (float)mt_rand() / (float)mt_getrandmax();
    }

    /**
     * Sigmoid function
     *
     * @param $netInput
     * @param $response
     * @return float
     */
    static public function sigmoid($netInput, $response)
    {
        return (1 / (1 + exp(-$netInput / $response)));
    }

    static public function step($netInput, $maxValue)
    {
        $inputVal = self::sigmoid($netInput, ACTIVATION_RESPONSE);
        $stepSize = 1 / ($maxValue + 1);
        $step = 0;
        for ($i = 0; $i <= $maxValue; $i++) {
            $step += $stepSize;
            if ($step >= $inputVal) {
                return $i;
            }
        }
        return 2;
    }
}