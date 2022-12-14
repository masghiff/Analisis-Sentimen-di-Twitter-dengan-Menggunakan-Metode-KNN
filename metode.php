<?php

use Phpml\Math\Distance;

class Dice implements Distance
{
    /**
     * @var float
     */
    private $lambda; // lambda atau alpha bisa di set atau default 0.5

    public function __construct(float $lambda = 0.5)
    {
        $this->lambda = $lambda;
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return float
     */
    public function distance(array $a, array $b): float
    {
        // rumus dice ada di slide
        $count = count($a);
        $numerator = 0.0;
        $denom_wkq = 0.0;
        $denom_wkj = 0.0;
        $denom = 0.0;

        for ($i = 0; $i < $count; ++$i) {
            $numerator += $a[$i] * $b[$i];
            $denom_wkq += $a[$i] ** 2;
            $denom_wkj += $b[$i] ** 2;
        }

        $denom = ($this->lambda * $denom_wkq) + ((1 - $this->lambda) * $denom_wkj);

        if ($numerator == 0.0 || $denom == 0.0) {
            return 0.0;
        } else {
            return ($numerator / $denom) * -1;
        }
    }
}

class Jaccard implements Distance
{
    /**
     * @param array $a
     * @param array $b
     *
     * @return float
     */
    public function distance(array $a, array $b): float
    {
        // rumus jaccard ada di slide
        $count = count($a);
        $numerator = 0.0;
        $denom_wkq = 0.0;
        $denom_wkj = 0.0;
        $denom = 0.0;

        for ($i = 0; $i < $count; ++$i) {
            $numerator += $a[$i] * $b[$i];
            $denom_wkq += $a[$i] ** 2;
            $denom_wkj += $b[$i] ** 2;
        }

        $denom = $denom_wkq + $denom_wkj - $numerator;

        if ($numerator == 0.0 || $denom == 0.0) {
            return 0.0;
        } else {
            return ($numerator / $denom) * -1;
        }
    }
}


class Cosine implements Distance
{
    /**
     * @param array $a
     * @param array $b
     *
     * @return float
     */
    public function distance(array $a, array $b): float
    {
        // rumus cosine ada di slide
        $count = count($a);
        $numerator = 0.0;
        $denom_wkq = 0.0;
        $denom_wkj = 0.0;
        $denom = 0.0;

        for ($i = 0; $i < $count; ++$i) {
            $numerator += $a[$i] * $b[$i];
            $denom_wkq += $a[$i] ** 2;
            $denom_wkj += $b[$i] ** 2;
        }

        $denom = sqrt($denom_wkq * $denom_wkj);

        if ($numerator == 0.0 || $denom == 0.0) {
            return 0.0;
        } else {
            return ($numerator / $denom) * -1;
        }
    }
}
