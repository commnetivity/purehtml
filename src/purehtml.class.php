<?php

/*
	Author: Nicholas Maietta <nick@icode4u.com>
	License: GPLv3
*/

class PureHTML {
    var $stylesheets;
    var $javascripts;

    public function __construct() {
        $this->stylesheets = (object) array("head"=>array(), "body"=>array());
        $this->javascripts = (object) array("head"=>array(), "body"=>array());
		$this->index = array();
    }
    public function title($html, $title="") {
		$html = $this->getInstanceOfDom($html);
		$html->getElementsByTagName("title")->item(0)->nodeValue = $title;
		return $html;
	}
	
    public function scan($html, $target="") {
		$html = $this->getInstanceOfDom($html);
		$blocks = array();
		if ( $target == "body" ) {
			$blocks = array("body");
		} elseif( $target == "head" ) {
			$blocks = array("head");
		} else {
			$blocks = array("body", "head");
		}

		foreach($blocks as $block) {
			$block = ( $block == "head" ) ? $html->getElementsByTagName('head')->item(0) : $html->getElementsByTagName('body')->item(0);
			if ( !empty($block) ) {
				$assets = $block->getElementsByTagName('link');
				for ($i = 0; $i < $assets->length; $i++) {
					$resource = $assets->item($i);
					$attributes = array();
					foreach ($resource->attributes as $attribute=>$value) {
						if ( strlen($value->nodeValue) > 0 ) {
							$attributes[$value->nodeName] = trim($value->nodeValue);
						}
					}
					ksort($attributes);
					$key = sha1(serialize($attributes));
					if ( !in_array($key, $this->index) ) {
						if ( $resource->hasAttribute('body') ) {
							$this->stylesheets->body[] = $attributes;
						} else {
							$this->stylesheets->head[] = $attributes;
						}
						$this->index[] = $key;
					}
				}
				$assets = $block->getElementsByTagName('script');
				for ($i = 0; $i < $assets->length; $i++) {
					$resource = $assets->item($i);
					if ( strlen(trim($resource->textContent)) > 12 ) {
						$key = sha1(serialize(trim($resource->textContent)));
						if ( !in_array($key, $this->index) ) {
							if ( $resource->hasAttribute('body') ) {
								$this->javascripts->body[] = rtrim($resource->textContent);
							} else {
								$this->javascripts->head[] = rtrim($resource->textContent);
							}
							$this->index[] = $key;
						}
					} else {
						$attributes = array();
						foreach ($resource->attributes as $attribute=>$value) {
							if ( strlen($value->nodeValue) > 0 ) {
								$attributes[$value->nodeName] = trim($value->nodeValue);
							}
						}
						ksort($attributes);
						$key = sha1(serialize($attributes));
						if ( !in_array($key, $this->index) ) {
							if ( $resource->hasAttribute('body') ) {
								$this->javascripts->body[] = $attributes;
							} else {
								$this->javascripts->head[] = $attributes;
							}
							$this->index[] = $key;
						}
					}
				}	
				$assets = $block->getElementsByTagName('style');
				for ($i = 0; $i < $assets->length; $i++) {
					$resource = $assets->item($i);
					if (  strlen(trim($resource->textContent)) > 6  ) {	// *{v:1;}
						$key = sha1(serialize(trim($resource->textContent)));
						if ( !in_array($key, $this->index) ) {					
							if ( $resource->hasAttribute('body') ) {
								$this->stylesheets->body[] = rtrim($resource->textContent);
							} else {
								$this->stylesheets->head[] = rtrim($resource->textContent);
							}
							$this->index[] = $key;
						}
						
					}
				}
			}
		}
		return $this;
    }

	public function getInstanceOfDom($dom) {
		if ( !$dom instanceof DOMDocument ) {
			$nonDom= $dom;
            libxml_use_internal_errors(true);
            $dom = new DOMDocument('1.0', 'UTF-8');
			$dom->loadHTML($nonDom);
			unset($nonDom);
        }
		return $dom;
	}	

	private function removeNode(&$node) {
		$parent = $node->parentNode;
		$this->removeChildren($node);
		$parent->removeChild($node);
	}
	
	private function removeChildren(&$node) {
		while ($node->firstChild) {
			while ($node->firstChild->firstChild) {
				$this->removeChildren($node->firstChild);
			}
			$node->removeChild($node->firstChild);
		}
	}
	
    public function scrub($html, $what_to_strip="") {
		$html = $this->getInstanceOfDom($html);
        switch($what_to_strip) {
            case "styles":
                $nodes = $html->getElementsByTagName("link");
                while ($nodes->length > 0) { $node = $nodes->item(0); $this->removeNode($node); }
                $nodes = $html->getElementsByTagName("style");
                while ($nodes->length > 0) { $node = $nodes->item(0); $this->removeNode($node); }
                break;
            case "scripts":
                $nodes = $html->getElementsByTagName("script");
                while ($nodes->length > 0) { $node = $nodes->item(0); $this->removeNode($node); }
                break;
            default:
                $nodes = $html->getElementsByTagName("link");
                while ($nodes->length > 0) { $node = $nodes->item(0); $this->removeNode($node); }
                $nodes = $html->getElementsByTagName("style");
                while ($nodes->length > 0) { $node = $nodes->item(0); $this->removeNode($node); }
                $nodes = $html->getElementsByTagName("script");
                while ($nodes->length > 0) { $node = $nodes->item(0); $this->removeNode($node); }
                break;
        }
		return $html->saveHTML();
    }
	
    public function splice($html, $new, $tag) {
		$html = $this->getInstanceOfDom($html);
		$doc = new DOMDocument();
		$doc->loadHTML('<?xml encoding="UTF-8">' . $new);
		$element = $html->getElementById($tag);
		while($element->childNodes->length){
			$element->removeChild($element->firstChild);
		}
		$xpath = new DOMXPath($doc);
		$body = $xpath->query('/html/body');
		$frag = $html->createDocumentFragment();
		$body = $doc->saveXml($body->item(0));
		$frag->appendXML($body);
		@$element->appendChild($frag);
		return $html->saveHTML($html->documentElement);
    }

    public function rebuild($html, $resources=false) {
		$dom = $this->getInstanceOfDom($html);
		
		$this->used = array();
		foreach($this->stylesheets as $dom_location=>$resources) {
			foreach($resources as $resource) {
				$domDocument = new DOMDocument();
				if ( is_array($resource) )  {
					$domElement = $domDocument->createElement('link');
					$keys = array_keys($resource);
					$sorted_resource = array_merge(array_flip(array('href', 'integrity', 'crossorigin', 'hreflang', 'defer', 'rel', 'media', 'sizes',  'type')), $resource);
					foreach($sorted_resource as $element=>$value) {
						if ( !in_array($element, $keys) ) {
							continue;
						}
						$domAttribute = $domDocument->createAttribute($element);
						$domAttribute->value = $value;
						$domElement->appendChild($domAttribute);
					}
					$domDocument->appendChild($domElement);
					$frag = $dom->createDocumentFragment();
					$items = explode("\n", $domDocument->saveXML()); array_shift($items);
					$frag->appendXML(implode("\n", $items));
					if ( $dom_location == "body" ) {	
						$dom->getElementsByTagName('body')->item(0)->appendChild($frag);
					} else {
						$dom->getElementsByTagName('head')->item(0)->appendChild($frag);
					}
				}
			}
		}
		
		foreach($this->javascripts as $dom_location=>$resources) {
			foreach($resources as $resource) {
				$domDocument = new DOMDocument();
				if ( is_array($resource) ) {
					$domElement = $domDocument->createElement('script');
					$keys = array_keys($resource);
					$sorted_resource = array_merge(array_flip(array('src', 'integrity', 'crossorigin', 'async', 'defer', 'charset', 'type')), $resource);					
					foreach($sorted_resource as $element=>$value) {
						if ( !in_array($element, $keys) ) {
							continue;
						}
						$domAttribute = $domDocument->createAttribute($element);
						$domAttribute->value = $value;
						$domElement->appendChild($domAttribute);
					}
					$domDocument->appendChild($domElement);
					$items = explode("\n", $domDocument->saveXML()); array_shift($items);
					$frag = $dom->createDocumentFragment();
					$frag->appendXML(implode("\n", $items));
					if ( $dom_location == "body"  ) {
						$dom->getElementsByTagName('body')->item(0)->appendChild($frag);
					} else {
						$dom->getElementsByTagName('head')->item(0)->appendChild($frag);
					}
				}
				if ( is_string($resource) && strlen(trim($resource)) > 6 ) {
					$domElement = $domDocument->createElement('script', trim($resource));
					$domDocument->appendChild($domElement);
					$items = explode("\n", $domDocument->saveXML()); array_shift($items);
					$frag = $dom->createDocumentFragment();
					$frag->appendXML(implode("\n", $items));
					if ( $dom_location == "body" ) {
						$dom->getElementsByTagName('body')->item(0)->appendChild($frag);
					} else {
						$dom->getElementsByTagName('head')->item(0)->appendChild($frag);
					}
				}
			}
		}
		
		foreach($this->stylesheets as $dom_location=>$resources) {
			foreach($resources as $resource) {
				$domDocument = new DOMDocument();
				if ( !is_array($resource) ) {
					if ( strlen(trim($resource)) > 0 ) {
						$domElement = $domDocument->createElement('style', trim($resource));
						$domDocument->appendChild($domElement);
						$items = explode("\n", $domDocument->saveXML()); array_shift($items);
						$frag = $dom->createDocumentFragment();
						$frag->appendXML(implode("\n", $items));
						if ( $dom_location == "body" ) {
							$dom->getElementsByTagName('body')->item(0)->appendChild($frag);
						} else {
							$dom->getElementsByTagName('head')->item(0)->appendChild($frag);
						}
					}
				}
			}
		}
		return $dom;
    }
	
	function hasChild($p) {
		if ($p->hasChildNodes()) {
			foreach ($p->childNodes as $c) {
				if ($c->nodeType == XML_ELEMENT_NODE) {
					return true;
				}
			}
		}
		return false;
	}
	
	function beautifyDOM($html, $depth=1) {
		if ( $depth == 1 ) $markup = "<!DOCTYPE html>\n";
		$no_returns = array("title", "a", "script", "h1", "h2", "h3", "h5", "h6", "p", "legend", "label", "br", "span", "option", "button", "iframe", "i");
		$self_closing = array("area", "base","br","col","command","embed","hr","img","input","keygen","link","meta","source","track","wbr");
		foreach ($html->childNodes as $node) {
			if ($node->nodeType == XML_ELEMENT_NODE ) {
				$markup .= ( $node->parentNode->tagName != "p" )
					?        str_repeat("   ", $depth-1)."<" . $node->tagName
					: "\n" . str_repeat("   ", $depth-1)."<" . $node->tagName;
				foreach($node->attributes as $attribute) {
					$markup .= " ".$attribute->name;
					if ($attribute->value != "") {
						$markup .= '="'.$attribute->value.'"';
					}
				}
				if ( $node->hasChildNodes() ) {
					$factorial = function($n, $depth) use (&$factorial) {
						foreach ($n->childNodes as $p) {
							if (!$this->hasChild($p)) {
								$p->nodeValue = wordwrap(trim($p->nodeValue), 200, "\n".str_repeat("   ", $depth));
								return false;
							} else {
								$factorial($p, $depth);
							}
						}
					};
					$factorial($node, $depth);
				}
				if ( in_array($node->tagName, $self_closing) && $node->parentNode->nodeName != "head" ) {
					$markup .= ' />'.trim($node->nodeValue);
				} else {
					$markup .= ( in_array($node->tagName, $no_returns) ) ? '>'.trim($node->nodeValue) : ">\n";
				}
				$markup .= $this->beautifyDOM($node, $depth+1);
				if ( !in_array($node->tagName, $self_closing) ) {
					if ( !in_array($node->tagName, $no_returns) ) {
						$markup .= str_repeat("   ", $depth-1);
					}
					$markup .= "</" . $node->tagName . ">\n";	
				}
				if ( $node->parentNode->tagName == "p" ) {
					$markup .= str_repeat("  ", $depth);
				}
			}
		}
		$markup = preg_replace('/a>\n\s+<\/li/', 'a></li', $markup);
		$markup = preg_replace('/li>\n\s+<a/', 'li><a', $markup);
		$markup = preg_replace('/<\/a>\n\s+<\/p>/', '</a></p>', $markup);
		$markup = preg_replace('/>\n\s+<input/', '><input', $markup);
		$markup = preg_replace('/>\n\s+<button/', '><button', $markup);
		$markup = preg_replace('/\n\s+<a/', ' <a', $markup);
		$markup = preg_replace('/">\n\s+<\//', '"></', $markup);
		$markup = preg_replace('/\/>\s+<\//', "/></",  $markup);
		$markup = str_replace("<br /></p>", "</p>", $markup);
		$markup = preg_replace('/p>\n\s+<img/', 'p><img', $markup);
		return $markup;
	}
}

?>
