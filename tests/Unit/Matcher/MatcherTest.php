<?php

declare(strict_types=1);

/*
 * This file is part of the ChamberOrchestra package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Matcher;

use ChamberOrchestra\MenuBundle\Matcher\Matcher;
use ChamberOrchestra\MenuBundle\Matcher\Voter\VoterInterface;
use ChamberOrchestra\MenuBundle\Menu\Item;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MatcherTest extends TestCase
{
    private Matcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new Matcher();
    }

    #[Test]
    public function isCurrentReturnsFalseWithNoVoters(): void
    {
        self::assertFalse($this->matcher->isCurrent(new Item('home')));
    }

    #[Test]
    public function isCurrentReturnsFalseWhenAllVotersAbstain(): void
    {
        $this->matcher->addVoters([$this->makeVoter(null), $this->makeVoter(null)]);

        self::assertFalse($this->matcher->isCurrent(new Item('home')));
    }

    #[Test]
    public function isCurrentReturnsTrueWhenVoterVotesYes(): void
    {
        $this->matcher->addVoters([$this->makeVoter(true)]);

        self::assertTrue($this->matcher->isCurrent(new Item('home')));
    }

    #[Test]
    public function isCurrentReturnsFalseWhenVoterVotesNo(): void
    {
        $this->matcher->addVoters([$this->makeVoter(false)]);

        self::assertFalse($this->matcher->isCurrent(new Item('home')));
    }

    #[Test]
    public function isCurrentStopsAtFirstDecisiveVoter(): void
    {
        $decisive = $this->makeVoter(true);
        $neverCalled = $this->createMock(VoterInterface::class);
        $neverCalled->expects(self::never())->method('matchItem');

        $this->matcher->addVoters([$decisive, $neverCalled]);
        $this->matcher->isCurrent(new Item('home'));
    }

    #[Test]
    public function isCurrentCachesResultAndCallsVoterOnlyOnce(): void
    {
        $voter = $this->createMock(VoterInterface::class);
        $voter->expects(self::once())->method('matchItem')->willReturn(true);
        $this->matcher->addVoters([$voter]);

        $item = new Item('home');
        self::assertTrue($this->matcher->isCurrent($item));
        self::assertTrue($this->matcher->isCurrent($item));
    }

    #[Test]
    public function clearInvalidatesCacheAndReVotes(): void
    {
        $voter = $this->createMock(VoterInterface::class);
        $voter->expects(self::exactly(2))->method('matchItem')->willReturn(false);
        $this->matcher->addVoters([$voter]);

        $item = new Item('home');
        $this->matcher->isCurrent($item);
        $this->matcher->clear();
        $this->matcher->isCurrent($item); // voter called again
    }

    #[Test]
    public function isAncestorReturnsFalseWhenNoChildIsCurrent(): void
    {
        $this->matcher->addVoters([$this->makeVoter(null)]);

        $parent = new Item('parent');
        $parent->add(new Item('child'));

        self::assertFalse($this->matcher->isAncestor($parent));
    }

    #[Test]
    public function isAncestorReturnsTrueWhenDirectChildIsCurrent(): void
    {
        $child = new Item('child');
        $parent = new Item('parent');
        $parent->add($child);

        $voter = $this->createStub(VoterInterface::class);
        $voter->method('matchItem')
            ->willReturnCallback(static fn (Item $item) => $item === $child ? true : null);
        $this->matcher->addVoters([$voter]);

        self::assertTrue($this->matcher->isAncestor($parent));
    }

    #[Test]
    public function isAncestorReturnsFalseWithDepthZero(): void
    {
        $child = new Item('child');
        $parent = new Item('parent');
        $parent->add($child);
        $this->matcher->addVoters([$this->makeVoter(true)]);

        self::assertFalse($this->matcher->isAncestor($parent, depth: 0));
    }

    #[Test]
    public function isAncestorRespectsDepthLimit(): void
    {
        $grandchild = new Item('grandchild');
        $child = new Item('child');
        $child->add($grandchild);
        $parent = new Item('parent');
        $parent->add($child);

        $voter = $this->createStub(VoterInterface::class);
        $voter->method('matchItem')
            ->willReturnCallback(static fn (Item $item) => $item === $grandchild ? true : null);
        $this->matcher->addVoters([$voter]);

        self::assertFalse($this->matcher->isAncestor($parent, depth: 1));
        $this->matcher->clear();
        self::assertTrue($this->matcher->isAncestor($parent, depth: 2));
    }

    #[Test]
    public function isAncestorIsTrueForGrandchildWithUnlimitedDepth(): void
    {
        $grandchild = new Item('grandchild');
        $child = new Item('child');
        $child->add($grandchild);
        $parent = new Item('parent');
        $parent->add($child);

        $voter = $this->createStub(VoterInterface::class);
        $voter->method('matchItem')
            ->willReturnCallback(static fn (Item $item) => $item === $grandchild ? true : null);
        $this->matcher->addVoters([$voter]);

        self::assertTrue($this->matcher->isAncestor($parent));
    }

    private function makeVoter(?bool $result): VoterInterface
    {
        $voter = $this->createStub(VoterInterface::class);
        $voter->method('matchItem')->willReturn($result);

        return $voter;
    }
}
