<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 数组转xml文件
 */
/**
 * 数组转XML类
 */
class Array_To_Xml
{
    private $version = '1.0';
    private $encoding = 'UTF-8';
    private $root = 'root';
    private $xml = null;

    public function __construct ()
    {
        $this->xml = new XmlWriter();
    }
    
    /**
     * 设置根节点名称
     * @param int $root
     */
    public function set_root($root)
    {
        if (!empty($root)) {
            $this->root = $root;
        }
    }
    
    /**
     * 转换成XML
     * @param array $data 数据
     * @param bool $e_is_array 
     */
    public function to_xml ($data, $e_is_array = FALSE)
    {
        if (! $e_is_array) {
            $this->xml->openMemory();
            $this->xml->startDocument($this->version, $this->encoding);
            $root = $this->root == 'hash' ? 'hash' : $this->root.'s';
            $this->xml->startElement($root);
        }
        foreach ($data as $key => $value) {
            if (!$e_is_array) {
                $key = is_int($key) ? $this->root : $key;
            } else {
                $key = is_int($key) ? 'id' : $key;             
            }
            if (is_array($value)) {
                $this->xml->startElement($key);
                $this->to_xml($value, TRUE);
                $this->xml->endElement();
                continue;
            }
            $this->xml->writeElement($key, $value);
        }
        if (! $e_is_array) {
            $this->xml->endElement();
            return $this->xml->outputMemory(true);
        }
    }
}  