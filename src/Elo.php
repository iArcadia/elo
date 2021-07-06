<?php

namespace iArcadia;

/**
 * @class Elo
 */
class Elo {
    /**
     * @var int $factor
     * Factor of the formula.
     */
    static protected $factor = 32;
    
    /**
     * Return the R variable of the formula.
     *
     * @param int $rating Rating of a player.
     *
     * @return int
     */
    static protected function getR(int $rating): int {
        return pow(10, $rating / 400);
    }
    
    /**
     * Return the E variable of the formula.
     *
     * @param int $r1 R of the home team.
     * @param int $r2 R of the opponent team.
     *
     * @return float
     */
    static protected function getE(int $r1, int $r2): float {
        return $r1 / ($r1 + $r2);
    }
    
    /**
     * Get the K factor.
     *
     * @return int
     */
    static public function getFactor(): int {
        return self::$factor;
    }
    
    /**
     * Set the K factor.
     *
     * @return void
     */
    static public function setFactor(int $factor): void {
        self::$factor = $factor;
    }
    
    /**
     * Calculate the new home and opponent ratings.
     * 
     * @param int $homeRating Rating of the home player.
     * @param int $opponentRating Rating of the opponent player.
     * @param float $homeResult Result of the home player. 1.0 = Win, 0.5 = Draw, 0.0 = Lose.
     * @param bool $doubleReturn The method returns the new rating of both players if true. Returns only the home player new rating otherwise.
     *
     * @return array
     */
    static public function oneVsOne(int $homeRating, int $opponentRating, float $homeResult = 1.0, bool $doubleReturn = true): array {
        $homeR = self::getR($homeRating);
        $opponentR = self::getR($opponentRating);
        
        $homeE = self::getE($homeR, $opponentR);
        $opponentE = self::getE($opponentR, $homeR);
        
        $factor = self::getFactor();
        
        $homeNewRating = $homeRating + $factor * ($homeResult - $homeE);
        $opponentNewRating = $opponentRating + $factor * (abs(1 - $homeResult) - $opponentE);
        
        if (!$doubleReturn) {
            return ['oldRating' => $homeRating, 'newRating' => $homeNewRating, 'change' => $homeNewRating - $homeRating];
        }
        
        return [
            'home' => ['oldRating' => $homeRating, 'newRating' => $homeNewRating, 'change' => $homeNewRating - $homeRating],
            'opponent' => ['oldRating' => $opponentRating, 'newRating' => $opponentNewRating, 'change' => $opponentNewRating - $opponentRating]
        ];
    }
    
    /**
     * Calculate the new home and opponent ratings for a team match.
     *
     * @param array $homeRatings Ratings of the home players.
     * @param array $opponentRatings Ratings of the opponent players.
     * @param float $homeResult Result of the home team. 1.0 = Win, 0.5 = Draw, 0.0 = Lose.
     *
     * @return array
     */
    static public function manyVsMany(array $homeRatings, array $opponentRatings, float $homeResult = 1.0): array {
        $homeMean = array_sum($homeRatings) / count($homeRatings);
        $opponentMean = array_sum($opponentRatings) / count($opponentRatings);
        
        $homeData = [];
        $opponentData = [];
        
        foreach ($homeRatings as $homeRating) {
            $homeData[] = self::oneVsOne($homeRating, $opponentMean, $homeResult, false);
        }
        
        foreach ($opponentRatings as $opponentRating) {
            $opponentData[] = self::oneVsOne($opponentRating, $homeMean, abs(1 - $homeResult), false);
        }
        
        return ['home' => $homeData, 'opponent' => $opponentData];
    }
    
    /**
     * Calculate the new rating of all players for a free-for-all match.
     *
     * @param array $ratings Ratings of all players. Must be sorted by final position.
     * 
     * @return array
     */
    static public function freeForAll(array $ratings): array {
        $newRatings = [];
        
        foreach ($ratings as $homeRank => $homeRating) {
            $ratingChange = 0;
            
            foreach ($ratings as $opponentRank => $opponentRating) {
                $homeResult = null;
                
                if ($homeRank === $opponentRank) {
                    continue;
                } elseif ($homeRank < $opponentRank) {
                    $homeResult = 1.0;
                } elseif ($homeRank > $opponentRank) {
                    $homeResult = 0.0;
                } else {
                    $homeResult = 0.5;
                }
                
                $homeData = self::oneVsOne($homeRating, $opponentRating, $homeResult, false);
                $ratingChange += $homeData['change'];
            }
            
            $newRatings[] = ['oldRating' => $homeRating, 'newRating' => $homeRating + $ratingChange, 'change' => $ratingChange];
        }
        
        return $newRatings;
    }
}