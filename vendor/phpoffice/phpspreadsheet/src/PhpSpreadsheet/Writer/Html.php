<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use HTMLPurifier;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Document\Properties;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Shared\Font as SharedFont;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Html extends BaseWriter
{
    /**
     * Spreadsheet object.
     *
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * Sheet index to write.
     *
     * @var null|int
     */
    private $sheetIndex = 0;

    /**
     * Images root.
     *
     * @var string
     */
    private $imagesRoot = '';

    /**
     * embed images, or link to images.
     *
     * @var bool
     */
    protected $embedImages = false;

    /**
     * Use inline CSS?
     *
     * @var bool
     */
    private $useInlineCss = false;

    /**
     * Use embedded CSS?
     *
     * @var bool
     */
    private $useEmbeddedCSS = true;

    /**
     * Array of CSS styles.
     *
     * @var array
     */
    private $cssStyles;

    /**
     * Array of column widths in points.
     *
     * @var array
     */
    private $columnWidths;

    /**
     * Default font.
     *
     * @var Font
     */
    private $defaultFont;

    /**
     * Flag whether spans have been calculated.
     *
     * @var bool
     */
    private $spansAreCalculated = false;

    /**
     * Excel cells that should not be written as HTML cells.
     *
     * @var array
     */
    private $isSpannedCell = [];

    /**
     * Excel cells that are upper-left corner in a cell merge.
     *
     * @var array
     */
    private $isBaseCell = [];

    /**
     * Excel rows that should not be written as HTML rows.
     *
     * @var array
     */
    private $isSpannedRow = [];

    /**
     * Is the current writer creating PDF?
     *
     * @var bool
     */
    protected $isPdf = false;

    /**
     * Is the current writer creating mPDF?
     *
     * @var bool
     */
    protected $isMPdf = false;

    /**
     * Generate the Navigation block.
     *
     * @var bool
     */
    private $generateSheetNavigationBlock = true;

    /**
     * Callback for editing generated html.
     *
     * @var null|callable
     */
    private $editHtmlCallback;

    /**
     * Create a new HTML.
     */
    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
        $this->defaultFont = $this->spreadsheet->getDefaultStyle()->getFont();
    }

    /**
     * Save Spreadsheet to file.
     *
     * @param resource|string $filename
     */
    public function save($filename, int $flags = 0): void
    {
        $this->processFlags($flags);

        // Open file
        $this->openFileHandle($filename);

        // Write html
        fwrite($this->fileHandle, $this->generateHTMLAll());

        // Close file
        $this->maybeCloseFileHandle();
    }

    /**
     * Save Spreadsheet as html to variable.
     *
     * @return string
     */
    public function generateHtmlAll()
    {
        // garbage collect
        $this->spreadsheet->garbageCollect();

        $saveDebugLog = Calculation::getInstance($this->spreadsheet)->getDebugLog()->getWriteDebugLog();
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog(false);
        $saveArrayReturnType = Calculation::getArrayReturnType();
        Calculation::setArrayReturnType(Calculation::RETURN_ARRAY_AS_VALUE);

        // Build CSS
        $this->buildCSS(!$this->useInlineCss);

        $html = '';

        // Write headers
        $html .= $this->generateHTMLHeader(!$this->useInlineCss);

        // Write navigation (tabs)
        if ((!$this->isPdf) && ($this->generateSheetNavigationBlock)) {
            $html .= $this->generateNavigation();
        }

        // Write data
        $html .= $this->generateSheetData();

        // Write footer
        $html .= $this->generateHTMLFooter();
        $callback = $this->editHtmlCallback;
        if ($callback) {
            $html = $callback($html);
        }

        Calculation::setArrayReturnType($saveArrayReturnType);
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog($saveDebugLog);

        return $html;
    }

    /**
     * Set a callback to edit the entire HTML.
     *
     * The callback must accept the HTML as string as first parameter,
     * and it must return the edited HTML as string.
     */
    public function setEditHtmlCallback(?callable $callback): void
    {
        $this->editHtmlCallback = $callback;
    }

    /**
     * Map VAlign.
     *
     * @param string $vAlign Vertical alignment
     *
     * @return string
     */
    private function mapVAlign($vAlign)
    {
        return Alignment::VERTICAL_ALIGNMENT_FOR_HTML[$vAlign] ?? '';
    }

    /**
     * Map HAlign.
     *
     * @param string $hAlign Horizontal alignment
     *
     * @return string
     */
    private function mapHAlign($hAlign)
    {
        return Alignment::HORIZONTAL_ALIGNMENT_FOR_HTML[$hAlign] ?? '';
    }

    const BORDER_ARR = [
        Border::BORDER_NONE => 'none',
        Border::BORDER_DASHDOT => '1px dashed',
        Border::BORDER_DASHDOTDOT => '1px dotted',
        Border::BORDER_DASHED => '1px dashed',
        Border::BORDER_DOTTED => '1px dotted',
        Border::BORDER_DOUBLE => '3px double',
        Border::BORDER_HAIR => '1px solid',
        Border::BORDER_MEDIUM => '2px solid',
        Border::BORDER_MEDIUMDASHDOT => '2px dashed',
        Border::BORDER_MEDIUMDASHDOTDOT => '2px dotted',
        Border::BORDER_SLANTDASHDOT => '2px dashed',
        Border::BORDER_THICK => '3px solid',
    ];

    /**
     * Map border style.
     *
     * @param int|string $borderStyle Sheet index
     *
     * @return string
     */
    private function mapBorderStyle($borderStyle)
    {
        return array_key_exists($borderStyle, self::BORDER_ARR) ? self::BORDER_ARR[$borderStyle] : '1px solid';
    }

    /**
     * Get sheet index.
     */
    public function getSheetIndex(): ?int
    {
        return $this->sheetIndex;
    }

    /**
     * Set sheet index.
     *
     * @param int $sheetIndex Sheet index
     *
     * @return $this
     */
    public function setSheetIndex($sheetIndex)
    {
        $this->sheetIndex = $sheetIndex;

        return $this;
    }

    /**
     * Get sheet index.
     *
     * @return bool
     */
    public function getGenerateSheetNavigationBlock()
    {
        return $this->generateSheetNavigationBlock;
    }

    /**
     * Set sheet index.
     *
     * @param bool $generateSheetNavigationBlock Flag indicating whether the sheet navigation block should be generated or not
     *
     * @return $this
     */
    public function setGenerateSheetNavigationBlock($generateSheetNavigationBlock)
    {
        $this->generateSheetNavigationBlock = (bool) $generateSheetNavigationBlock;

        return $this;
    }

    /**
     * Write all sheets (resets sheetIndex to NULL).
     *
     * @return $this
     */
    public function writeAllSheets()
    {
        $this->sheetIndex = null;

        return $this;
    }

    private static function generateMeta(?string $val, string $desc): string
    {
        return ($val || $val === '0')
            ? ('      <meta name="' . $desc . '" content="' . htmlspecialchars($val, Settings::htmlEntityFlags()) . '" />' . PHP_EOL)
            : '';
    }

    public const BODY_LINE = '  <body>' . PHP_EOL;

    private const CUSTOM_TO_META = [
        Properties::PROPERTY_TYPE_BOOLEAN => 'bool',
        Properties::PROPERTY_TYPE_DATE => 'date',
        Properties::PROPERTY_TYPE_FLOAT => 'float',
        Properties::PROPERTY_TYPE_INTEGER => 'int',
        Properties::PROPERTY_TYPE_STRING => 'string',
    ];

    /**
     * Generate HTML header.
     *
     * @param bool $includeStyles Include styles?
     *
     * @return string
     */
    public function generateHTMLHeader($includeStyles = false)
    {
        // Construct HTML
        $properties = $this->spreadsheet->getProperties();
        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . PHP_EOL;
        $html .= '<html xmlns="http://www.w3.org/1999/xhtml">' . PHP_EOL;
        $html .= '  <head>' . PHP_EOL;
        $html .= '      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . PHP_EOL;
        $html .= '      <meta name="generator" content="PhpSpreadsheet, https://github.com/PHPOffice/PhpSpreadsheet" />' . PHP_EOL;
        $html .= '      <title>' . htmlspecialchars($properties->getTitle(), Settings::htmlEntityFlags()) . '</title>' . PHP_EOL;
        $html .= self::generateMeta($properties->getCreator(), 'author');
        $html .= self::generateMeta($properties->getTitle(), 'title');
        $html .= self::generateMeta($properties->getDescription(), 'description');
        $html .= self::generateMeta($properties->getSubject(), 'subject');
        $html .= self::generateMeta($properties->getKeywords(), 'keywords');
        $html .= self::generateMeta($properties->getCategory(), 'category');
        $html .= self::generateMeta($properties->getCompany(), 'company');
        $html .= self::generateMeta($properties->getManager(), 'manager');
        $html .= self::generateMeta($properties->getLastModifiedBy(), 'lastModifiedBy');
        $date = Date::dateTimeFromTimestamp((string) $properties->getCreated());
        $date->setTimeZone(Date::getDefaultOrLocalTimeZone());
        $html .= self::generateMeta($date->format(DATE_W3C), 'created');
        $date = Date::dateTimeFromTimestamp((string) $properties->getModified());
        $date->setTimeZone(Date::getDefaultOrLocalTimeZone());
        $html .= self::generateMeta($date->format(DATE_W3C), 'modified');

        $customProperties = $properties->getCustomProperties();
        foreach ($customProperties as $customProperty) {
            $propertyValue = $properties->getCustomPropertyValue($customProperty);
            $propertyType = $properties->getCustomPropertyType($customProperty);
            $propertyQualifier = self::CUSTOM_TO_META[$propertyType] ?? null;
            if ($propertyQualifier !== null) {
                if ($propertyType === Properties::PROPERTY_TYPE_BOOLEAN) {
                    $propertyValue = $propertyValue ? '1' : '0';
                } elseif ($propertyType === Properties::PROPERTY_TYPE_DATE) {
                    $date = Date::dateTimeFromTimestamp((string) $propertyValue);
                    $date->setTimeZone(Date::getDefaultOrLocalTimeZone());
                    $propertyValue = $date->format(DATE_W3C);
                } else {
                    $propertyValue = (string) $propertyValue;
                }
                $html .= self::generateMeta($propertyValue, htmlspecialchars("custom.$propertyQualifier.$customProperty"));
            }
        }

        if (!empty($properties->getHyperlinkBase())) {
            $html .= '      <base href="' . htmlspecialchars($properties->getHyperlinkBase()) . '" />' . PHP_EOL;
        }

        $html .= $includeStyles ? $this->generateStyles(true) : $this->generatePageDeclarations(true);

        $html .= '  </head>' . PHP_EOL;
        $html .= '' . PHP_EOL;
        $html .= self::BODY_LINE;

        return $html;
    }

    private function generateSheetPrep(): array
    {
        // Ensure that Spans have been calculated?
        $this->calculateSpans();

        // Fetch sheets
        if ($this->sheetIndex === null) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets = [$this->spreadsheet->getSheet($this->sheetIndex)];
        }

        return $sheets;
    }

    private function generateSheetStarts(Worksheet $sheet, int $rowMin): array
    {
        // calculate start of <tbody>, <thead>
        $tbodyStart = $rowMin;
        $theadStart = $theadEnd = 0; // default: no <thead>    no </thead>
        if ($sheet->getPageSetup()->isRowsToRepeatAtTopSet()) {
            $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();

            // we can only support repeating rows that start at top row
            if ($rowsToRepeatAtTop[0] == 1) {
                $theadStart = $rowsToRepeatAtTop[0];
                $theadEnd = $rowsToRepeatAtTop[1];
                $tbodyStart = $rowsToRepeatAtTop[1] + 1;
            }
        }

        return [$theadStart, $theadEnd, $tbodyStart];
    }

    private function generateSheetTags(int $row, int $theadStart, int $theadEnd, int $tbodyStart): array
    {
        // <thead> ?
        $startTag = ($row == $theadStart) ? ('        <thead>' . PHP_EOL) : '';
        if (!$startTag) {
            $startTag = ($row == $tbodyStart) ? ('        <tbody>' . PHP_EOL) : '';
        }
        $endTag = ($row == $theadEnd) ? ('        </thead>' . PHP_EOL) : '';
        $cellType = ($row >= $tbodyStart) ? 'td' : 'th';

        return [$cellType, $startTag, $endTag];
    }

    /**
     * Generate sheet data.
     *
     * @return string
     */
    public function generateSheetData()
    {
        $sheets = $this->generateSheetPrep();

        // Construct HTML
        $html = '';

        // Loop all sheets
        $sheetId = 0;
        foreach ($sheets as $sheet) {
            // Write table header
            $html .= $this->generateTableHeader($sheet);

            // Get worksheet dimension
            [$min, $max] = explode(':', $sheet->calculateWorksheetDataDimension());
            [$minCol, $minRow] = Coordinate::indexesFromString($min);
            [$maxCol, $maxRow] = Coordinate::indexesFromString($max);

            [$theadStart, $theadEnd, $tbodyStart] = $this->generateSheetStarts($sheet, $minRow);

            // Loop through cells
            $row = $minRow - 1;
            while ($row++ < $maxRow) {
                [$cellType, $startTag, $endTag] = $this->generateSheetTags($row, $theadStart, $theadEnd, $tbodyStart);
                $html .= $startTag;

                // Write row if there are HTML table cells in it
                if (!isset($this->isSpannedRow[$sheet->getParent()->getIndex($sheet)][$row])) {
                    // Start a new rowData
                    $rowData = [];
                    // Loop through columns
                    $column = $minCol;
                    while ($column <= $maxCol) {
                        // Cell exists?
                        $cellAddress = Coordinate::stringFromColumnIndex($column) . $row;
                        $rowData[$column++] = ($sheet->getCellCollection()->has($cellAddress)) ? $cellAddress : '';
                    }
                    $html .= $this->generateRow($sheet, $rowData, $row - 1, $cellType);
                }

                $html .= $endTag;
            }
            --$row;
            $html .= $this->extendRowsForChartsAndImages($sheet, $row);

            // Write table footer
            $html .= $this->generateTableFooter();
            // Writing PDF?
            if ($this->isPdf && $this->useInlineCss) {
                if ($this->sheetIndex === null && $sheetId + 1 < $this->spreadsheet->getSheetCount()) {
                    $html .= '<div style="page-break-before:always" ></div>';
                }
            }

            // Next sheet
            ++$sheetId;
        }

        return $html;
    }

    /**
     * Generate sheet tabs.
     *
     * @return string
     */
    public function generateNavigation()
    {
        // Fetch sheets
        $sheets = [];
        if ($this->sheetIndex === null) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets[] = $this->spreadsheet->getSheet($this->sheetIndex);
        }

        // Construct HTML
        $html = '';

        // Only if there are more than 1 sheets
        if (count($sheets) > 1) {
            // Loop all sheets
            $sheetId = 0;

            $html .= '<ul class="navigation">' . PHP_EOL;

            foreach ($sheets as $sheet) {
                $html .= '  <li class="sheet' . $sheetId . '"><a href="#sheet' . $sheetId . '">' . htmlspecialchars($sheet->getTitle()) . '</a></li>' . PHP_EOL;
                ++$sheetId;
            }

            $html .= '</ul>' . PHP_EOL;
        }

        return $html;
    }

    /**
     * Extend Row if chart is placed after nominal end of row.
     * This code should be exercised by sample:
     * Chart/32_Chart_read_write_PDF.php.
     *
     * @param int $row Row to check for charts
     *
     * @return array
     */
    private function extendRowsForCharts(Worksheet $worksheet, int $row)
    {
        $rowMax = $row;
        $colMax = 'A';
        $anyfound = false;
        if ($this->includeCharts) {
            foreach ($worksheet->getChartCollection() as $chart) {
                if ($chart instanceof Chart) {
                    $anyfound = true;
                    $chartCoordinates = $chart->getTopLeftPosition();
                    $chartTL = Coordinate::coordinateFromString($chartCoordinates['cell']);
                    $chartCol = Coordinate::columnIndexFromString($chartTL[0]);
                    if ($chartTL[1] > $rowMax) {
                        $rowMax = $chartTL[1];
                        if ($chartCol > Coordinate::columnIndexFromString($colMax)) {
                            $colMax = $chartTL[0];
                        }
                    }
                }
            }
        }

        return [$rowMax, $colMax, $anyfound];
    }

    private function extendRowsForChartsAndImages(Worksheet $worksheet, int $row): string
    {
        [$rowMax, $colMax, $anyfound] = $this->extendRowsForCharts($worksheet, $row);

        foreach ($worksheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof Drawing && $drawing->getPath() === '') {
                continue;
            }
            $anyfound = true;
            $imageTL = Coordinate::coordinateFromString($drawing->getCoordinates());
            $imageCol = Coordinate::columnIndexFromString($imageTL[0]);
            if ($imageTL[1] > $rowMax) {
                $rowMax = $imageTL[1];
                if ($imageCol > Coordinate::columnIndexFromString($colMax)) {
                    $colMax = $imageTL[0];
                }
            }
        }

        // Don't extend rows if not needed
        if ($row === $rowMax || !$anyfound) {
            return '';
        }

        $html = '';
        ++$colMax;
        ++$row;
        while ($row <= $rowMax) {
            $html .= '<tr>';
            for ($col = 'A'; $col != $colMax; ++$col) {
                $htmlx = $this->writeImageInCell($worksheet, $col . $row);
                $htmlx .= $this->includeCharts ? $this->writeChartInCell($worksheet, $col . $row) : '';
                if ($htmlx) {
                    $html .= "<td class='style0' style='position: relative;'>$htmlx</td>";
                } else {
                    $html .= "<td class='style0'></td>";
                }
            }
            ++$row;
            $html .= '</tr>' . PHP_EOL;
        }

        return $html;
    }

    /**
     * Convert Windows file name to file protocol URL.
     *
     * @param string $filename file name on local system
     *
     * @return string
     */
    public static function winFileToUrl($filename, bool $mpdf = false)
    {
        // Windows filename
        if (substr($filename, 1, 2) === ':\\') {
            $protocol = $mpdf ? '' : 'file:///';
            $filename = $protocol . str_replace('\\', '/', $filename);
        }

        return $filename;
    }

    /**
     * Generate image tag in cell.
     *
     * @param Worksheet $worksheet \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
     * @param string $coordinates Cell coordinates
     *
     * @return string
     */
    private function writeImageInCell(Worksheet $worksheet, $coordinates)
    {
        // Construct HTML
        $html = '';

        // Write images
        foreach ($worksheet->getDrawingCollection() as $drawing) {
            if ($drawing->getCoordinates() != $coordinates) {
                continue;
            }
            $filedesc = $drawing->getDescription();
            $filedesc = $filedesc ? htmlspecialchars($filedesc, ENT_QUOTES) : 'Embedded image';
            if ($drawing instanceof Drawing && $drawing->getPath() !== '') {
                $filename = $drawing->getPath();

                // Strip off eventual '.'
                $filename = (string) preg_replace('/^[.]/', '', $filename);

                // Prepend images root
                $filename = $this->getImagesRoot() . $filename;

                // Strip off eventual '.' if followed by non-/
                $filename = (string) preg_replace('@^[.]([^/])@', '$1', $filename);

                // Convert UTF8 data to PCDATA
                $filename = htmlspecialchars($filename, Settings::htmlEntityFlags());

                $html .= PHP_EOL;
                $imageData = self::winFileToUrl($filename, $this->isMPdf);

                if ($this->embedImages || substr($imageData, 0, 6) === 'zip://') {
                    $imageData = 'data:,';
                    $picture = @file_get_contents($filename);
                    if ($picture !== false) {
                        $mimeContentType = (string) @mime_content_type($filename);
                        if (substr($mimeContentType, 0, 6) === 'image/') {
                            // base64 encode the binary data
                            $base64 = base64_encode($picture);
                            $imageData = 'data:' . $mimeContentType . ';base64,' . $base64;
                        }
                    }
                }

                $html .= '<img style="position: absolute; z-index: 1; left: ' .
                    $drawing->getOffsetX() . 'px; top: ' . $drawing->getOffsetY() . 'px; width: ' .
                    $drawing->getWidth() . 'px; height: ' . $drawing->getHeight() . 'px;" src="' .
                    $imageData . '" alt="' . $filedesc . '" />';
            } elseif ($drawing instanceof MemoryDrawing) {
                $imageResource = $drawing->getImageResource();
                if ($imageResource) {
                    ob_start(); //  Let's start output buffering.
                    imagepng($imageResource); //  This will normally output the image, but because of ob_start(), it won't.
                    $contents = (string) ob_get_contents(); //  Instead, output above is saved to $contents
                    ob_end_clean(); //  End the output buffer.

                    $dataUri = 'data:image/png;base64,' . base64_encode($contents);

                    //  Because of the nature of tables, width is more important than height.
                    //  max-width: 100% ensures that image doesnt overflow containing cell
                    //  width: X sets width of supplied image.
                    //  As a result, images bigger than cell will be contained and images smaller will not get stretched
                    $html .= '<img alt="' . $filedesc . '" src="' . $dataUri . '" style="max-width:100%;width:' . $drawing->getWidth() . 'px;left: ' .
                    $drawing->getOffsetX() . 'px; top: ' . $drawing->getOffsetY() . 'px;position: absolute; z-index: 1;" />';
                }
            }
        }

        return $html;
    }

    /**
     * Generate chart tag in cell.
     * This code should be exercised by sample:
     * Chart/32_Chart_read_write_PDF.php.
     */
    private function writeChartInCell(Worksheet $worksheet, string $coordinates): string
    {
        // Construct HTML
        $html = '';

        // Write charts
        foreach ($worksheet->getChartCollection() as $chart) {
            if ($chart instanceof Chart) {
                $chartCoordinates = $chart->getTopLeftPosition();
                if ($chartCoordinates['cell'] == $coordinates) {
                    $chartFileName = File::sysGetTempDir() . '/' . uniqid('', true) . '.png';
                    if (!$chart->render($chartFileName)) {
                        return '';
                    }

                    $html .= PHP_EOL;
                    $imageDetails = getimagesize($chartFileName) ?: [];
                    $filedesc = $chart->getTitle();
                    $filedesc = $filedesc ? $filedesc->getCaptionText() : '';
                    $filedesc = $filedesc ? htmlspecialchars($filedesc, ENT_QUOTES) : 'Embedded chart';
                    $picture = file_get_contents($chartFileName);
                    if ($picture !== false) {
                        $base64 = base64_encode($picture);
                        $imageData = 'data:' . $imageDetails['mime'] . ';base64,' . $base64;

                        $html .= '<img style="position: absolute; z-index: 1; left: ' . $chartCoordinates['xOffset'] . 'px; top: ' . $chartCoordinates['yOffset'] . 'px; width: ' . $imageDetails[0] . 'px; height: ' . $imageDetails[1] . 'px;" src="' . $imageData . '" alt="' . $filedesc . '" />' . PHP_EOL;
                    }
                    unlink($chartFileName);
                }
            }
        }

        // Return
        return $html;
    }

    /**
     * Generate CSS styles.
     *
     * @param bool $generateSurroundingHTML Generate surrounding HTML tags? (&lt;style&gt; and &lt;/style&gt;)
     *
     * @return string
     */
    public function generateStyles($generateSurroundingHTML = true)
    {
        // Build CSS
        $css = $this->buildCSS($generateSurroundingHTML);

        // Construct HTML
        $html = '';

        // Start styles
        if ($generateSurroundingHTML) {
            $html .= '    <style type="text/css">' . PHP_EOL;
            $html .= (array_key_exists('html', $css)) ? ('      html { ' . $this->assembleCSS($css['html']) . ' }' . PHP_EOL) : '';
        }

        // Write all other styles
        foreach ($css as $styleName => $styleDefinition) {
            if ($styleName != 'html') {
                $html .= '      ' . $styleName . ' { ' . $this->assembleCSS($styleDefinition) . ' }' . PHP_EOL;
            }
        }
        $html .= $this->generatePageDeclarations(false);

        // End styles
        if ($generateSurroundingHTML) {
            $html .= '    </style>' . PHP_EOL;
        }

        // Return
        return $html;
    }

    private function buildCssRowHeights(Worksheet $sheet, array &$css, int $sheetIndex): void
    {
        // Calculate row heights
        foreach ($sheet->getRowDimensions() as $rowDimension) {
            $row = $rowDimension->getRowIndex() - 1;

            // table.sheetN tr.rowYYYYYY { }
            $css['table.sheet' . $sheetIndex . ' tr.row' . $row] = [];

            if ($rowDimension->getRowHeight() != -1) {
                $pt_height = $rowDimension->getRowHeight();
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['height'] = $pt_height . 'pt';
            }
            if ($rowDimension->getVisible() === false) {
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['display'] = 'none';
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['visibility'] = 'hidden';
            }
        }
    }

    private function buildCssPerSheet(Worksheet $sheet, array &$css): void
    {
        // Calculate hash code
        $sheetIndex = $sheet->getParentOrThrow()->getIndex($sheet);
        $setup = $sheet->getPageSetup();
        if ($setup->getFitToPage() && $setup->getFitToHeight() === 1) {
            $css["table.sheet$sheetIndex"]['page-break-inside'] = 'avoid';
            $css["table.sheet$sheetIndex"]['break-inside'] = 'avoid';
        }

        // Build styles
        // Calculate column widths
        $sheet->calculateColumnWidths();

        // col elements, initialize
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn()) - 1;
        $column = -1;
        while ($column++ < $highestColumnIndex) {
            $this->columnWidths[$sheetIndex][$column] = 42; // approximation
            $css['table.sheet' . $sheetIndex . ' col.col' . $column]['width'] = '42pt';
        }

        // col elements, loop through columnDimensions and set width
        foreach ($sheet->getColumnDimensions() as $columnDimension) {
            $column = Coordinate::columnIndexFromString($columnDimension->getColumnIndex()) - 1;
            $width = SharedDrawing::cellDimensionToPixels($columnDimension->getWidth(), $this->defaultFont);
            $width = SharedDrawing::pixelsToPoints($width);
            if ($columnDimension->getVisible() === false) {
                $css['table.sheet' . $sheetIndex . ' .column' . $column]['display'] = 'none';
            }
            if ($width >= 0) {
                $this->columnWidths[$sheetIndex][$column] = $width;
                $css['table.sheet' . $sheetIndex . ' col.col' . $column]['width'] = $width . 'pt';
            }
        }

        // Default row height
        $rowDimension = $sheet->getDefaultRowDimension();

        // table.sheetN tr { }
        $css['table.sheet' . $sheetIndex . ' tr'] = [];

        if ($rowDimension->getRowHeight() == -1) {
            $pt_height = SharedFont::getDefaultRowHeightByFont($this->spreadsheet->getDefaultStyle()->getFont());
        } else {
            $pt_height = $rowDimension->getRowHeight();
        }
        $css['table.sheet' . $sheetIndex . ' tr']['height'] = $pt_height . 'pt';
        if ($rowDimension->getVisible() === false) {
            $css['table.sheet' . $sheetIndex . ' tr']['display'] = 'none';
            $css['table.sheet' . $sheetIndex . ' tr']['visibility'] = 'hidden';
        }

        $this->buildCssRowHeights($sheet, $css, $sheetIndex);
    }

    /**
     * Build CSS styles.
     *
     * @param bool $generateSurroundingHTML Generate surrounding HTML style? (html { })
     *
     * @return array
     */
    public function buildCSS($generateSurroundingHTML = true)
    {
        // Cached?
        if ($this->cssStyles !== null) {
            return $this->cssStyles;
        }

        // Ensure that spans have been calculated
        $this->calculateSpans();

        // Construct CSS
        $css = [];

        // Start styles
        if ($generateSurroundingHTML) {
            // html { }
            $css['html']['font-family'] = 'Calibri, Arial, Helvetica, sans-serif';
            $css['html']['font-size'] = '11pt';
            $css['html']['background-color'] = 'white';
        }

        // CSS for comments as found in LibreOffice
        $css['a.comment-indicator:hover + div.comment'] = [
            'background' => '#ffd',
            'position' => 'absolute',
            'display' => 'block',
            'border' => '1px solid black',
            'padding' => '0.5em',
        ];

        $css['a.comment-indicator'] = [
            'background' => 'red',
            'display' => 'inline-block',
            'border' => '1px solid black',
            'width' => '0.5em',
            'height' => '0.5em',
        ];

        $css['div.comment']['display'] = 'none';

        // table { }
        $css['table']['border-collapse'] = 'collapse';

        // .b {}
        $css['.b']['text-align'] = 'center'; // BOOL

        // .e {}
        $css['.e']['text-align'] = 'center'; // ERROR

        // .f {}
        $css['.f']['text-align'] = 'right'; // FORMULA

        // .inlineStr {}
        $css['.inlineStr']['text-align'] = 'left'; // INLINE

        // .n {}
        $css['.n']['text-align'] = 'right'; // NUMERIC

        // .s {}
        $css['.s']['text-align'] = 'left'; // STRING

        // Calculate cell style hashes
        foreach ($this->spreadsheet->getCellXfCollection() as $index => $style) {
            $css['td.style' . $index . ', th.style' . $index] = $this->createCSSStyle($style);
            //$css['th.style' . $index] = $this->createCSSStyle($style);
        }

        // Fetch sheets
        $sheets = [];
        if ($this->sheetIndex === null) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets[] = $this->spreadsheet->getSheet($this->sheetIndex);
        }

        // Build styles per sheet
        foreach ($sheets as $sheet) {
            $this->buildCssPerSheet($sheet, $css);
        }

        // Cache
        if ($this->cssStyles === null) {
            $this->cssStyles = $css;
        }

        // Return
        return $css;
    }

    /**
     * Create CSS style.
     *
     * @return array
     */
    private function createCSSStyle(Style $style)
    {
        // Create CSS
        return array_merge(
            $this->createCSSStyleAlignment($style->getAlignment()),
            $this->createCSSStyleBorders($style->getBorders()),
            $this->createCSSStyleFont($style->getFont()),
            $this->createCSSStyleFill($style->getFill())
        );
    }

    /**
     * Create CSS style.
     *
     * @return array
     */
    private function createCSSStyleAlignment(Alignment $alignment)
    {
        // Construct CSS
        $css = [];

        // Create CSS
        $verticalAlign = $this->mapVAlign($alignment->getVertical() ?? '');
        if ($verticalAlign) {
            $css['vertical-align'] = $verticalAlign;
        }
        $textAlign = $this->mapHAlign($alignment->getHorizontal() ?? '');
        if ($textAlign) {
            $css['text-align'] = $textAlign;
            if (in_array($textAlign, ['left', 'right'])) {
                $css['padding-' . $textAlign] = (string) ((int) $alignment->getIndent() * 9) . 'px';
            }
        }
        $rotation = $alignment->getTextRotation();
        if ($rotation !== 0 && $rotation !== Alignment::TEXTROTATION_STACK_PHPSPREADSHEET) {
            if ($this->isMPdf) {
                $css['text-rotate'] = "$rotation";
            } else {
                $css['transform'] = "rotate({$rotation}deg)";
            }
        }

        return $css;
    }

    /**
     * Create CSS style.
     *
     * @return array
     */
    private function createCSSStyleFont(Font $font)
    {
        // Construct CSS
        $css = [];

        // Create CSS
        if ($font->getBold()) {
            $css['font-weight'] = 'bold';
        }
        if ($font->getUnderline() != Font::UNDERLINE_NONE && $font->getStrikethrough()) {
            $css['text-decoration'] = 'underline line-through';
        } elseif ($font->getUnderline() != Font::UNDERLINE_NONE) {
            $css['text-decoration'] = 'underline';
        } elseif ($font->getStrikethrough()) {
            $css['text-decoration'] = 'line-through';
        }
        if ($font->getItalic()) {
            $css['font-style'] = 'italic';
        }

        $css['color'] = '#' . $font->getColor()->getRGB();
        $css['font-family'] = '\'' . htmlspecialchars((string) $font->getName(), ENT_QUOTES) . '\'';
        $css['font-size'] = $font->getSize() . 'pt';

        return $css;
    }

    /**
     * Create CSS style.
     *
     * @param Borders $borders Borders
     *
     * @return array
     */
    private function createCSSStyleBorders(Borders $borders)
    {
        // Construct CSS
        $css = [];

        // Create CSS
        $css['border-bottom'] = $this->createCSSStyleBorder($borders->getBottom());
        $css['border-top'] = $this->createCSSStyleBorder($borders->getTop());
        $css['border-left'] = $this->createCSSStyleBorder($borders->getLeft());
        $css['border-right'] = $this->createCSSStyleBorder($borders->getRight());

        return $css;
    }

    /**
     * Create CSS style.
     *
     * @param Border $border Border
     */
    private function createCSSStyleBorder(Border $border): string
    {
        //    Create CSS - add !important to non-none border styles for merged cells
        $borderStyle = $this->mapBorderStyle($border->getBorderStyle());

        return $borderStyle . ' #' . $border->getColor()->getRGB() . (($borderStyle == 'none') ? '' : ' !important');
    }

    /**
     * Create CSS style (Fill).
     *
     * @param Fill $fill Fill
     *
     * @return array
     */
    private function createCSSStyleFill(Fill $fill)
    {
        // Construct HTML
        $css = [];

        // Create CSS
        if ($fill->getFillType() !== Fill::FILL_NONE) {
            $value = $fill->getFillType() == Fill::FILL_NONE ?
                'white' : '#' . $fill->getStartColor()->getRGB();
            $css['background-color'] = $value;
        }

        return $css;
    }

    /**
     * Generate HTML footer.
     */
    public function generateHTMLFooter(): string
    {
        // Construct HTML
        $html = '';
        $html .= '  </body>' . PHP_EOL;
        $html .= '</html>' . PHP_EOL;

        return $html;
    }

    private function generateTableTagInline(Worksheet $worksheet, string $id): string
    {
        $style = isset($this->cssStyles['table']) ?
            $this->assembleCSS($this->cssStyles['table']) : '';

        $prntgrid = $worksheet->getPrintGridlines();
        $viewgrid = $this->isPdf ? $prntgrid : $worksheet->getShowGridlines();
        if ($viewgrid && $prntgrid) {
            $html = "    <table border='1' cellpadding='1' $id cellspacing='1' style='$style' class='gridlines gridlinesp'>" . PHP_EOL;
        } elseif ($viewgrid) {
            $html = "    <table border='0' cellpadding='0' $id cellspacing='0' style='$style' class='gridlines'>" . PHP_EOL;
        } elseif ($prntgrid) {
            $html = "    <table border='0' cellpadding='0' $id cellspacing='0' style='$style' class='gridlinesp'>" . PHP_EOL;
        } else {
            $html = "    <table border='0' cellpadding='1' $id cellspacing='0' style='$style'>" . PHP_EOL;
        }

        return $html;
    }

    private function generateTableTag(Worksheet $worksheet, string $id, string &$html, int $sheetIndex): void
    {
        if (!$this->useInlineCss) {
            $gridlines = $worksheet->getShowGridlines() ? ' gridlines' : '';
            $gridlinesp = $worksheet->getPrintGridlines() ? ' gridlinesp' : '';
            $html .= "    <table border='0' cellpadding='0' cellspacing='0' $id class='sheet$sheetIndex$gridlines$gridlinesp'>" . PHP_EOL;
        } else {
            $html .= $this->generateTableTagInline($worksheet, $id);
        }
    }

    /**
     * Generate table header.
     *
     * @param Worksheet $worksheet The worksheet for the table we are writing
     * @param bool $showid whether or not to add id to table tag
     *
     * @return string
     */
    private function generateTableHeader(Worksheet $worksheet, $showid = true)
    {
        $sheetIndex = $worksheet->getParentOrThrow()->getIndex($worksheet);

        // Construct HTML
        $html = '';
        $id = $showid ? "id='sheet$sheetIndex'" : '';
        if ($showid) {
            $html .= "<div style='page: page$sheetIndex'>" . PHP_EOL;
        } else {
            $html .= "<div style='page: page$sheetIndex' class='scrpgbrk'>" . PHP_EOL;
        }

        $this->generateTableTag($worksheet, $id, $html, $sheetIndex);

        // Write <col> elements
        $highestColumnIndex = Coordinate::columnIndexFromString($worksheet->getHighestColumn()) - 1;
        $i = -1;
        while ($i++ < $highestColumnIndex) {
            if (!$this->useInlineCss) {
                $html .= '        <col class="col' . $i . '" />' . PHP_EOL;
            } else {
                $style = isset($this->cssStyles['table.sheet' . $sheetIndex . ' col.col' . $i]) ?
                    $this->assembleCSS($this->cssStyles['table.sheet' . $sheetIndex . ' col.col' . $i]) : '';
                $html .= '        <col style="' . $style . '" />' . PHP_EOL;
            }
        }

        return $html;
    }

    /**
     * Generate table footer.
     */
    private function generateTableFooter(): string
    {
        return '    </tbody></table>' . PHP_EOL . '</div>' . PHP_EOL;
    }

    /**
     * Generate row start.
     *
     * @param int $sheetIndex Sheet index (0-based)
     * @param int $row row number
     *
     * @return string
     */
    private function generateRowStart(Worksheet $worksheet, $sheetIndex, $row)
    {
        $html = '';
        if (count($worksheet->getBreaks()) > 0) {
            $breaks = $worksheet->getRowBreaks();

            // check if a break is needed before this row
            if (isset($breaks['A' . $row])) {
                // close table: </table>
                $html .= $this->generateTableFooter();
                if ($this->isPdf && $this->useInlineCss) {
                    $html .= '<div style="page-break-before:always" />';
                }

                // open table again: <table> + <col> etc.
                $html .= $this->generateTableHeader($worksheet, false);
                $html .= '<tbody>' . PHP_EOL;
            }
        }

        // Write row start
        if (!$this->useInlineCss) {
            $html .= '          <tr class="row' . $row . '">' . PHP_EOL;
        } else {
            $style = isset($this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $row])
                ? $this->assembleCSS($this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $row]) : '';

            $html .= '          <tr style="' . $style . '">' . PHP_EOL;
        }

        return $html;
    }

    private function generateRowCellCss(Worksheet $worksheet, string $cellAddress, int $row, int $columnNumber): array
    {
        $cell = ($cellAddress > '') ? $worksheet->getCellCollection()->get($cellAddress) : '';
        $coordinate = Coordinate::stringFromColumnIndex($columnNumber + 1) . ($row + 1);
        if (!$this->useInlineCss) {
            $cssClass = 'column' . $columnNumber;
        } else {
            $cssClass = [];
            // The statements below do nothing.
            // Commenting out the code rather than deleting it
            // in case someone can figure out what their intent was.
            //if ($cellType == 'th') {
            //    if (isset($this->cssStyles['table.sheet' . $sheetIndex . ' th.column' . $colNum])) {
            //        $this->cssStyles['table.sheet' . $sheetIndex . ' th.column' . $colNum];
            //    }
            //} else {
            //    if (isset($this->cssStyles['table.sheet' . $sheetIndex . ' td.column' . $colNum])) {
            //        $this->cssStyles['table.sheet' . $sheetIndex . ' td.column' . $colNum];
            //    }
            //}
            // End of mystery statements.
        }

        return [$cell, $cssClass, $coordinate];
    }

    private function generateRowCellDataValueRich(Cell $cell, string &$cellData): void
    {
        // Loop through rich text elements
        $elements = $cell->getValue()->getRichTextElements();
        foreach ($elements as $element) {
            // Rich text start?
            if ($element instanceof Run) {
                $cellEnd = '';
                if ($element->getFont() !== null) {
                    $cellData .= '<span style="' . $this->assembleCSS($this->createCSSStyleFont($element->getFont())) . '">';

                    if ($element->getFont()->getSuperscript()) {
                        $cellData .= '<sup>';
                        $cellEnd = '</sup>';
                    } elseif ($element->getFont()->getSubscript()) {
                        $cellData .= '<sub>';
                        $cellEnd = '</sub>';
                    }
                }

                // Convert UTF8 data to PCDATA
                $cellText = $element->getText();
                $cellData .= htmlspecialchars($cellText, Settings::htmlEntityFlags());

                $cellData .= $cellEnd;

                $cellData .= '</span>';
            } else {
                // Convert UTF8 data to PCDATA
                $cellText = $element->getText();
                $cellData .= htmlspecialchars($cellText, Settings::htmlEntityFlags());
            }
        }
    }

    private function generateRowCellDataValue(Worksheet $worksheet, Cell $cell, string &$cellData): void
    {
        if ($cell->getValue() instanceof RichText) {
            $this->generateRowCellDataValueRich($cell, $cellData);
        } else {
            $origData = $this->preCalculateFormulas ? $cell->getCalculatedValue() : $cell->getValue();
            $formatCode = $worksheet->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex())->getNumberFormat()->getFormatCode();

            $cellData = NumberFormat::toFormattedString(
                $origData ?? '',
                $formatCode ?? NumberFormat::FORMAT_GENERAL,
                [$this, 'formatColor']
            );

            if ($cellData === $origData) {
                $cellData = htmlspecialchars($cellData, Settings::htmlEntityFlags());
            }
            if ($worksheet->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex())->getFont()->getSuperscript()) {
                $cellData = '<sup>' . $cellData . '</sup>';
            } elseif ($worksheet->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex())->getFont()->getSubscript()) {
                $cellData = '<sub>' . $cellData . '</sub>';
            }
        }
    }

    /**
     * @param null|Cell|string $cell
     * @param array|string $cssClass
     */
    private function generateRowCellData(Worksheet $worksheet, $cell, &$cssClass, string $cellType): string
    {
        $cellData = '&nbsp;';
        if ($cell instanceof Cell) {
            $cellData = '';
            // Don't know what this does, and no test cases.
            //if ($cell->getParent() === null) {
            //    $cell->attach($worksheet);
            //}
            // Value
            $this->generateRowCellDataValue($worksheet, $cell, $cellData);

            // Converts the cell content so that spaces occuring at beginning of each new line are replaced by &nbsp;
            // Example: "  Hello\n to the world" is converted to "&nbsp;&nbsp;Hello\n&nbsp;to the world"
            $cellData = (string) preg_replace('/(?m)(?:^|\G) /', '&nbsp;', $cellData);

            // convert newline "\n" to '<br>'
            $cellData = nl2br($cellData);

            // Extend CSS class?
            if (!$this->useInlineCss && is_string($cssClass)) {
                $cssClass .= ' style' . $cell->getXfIndex();
                $cssClass .= ' ' . $cell->getDataType();
            } elseif (is_array($cssClass)) {
                if ($cellType == 'th') {
                    if (isset($this->cssStyles['th.style' . $cell->getXfIndex()])) {
                        $cssClass = array_merge($cssClass, $this->cssStyles['th.style' . $cell->getXfIndex()]);
                    }
                } else {
                    if (isset($this->cssStyles['td.style' . $cell->getXfIndex()])) {
                        $cssClass = array_merge($cssClass, $this->cssStyles['td.style' . $cell->getXfIndex()]);
                    }
                }

                // General horizontal alignment: Actual horizontal alignment depends on dataType
                $sharedStyle = $worksheet->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex());
                if (
                    $sharedStyle->getAlignment()->getHorizontal() == Alignment::HORIZONTAL_GENERAL
                    && isset($this->cssStyles['.' . $cell->getDataType()]['text-align'])
                ) {
                    $cssClass['text-align'] = $this->cssStyles['.' . $cell->getDataType()]['text-align'];
                }
            }
        } else {
            // Use default borders for empty cell
            if (is_string($cssClass)) {
                $cssClass .= ' style0';
            }
        }

        return $cellData;
    }

    private function generateRowIncludeCharts(Worksheet $worksheet, string $coordinate): string
    {
        return $this->includeCharts ? $this->writeChartInCell($worksheet, $coordinate) : '';
    }

    private function generateRowSpans(string $html, int $rowSpan, int $colSpan): string
    {
        $html .= ($colSpan > 1) ? (' colspan="' . $colSpan . '"') : '';
        $html .= ($rowSpan > 1) ? (' rowspan="' . $rowSpan . '"') : '';

        return $html;
    }

    /**
     * @param array|string $cssClass
     */
    private function generateRowWriteCell(string &$html, Worksheet $worksheet, string $coordinate, string $cellType, string $cellData, int $colSpan, int $rowSpan, $cssClass, int $colNum, int $sheetIndex, int $row): void
    {
        // Image?
        $htmlx = $this->writeImageInCell($worksheet, $coordinate);
        // Chart?
        $htmlx .= $this->generateRowIncludeCharts($worksheet, $coordinate);
        // Column start
        $html .= '            <' . $cellType;
        if (!$this->useInlineCss && !$this->isPdf && is_string($cssClass)) {
            $html .= ' class="' . $cssClass . '"';
            if ($htmlx) {
                $html .= " style='position: relative;'";
            }
        } else {
            //** Necessary redundant code for the sake of \PhpOffice\PhpSpreadsheet\Writer\Pdf **
            // We must explicitly write the width of the <td> element because TCPDF
            // does not recognize e.g. <col style="width:42pt">
            if ($this->useInlineCss) {
                $xcssClass = is_array($cssClass) ? $cssClass : [];
            } else {
                if (is_string($cssClass)) {
                    $html .= ' class="' . $cssClass . '"';
                }
                $xcssClass = [];
            }
            $width = 0;
            $i = $colNum - 1;
            $e = $colNum + $colSpan - 1;
            while ($i++ < $e) {
                if (isset($this->columnWidths[$sheetIndex][$i])) {
                    $width += $this->columnWidths[$sheetIndex][$i];
                }
            }
            $xcssClass['width'] = (string) $width . 'pt';
            // We must also explicitly write the height of the <td> element because TCPDF
            // does not recognize e.g. <tr style="height:50pt">
            if (isset($this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $row]['height'])) {
                $height = $this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $row]['height'];
                $xcssClass['height'] = $height;
            }
            //** end of redundant code **

            if ($htmlx) {
                $xcssClass['position'] = 'relative';
            }
            $html .= ' style="' . $this->assembleCSS($xcssClass) . '"';
        }
        $html = $this->generateRowSpans($html, $rowSpan, $colSpan);

        $html .= '>';
        $html .= $htmlx;

        $html .= $this->writeComment($worksheet, $coordinate);

        // Cell data
        $html .= $cellData;

        // Column end
        $html .= '</' . $cellType . '>' . PHP_EOL;
    }

    /**
     * Generate row.
     *
     * @param array $values Array containing cells in a row
     * @param int $row Row number (0-based)
     * @param string $cellType eg: 'td'
     *
     * @return string
     */
    private function generateRow(Worksheet $worksheet, array $values, $row, $cellType)
    {
        // Sheet index
        $sheetIndex = $worksheet->getParentOrThrow()->getIndex($worksheet);
        $html = $this->generateRowStart($worksheet, $sheetIndex, $row);
        $generateDiv = $this->isMPdf && $worksheet->getRowDimension($row + 1)->getVisible() === false;
        if ($generateDiv) {
            $html .= '<div style="visibility:hidden; display:none;">' . PHP_EOL;
        }

        // Write cells
        $colNum = 0;
        foreach ($values as $cellAddress) {
            [$cell, $cssClass, $coordinate] = $this->generateRowCellCss($worksheet, $cellAddress, $row, $colNum);

            // Cell Data
            $cellData = $this->generateRowCellData($worksheet, $cell, $cssClass, $cellType);

            // Hyperlink?
            if ($worksheet->hyperlinkExists($coordinate) && !$worksheet->getHyperlink($coordinate)->isInternal()) {
                $url = $worksheet->getHyperlink($coordinate)->getUrl();
                $urlDecode1 = html_entity_decode($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $urlTrim = preg_replace('/^\s+/u', '', $urlDecode1) ?? $urlDecode1;
                $parseScheme = preg_match('/^([\w\s\x00-\x1f]+):/u', strtolower($urlTrim), $matches);
                if ($parseScheme === 1 && !in_array($matches[1], ['http', 'https', 'file', 'ftp', 'mailto', 's3'], true)) {
                    $cellData = htmlspecialchars($url, Settings::htmlEntityFlags());
                    $cellData = self::replaceControlChars($cellData);
                } else {
                    $cellData = '<a href="' . htmlspecialchars($url, Settings::htmlEntityFlags()) . '" title="' . htmlspecialchars($worksheet->getHyperlink($coordinate)->getTooltip(), Settings::htmlEntityFlags()) . '">' . $cellData . '</a>';
                }
            }

            // Should the cell be written or is it swallowed by a rowspan or colspan?
            $writeCell = !(isset($this->isSpannedCell[$worksheet->getParentOrThrow()->getIndex($worksheet)][$row + 1][$colNum])
                && $this->isSpannedCell[$worksheet->getParentOrThrow()->getIndex($worksheet)][$row + 1][$colNum]);

            // Colspan and Rowspan
            $colSpan = 1;
            $rowSpan = 1;
            if (isset($this->isBaseCell[$worksheet->getParentOrThrow()->getIndex($worksheet)][$row + 1][$colNum])) {
                $spans = $this->isBaseCell[$worksheet->getParentOrThrow()->getIndex($worksheet)][$row + 1][$colNum];
                $rowSpan = $spans['rowspan'];
                $colSpan = $spans['colspan'];

                //    Also apply style from last cell in merge to fix borders -
                //        relies on !important for non-none border declarations in createCSSStyleBorder
                $endCellCoord = Coordinate::stringFromColumnIndex($colNum + $colSpan) . ($row + $rowSpan);
                if (!$this->useInlineCss) {
                    $cssClass .= ' style' . $worksheet->getCell($endCellCoord)->getXfIndex();
                }
            }

            // Write
            if ($writeCell) {
                $this->generateRowWriteCell($html, $worksheet, $coordinate, $cellType, $cellData, $colSpan, $rowSpan, $cssClass, $colNum, $sheetIndex, $row);
            }

            // Next column
            ++$colNum;
        }

        // Write row end
        if ($generateDiv) {
            $html .= '</div>' . PHP_EOL;
        }
        $html .= '          </tr>' . PHP_EOL;

        // Return
        return $html;
    }

    private static function replaceNonAscii(array $matches): string
    {
        return '&#' . mb_ord($matches[0], 'UTF-8') . ';';
    }

    private static function replaceControlChars(string $convert): string
    {
        return (string) preg_replace_callback(
            '/[\x00-\x1f]/',
            [self::class, 'replaceNonAscii'],
            $convert
        );
    }

    /**
     * Takes array where of CSS properties / values and converts to CSS string.
     *
     * @return string
     */
    private function assembleCSS(array $values = [])
    {
        $pairs = [];
        foreach ($values as $property => $value) {
            $pairs[] = $property . ':' . $value;
        }
        $string = implode('; ', $pairs);

        return $string;
    }

    /**
     * Get images root.
     *
     * @return string
     */
    public function getImagesRoot()
    {
        return $this->imagesRoot;
    }

    /**
     * Set images root.
     *
     * @param string $imagesRoot
     *
     * @return $this
     */
    public function setImagesRoot($imagesRoot)
    {
        $this->imagesRoot = $imagesRoot;

        return $this;
    }

    /**
     * Get embed images.
     *
     * @return bool
     */
    public function getEmbedImages()
    {
        return $this->embedImages;
    }

    /**
     * Set embed images.
     *
     * @param bool $embedImages
     *
     * @return $this
     */
    public function setEmbedImages($embedImages)
    {
        $this->embedImages = $embedImages;

        return $this;
    }

    /**
     * Get use inline CSS?
     *
     * @return bool
     */
    public function getUseInlineCss()
    {
        return $this->useInlineCss;
    }

    /**
     * Set use inline CSS?
     *
     * @param bool $useInlineCss
     *
     * @return $this
     */
    public function setUseInlineCss($useInlineCss)
    {
        $this->useInlineCss = $useInlineCss;

        return $this;
    }

    /**
     * Get use embedded CSS?
     *
     * @return bool
     *
     * @codeCoverageIgnore
     *
     * @deprecated no longer used
     */
    public function getUseEmbeddedCSS()
    {
        return $this->useEmbeddedCSS;
    }

    /**
     * Set use embedded CSS?
     *
     * @param bool $useEmbeddedCSS
     *
     * @return $this
     *
     * @codeCoverageIgnore
     *
     * @deprecated no longer used
     */
    public function setUseEmbeddedCSS($useEmbeddedCSS)
    {
        $this->useEmbeddedCSS = $useEmbeddedCSS;

        return $this;
    }

    /**
     * Add color to formatted string as inline style.
     *
     * @param string $value Plain formatted value without color
     * @param string $format Format code
     *
     * @return string
     */
    public function formatColor($value, $format)
    {
        // Color information, e.g. [Red] is always at the beginning
        $color = null; // initialize
        $matches = [];

        $color_regex = '/^\[[a-zA-Z]+\]/';
        if (preg_match($color_regex, $format, $matches)) {
            $color = str_replace(['[', ']'], '', $matches[0]);
            $color = strtolower($color);
        }

        // convert to PCDATA
        $result = htmlspecialchars($value, Settings::htmlEntityFlags());

        // color span tag
        if ($color !== null) {
            $result = '<span style="color:' . $color . '">' . $result . '</span>';
        }

        return $result;
    }

    /**
     * Calculate information about HTML colspan and rowspan which is not always the same as Excel's.
     */
    private function calculateSpans(): void
    {
        if ($this->spansAreCalculated) {
            return;
        }
        // Identify all cells that should be omitted in HTML due to cell merge.
        // In HTML only the upper-left cell should be written and it should have
        //   appropriate rowspan / colspan attribute
        $sheetIndexes = $this->sheetIndex !== null ?
            [$this->sheetIndex] : range(0, $this->spreadsheet->getSheetCount() - 1);

        foreach ($sheetIndexes as $sheetIndex) {
            $sheet = $this->spreadsheet->getSheet($sheetIndex);

            $candidateSpannedRow = [];

            // loop through all Excel merged cells
            foreach ($sheet->getMergeCells() as $cells) {
                [$cells] = Coordinate::splitRange($cells);
                $first = $cells[0];
                $last = $cells[1];

                [$fc, $fr] = Coordinate::indexesFromString($first);
                $fc = $fc - 1;

                [$lc, $lr] = Coordinate::indexesFromString($last);
                $lc = $lc - 1;

                // loop through the individual cells in the individual merge
                $r = $fr - 1;
                while ($r++ < $lr) {
                    // also, flag this row as a HTML row that is candidate to be omitted
                    $candidateSpannedRow[$r] = $r;

                    $c = $fc - 1;
                    while ($c++ < $lc) {
                        if (!($c == $fc && $r == $fr)) {
                            // not the upper-left cell (should not be written in HTML)
                            $this->isSpannedCell[$sheetIndex][$r][$c] = [
                                'baseCell' => [$fr, $fc],
                            ];
                        } else {
                            // upper-left is the base cell that should hold the colspan/rowspan attribute
                            $this->isBaseCell[$sheetIndex][$r][$c] = [
                                'xlrowspan' => $lr - $fr + 1, // Excel rowspan
                                'rowspan' => $lr - $fr + 1, // HTML rowspan, value may change
                                'xlcolspan' => $lc - $fc + 1, // Excel colspan
                                'colspan' => $lc - $fc + 1, // HTML colspan, value may change
                            ];
                        }
                    }
                }
            }

            $this->calculateSpansOmitRows($sheet, $sheetIndex, $candidateSpannedRow);

            // TODO: Same for columns
        }

        // We have calculated the spans
        $this->spansAreCalculated = true;
    }

    private function calculateSpansOmitRows(Worksheet $sheet, int $sheetIndex, array $candidateSpannedRow): void
    {
        // Identify which rows should be omitted in HTML. These are the rows where all the cells
        //   participate in a merge and the where base cells are somewhere above.
        $countColumns = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        foreach ($candidateSpannedRow as $rowIndex) {
            if (isset($this->isSpannedCell[$sheetIndex][$rowIndex])) {
                if (count($this->isSpannedCell[$sheetIndex][$rowIndex]) == $countColumns) {
                    $this->isSpannedRow[$sheetIndex][$rowIndex] = $rowIndex;
                }
            }
        }

        // For each of the omitted rows we found above, the affected rowspans should be subtracted by 1
        if (isset($this->isSpannedRow[$sheetIndex])) {
            foreach ($this->isSpannedRow[$sheetIndex] as $rowIndex) {
                $adjustedBaseCells = [];
                $c = -1;
                $e = $countColumns - 1;
                while ($c++ < $e) {
                    $baseCell = $this->isSpannedCell[$sheetIndex][$rowIndex][$c]['baseCell'];

                    if (!in_array($baseCell, $adjustedBaseCells, true)) {
                        // subtract rowspan by 1
                        --$this->isBaseCell[$sheetIndex][$baseCell[0]][$baseCell[1]]['rowspan'];
                        $adjustedBaseCells[] = $baseCell;
                    }
                }
            }
        }
    }

    /**
     * Write a comment in the same format as LibreOffice.
     *
     * @see https://github.com/LibreOffice/core/blob/9fc9bf3240f8c62ad7859947ab8a033ac1fe93fa/sc/source/filter/html/htmlexp.cxx#L1073-L1092
     *
     * @param string $coordinate
     *
     * @return string
     */
    private function writeComment(Worksheet $worksheet, $coordinate)
    {
        $result = '';
        if (!$this->isPdf && isset($worksheet->getComments()[$coordinate])) {
            $sanitizer = new HTMLPurifier();
            $cachePath = File::sysGetTempDir() . '/phpsppur';
            if (is_dir($cachePath) || mkdir($cachePath)) {
                $sanitizer->config->set('Cache.SerializerPath', $cachePath);
            }
            $sanitizedString = $sanitizer->purify($worksheet->getComment($coordinate)->getText()->getPlainText());
            if ($sanitizedString !== '') {
                $result .= '<a class="comment-indicator"></a>';
                $result .= '<div class="comment">' . nl2br($sanitizedString) . '</div>';
                $result .= PHP_EOL;
            }
        }

        return $result;
    }

    public function getOrientation(): ?string
    {
        // Expect Pdf classes to override this method.
        return $this->isPdf ? PageSetup::ORIENTATION_PORTRAIT : null;
    }

    /**
     * Generate @page declarations.
     *
     * @param bool $generateSurroundingHTML
     *
     * @return    string
     */
    private function generatePageDeclarations($generateSurroundingHTML)
    {
        // Ensure that Spans have been calculated?
        $this->calculateSpans();

        // Fetch sheets
        $sheets = [];
        if ($this->sheetIndex === null) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets[] = $this->spreadsheet->getSheet($this->sheetIndex);
        }

        // Construct HTML
        $htmlPage = $generateSurroundingHTML ? ('<style type="text/css">' . PHP_EOL) : '';

        // Loop all sheets
        $sheetId = 0;
        foreach ($sheets as $worksheet) {
            $htmlPage .= "@page page$sheetId { ";
            $left = StringHelper::formatNumber($worksheet->getPageMargins()->getLeft()) . 'in; ';
            $htmlPage .= 'margin-left: ' . $left;
            $right = StringHelper::FormatNumber($worksheet->getPageMargins()->getRight()) . 'in; ';
            $htmlPage .= 'margin-right: ' . $right;
            $top = StringHelper::FormatNumber($worksheet->getPageMargins()->getTop()) . 'in; ';
            $htmlPage .= 'margin-top: ' . $top;
            $bottom = StringHelper::FormatNumber($worksheet->getPageMargins()->getBottom()) . 'in; ';
            $htmlPage .= 'margin-bottom: ' . $bottom;
            $orientation = $this->getOrientation() ?? $worksheet->getPageSetup()->getOrientation();
            if ($orientation === PageSetup::ORIENTATION_LANDSCAPE) {
                $htmlPage .= 'size: landscape; ';
            } elseif ($orientation === PageSetup::ORIENTATION_PORTRAIT) {
                $htmlPage .= 'size: portrait; ';
            }
            $htmlPage .= '}' . PHP_EOL;
            ++$sheetId;
        }
        $htmlPage .= implode(PHP_EOL, [
            '.navigation {page-break-after: always;}',
            '.scrpgbrk, div + div {page-break-before: always;}',
            '@media screen {',
            '  .gridlines td {border: 1px solid black;}',
            '  .gridlines th {border: 1px solid black;}',
            '  body>div {margin-top: 5px;}',
            '  body>div:first-child {margin-top: 0;}',
            '  .scrpgbrk {margin-top: 1px;}',
            '}',
            '@media print {',
            '  .gridlinesp td {border: 1px solid black;}',
            '  .gridlinesp th {border: 1px solid black;}',
            '  .navigation {display: none;}',
            '}',
            '',
        ]);
        $htmlPage .= $generateSurroundingHTML ? ('</style>' . PHP_EOL) : '';

        return $htmlPage;
    }
}
