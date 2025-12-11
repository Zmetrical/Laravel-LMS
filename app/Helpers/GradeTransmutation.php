<?php

namespace App\Helpers;

class GradeTransmutation
{
    /**
     * Standard DepEd transmutation table
     * Maps initial grade (percentage) to transmuted grade
     */
    private static $transmutationTable = [
        ['min' => 98.40, 'max' => 100.00, 'grade' => 100],
        ['min' => 96.80, 'max' => 98.39, 'grade' => 99],
        ['min' => 95.20, 'max' => 96.79, 'grade' => 98],
        ['min' => 93.60, 'max' => 95.19, 'grade' => 97],
        ['min' => 92.00, 'max' => 93.59, 'grade' => 96],
        ['min' => 90.40, 'max' => 91.99, 'grade' => 95],
        ['min' => 88.80, 'max' => 90.39, 'grade' => 94],
        ['min' => 87.20, 'max' => 88.79, 'grade' => 93],
        ['min' => 85.60, 'max' => 87.19, 'grade' => 92],
        ['min' => 84.00, 'max' => 85.59, 'grade' => 91],
        ['min' => 82.40, 'max' => 83.99, 'grade' => 90],
        ['min' => 80.80, 'max' => 82.39, 'grade' => 89],
        ['min' => 79.20, 'max' => 80.79, 'grade' => 88],
        ['min' => 77.60, 'max' => 79.19, 'grade' => 87],
        ['min' => 76.00, 'max' => 77.59, 'grade' => 86],
        ['min' => 74.40, 'max' => 75.99, 'grade' => 85],
        ['min' => 72.80, 'max' => 74.39, 'grade' => 84],
        ['min' => 71.20, 'max' => 72.79, 'grade' => 83],
        ['min' => 69.60, 'max' => 71.19, 'grade' => 82],
        ['min' => 68.00, 'max' => 69.59, 'grade' => 81],
        ['min' => 66.40, 'max' => 67.99, 'grade' => 80],
        ['min' => 64.80, 'max' => 66.39, 'grade' => 79],
        ['min' => 63.20, 'max' => 64.79, 'grade' => 78],
        ['min' => 61.60, 'max' => 63.19, 'grade' => 77],
        ['min' => 60.00, 'max' => 61.59, 'grade' => 76],
        ['min' => 56.00, 'max' => 59.99, 'grade' => 75],
        ['min' => 52.00, 'max' => 55.99, 'grade' => 74],
        ['min' => 48.00, 'max' => 51.99, 'grade' => 73],
        ['min' => 44.00, 'max' => 47.99, 'grade' => 72],
        ['min' => 40.00, 'max' => 43.99, 'grade' => 71],
        ['min' => 36.00, 'max' => 39.99, 'grade' => 70],
        ['min' => 32.00, 'max' => 35.99, 'grade' => 69],
        ['min' => 28.00, 'max' => 31.99, 'grade' => 68],
        ['min' => 24.00, 'max' => 27.99, 'grade' => 67],
        ['min' => 20.00, 'max' => 23.99, 'grade' => 66],
        ['min' => 16.00, 'max' => 19.99, 'grade' => 65],
        ['min' => 12.00, 'max' => 15.99, 'grade' => 64],
        ['min' => 8.00, 'max' => 11.99, 'grade' => 63],
        ['min' => 4.00, 'max' => 7.99, 'grade' => 62],
        ['min' => 0.00, 'max' => 3.99, 'grade' => 61],
    ];


    /**
     * Transmute initial grade to final grade
     */
    public static function transmute($initialGrade)
    {
        if ($initialGrade === null) {
            return null;
        }

        foreach (self::$transmutationTable as $range) {
            if ($initialGrade >= $range['min'] && $initialGrade <= $range['max']) {
                return $range['grade'];
            }
        }

        // Fallback: round to nearest integer
        return round($initialGrade);
    }

    /**
     * Get remarks based on transmuted grade
     */
    public static function getRemarks($transmutedGrade)
    {
        if ($transmutedGrade === null) {
            return null;
        }

        if ($transmutedGrade >= 75) {
            return 'PASSED';
        }

        return 'FAILED';
    }

    
}