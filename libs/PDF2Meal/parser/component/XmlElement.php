<?php

abstract class XmlElement
{
    protected $children;

    /**
     * To be overriden - Used to define the possible children of the current XML element
     * One set of children = one entry in the array.
     * Each entry must look like this:
     * tagName => options
     *
     * ... where options is an array that can have the following elements:
     *      - accessor: defines a custom name for the getter, instead of using the tagName
     *      - cache_attr: useful when some XML is duplicated at several places in the file, and each one of the duplicata has got complementary informations over the others.
     *                    In the end, the parser will hold an unique object that gathers all of these complementary informations.
     *                    The value of this option must be the name of the attribute holding the unique identifier.
     *                    The cache's scope is limited to the XmlElement defining the cache_attr option and its children.
     *
     * Example :
     *
     * Consider the XML file:
     * <root>
     *  <node1>
     *   <node2>
     *    <node3 id="1" attr1="1"></node>
     *   </node2>
     *   <node4>
     *    <node3 id="1" attr1="1">
     *     <additionalData>Hello</additionalData>
     *    </node>
     *   </node4>
     *  </node1>
     *  <!-- new node1-->
     *  <node1>
     *   <node2>
     *    <node3 id="1" attr1="1"></node>
     *   </node2>
     *   <node4>
     *    <node3 id="1" attr1="1">
     *     <additionalData>Hello</additionalData>
     *    </node>
     *   </node4>
     *  </node1>
     * </root>
     *
     * Consider the configuration:
     * root: has_many('node1' => array('accessor' => 'node1s'))
     * node1: has_one('node2', 'node4')
     *        has_many('node3' => array('cache_attr' => 'id'))
     * node2: has_one('node3')
     * node4: has_one('node3')
     *
     * @var array
     */
    protected $hasOne = array(), $hasMany = array();

    /**
     * To be overriden - Indicates if the element is cached
     *
     * Some XML tags can sometimes be duplicated at several places in the file.
     * If this is set to true, we'll scan the parent tree until we find a XmlElement which has this kind of element for children, and which has a 'cache_attr' on it.
     * Then, instead of creating a new element, we'll reuse an older one if available, and we'll complete it.
     *
     * @var bool
     */
    protected $cached = false;

    private $parent;

    protected function __construct($parent) {
        $this->parent = $parent;
        $this->initChildren();
    }

    private function initChildren() {
        forEach($this->hasOne) {
            $this->children = array();
        }
    }

    abstract public function getName();

    public function __get($name) {
    }

    abstract protected function tagOpen($tag, $attrs);

    abstract protected function tagData($data);

    abstract protected function tagClosed($tag);
} 