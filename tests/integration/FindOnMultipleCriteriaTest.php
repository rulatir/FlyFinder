<?php
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

namespace Flyfinder;

use PHPUnit\Framework\TestCase;

/**
 * Integration test against examples/02-find-on-multiple-criteria.php
 * @coversNothing
 */
class FindOnMultipleCriteriaTest extends TestCase
{
    use TestsBothAlgorithms;
    /**
     * @param int $finderAlgorithm
     * @dataProvider algorithms
     */
    public function testFindingFilesOnMultipleCriteria(int $finderAlgorithm)
    {
        $result = [];
        include(__DIR__ . '/../../examples/02-find-on-multiple-criteria.php');

        $this->assertCount(2, $result);
        $this->assertSame('found.txt', $result[0]['basename']);
        $this->assertSame('example.txt', $result[1]['basename']);
    }

    public function algorithms() : array
    {
        return [
            'legacy algorithm' => [Finder::ALGORITHM_LEGACY],
            'optimized algorithm' => [Finder::ALGORITHM_OPTIMIZED]
        ];
    }
}
