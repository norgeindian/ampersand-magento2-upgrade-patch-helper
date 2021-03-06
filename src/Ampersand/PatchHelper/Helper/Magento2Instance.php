<?php
namespace Ampersand\PatchHelper\Helper;

use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\View\Design\FileResolution\Fallback\Resolver\Minification;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Design\Theme\ThemeList;

class Magento2Instance
{
    /** @var \Magento\Framework\App\Http $app */
    private $app;

    /** @var \Magento\Framework\ObjectManagerInterface $objectManager */
    private $objectManager;

    /** @var \Magento\Framework\ObjectManager\ConfigInterface */
    private $config;

    /** @var \Magento\Theme\Model\Theme[] */
    private $customThemes;

    /** @var  \Magento\Framework\View\Design\FileResolution\Fallback\Resolver\Minification */
    private $minificationResolver;

    /** @var  array */
    private $listOfXmlFiles = [];

    /** @var  array */
    private $listOfHtmlFiles = [];

    /** @var  array */
    private $areaConfig = [];

    public function __construct($path)
    {
        require rtrim($path, '/') . '/app/bootstrap.php';

        /** @var \Magento\Framework\App\Bootstrap $bootstrap */
        $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
        $this->app = $bootstrap->createApplication(\Magento\Framework\App\Http::class)->launch();
        $objectManager = $bootstrap->getObjectManager();
        $this->objectManager = $objectManager;

        $this->config = $objectManager->get(ConfigInterface::class);

        // Frontend theme
        $this->minificationResolver = $objectManager->get(Minification::class);

        $themeList = $objectManager->get(ThemeList::class);
        foreach ($themeList as $theme) {
            // ignore non-frontend themes
            if ($theme->getArea() !== DesignInterface::DEFAULT_AREA) {
                continue;
            }
            // ignore Magento themes
            if (strpos($theme->getCode(), 'Magento/') === 0) {
                continue;
            }

            $this->customThemes[] = $theme;
        }
        if (empty($this->customThemes)) {
            throw new \Exception('Unable to find custom theme(s)');
        }

        // Config per area
        $configLoader = $objectManager->get(\Magento\Framework\ObjectManager\ConfigLoaderInterface::class);
        $this->areaConfig['adminhtml'] = $configLoader->load('adminhtml');
        $this->areaConfig['frontend'] = $configLoader->load('frontend');
        $this->areaConfig['global'] = $configLoader->load('global');

        // All xml files
        $dirList = $objectManager->get(\Magento\Framework\Filesystem\DirectoryList::class);
        $this->listXmlFiles([$dirList->getPath('app'), $dirList->getRoot() . '/vendor']);
        $this->listHtmlFiles([$dirList->getPath('app'), $dirList->getRoot() . '/vendor']);
    }

    /**
     * @return \Magento\Framework\ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Loads list of all xml files into memory to prevent repeat scans of the file system
     *
     * @param $directories
     */
    private function listXmlFiles($directories)
    {
        foreach ($directories as $dir) {
            $files = array_filter(explode(PHP_EOL, shell_exec("find {$dir} -name \"*.xml\"")));
            $this->listOfXmlFiles = array_merge($this->listOfXmlFiles, $files);
        }

        sort($this->listOfXmlFiles);
    }

    /**
     * Loads list of all html files into memory to prevent repeat scans of the file system
     *
     * @param $directories
     */
    private function listHtmlFiles($directories)
    {
        foreach ($directories as $dir) {
            $files = array_filter(explode(PHP_EOL, shell_exec("find {$dir} -name \"*.html\"")));
            $this->listOfHtmlFiles = array_merge($this->listOfHtmlFiles, $files);
        }

        sort($this->listOfHtmlFiles);
    }

    /**
     * @return array
     */
    public function getListOfHtmlFiles()
    {
        return $this->listOfHtmlFiles;
    }

    /**
     * @return array
     */
    public function getListOfXmlFiles()
    {
        return $this->listOfXmlFiles;
    }

    /**
     * @return Minification
     */
    public function getMinificationResolver()
    {
        return $this->minificationResolver;
    }

    /**
     * @return \Magento\Theme\Model\Theme
     */
    public function getCustomThemes()
    {
        return  $this->customThemes;
    }

    /**
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getAreaConfig()
    {
        return $this->areaConfig;
    }
}
