<?php 

/*
* This file is part of phpOlap.
*
* (c) Julien Jacottet <jjacottet@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace phpOLAPi\Xmla\Metadata;

use phpOLAPi\Xmla\Connection\ConnectionInterface;
use phpOLAPi\Xmla\Metadata\MetadataBase;
use phpOLAPi\Xmla\Metadata\CellAxis;
use phpOLAPi\Xmla\Metadata\CellData;
use phpOLAPi\Metadata\ResultSetInterface;
use phpOLAPi\Xmla\Metadata\MetadataException;
use phpOLAPi\Xmla\Metadata\MDXResultXMLParser;

/**
*	ResultSet
*
*	@package Xmla
*	@subpackage Metadata
*  	@author Julien Jacottet <jjacottet@gmail.com>
*/

class ResultSet implements ResultSetInterface
{
	protected $cubeName;
	protected $hierarchiesName = array();
	protected $cellAxisSet = array();
	protected $cellDataSet = array();
	
    /**
     * Get cube name
     *
     * @return String cube name
     *
     */	
	public function getCubeName()
	{
		return $this->cubeName;
	}

    /**
     * Get columns name in array   
     *
     * @return Array columns name
     *
     */
	public function getColHierarchiesName()
	{
		if (isset($this->hierarchiesName['Axis0'])) {
			return $this->hierarchiesName['Axis0'];
		}
		return null;
	}

    /**
     * Get rows name in array   
     *
     * @return Array rows name
     *
     */
	public function getRowHierarchiesName()
	{
		if (isset($this->hierarchiesName['Axis1'])) {
			return $this->hierarchiesName['Axis1'];
		}
		return null;
	}

    /**
     * Get filters name in array   
     *
     * @return Array filters name
     *
     */
	public function getFilterHierarchiesName()
	{
		if (isset($this->hierarchiesName['SlicerAxis'])) {
			return $this->hierarchiesName['SlicerAxis'];
		}
		return null;
	}

    /**
     * Get columns CellAxis collection
     *
     * @return Array CellAxis collection
     *
     */
	public function getColAxisSet()
	{
		if (isset($this->cellAxisSet['Axis0'])) {
			return $this->cellAxisSet['Axis0'];
		}
		return null;
	}

    /**
     * Get rows CellAxis collection
     *
     * @return Array CellAxis collection
     *
     */
	public function getRowAxisSet()
	{
		if (isset($this->cellAxisSet['Axis1'])) {
			return $this->cellAxisSet['Axis1'];
		}
		return null;
	}

    /**
     * Get filter CellAxis collection
     *
     * @return Array CellAxis collection
     *
     */
	public function getFilterAxisSet()
	{
		if (isset($this->cellAxisSet['SlicerAxis'])) {
			return $this->cellAxisSet['SlicerAxis'];
		}
		return null;
	}

    /**
     * Get CellData collection
     *
     * @return Array CellData collection
     *
     */
	public function getDataSet()
	{
		return $this->cellDataSet;
	}

    /**
     * Get CellData by ordinal
     *
     * @return CellData CellData object
     *
     */
	public function getDataCell($ordinal)
	{
		return $this->cellDataSet[$ordinal];
	}

    /**
     * Hydrate Element
     *
     * @param DOMNode $node Node
     *
     */	
	public function hydrate(\DOMNode $node)
	{

		$xml_parser = new MDXResultXMLParser();

		$doc = new \DOMDocument();
		$node = $doc->importNode($node, true);
		$xml_parser->parse($doc->saveXML($node));
		

		$this->cubeName = $xml_parser->data['CubeName'];
		$this->hierarchiesName = $xml_parser->data['hierarchiesName'];
		$this->cellAxisSet = $xml_parser->data['cellAxisSet'];
		$this->cellDataSet = $xml_parser->data['cellDataSet'];
	}
}