<?php

declare(strict_types=1);

namespace Tests\Integrational;

use ChamberOrchestra\MenuBundle\Factory\Extension\CoreExtension;
use ChamberOrchestra\MenuBundle\Factory\Extension\LabelExtension;
use ChamberOrchestra\MenuBundle\Factory\Factory;
use ChamberOrchestra\MenuBundle\Menu\MenuBuilderInterface;
use ChamberOrchestra\MenuBundle\Navigation\AbstractNavigation;
use ChamberOrchestra\MenuBundle\NavigationFactory;
use ChamberOrchestra\MenuBundle\Registry\NavigationRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tests the full pipeline: Navigation → MenuBuilder → Item tree, using real classes.
 */
final class NavigationBuildTest extends TestCase
{
    private Factory $factory;
    private NavigationFactory $navigationFactory;

    protected function setUp(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0); // identity translator

        $this->factory = new Factory();
        $this->factory->addExtension(new LabelExtension($translator), priority: 10);
        $this->factory->addExtension(new CoreExtension(), priority: -10);

        $registry = $this->createStub(NavigationRegistry::class);
        $this->navigationFactory = new NavigationFactory($registry, $this->factory, null);
    }

    #[Test]
    public function buildsSimpleFlatNavigation(): void
    {
        $nav = new class extends AbstractNavigation {
            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                $builder
                    ->add('home', ['label' => 'Home', 'uri' => '/'])
                    ->add('about', ['label' => 'About', 'uri' => '/about'])
                    ->add('contact', ['label' => 'Contact', 'uri' => '/contact']);
            }
        };

        $root = $this->navigationFactory->create($nav, []);

        self::assertCount(3, $root);
        self::assertSame('home', $root->getFirstChild()->getName());
        self::assertSame('Home', $root->getFirstChild()->getLabel());
        self::assertSame('/', $root->getFirstChild()->getUri());
        self::assertSame('contact', $root->getLastChild()->getName());
    }

    #[Test]
    public function buildsNestedNavigation(): void
    {
        $nav = new class extends AbstractNavigation {
            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                $builder
                    ->add('products', ['label' => 'Products'])
                    ->children()
                        ->add('shoes', ['label' => 'Shoes'])
                        ->add('bags', ['label' => 'Bags'])
                    ->end()
                    ->add('about', ['label' => 'About']);
            }
        };

        $root = $this->navigationFactory->create($nav, []);

        self::assertCount(2, $root);

        $products = $root->getFirstChild();
        self::assertSame('products', $products->getName());
        self::assertCount(2, $products);
        self::assertSame('shoes', $products->getFirstChild()->getName());
        self::assertSame('bags', $products->getLastChild()->getName());
    }

    #[Test]
    public function buildsSectionItems(): void
    {
        $nav = new class extends AbstractNavigation {
            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                $builder
                    ->add('main', ['label' => 'Main'], section: true)
                    ->children()
                        ->add('dashboard', ['label' => 'Dashboard'])
                    ->end();
            }
        };

        $root = $this->navigationFactory->create($nav, []);

        $section = $root->getFirstChild();
        self::assertTrue($section->isSection());
        self::assertSame('Main', $section->getLabel());
        self::assertCount(1, $section);
    }

    #[Test]
    public function buildsItemsWithRoleRestrictions(): void
    {
        $nav = new class extends AbstractNavigation {
            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                $builder
                    ->add('dashboard', ['roles' => ['ROLE_USER']])
                    ->add('admin', ['roles' => ['ROLE_ADMIN']]);
            }
        };

        $root = $this->navigationFactory->create($nav, []);

        self::assertSame(['ROLE_USER'], $root->getFirstChild()->getRoles());
        self::assertSame(['ROLE_ADMIN'], $root->getLastChild()->getRoles());
    }

    #[Test]
    public function nonCachedNavigationIsBuiltOnEachCall(): void
    {
        $nav = new class extends AbstractNavigation {
            public int $buildCount = 0;

            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                ++$this->buildCount;
                $builder->add('item');
            }
        };

        $this->navigationFactory->create($nav, []);
        $this->navigationFactory->create($nav, []);

        self::assertSame(2, $nav->buildCount);
    }

    #[Test]
    public function itemTreeSurvivesSerializationRoundTrip(): void
    {
        $nav = new class extends AbstractNavigation {
            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                $builder
                    ->add('parent', ['label' => 'Parent'], section: true)
                    ->children()
                        ->add('child', ['label' => 'Child'])
                    ->end();
            }
        };

        $root = $this->navigationFactory->create($nav, []);
        /** @var \ChamberOrchestra\MenuBundle\Menu\ItemInterface $restored */
        $restored = \unserialize(\serialize($root));

        $parent = $restored->getFirstChild();
        self::assertSame('Parent', $parent->getLabel());
        self::assertTrue($parent->isSection());
        self::assertSame('child', $parent->getFirstChild()->getName());
        self::assertSame('Child', $parent->getFirstChild()->getLabel());
    }

    #[Test]
    public function labelExtensionFallsBackToKeyWhenLabelMissing(): void
    {
        $nav = new class extends AbstractNavigation {
            public function build(MenuBuilderInterface $builder, array $options = []): void
            {
                $builder->add('dashboard'); // no label option
            }
        };

        $root = $this->navigationFactory->create($nav, []);

        // LabelExtension sets label from 'key' (= name) when label is absent
        self::assertSame('dashboard', $root->getFirstChild()->getLabel());
    }
}
