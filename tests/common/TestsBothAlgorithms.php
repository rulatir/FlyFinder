<?php

namespace Flyfinder;

trait TestsBothAlgorithms
{
    public function algorithms() : array
    {
        return [
            [Finder::ALGORITHM_LEGACY],
            [Finder::ALGORITHM_OPTIMIZED]
        ];
    }
}