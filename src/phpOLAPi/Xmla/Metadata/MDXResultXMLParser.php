<?php

namespace phpOLAPi\Xmla\Metadata;

class MDXResultXMLParser
{
    private $parser;

    /**
     * Location in the XML tree, contains the parent elements of the currently parsed element,
     * in order, starting from the current elements parent to the document root.
     */
    private $position;
    private $depth;
    /**
     * The char_data_handler can be called multiple times, for the same text-node, in which case
     * we want to concatenate the text together before we use it. This variable is utilised to 
     * keep track of when to concatenate, and when to use the value.
     */
    private $insideData;
    private $concatenatedTxt;

    /**
     * 1 = inside of AxisInfo tag
     * 2 = inside of Axis tag
     * 3 = inside of Cell tag
     */
    private $state;

    public $data;

    function __construct()
    {
        $this->parser = xml_parser_create();
        $this->position = [];
        $this->depth = 0;
        $this->insideData = false;
        $this->concatenatedTxt = '';
        $this->state = 0;

        $this->data = [];

        xml_set_object($this->parser, $this);
        xml_set_element_handler(
            $this->parser,
            array(&$this, 'tag_open'),
            array(&$this, 'tag_close')
        );
        xml_set_character_data_handler(
            $this->parser,
            array(&$this, "cdata")
        );
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
    }

    function __destruct()
    {
        xml_parser_free($this->parser);
        unset($this->parser);
    }

    function parse($txt)
    {

        $this->position = [];
        $this->depth = 0;
        $this->insideData = false;
        $this->concatenatedTxt = '';
        $this->state = 0;

        $this->data = [];
        $this->data['hierarchiesName'] = [];
        $this->data['cellAxisSet'] = [];
        $this->data['cellDataSet'] = [];

        // Use a stream, and parse it chunk-wise to avoid "No Memory" Error
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $txt);
        rewind($stream);

        $MB = 1024 * 1024;
        $chunkSize = 5 * $MB;
        $maxSize = 1000 * $MB;

        $parsedChunksCount = 0;
        $isEndOfStream = false;

        do {
            $fileContent = fread($stream, $chunkSize);
            $isEndOfStream = feof($stream);

            if (!xml_parse($this->parser, $fileContent, $isEndOfStream)) {
                die(sprintf(
                    "XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($this->parser)),
                    xml_get_current_line_number($this->parser)
                ));
            }

            ++$parsedChunksCount;
        } while (!$isEndOfStream && ($parsedChunksCount * $chunkSize) < $maxSize);

    }

    function tag_open($parser, $name, $attrs)
    {
        if (!isset($this->depth)) {
            $this->depth = 0;
        }
        $this->depth++;

        $this->position[$this->depth] = $name;

        $this->insideData = false;


        /** Handle AxisInfo Tags */

        if ($this->state === 0 && $name == 'AxisInfo') {
            $this->state = 1;
            $this->data['axisName'] = $this->getDataSafely($attrs, 'name');
        }

        if ($this->state === 1 && $name == 'HierarchyInfo') {
            $this->data['hierarchiesName'][$this->data['axisName']][] = $this->getDataSafely($attrs, 'name');
        }


        /** Handle Axis Tags */

        if ($this->state === 0 && $name == 'Axis') {
            $this->state = 2;
            $this->data['axisName'] = $this->getDataSafely($attrs, 'name');
            $this->data['i'] = 0;
        }

        if ($this->state === 2 && $name == 'Member') {
            $this->data['member'] = [];
        }


        /** Handle Cell Tags */

        if ($this->state === 0 && $name == 'Cell') {
            $this->state = 3;
            $this->data['cellOrdinal'] = $this->getDataSafely($attrs, 'CellOrdinal');
            $this->data['cell'] = [];
        }
    }

    function tag_close($parser, $name)
    {


        if ($this->state === 2 && $name == 'Tuple') {
            $this->data['i']++;
        }

        if ($this->state === 2 && $name == 'Member') {
            $cell = new CellAxis();
            $cell->hydrateObj($this->data['member']);
            $this->data['cellAxisSet'][$this->data['axisName']][$this->data['i']][] = $cell;
        }
        if ($this->state === 3 && $name == 'Cell') {
            $cell = new CellData();
            $cell->hydrateObj($this->data['cell']);
            $this->data['cellDataSet'][$this->data['cellOrdinal']] = $cell;
        }

        if (
            $this->state === 1 && $name == 'AxisInfo'
            || $this->state === 2 && $name == 'Axis'
            || $this->state === 3 && $name == 'Cell'
        ) {
            $this->state = 0;
        }

        $this->depth--;
        $this->insideData = false;
    }

    function cdata($parser, $txt)
    {
        if (!$this->insideData) {
            $this->concatenatedTxt = '';
        }
        $this->concatenatedTxt = $this->concatenatedTxt . $txt;

        $parentName = $this->position[$this->depth - 1];
        $name = $this->position[$this->depth];

        if ($this->state === 2 && $parentName == 'Member') {
            foreach (['UName', 'Caption', 'LName', 'LNum', 'DisplayInfo'] as $key) {
                if ($name == $key) {
                    $this->data['member'][$key] = $this->parseTextNodeValue($this->concatenatedTxt);
                }
            }
        }

        if ($this->state === 3 && $parentName == 'Cell') {
            foreach (['Value', 'FmtValue', 'FormatString'] as $key) {
                if ($name == $key) {
                    $this->data['cell'][$key] = $this->parseTextNodeValue($this->concatenatedTxt);
                }
            }
        }

        if ($this->state === 0 && $name == 'CubeName') {
            $this->data['CubeName'] = $this->concatenatedTxt;
        }

        $this->insideData = true;
    }
    function getDataSafely($arr, $value)
    {
        if (isset($arr[$value])) {
            return $arr[$value];
        } else {
            throw new MetadataException('Hydratation error.');
        }
    }

    function parseTextNodeValue($value)
    {
        return ($value == 'false') ? false : $value;
    }
}
