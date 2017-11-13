<?php

namespace App\Http\Controllers\Scraper;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\DomCrawler\Crawler;
use App\Products;

class ProductsController extends Controller
{
    private $_products = [],
            $_product_dom;

    public function info($products_dom){
        $crawler = new Crawler($products_dom);

        $elements = $crawler->filterXPath('//tr')->each(function(Crawler $node, $i){
            return $node->html();
        });

        for ($i=0; $i < 2; $i++) { 
            array_pop($elements);
        }

        foreach($elements as $k => $element){
            $crawler = new Crawler($element);
            $td = $crawler->filterXPath('//td');

            if($td->count() > 2){
                $this->_product_dom = $this->product($td);
                $this->_products[] = [
                    'product_name' => $this->productName(),
                    'category' => $this->category(),
                    'price' => $this->price(),
                    'amount' => $this->amount(),
                    'sub_roducts' => []
                ];

            } else {
                $this->_products[count($this->_products) - 1]['sub_products'][] = $this->subProduct($td);
            }
        }

        return $this->_products;
    }


    private function product($td){
        $product = $td->filterXPath('//td');

        return $product;
    }

    private function category(){
        return str_replace('[', '', $this->title()[0]);
    }

    private function title(){
        $title = explode('] ', trim($this->_product_dom->eq(1)->text()));

        return $title;
    }

    private function productName(){
        return $this->title()[1];
    }

    private function subProduct($td){
    	$sub = trim($td->filterXPath('//td')->eq(1)->text());
    	$sub = str_replace('+ ', '', $sub);
    	
    	return $sub;
    }

    private function price(){
        return trim(str_replace(',', '.', $this->_product_dom->eq(3)->text()));
    }

    private function amount(){
        return trim($this->_product_dom->eq(0)->text());
    }

    public static function saveDB($products, $order_id, $customer_id){
        foreach ($products as $product) {
            $order = Products::firstOrNew(['order_id' => $order_id]);
            $order->order_id = $order_id;
            $order->customer_id = $customer_id;

            foreach ($product as $key => $value) {
                if($products[$key] == 'sub_products'){
                    $order->sub_products = json_encode($value);
                } else {
                    $order->$key = $value;
                }
            }

            $order->save();                
        }
    }
}
