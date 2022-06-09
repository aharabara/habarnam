<?php

namespace Tests\Unit;

use Base\Primitives\Position;
use PHPUnit\Framework\TestCase;
use Tests\Factory\PositionFactory;

class PositionTest extends TestCase
{
    /**
     * @dataProvider positionMovementDataProvider
     */
    public function testMove(Position $basePos, Position $displacement){
        /* create a data provider */
        $newPos = $basePos->move($displacement);
        self::assertEquals($newPos->getX(), $basePos->getX() + $displacement->getX());
        self::assertEquals($newPos->getY(), $basePos->getY() + $displacement->getY());
    }

    /**
     * @dataProvider positionOneDirectionMovementDataProvider
     */
    public function testUp(Position $basePos, int $displacement){
        $oldPos = $basePos->getY();
        $basePos->up($displacement);
        self::assertEquals($basePos->getY(), $oldPos + $displacement);
    }

    public function positionMovementDataProvider(): array
    {
        $factory = new PositionFactory();
        $closure = function () use ($factory) {
            $amount = 20;
            foreach (range(0 ,$amount) as $item) {
                yield [$factory->create(), $factory->create()];
            }
        };
        return iterator_to_array($closure());
    }

    public function positionOneDirectionMovementDataProvider(): array
    {
        $factory = new PositionFactory();
        $closure = function () use ($factory) {
            $amount = 20;
            foreach (range(0 ,$amount) as $item) {
                yield [$factory->create(), random_int(0, 100)];
            }
        };
        return iterator_to_array($closure());
    }

}