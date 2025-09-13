<?php

declare(strict_types=1);

namespace PhpEmbeddings\Math;

/**
 * Pure PHP vector math operations with SIMD-style optimizations
 */
class VectorMath
{
    /**
     * Calculate cosine similarity between two vectors
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Vectors must have the same dimension');
        }

        $dotProduct = $this->dotProduct($a, $b);
        $normA = $this->norm($a);
        $normB = $this->norm($b);

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Calculate dot product of two vectors
     */
    public function dotProduct(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Vectors must have the same dimension');
        }

        $result = 0.0;
        $count = count($a);

        // SIMD-style optimization: process 4 elements at a time
        for ($i = 0; $i < $count; $i += 4) {
            $result += $a[$i] * $b[$i];
            if ($i + 1 < $count) $result += $a[$i + 1] * $b[$i + 1];
            if ($i + 2 < $count) $result += $a[$i + 2] * $b[$i + 2];
            if ($i + 3 < $count) $result += $a[$i + 3] * $b[$i + 3];
        }

        return $result;
    }

    /**
     * Calculate L2 norm of a vector
     */
    public function norm(array $vector): float
    {
        $sum = 0.0;
        $count = count($vector);

        // SIMD-style optimization
        for ($i = 0; $i < $count; $i += 4) {
            $sum += $vector[$i] * $vector[$i];
            if ($i + 1 < $count) $sum += $vector[$i + 1] * $vector[$i + 1];
            if ($i + 2 < $count) $sum += $vector[$i + 2] * $vector[$i + 2];
            if ($i + 3 < $count) $sum += $vector[$i + 3] * $vector[$i + 3];
        }

        return sqrt($sum);
    }

    /**
     * Normalize vector to unit length
     */
    public function normalize(array $vector): array
    {
        $norm = $this->norm($vector);
        
        if ($norm === 0.0) {
            return $vector;
        }

        $result = [];
        $count = count($vector);

        // SIMD-style optimization
        for ($i = 0; $i < $count; $i += 4) {
            $result[$i] = $vector[$i] / $norm;
            if ($i + 1 < $count) $result[$i + 1] = $vector[$i + 1] / $norm;
            if ($i + 2 < $count) $result[$i + 2] = $vector[$i + 2] / $norm;
            if ($i + 3 < $count) $result[$i + 3] = $vector[$i + 3] / $norm;
        }

        return $result;
    }

    /**
     * Calculate Euclidean distance between two vectors
     */
    public function euclideanDistance(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Vectors must have the same dimension');
        }

        $sum = 0.0;
        $count = count($a);

        // SIMD-style optimization
        for ($i = 0; $i < $count; $i += 4) {
            $diff = $a[$i] - $b[$i];
            $sum += $diff * $diff;
            
            if ($i + 1 < $count) {
                $diff = $a[$i + 1] - $b[$i + 1];
                $sum += $diff * $diff;
            }
            if ($i + 2 < $count) {
                $diff = $a[$i + 2] - $b[$i + 2];
                $sum += $diff * $diff;
            }
            if ($i + 3 < $count) {
                $diff = $a[$i + 3] - $b[$i + 3];
                $sum += $diff * $diff;
            }
        }

        return sqrt($sum);
    }

    /**
     * Pack float array to binary format for storage
     */
    public function packVector(array $vector): string
    {
        return pack('f*', ...$vector);
    }

    /**
     * Unpack binary data to float array
     */
    public function unpackVector(string $data, int $dimension): array
    {
        return array_values(unpack('f*', $data));
    }

    /**
     * Calculate mean of vectors
     */
    public function mean(array $vectors): array
    {
        if (empty($vectors)) {
            return [];
        }

        $dimension = count($vectors[0]);
        $result = array_fill(0, $dimension, 0.0);
        $count = count($vectors);

        foreach ($vectors as $vector) {
            for ($i = 0; $i < $dimension; $i++) {
                $result[$i] += $vector[$i];
            }
        }

        for ($i = 0; $i < $dimension; $i++) {
            $result[$i] /= $count;
        }

        return $result;
    }
}
