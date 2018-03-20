<?php declare(strict_types=1);

namespace Shopware;

use Shopware\Framework\Doctrine\DatabaseConnector;
use Shopware\Framework\Framework;
use Shopware\Framework\Plugin\Plugin;
use Shopware\Framework\Plugin\PluginCollection;
use Shopware\Storefront\Theme\Theme;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * @var \PDO
     */
    private static $connection;

    /**
     * @var PluginCollection
     */
    private static $plugins;

    /**
     * @var array
     */
    private $themes = [];

    /**
     * {@inheritdoc}
     */
    public function __construct($environment, $debug)
    {
        parent::__construct($environment, $debug);

        self::$plugins = new PluginCollection();
    }

    public function registerBundles()
    {
        $bundles = [
            // symfony
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new \Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new \Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),

            // shopware
            new \Shopware\Framework\Framework(),
            new \Shopware\Version\Version(),
            new \Shopware\Rest\Rest(),
            new \Shopware\Api\Api(),
            new \Shopware\Cart\Cart(),
            new \Shopware\CartBridge\CartBridge(),
            new \Shopware\Context\Context(),
            new \Shopware\Administration\Administration(),
            new \Shopware\Translation\Translation(),
            new \Shopware\Filesystem\Filesystem(),
            new \Shopware\DbalIndexing\DbalIndexing(),
            new \Shopware\StorefrontApi\StorefrontApi(),
            new \Shopware\Payment\Payment(),

            // customization
            new \Shopware\Category\Category(),
            new \Shopware\Media\Media(),
            new \Shopware\Product\Product(),
            new \Shopware\Seo\Seo(),
        ];

        // debug
        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new \Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new \Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new \Shopware\Traceable\Traceable();
        }

        // themes and plugins
        $bundles = array_merge($bundles, $this->themes);
        $bundles = array_merge($bundles, self::$plugins->getActivePlugins());

        return $bundles;
    }

    public function boot($withPlugins = true)
    {
        if ($this->booted === true) {
            return;
        }

        if ($withPlugins) {
            $this->initializePluginSystem();
        }

        // init bundles
        $this->initializeBundles();

        // init container
        $this->initializeContainer();

        foreach ($this->getBundles() as $bundle) {
            $bundle->setContainer($this->container);
            $bundle->boot();
        }

        $this->booted = true;
    }

    /**
     * @return PluginCollection
     */
    public static function getPlugins(): PluginCollection
    {
        return self::$plugins;
    }

    /**
     * @return Theme[]
     */
    public function getThemes(): array
    {
        return array_filter($this->bundles, function ($bundle) {
            return $bundle instanceof Theme;
        });
    }

    public static function getConnection(): ?\PDO
    {
        return self::$connection;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return sprintf(
            '%s/var/cache/%s_%s',
            $this->getProjectDir(),
            $this->getEnvironment(),
            Framework::REVISION
        );
    }

    public function getLogDir()
    {
        return $this->getProjectDir() . '/var/logs';
    }

    public function getPluginDir()
    {
        return $this->getProjectDir() . '/custom/plugins';
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }

    /**
     * {@inheritdoc}
     */
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        $activePluginMeta = [];

        foreach (self::getPlugins()->getActivePlugins() as $namespace => $plugin) {
            $pluginName = $plugin->getName();
            $activePluginMeta[$pluginName] = [
                'name' => $pluginName,
                'path' => $plugin->getPath(),
            ];
        }

        return array_merge(
            $parameters,
            [
                'kernel.plugin_dir' => $this->getPluginDir(),
                'kernel.active_plugins' => $activePluginMeta,
            ]
        );
    }

    protected function getContainerClass()
    {
        $pluginHash = sha1(implode('', array_keys(self::getPlugins()->getActivePlugins())));

        return $this->name
            . ucfirst($this->environment)
            . $pluginHash
            . ($this->debug ? 'Debug' : '')
            . 'ProjectContainer';
    }

    protected function initializeThemes(): array
    {
        return $this->themes = [
            new \Shopware\Storefront\Storefront(),
        ];
    }

    protected function initializePlugins(): void
    {
        $stmt = self::$connection->query(
            'SELECT `name` FROM `plugin` WHERE `active` = 1 AND `installation_date` IS NOT NULL'
        );
        $activePlugins = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $finder = new Finder();
        $iterator = $finder->directories()->depth(0)->in($this->getPluginDir())->getIterator();

        foreach ($iterator as $pluginDir) {
            $pluginName = $pluginDir->getFilename();
            $pluginFile = $pluginDir->getPath() . '/' . $pluginName . '/' . $pluginName . '.php';
            if (!is_file($pluginFile)) {
                continue;
            }

            $namespace = $pluginName;
            $className = '\\' . $namespace . '\\' . $pluginName;

            if (!class_exists($className)) {
                throw new \RuntimeException(
                    sprintf('Unable to load class %s for plugin %s in file %s', $className, $pluginName, $pluginFile)
                );
            }

            $isActive = in_array($pluginName, $activePlugins, true);

            /** @var Plugin $plugin */
            $plugin = new $className($isActive);

            if (!$plugin instanceof Plugin) {
                throw new \RuntimeException(
                    sprintf('Class %s must extend %s in file %s', get_class($plugin), Plugin::class, $pluginFile)
                );
            }

            self::$plugins->add($plugin);
        }
    }

    private function initializePluginSystem(): void
    {
        self::$connection = DatabaseConnector::createPdoConnection();

        $this->initializePlugins();
        $this->initializeThemes();
    }
}
