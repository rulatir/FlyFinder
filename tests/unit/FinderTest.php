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

use Flyfinder\Specification\InPath;
use Flyfinder\Specification\IsHidden;
use League\Flysystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * Test case for Finder
 * @coversDefaultClass Flyfinder\Finder
 */
class FinderTest extends TestCase
{
    use TestsBothAlgorithms;

    /** @var Finder */
    private $fixture;

    /**
     * Initializes the fixture for this test.
     */
    public function setUp()
    {
        $this->fixture = new Finder();
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @covers ::getMethod
     */
    public function testGetMethod()
    {
        $this->assertSame('find', $this->fixture->getMethod());
    }

    /**
     * @covers ::handle
     * @covers ::setFilesystem
     * @covers ::<private>
     * @dataProvider algorithms
     * @param int $finderAlgorithm
     */
    public function testIfCorrectFilesAreBeingYielded(int $finderAlgorithm)
    {
        $this->fixture->setAlgorithm($finderAlgorithm);

        $isHidden = m::mock(IsHidden::class);
        $filesystem = m::mock(Filesystem::class);

        $listContents1 = [
            0 => [
                'type' => 'dir',
                'path' => '.hiddendir',
                'dirname' => '',
                'basename' => '.hiddendir',
                'filename' => '.hiddendir',
            ],
            1 => [
                'type' => 'file',
                'path' => 'test.txt',
                'basename' => 'test.txt',
            ],
        ];

        $listContents2 = [
            0 => [
                'type' => 'file',
                'path' => '.hiddendir/.test.txt',
                'dirname' => '.hiddendir',
                'basename' => '.test.txt',
                'filename' => '.test',
                'extension' => 'txt',
            ],
        ];

        $filesystem->shouldReceive('listContents')
            ->with('')
            ->andReturn($listContents1);

        $filesystem->shouldReceive('listContents')
            ->with('.hiddendir')
            ->andReturn($listContents2);

        $isHidden->shouldReceive('isSatisfiedBy')
            ->with($listContents1[0])
            ->andReturn(true);

        if (Finder::ALGORITHM_OPTIMIZED === $finderAlgorithm) {
            $isHidden->shouldReceive('canBeSatisfiedByAnythingBelow')
                ->with($listContents1[0])
                ->andReturn(true);
        }

        $isHidden->shouldReceive('isSatisfiedBy')
            ->with($listContents1[1])
            ->andReturn(false);

        $isHidden->shouldReceive('isSatisfiedBy')
            ->with($listContents2[0])
            ->andReturn(true);

        $this->fixture->setFilesystem($filesystem);
        $generator = $this->fixture->handle($isHidden);

        $result = [];

        foreach ($generator as $value) {
            $result[] = $value;
        }

        $expected = [
            0 => [
                'type' => 'file',
                'path' => '.hiddendir/.test.txt',
                'dirname' => '.hiddendir',
                'basename' => '.test.txt',
                'filename' => '.test',
                'extension' => 'txt',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * @param int $finderAlgorithm
     * @dataProvider algorithms
     */
    public function testPrefixCullingOptimization(int $finderAlgorithm)
    {
        $this->fixture->setAlgorithm($finderAlgorithm);

        $filesystem = m::mock(Filesystem::class);

        $pathList = [
            'foo/bar/baz/file.txt',
            'foo/bar/baz/file2.txt',
            'foo/bar/baz/excluded/excluded.txt',
            'foo/bar/baz/excluded/culled/culled.txt',
            'foo/bar/baz/excluded/important/reincluded.txt',
            'foo/bar/file3.txt',
            'foo/lou/someSubdir/file4.txt',
            'foo/irrelevant1/',
            'irrelevant2/irrelevant3/irrelevantFile.txt'
        ];

        $fsContents = $this->mockFileTree($pathList);

        $culledPaths = Finder::ALGORITHM_OPTIMIZED === $finderAlgorithm
            ? [
                'foo/irrelevant1',
                'irrelevant2',
                'foo/bar/baz/excluded/culled'
            ]
            : [

            ];

        $filesystem->shouldReceive('listContents')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function(string $path) use ($culledPaths, $fsContents) : array {

                $this->assertNotContains($path, $culledPaths);
                return array_values($this->mockListContents($fsContents, $path));
            });

        $inFooBar = new InPath(new Path("foo/bar"));
        $inFooLou = new InPath(new Path("foo/lou"));
        $inExcl = new InPath(new Path("foo/bar/baz/excl*"));
        $inReincl = new InPath(new Path("foo/bar/baz/*/important"));
        $spec =
            $inFooBar
                ->orSpecification($inFooLou)
                ->andSpecification($inExcl->notSpecification())
                ->orSpecification($inReincl);

        $finder = $this->fixture;
        $finder->setFilesystem($filesystem);
        $generator = $finder->handle($spec);

        $expected = [
            'foo/bar/baz/file.txt',
            'foo/bar/baz/file2.txt',
            'foo/bar/file3.txt',
            'foo/bar/baz/excluded/important/reincluded.txt',
            'foo/lou/someSubdir/file4.txt',
        ];
        sort($expected);

        $actual = array_map(function($v) {return $v['path']; }, iterator_to_array($generator));
        sort($actual);

        $this->assertEquals($expected, $actual);

        $this->addToAssertionCount(1);
    }

    protected function mockFileTree(array $pathList) : array
    {
        $result = [
            "." => [
                'type' => 'dir',
                'path' => '',
                'dirname' => '.',
                'basename' => '.',
                'filename' => '.',
                'contents' => []
            ]
        ];
        foreach($pathList as $path) {

            $isFile = "/" !== substr($path,-1);
            $child = null;
            while(true) {
                $info = pathinfo($path);
                if ($isFile) {
                    $isFile = false;
                    $result[$path] = [
                        'type' => 'file',
                        'path' => $path,
                        'dirname' => $info['dirname'],
                        'basename' => $info['basename'],
                        'filename' => $info['filename'],
                        'extension' => $info['extension']
                    ];
                }
                else {
                    $existed = true;
                    if (!isset($result[$path])) {
                        $existed = false;
                        $result[$path] = [
                            'type' => 'dir',
                            'path' => $path,
                            'basename' => $info['basename'],
                            'filename' => $info['filename'],
                            'contents' => []
                        ];
                    }
                    if (null!==$child) {
                        $result[$path]['contents'][] = $child;
                    }
                    if ($existed) break;
                }
                $child = $info['basename'];
                $path = $info['dirname'];
            }
        }
        return $result;
    }

    protected function mockListContents(array $fileTreeMock, string $path) : array
    {
        $path = trim($path,"/");
        if (substr($path."  ",0,2)==="./") $path = substr($path,2);
        if ($path==="") $path = ".";

        if (!isset($fileTreeMock[$path]) || 'file' === $fileTreeMock[$path]['type']) {
            return [];
        }
        $result = [];
        foreach($fileTreeMock[$path]['contents'] as $basename) {
            $childPath = ($path==="." ? "" : $path."/").$basename;
            if (isset($fileTreeMock[$childPath])) {
                $result[$basename] = $fileTreeMock[$childPath];
            }
        }
        return $result;
    }
}
