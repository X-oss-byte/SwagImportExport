<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\FileIO;

use SwagImportExport\Components\Converter\XmlConverter;
use SwagImportExport\Components\Utils\FileHelper;

/**
 * This class is responsible to generate XML file or portions of an XML file on the hard disk.
 * The input data must be in php array forming a tree-like structure
 */
class XmlFileWriter implements FileWriter
{
    private const FORMAT = 'xml';

    private bool $treeStructure = true;

    private XmlConverter $xmlConvertor;

    private FileHelper $fileHelper;

    public function __construct(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
        $this->xmlConvertor = new XmlConverter();
    }

    public function supports(string $format): bool
    {
        return $format === self::FORMAT;
    }

    /**
     * Writes the header data in the file. The header data should be in a tree-like structure.
     *
     * @throws \Exception
     */
    public function writeHeader(string $fileName, array $headerData): void
    {
        $dataParts = $this->splitHeaderFooter($headerData);
        $this->getFileHelper()->writeStringToFile($fileName, $dataParts[0]);
    }

    /**
     * Writes records in the file. The data must be a tree-like structure.
     * The header of the file must be already written on the harddisk,
     * otherwise the xml fill have an invalid format.
     *
     * @throws \Exception
     */
    public function writeRecords(string $fileName, array $treeData): void
    {
        // converting the whole template tree without the iteration part
        $encodedTreeData = $this->xmlConvertor->_encode($treeData);

        $this->getFileHelper()->writeStringToFile($fileName, \trim($encodedTreeData), \FILE_APPEND);
    }

    /**
     * Writes the footer data in the file. These are usually some closing tags -
     * they should be in a tree-like structure.
     *
     * @throws \Exception
     */
    public function writeFooter(string $fileName, ?array $footerData): void
    {
        $dataParts = $this->splitHeaderFooter($footerData ?? []);

        $data = $dataParts[1] ?? null;

        $this->getFileHelper()->writeStringToFile($fileName, $data, \FILE_APPEND);
    }

    public function hasTreeStructure(): bool
    {
        return $this->treeStructure;
    }

    public function getFileHelper(): FileHelper
    {
        return $this->fileHelper;
    }

    /**
     * Splitting the tree into two parts
     *
     * @throws \Exception
     *
     * @return array<string>
     */
    private function splitHeaderFooter(array $data): array
    {
        // converting the whole template tree without the iteration part
        $encodedData = $this->xmlConvertor->encode($data);

        // splitting the tree in to two parts
        return \explode('<_currentMarker></_currentMarker>', $encodedData);
    }
}
