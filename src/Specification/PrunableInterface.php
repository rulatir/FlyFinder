<?php


namespace Flyfinder\Specification;


interface PrunableInterface
{
    /**
     * Checks if anything under the directory path in value can possibly satisfy the specification.
     *
     * @param mixed[] $value
     * @return bool
     */
    public function canBeSatisfiedByAnythingBelow(array $value): bool;

    /**
     * Returns true if it is known or can be deduced that everything under the directory path in value
     * will certainly satisfy the specification.
     *
     * @param mixed[] $value
     * @return bool
     */
    public function willBeSatisfiedByEverythingBelow(array $value): bool;
}