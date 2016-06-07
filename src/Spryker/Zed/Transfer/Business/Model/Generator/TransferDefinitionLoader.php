<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Transfer\Business\Model\Generator;

use Zend\Config\Factory;
use Zend\Filter\Word\UnderscoreToCamelCase;

class TransferDefinitionLoader implements LoaderInterface
{

    const KEY_BUNDLE = 'bundle';
    const KEY_CONTAINING_BUNDLE = 'containing bundle';
    const KEY_TRANSFER = 'transfer';
    const TRANSFER_SCHEMA_SUFFIX = '.transfer.xml';

    /**
     * @var \Spryker\Zed\Transfer\Business\Model\Generator\FinderInterface
     */
    private $finder;

    /**
     * @var \Spryker\Zed\Transfer\Business\Model\Generator\DefinitionNormalizer
     */
    private $definitionNormalizer;

    /**
     * @var array
     */
    private $transferDefinitions = [];

    /**
     * @param \Spryker\Zed\Transfer\Business\Model\Generator\FinderInterface $finder
     * @param \Spryker\Zed\Transfer\Business\Model\Generator\DefinitionNormalizer $normalizer
     */
    public function __construct(FinderInterface $finder, DefinitionNormalizer $normalizer)
    {
        $this->finder = $finder;
        $this->definitionNormalizer = $normalizer;
    }

    /**
     * @return array
     */
    public function getDefinitions()
    {
        $this->loadDefinitions();
        $this->transferDefinitions = $this->definitionNormalizer->normalizeDefinitions(
            $this->transferDefinitions
        );

        return $this->transferDefinitions;
    }

    /**
     * @return array
     */
    private function loadDefinitions()
    {
        $xmlTransferDefinitions = $this->finder->getXmlTransferDefinitionFiles();
        foreach ($xmlTransferDefinitions as $xmlTransferDefinition) {
            $bundle = $this->getBundleFromPathName($xmlTransferDefinition->getFilename());
            $containingBundle = $this->getContainingBundleFromPathName($xmlTransferDefinition->getPathname());
            $definition = Factory::fromFile($xmlTransferDefinition->getPathname(), true)->toArray();
            $this->addDefinition($definition, $bundle, $containingBundle);
        }
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getBundleFromPathName($fileName)
    {
        $filter = new UnderscoreToCamelCase();

        return $filter->filter(str_replace(self::TRANSFER_SCHEMA_SUFFIX, '', $fileName));
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    private function getContainingBundleFromPathName($filePath)
    {
        $pathParts = explode(DIRECTORY_SEPARATOR, $filePath);
        $sharedDirectoryPosition = array_search('Shared', array_values($pathParts));

        $containingBundle = $pathParts[$sharedDirectoryPosition + 1];

        return $containingBundle;
    }

    /**
     * @param array $definition
     * @param string $bundle
     * @param string $containingBundle
     *
     * @return void
     */
    private function addDefinition(array $definition, $bundle, $containingBundle)
    {
        if (isset($definition[self::KEY_TRANSFER][0])) {
            foreach ($definition[self::KEY_TRANSFER] as $transfer) {
                $transfer[self::KEY_BUNDLE] = $bundle;
                $transfer[self::KEY_CONTAINING_BUNDLE] = $containingBundle;

                $this->transferDefinitions[] = $transfer;
            }
        } else {
            $transfer = $definition[self::KEY_TRANSFER];
            $transfer[self::KEY_BUNDLE] = $bundle;
            $transfer[self::KEY_CONTAINING_BUNDLE] = $containingBundle;
            $this->transferDefinitions[] = $transfer;
        }
    }

}
