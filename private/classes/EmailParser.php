<?php

namespace Parser;
use DOMDocument;
use DOMElement;
use Exception;

error_reporting(E_ALL ^ E_WARNING);

class EmailParser {

    protected string $message;
    protected array $order_details;
    protected $dom;
    /**
     * Creates a new instance of the EmailParser class.
     * 
     * @param string $message The email message to parse. This should be the
     * HTML content of the email. The constructor will clean up the message
     * to remove any unnecessary characters that may have been added by the
     * mail server. The cleaned up message is then stored in the message
     * property.
     * 
     * @return void
     */
    public function __construct($message, $id) {
        $this->dom = new DomDocument();
        $this->order_details = ["items"=>[], "item_prices"=>[]];
        $this->message = $message;
    }

    /**
     * Parse the HTML email message and extract customer order data.
     *
     * Extracts the customer name, order date, and order time from the email
     * header section. Then iterates over each table cell in the email to
     * extract the customer contact details and shipping address details.
     * Stores the extracted data in $this->order_details.
     *
     * @return void
     */
    public function parse() {
        try {
            $this->dom->loadHTML($this->message);
            $tds = $this->dom->getElementsByTagName('td');
            $prev_node = null;
            foreach ($tds as $td) {
                $class_names = $this->getClassList($td);
                if (in_array("subheader-ordersubject-wrapper", $class_names)) {
                    $this->order_details = array_merge($this->order_details, $this->getOrderDetails($td));
                    continue;    
                }
                if(in_array("methods-customer-td", $class_names)) {
                    $this->order_details = array_merge($this->order_details, $this->getContactDetails($td));
                    continue;
                }
                if (in_array("methods-address-details", $class_names)) {
                    $this->order_details = array_merge($this->order_details, $this->getAddressDetails($td));
                    continue;
                }
                if (in_array("order-details-table-table-itemname-td", $class_names)) {
                    $this->order_details['items'][] = $this->getItemDetails($td);
                    continue;
                }
                if (in_array("order-details-table-td-price", $class_names)) {
                    $this->order_details['item_prices'][] = $this->getItemPrices($td);
                    continue;
                }
                if (in_array("order-summary-table-order-subtotal", $class_names)) {
                    $prev_node = $td;
                    continue;
                }
                if (in_array("order-summary-table-order-total", $class_names)) {
                    $this->order_details['totals'][trim($prev_node->textContent)] = $this->getOrderSubTotals($td);
                    continue;
                }
                if (in_array("order-total-amount", $class_names)) {
                    $this->order_details['totals']['vat'] = $this->getVAT($td);
                    continue;
                }
                if (in_array("order-total-fullprice", $class_names)) {
                    $this->order_details['totals']['total'] = (float)str_replace("£", "", trim($td->nodeValue));
                    continue;
                }
            }
            $spans = $this->dom->getElementsByTagName('span');
            foreach ($spans as $span) {
                if ($span->className == "methods-details-method-name")
                    $this->order_details["postage_method"] = trim($this->removeNewlines($span->textContent));
            }
        }

        catch (Exception $e) {
            error_log($e);
            throw new Exception($e);
        }
    }

    public function rearrayItems() {
        foreach ($this->order_details['items'] as $idx=>&$item) {
            $item['amount'] = $this->order_details['item_prices'][$idx]['amount'];
            $item['price'] = $this->order_details['item_prices'][$idx]['price'];
        }
        unset($this->order_details['item_prices']);
    }

    public function get() {
        return $this->order_details;
    }

    /**
     * Get the customer name, order date, and order time from the email.
     * @return array associative array with keys 'order_date', 'order_time', and 'name'
     */
    private function getOrderDetails($node) {
        $headings = $node->getElementsByTagName('h1');
        foreach ($headings as $heading) {
            $class_list = $this->getClassList($heading);
            if (in_array("subheader-ordersubject-header", $class_list)) {
                $order_no_arr = explode("#", $heading->textContent);
                $order_no = (int)array_pop($order_no_arr);
                $name_string = $heading->nextSibling->textContent;
                $name_array = $this->splitNameString($name_string);
            }
        }
        return ["order_no"=>$order_no, ...$name_array];
    }

    /**
     * Takes a string containing customer name, order date, and order time
     * and splits it into an associative array with keys 'order_date',
     * 'order_time', and 'name'.
     *
     * @param string $string The string to be split.
     * @return array An associative array with the extracted information.
     */
    private function splitNameString($string) {
        $name_arr = [];
        $string = $this->removeNewlines($string);
        $tmp_arr = explode(' ', $string);
        if (trim($tmp_arr[0]) == '') return false;
        $name_arr['order_date'] = array_shift($tmp_arr);
        $name_arr['order_time'] = array_shift($tmp_arr);
        foreach($tmp_arr as &$el) $el = trim($el);
        foreach($tmp_arr as &$el) $el = ucwords($el);
        $name_arr['name'] = $this->removeNewlines(implode(" ", $tmp_arr));
        return $name_arr;
    }

    /**
     * Extracts contact details from a given DOM element.
     *
     * @param DOMElement $td The DOM element containing contact information.
     * @return array An associative array with keys 'email' and 'phone' containing 
     *               the extracted contact details.
     */

    private function getContactDetails($td) {
        $divs = $td->getElementsByTagName('div');
        $data = [];
        foreach ($divs as $div) {
            $data[] = $div->nodeValue;
        }

        if (sizeof($data) == 0) {
            $ps = $td->getElementsByTagName('p');
            foreach ($ps as $p) {
                $data[] = $p->nodeValue;
            }
        }

        return [
            "email" => $data[1],
            "phone" => $data[2]
        ];
    }

    /**
     * Extracts address details from a given DOM element.
     *
     * @param DOMElement $td The DOM element containing address information.
     * @return array An associative array with keys 'address', 'postcode', and
     *               'country' containing the extracted address details.
     */
    private function getAddressDetails($td) {
        $divs = $td->getElementsByTagName('div');
        $data = [];
        foreach ($divs as $div) {
            $data[] = $div->nodeValue;
        }
        if (sizeof($data) == 0) {
            $ps = $td->getElementsByTagName('p');
            foreach ($ps as $p) {
                $data[] = $p->nodeValue;
            }
        }
        $tmp_arr = explode(" ", $this->removeNewlines($data[2]));
        $postcode = strtoupper(array_shift($tmp_arr) . " " . array_shift($tmp_arr));
        $town = implode(" ", $tmp_arr);

        return [
            "address" => ucwords($this->removeNewLines($data[1])),
            "town" => ucwords($town),
            "postcode" => $postcode,
            "country" => ucwords($this->removeNewlines($data[3]))
        ];
    }
    
    private function getItemDetails($td) {
        $divs = $td->getElementsByTagName('div');
        $data = [];
        foreach ($divs as $div) {
            $data[] = $div->nodeValue;
        }
        if (sizeof($data) == 0) {
            $ps = $td->getElementsByTagName('p');
            foreach ($ps as $p) {
                $data[] = $p->nodeValue;
            }
        }
        return [
            "item" => $this->removeNewlines($data[0]),
        ];

    }

    private function getItemPrices($td) {
        $price_arr = explode("x", $td->textContent);
        return [
            "amount"=>$this->get_numeric(trim($price_arr[0])),
            "price"=>$this->get_numeric(str_replace("£", "", trim($price_arr[1])))
        ];
    }

    private function getOrderSubTotals($td) {
        return (float)str_replace("£", "", $td->textContent);
    }

    private function getVAT($td) {
        $divs = $td->getElementsByTagName('div');
        foreach ($divs as $div) {
            if ($div->className == "order-total-tax") {
                $str = $div->textContent;
                $str = str_replace("incl.", "", $str);
                $str = str_replace("VAT", "", $str);
                return (float)str_replace("£", "", trim($str));
            }
        }
        $ps = $td->getElementsByTagName('p');
        foreach ($ps as $p) {
            if ($p->className == "order-total-tax") {
                $str = $p->textContent;
                $str = str_replace("incl.", "", $str);
                $str = str_replace("VAT", "", $str);
                return (float)str_replace("£", "", trim($str));
            }
        }
    }

    private function removeNewlines($str) {
        if (!$str) return "";
        $str = str_replace("\n", "", $str);
        $str = str_replace("\r", " ", $str);
        return $str;
    }
    

    private function getClassList($node) {
        return explode(" ", $node->className);
    }

    private function get_numeric($val) {
        if (is_numeric($val)) {
          return $val + 0;
        }
        return 0;
      }
}
