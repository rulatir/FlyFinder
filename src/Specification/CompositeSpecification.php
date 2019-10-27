<?php
declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2010-2018 Mike van Riel<mike@phpdoc.org>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace Flyfinder\Specification;

/**
 * Class CompositeSpecification
 * Base class for specifications, allows for combining specifications
 */
abstract class CompositeSpecification implements SpecificationInterface, PrunableInterface
{
    /**
     * Returns a specification that satisfies the original specification
     * as well as the other specification
     * @param SpecificationInterface $other
     * @return AndSpecification
     */
    public function andSpecification(SpecificationInterface $other): AndSpecification
    {
        return new AndSpecification($this, $other);
    }

    /**
     * Returns a specification that satisfies the original specification
     * or the other specification
     * @param SpecificationInterface $other
     * @return OrSpecification
     */
    public function orSpecification(SpecificationInterface $other): OrSpecification
    {
        return new OrSpecification($this, $other);
    }

    /**
     * Returns a specification that is the inverse of the original specification
     * i.e. does not meet the original criteria
     */
    public function notSpecification(): NotSpecification
    {
        return new NotSpecification($this);
    }

    /** @inheritDoc */
    public function canBeSatisfiedByAnythingBelow(array $value): bool
    {
        return true;
    }

    public function willBeSatisfiedByEverythingBelow(array $value): bool
    {
        return false;
    }

    /**
     * Provide default {@see canBeSatisfiedByAnythingBelow()} logic for specification classes
     * that don't implement PrunableInterface
     * @param SpecificationInterface $specification
     * @param array $value
     * @return bool
     */
    public static function thatCanBeSatisfiedByAnythingBelow(SpecificationInterface $specification, array $value): bool
    {
        return
            $specification instanceof PrunableInterface
                ? $specification->canBeSatisfiedByAnythingBelow($value)
                : true;
    }

    /**
     * Provide default {@see willBeSatisfiedByEverythingBelow()} logic for specification classes
     * that don't implement PrunableInterface
     * @param SpecificationInterface $specification
     * @param array $value
     * @return bool
     */
    public static function thatWillBeSatisfiedByEverythingBelow(SpecificationInterface $specification, array $value): bool
    {
        return
            $specification instanceof PrunableInterface
            && $specification->willBeSatisfiedByEverythingBelow($value);
    }

}
