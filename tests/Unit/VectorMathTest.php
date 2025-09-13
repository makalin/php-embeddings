<?php

declare(strict_types=1);

namespace PhpEmbeddings\Tests\Unit;

use PhpEmbeddings\Math\VectorMath;
use PHPUnit\Framework\TestCase;

class VectorMathTest extends TestCase
{
    private VectorMath $vectorMath;

    protected function setUp(): void
    {
        $this->vectorMath = new VectorMath();
    }

    public function testCosineSimilarity(): void
    {
        $a = [1.0, 0.0, 0.0];
        $b = [1.0, 0.0, 0.0];
        
        $similarity = $this->vectorMath->cosineSimilarity($a, $b);
        $this->assertEquals(1.0, $similarity, '', 0.001);
    }

    public function testCosineSimilarityOrthogonal(): void
    {
        $a = [1.0, 0.0, 0.0];
        $b = [0.0, 1.0, 0.0];
        
        $similarity = $this->vectorMath->cosineSimilarity($a, $b);
        $this->assertEquals(0.0, $similarity, '', 0.001);
    }

    public function testCosineSimilarityDifferentDimensions(): void
    {
        $a = [1.0, 0.0];
        $b = [1.0, 0.0, 0.0];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->vectorMath->cosineSimilarity($a, $b);
    }

    public function testDotProduct(): void
    {
        $a = [1.0, 2.0, 3.0];
        $b = [4.0, 5.0, 6.0];
        
        $result = $this->vectorMath->dotProduct($a, $b);
        $expected = 1*4 + 2*5 + 3*6; // 32
        $this->assertEquals($expected, $result);
    }

    public function testNorm(): void
    {
        $vector = [3.0, 4.0, 0.0];
        $norm = $this->vectorMath->norm($vector);
        $this->assertEquals(5.0, $norm);
    }

    public function testNormalize(): void
    {
        $vector = [3.0, 4.0, 0.0];
        $normalized = $this->vectorMath->normalize($vector);
        
        $norm = $this->vectorMath->norm($normalized);
        $this->assertEquals(1.0, $norm, '', 0.001);
    }

    public function testEuclideanDistance(): void
    {
        $a = [0.0, 0.0, 0.0];
        $b = [3.0, 4.0, 0.0];
        
        $distance = $this->vectorMath->euclideanDistance($a, $b);
        $this->assertEquals(5.0, $distance);
    }

    public function testPackUnpackVector(): void
    {
        $vector = [1.0, 2.0, 3.0, 4.0];
        $packed = $this->vectorMath->packVector($vector);
        $unpacked = $this->vectorMath->unpackVector($packed, count($vector));
        
        $this->assertEquals($vector, $unpacked);
    }

    public function testMean(): void
    {
        $vectors = [
            [1.0, 2.0, 3.0],
            [4.0, 5.0, 6.0],
            [7.0, 8.0, 9.0]
        ];
        
        $mean = $this->vectorMath->mean($vectors);
        $expected = [4.0, 5.0, 6.0];
        
        $this->assertEquals($expected, $mean);
    }
}
