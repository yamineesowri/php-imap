<?php
/*
* File: Part.php
* Category: -
* Author: M.Goldenbaum
* Created: 17.09.20 20:38
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;


use Carbon\Carbon;
use Illuminate\Support\Str;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;

/**
 * Class Part
 *
 * @package Webklex\PHPIMAP
 */
class Part {

    /**
     * @var string $raw
     */
    public $raw = "";

    /**
     * @var int $type
     */
    public $type = IMAP::MESSAGE_TYPE_TEXT;

    /**
     * @var string $content
     */
    public $content = "";

    /**
     * @var string $subtype
     */
    public $subtype = null;

    /**
     * @var string $charset
     */
    public $charset = "utf-8";

    /**
     * @var int $encoding
     */
    public $encoding = IMAP::MESSAGE_ENC_OTHER;

    /**
     * @var boolean $ifdisposition
     */
    public $ifdisposition = false;

    /**
     * @var string $disposition
     */
    public $disposition = null;

    /**
     * @var boolean $ifdescription
     */
    public $ifdescription = false;

    /**
     * @var string $description
     */
    public $description = null;

    /**
     * @var string $filename
     */
    public $filename = null;

    /**
     * @var string $name
     */
    public $name = null;

    /**
     * @var string $id
     */
    public $id = null;

    /**
     * @var integer $bytes
     */
    public $bytes = null;

    /**
     * @var Header $header
     */
    private $header = null;

    /**
     * Part constructor.
     * @param $raw_part
     * @param Header $header
     * @throws InvalidMessageDateException
     */
    public function __construct($raw_part, $header = null) {
        $this->raw = $raw_part;
        $this->header = $header;
        $this->parse();
    }

    /**
     * Parse the raw parts
     * @throws InvalidMessageDateException
     */
    protected function parse(){
        $body = $this->raw;

        if ($this->header === null) {
            $headers = "";
            if (preg_match_all("/(.*)(?s)(?-m)\\r\\n.+?(?=\n([^A-Z]|.{1,2}[^A-Z])|$)/im", $body, $matches)) {
                if (isset($matches[0][0])) {
                    $headers = $matches[0][0];
                    $body = substr($body, strlen($headers) + 2, -2);
                }
            }

            $this->header = new Header($headers);
        }
        $this->parseSubtype();
        $this->parseDisposition();
        $this->parseDescription();
        $this->parseEncoding();

        $this->charset = $this->header->get("charset");
        $this->name = $this->header->get("name");
        $this->filename = $this->header->get("filename");
        $this->id = $this->header->get("id");

        $this->content = trim(rtrim($body));
        $this->bytes = strlen($this->content);
    }

    private function parseSubtype(){
        $content_type = $this->header->get("content-type");
        if (($pos = strpos($content_type, "/")) !== false){
            $this->subtype = substr($content_type, $pos + 1);
        }
    }

    private function parseDisposition(){
        $content_disposition = $this->header->get("content-disposition");
        if($content_disposition !== null) {
            $this->ifdisposition = true;
            $this->disposition = $content_disposition;
        }
    }

    private function parseDescription(){
        $content_description = $this->header->get("content-description");
        if($content_description !== null) {
            $this->ifdescription = true;
            $this->description = $content_description;
        }
    }

    private function parseEncoding(){
        $encoding = $this->header->get("content-transfer-encoding");
        if($encoding !== null) {
            switch (strtolower($encoding)) {
                case "quoted-printable":
                    $this->encoding = IMAP::MESSAGE_ENC_QUOTED_PRINTABLE;
                    break;
                case "base64":
                    $this->encoding = IMAP::MESSAGE_ENC_BASE64;
                    break;
                case "7bit":
                    $this->encoding = IMAP::MESSAGE_ENC_7BIT;
                    break;
                case "8bit":
                    $this->encoding = IMAP::MESSAGE_ENC_8BIT;
                    break;
                case "binary":
                    $this->encoding = IMAP::MESSAGE_ENC_BINARY;
                    break;
                default:
                    $this->encoding = IMAP::MESSAGE_ENC_OTHER;
                    break;

            }
        }
    }

}