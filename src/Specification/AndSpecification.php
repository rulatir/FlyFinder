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
 * Class AndSpecification
 */
final class AndSpecification extends CompositeSpecification
{
    /**
     * @var SpecificationInterface
     */
    private $one;

    /**
     * @var SpecificationInterface
     */
    private $other;

    /**
     * Initializes the AndSpecification object
     * @param SpecificationInterface $one
     * @param SpecificationInterface $other
     */
    public function __construct(SpecificationInterface $one, SpecificationInterface $other)
    {
        $this->one = $one;
        $this->other = $other;
    }

    /**
     * Checks if the value meets the specification
     *
     * @param mixed[] $value
     * @return bool
     */
    public function isSatisfiedBy(array $value): bool
    {
        return $this->one->isSatisfiedBy($value) && $this->other->isSatisfiedBy($value);
    }

    /** @inheritDoc */
    public function canBeSatisfiedByAnythingBelow(array $value): bool
    {
        return
            self::thatCanBeSatisfiedByAnythingBelow($this->one, $value)
            && self::thatCanBeSatisfiedByAnythingBelow($this->other, $value);
    }

    /** @inheritDoc */
    public function willBeSatisfiedByEverythingBelow(array $value): bool
    {
        return
            self::thatWillBeSatisfiedByEverythingBelow($this->one, $value)
            && self::thatWillBeSatisfiedByEverythingBelow($this->other, $value);
    }
}
