<?php
// TODO: redesign exception scheme

/**
 * Class Parser
 *
 * Parses a PDF file and converts it into an object representation
 */
class PdfParser
{
    /**
     * Path to the PDF file we want to convert to a PHP object representation
     *
     * @var string
     */
    private $_pdfPath;
    /**
     * Holds the root element that will be filled by the XmlParser
     *
     * @var \XmlParser\XmlRootElement
     */
    private $_rootObject;

    /**.
     *
     * @param $pdfPath    string input PDF file
     * @param $rootObject \XmlParser\XmlRootElement output object representation of the PDF
     */
    public function __construct($pdfPath, $rootObject) {
        $this->_pdfPath = $pdfPath;
        $this->_rootObject = $rootObject;
    }

    /**
     * Launch the PDF parsing
     *
     * @return \XmlParser\XmlRootElement the rootObject that was given in the constructor, now filled with the PDF structure & data
     *
     * @throws Exception
     */
    public function parse() {
        if(!file_exists($this->_pdfPath)) {
            throw new Exception('Specified PDF was not found');
        }
        $xmlUniqueName = uniqid() . '.xml';
        exec('pdf2txt.py -o ' . $xmlUniqueName . ' ' . $this->_pdfPath);
        $xmlFileParser = new \XmlParser\XmlFileDataParser($this->_pdfPath, $this->_rootObject);
        $xmlFileParser->parse();
        unlink($xmlUniqueName);
        return $this->_rootObject;
    }
}