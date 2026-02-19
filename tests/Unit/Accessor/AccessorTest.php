<?php

declare(strict_types=1);

namespace Tests\Unit\Accessor;

use ChamberOrchestra\MenuBundle\Accessor\Accessor;
use ChamberOrchestra\MenuBundle\Menu\Item;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
final class AccessorTest extends TestCase
{
    private AuthorizationCheckerInterface $authChecker;
    private Accessor $accessor;

    protected function setUp(): void
    {
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->accessor = new Accessor($this->authChecker);
    }

    #[Test]
    public function hasAccessReturnsTrueWhenItemHasNoRoles(): void
    {
        $this->authChecker->expects(self::never())->method('isGranted');

        self::assertTrue($this->accessor->hasAccess(new Item('home')));
    }

    #[Test]
    public function hasAccessReturnsTrueWhenAllRolesGranted(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        self::assertTrue($this->accessor->hasAccess(new Item('admin', ['roles' => ['ROLE_ADMIN', 'ROLE_USER']])));
    }

    #[Test]
    public function hasAccessReturnsFalseWhenFirstRoleDenied(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        self::assertFalse($this->accessor->hasAccess(new Item('admin', ['roles' => ['ROLE_ADMIN']])));
    }

    #[Test]
    public function hasAccessReturnsFalseWhenSecondRoleDenied(): void
    {
        $this->authChecker->method('isGranted')
            ->willReturnMap([
                ['ROLE_USER', null, true],
                ['ROLE_ADMIN', null, false],
            ]);

        self::assertFalse($this->accessor->hasAccess(new Item('admin', ['roles' => ['ROLE_USER', 'ROLE_ADMIN']])));
    }

    #[Test]
    public function hasAccessCachesResultPerItem(): void
    {
        $this->authChecker->expects(self::once())->method('isGranted')->willReturn(true);
        $item = new Item('admin', ['roles' => ['ROLE_ADMIN']]);

        self::assertTrue($this->accessor->hasAccess($item));
        self::assertTrue($this->accessor->hasAccess($item)); // second call hits item-level cache
    }

    #[Test]
    public function hasAccessCachesRoleGrantDecisionAcrossItems(): void
    {
        $this->authChecker->expects(self::once())->method('isGranted')->willReturn(true);

        $item1 = new Item('a', ['roles' => ['ROLE_ADMIN']]);
        $item2 = new Item('b', ['roles' => ['ROLE_ADMIN']]);

        $this->accessor->hasAccess($item1);
        $this->accessor->hasAccess($item2); // ROLE_ADMIN already cached → no second isGranted call
    }

    // --- hasAccessToChildren ---

    #[Test]
    public function hasAccessToChildrenReturnsFalseForEmptyCollection(): void
    {
        self::assertFalse($this->accessor->hasAccessToChildren(new ArrayCollection()));
    }

    #[Test]
    public function hasAccessToChildrenReturnsTrueWhenAtLeastOneChildIsAccessible(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $restricted = new Item('restricted', ['roles' => ['ROLE_ADMIN']]);
        $open = new Item('open'); // no roles → always accessible

        self::assertTrue($this->accessor->hasAccessToChildren(new ArrayCollection([$restricted, $open])));
    }

    #[Test]
    public function hasAccessToChildrenReturnsTrueWhenFirstChildIsAccessible(): void
    {
        $open = new Item('open');

        self::assertTrue($this->accessor->hasAccessToChildren(new ArrayCollection([$open])));
    }

    #[Test]
    public function hasAccessToChildrenReturnsFalseWhenAllChildrenAreDenied(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $a = new Item('a', ['roles' => ['ROLE_ADMIN']]);
        $b = new Item('b', ['roles' => ['ROLE_ADMIN']]);

        self::assertFalse($this->accessor->hasAccessToChildren(new ArrayCollection([$a, $b])));
    }

    #[Test]
    public function hasAccessToChildrenStopsEarlyOnFirstAccessibleItem(): void
    {
        // authChecker called for first item (has role), first item accessible → stops
        $this->authChecker->expects(self::never())->method('isGranted');

        $open1 = new Item('open1');
        $open2 = new Item('open2');

        self::assertTrue($this->accessor->hasAccessToChildren(new ArrayCollection([$open1, $open2])));
    }
}
