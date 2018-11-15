<?php

/**
 * This file is part of the pd-admin pd-menu package.
 *
 * @package     pd-menu
 *
 * @license     LICENSE
 * @author      Kerem APAYDIN <kerem@apaydin.me>
 *
 * @link        https://github.com/appaydin/pd-menu
 */

namespace Pd\MenuBundle\Twig;

use Pd\MenuBundle\Builder\ItemInterface;
use Pd\MenuBundle\Builder\ItemProcessInterface;
use Pd\MenuBundle\Builder\MenuInterface;
use Pd\MenuBundle\Render\RenderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Menu Twig Extension.
 *
 * @author Kerem APAYDIN <kerem@apaydin.me>
 */
class MenuExtension extends \Twig_Extension
{
    /**
     * @var RenderInterface
     */
    private $engine;

    /**
     * @var ItemProcessInterface
     */
    private $itemProcess;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Default Menu Options.
     *
     * @var array
     */
    private $defaultOptions = [
        'template' => '@PdMenu/Default/menu.html.twig',
        'depth' => null,
        'currentClass' => 'active',
        'trans_domain' => null
    ];

    /**
     * MenuExtension constructor.
     *
     * @param RenderInterface      $engine
     * @param ItemProcessInterface $itemProcess
     * @param ContainerInterface   $container
     */
    public function __construct(RenderInterface $engine, ItemProcessInterface $itemProcess, ContainerInterface $container, TranslatorInterface $translator)
    {
        $this->engine = $engine;
        $this->itemProcess = $itemProcess;
        $this->container = $container;
        $this->translator = $translator;
    }

    /**
     * Create Twig Function.
     *
     * @return array|\Twig_Function[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('pd_menu_render', [$this, 'renderMenu'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('pd_menu_get', [$this, 'getMenu']),
            new \Twig_SimpleFunction('arrayToAttr', [$this, 'arrayToAttr'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render Menu.
     *
     * @param string $menuClass
     * @param array  $options
     *
     * @return string
     */
    public function renderMenu(string $menuClass = '', $options = []): string
    {
        // Merge Options
        $options = array_merge($this->defaultOptions, $options);

        // Get Menu
        $menu = $this->container->has($menuClass) ? $this->container->get($menuClass) : new $menuClass();

        // Render
        if ($menu instanceof MenuInterface) {
            // Process Menu
            $menu = $this->itemProcess->processMenu($menu->createMenu($options), $options);

            return $this->engine->render($menu, $options);
        }

        return false;
    }

    /**
     * Get Menu Array.
     *
     * @param string $menuClass
     * @param array  $options
     *
     * @return ItemInterface|bool
     */
    public function getMenu(string $menuClass, $options = []): ItemInterface
    {
        // Merge Options
        $options = array_merge($this->defaultOptions, $options);

        // Get Menu
        $menu = $this->container->has($menuClass) ? $this->container->get($menuClass) : new $menuClass();

        // Render
        if ($menu instanceof MenuInterface) {
            return $this->itemProcess->processMenu($menu->createMenu($options), $options);
        }

        return false;
    }

    /**
     * Array to Html Attr Convert.
     *
     * @param array $array
     * @param array $append
     *
     * @return string
     */
    public function arrayToAttr(array $array = [], array $append = [], array $options = [])
    {
        $array = array_merge_recursive($array, $append);
        $attr = '';

        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $value = implode(' ', $value);
            }

            if ('title' === mb_strtolower($key)) {
                if (!isset($array['title_translate'])) {
                    $value = $this->translator->trans($value, [], $options['trans_domain'] ?? null);
                }
            }

            $attr .= sprintf('%s="%s"', $key, $value);
        }

        return $attr;
    }
}
