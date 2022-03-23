<?php declare(strict_types=1);
/**
 * PimcoreDevkitBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreDevkitBundle\Model;

use Lukaschel\PimcoreDevkitBundle\Generator\Generator;
use Lukaschel\PimcoreDevkitBundle\PimcoreDevkitBundle;
use Lukaschel\PimcoreDevkitBundle\Traits\StringTransformationTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ModelEntity
 */
abstract class ModelEntity implements ModelInterface
{
    use StringTransformationTrait;

    /**
     * @var string
     */
    protected $model;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $bundleName;

    /**
     * @var string
     */
    protected $rawBundleName;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Generator
     */
    protected $generator;

    /**
     * @var string
     */
    protected $skeletonDir;

    /**
     * @var array
     */
    protected $templateMapping = [];

    /**
     * @var array
     */
    protected $appendingTemplateMapping = [];

    /**
     * @var array
     */
    protected $additionalParameter = [];

    /**
     * ModelEntity constructor.
     *
     * @param OutputInterface    $output
     * @param ContainerInterface $container
     */
    public function __construct(OutputInterface $output, ContainerInterface $container)
    {
        $this->output = $output;
        $this->container = $container;
        $this->skeletonDir = dirname(__DIR__) . '/Resources/skeleton/' . $this->getModel();
        $this->generator = new Generator();
    }

    /**
     * @return string
     */
    public function getSubDir(): string
    {
        return PimcoreDevkitBundle::SUB_DIR;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param string $model
     */
    public function setModel(string $model)
    {
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getBundleName(): string
    {
        return $this->bundleName;
    }

    /**
     * @param string $bundleName
     */
    public function setBundleName(string $bundleName): void
    {
        $this->bundleName = $bundleName;
        $this->setRawBundleName(preg_replace('#Bundle#i', '', $bundleName));
    }

    /**
     * @return string
     */
    public function getRawBundleName(): string
    {
        return $this->rawBundleName;
    }

    /**
     * @param string $rawBundleName
     */
    public function setRawBundleName(string $rawBundleName): void
    {
        $this->rawBundleName = $rawBundleName;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @return Generator
     */
    public function getGenerator(): Generator
    {
        return $this->generator;
    }

    /**
     * @param Generator $generator
     */
    public function setGenerator(Generator $generator): void
    {
        $this->generator = $generator;
    }

    /**
     * @return string
     */
    public function getSkeletonDir(): string
    {
        return $this->skeletonDir;
    }

    /**
     * @param string $skeletonDir
     */
    public function setSkeletonDir(string $skeletonDir): void
    {
        $this->skeletonDir = $skeletonDir;
    }

    /**
     * @return array
     */
    public function getTemplateMapping(): array
    {
        return $this->templateMapping;
    }

    /**
     * @param array $templateMapping
     */
    public function setTemplateMapping(array $templateMapping): void
    {
        $this->templateMapping = $templateMapping;
    }

    /**
     * @param array $templateMapping
     */
    public function addTemplateMapping(array $templateMapping): void
    {
        $this->templateMapping = array_merge($templateMapping, $this->templateMapping);
    }

    public function resetTemplateMapping(): void
    {
        $this->templateMapping = [];
    }

    /**
     * @return array
     */
    public function getAppendingTemplateMapping(): array
    {
        return $this->appendingTemplateMapping;
    }

    /**
     * @param array $appendingTemplateMapping
     */
    public function setAppendingTemplateMapping(array $appendingTemplateMapping): void
    {
        $this->appendingTemplateMapping = $appendingTemplateMapping;
    }

    /**
     * @param array $appendingTemplateMapping
     */
    public function addAppendingTemplateMapping(array $appendingTemplateMapping): void
    {
        $this->appendingTemplateMapping = array_merge($appendingTemplateMapping, $this->appendingTemplateMapping);
    }

    public function resetAppendingTemplateMapping(): void
    {
        $this->appendingTemplateMapping = [];
    }

    /**
     * @return array
     */
    public function getAdditionalParameter(): array
    {
        return $this->additionalParameter;
    }

    /**
     * @param array $additionalParameter
     */
    public function setAdditionalParameter(array $additionalParameter): void
    {
        $this->additionalParameter = $additionalParameter;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        $parameters = array_merge($this->getObjectVars(), $this->additionalParameter);
        foreach ($parameters as $key => $value) {
            $parameters[$this->hyphen($key, '_')] = $value;
        }

        return $parameters;
    }

    /**
     * @param InputInterface $input
     *
     * @return $this|null
     */
    public function create(InputInterface $input): ?self
    {
        if (empty($input->getOptions())) {
            return null;
        }

        foreach ($input->getOptions() as $key => $value) {
            $method = 'set' . $this->camelize($key, true);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getObjectVars(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function getRelativePath(string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $path = explode('/', ltrim(rtrim($path, '/'), '/'));

        return str_repeat('../', sizeof($path));
    }
}
