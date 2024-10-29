<?php

if (!class_exists('AIDN_AmazonLoader')):

    class AIDN_AmazonLoader extends AIDN_AbstractLoader {

        public function prepare_filter($filter) {
            if (!isset($filter['sitecode'])) {
                $filter['sitecode'] = get_option('aidn_amazon_default_site', 'com');
            }
            return $filter;
        }

        public function loadList($filter, $page = 1) {
            $MAX_RESULT_ITEMS = 50;
            $per_page = get_option('aidn_amazon_per_page', 10);
            $result = array("total" => 0, "per_page" => $per_page, "items" => array(), "error" => "");
            if ((isset($filter['aidn_productId']) && !empty($filter['aidn_productId'])) || (isset($filter['aidn_query']) && !empty($filter['aidn_query'])) || (isset($filter['category_id']) && $filter['category_id'] != 0)) {
                $single_product_id = (isset($filter['aidn_productId']) && $filter['aidn_productId']) ? $filter['aidn_productId'] : "";

                $query = (isset($filter['aidn_query'])) ? utf8_encode($filter['aidn_query']) : "";

                $site = isset($filter['sitecode']) ? $filter['sitecode'] : get_option('aidn_amazon_default_site', 'com');

                $category_id = (isset($filter['category_id']) && $filter['category_id']) ? $filter['category_id'] : "";
                $link_category_id = (isset($filter['link_category_id']) && IntVal($filter['link_category_id'])) ? IntVal($filter['link_category_id']) : 0;

                $priceFrom = (isset($filter['aidn_min_price']) && !empty($filter['aidn_min_price']) && floatval($filter['aidn_min_price']) > 0.009) ? sprintf("%01.2f", floatval($filter['aidn_min_price'])) : false;
                $priceTo = (isset($filter['aidn_max_price']) && !empty($filter['aidn_max_price']) && floatval($filter['aidn_max_price']) > 0.009) ? sprintf("%01.2f", floatval($filter['aidn_max_price'])) : false;

                $condition = (isset($filter['condition']) && $filter['condition']) ? $filter['condition'] : "";
                // <---------------------------

                if ($single_product_id) {
                    $params = array(
                        "Operation" => "ItemLookup",
                        "ItemId" => $single_product_id,
                        "IdType" => "ASIN",
                        "ResponseGroup" => "Images,ItemAttributes,Large,OfferFull,Offers,OfferSummary,VariationImages,Variations,VariationSummary",
                        "Version" => "2015-10-01"
                    );
                } else {
                    $params = array(
                        "Operation" => "ItemSearch",
                        "SearchIndex" => "All",
                        "ItemPage" => $page,
                        "Keywords" => $query,
                        "ResponseGroup" => "Images,ItemAttributes,Large,OfferFull,Offers,OfferSummary",
                        "Version" => "2015-10-01"
                            //"ResponseGroup" => "Images,ItemAttributes,Offers",
                            //"Sort" => "price"
                    );

                    if ($priceFrom) {
                        $params['MinimumPrice'] = intval(floatval($priceFrom) * 100);
                    }
                    if ($priceTo) {
                        $params['MaximumPrice'] = intval(floatval($priceTo) * 100);
                    }
                    if ($category_id) {
                        $params['SearchIndex'] = $category_id;
                    }
                    if ($condition) {
                        $params['Condition'] = $condition;
                    }
                    if ($condition != "New") {
                        $params['Availability'] = "Available";
                    }
                }
                //print_r($params);
                $response = $this->send_amazon_request($site, $params);

                if (isset($response['error'])) {
                    $result["error"] = $response['error'];
                    if (isset($response['body_message']) && $response['body_message']) {
                        $result["error"] .= "<br/>" . $response['body_message'];
                    }
                } else {
                    //echo "<pre>";print_r($response);echo "</pre>";


                    $items = isset($response['Items']['Item']) && $response['Items']['Item'] ? $response['Items']['Item'] : array();
                    //echo "<pre>";print_r($items);echo "</pre>";
                    if ($items) {
                        $total_results = isset($response['Items']['TotalResults']) ? IntVal($response['Items']['TotalResults']) : 1;

                        if ($single_product_id || $total_results === 1) {
                            $items = $items ? array($items) : array();
                        }

                        if ($total_results === 1) {
                            $total_results = count($items);
                        }

                        $currency_conversion_factor = floatval(get_option('aidn_currency_conversion_factor', 1));
                        foreach ($items as $item) {
                            //echo "<pre>";print_r($item);echo "</pre>";

                            $goods = $this->parse_amazon_item($item, array('condition' => $condition));


                            //if(!$condition || ($condition && isset($goods->additional_meta['condition']) && $goods->additional_meta['condition']===$condition)){
                            $goods->link_category_id = $link_category_id;

                            $goods->save("API");

                            if (strlen(trim((string) $goods->user_price)) == 0) {
                                $goods->user_price = round($goods->price * $currency_conversion_factor, 2);
                                $goods->saveField("user_price", sprintf("%01.2f", $goods->user_price));

                                if ($goods->regular_price) {
                                    $goods->user_regular_price = round($goods->regular_price * $currency_conversion_factor, 2);
                                    $goods->saveField("user_regular_price", sprintf("%01.2f", $goods->user_regular_price));
                                }
                            }

                            if (strlen(trim((string) $goods->user_image)) == 0) {
                                $goods->saveField("user_image", $goods->image);
                            }

                            $result["items"][] = apply_filters('aidn_modify_goods_data', $goods, $item, "amazon_load_list");
                            //}
                        }
                        $result["total"] = $total_results > $MAX_RESULT_ITEMS ? $MAX_RESULT_ITEMS : $total_results;
                    } else {
                        $result["error"] = 'There is no product to display!';
                    }
                }
            } else {
                $result["error"] = 'Please enter some search keywords or select item from category list!';
            }

            return $result;
        }

        /**
         * @param AIDN_Goods $goods
         * @param array $params
         * @return mixed
         */
        public function loadDetail(&$goods, $params = array()) {
            return array("state" => "ok", "message" => "", "goods" => $goods);
        }

        public function getDetail($productId, $params = array()) {
            $prms = array("Operation" => "ItemLookup", "ItemId" => $productId, "IdType" => "ASIN", "ResponseGroup" => "Images,ItemAttributes,Large,OfferFull,Offers,OfferSummary", "Version" => "2015-10-01");

            $site = isset($params['sitecode']) ? $params['sitecode'] : get_option('aidn_amazon_default_site', 'com');

            $response = $this->send_amazon_request($site, $prms);

            if (isset($response['error'])) {
                return array('state' => 'error', 'message' => $response['error']);
            } else {
                $item = $response['Items']['Item'];

                if ($item) {
                    $currency_conversion_factor = floatval(get_option('aidn_currency_conversion_factor', 1));

                    $goods = $this->parse_amazon_item($item);

                    $goods->user_price = round($goods->price * $currency_conversion_factor, 2);

                    if ($goods->regular_price) {
                        $goods->user_regular_price = round($goods->regular_price * $currency_conversion_factor, 2);
                    }

                    return array("state" => "ok", "message" => "", "goods" => apply_filters('aidn_modify_goods_data', $goods, $item, "amazon_get_detail"));
                }
            }
        }

        public function checkAvailability(/* @var $goods AIDN_Goods */ $goods) {
            return true;
        }

        public function parse_amazon_item($item, $params = array()) {
            //echo "<pre>";print_r($params);echo "</pre>";
            //if($item["ASIN"] == "B007VM4U2Y"){echo "<pre>";print_r($item);echo "</pre>";}
            //echo "<pre>";print_r($item);echo "</pre>";

            $goods = new AIDN_Goods();
            $goods->type = "amazon";
            $goods->external_id = $item["ASIN"];
            $goods->load();

            $goods->image = (isset($item["LargeImage"]["URL"])) ? $item["LargeImage"]["URL"] : AIDN_NO_IMAGE_URL;

            $goods->detail_url = $item["DetailPageURL"];

            $goods->title = $item["ItemAttributes"]["Title"];

            $goods->subtitle = "#notuse#";
            $goods->keywords = "#notuse#";

            $goods->category_id = 0;
            if (isset($item["BrowseNodes"]["BrowseNode"]["Name"])) {
                $goods->category_name = $item["BrowseNodes"]["BrowseNode"]["Name"];
            } else if (isset($item["BrowseNodes"]["BrowseNode"][0]["Name"])) {
                $goods->category_name = $item["BrowseNodes"]["BrowseNode"][0]["Name"];
            }

            $goods->description = "";
            foreach ($item['ItemAttributes'] as $attr => $value) {
                if ($attr == "Feature") {
                    $goods->description .= '<div class="feature"><span>Feature:</span>';
                    $goods->description .= '<ul>';
                    $value = is_array($value) ? $value : array($value);
                    foreach ($value as $v) {
                        $goods->description .= "<li>" . $v . "</li>";
                    }
                    $goods->description .= '</ul>';
                    $goods->description .= '</div>';
                }
            }
            if (isset($item['EditorialReviews']['EditorialReview'])) {
                if (isset($item['EditorialReviews']['EditorialReview'][0])) {
                    foreach ($item['EditorialReviews']['EditorialReview'] as $dd) {
                        if ($dd['Source'] == 'Product Description') {
                            $goods->description .= '<div class="product_description">' . $dd['Content'] . '</div>';
                        }
                    }
                } else if (isset($item['EditorialReviews']['EditorialReview']['Content'])) {
                    $goods->description .= '<div class="product_description">' . $item['EditorialReviews']['EditorialReview']['Content'] . '</div>';
                }
            }

            $goods->description = AIDN_Utils::removeTags($goods->description);

            $attrs = array();
            $attr_exclude = array("EANList", "Feature", "Label", "PackageDimensions", "PackageQuantity", "ProductGroup", "ProductTypeName", "UPCList", "Title");
            foreach ($item['ItemAttributes'] as $attr => $value) {
                if (!in_array($attr, $attr_exclude) && !is_array($value)) {
                    $attrs[] = array("name" => $attr, "value" => $value);
                }
            }
            
            if(isset($item["ParentASIN"]) && $item["ParentASIN"]){
                $goods->additional_meta['parent_id'] = $item["ParentASIN"];    
            }

            $goods->additional_meta['attribute'] = ($attrs) ? $attrs : array();

            $tmp_p = "";
            if (isset($item['ImageSets'])) {
                $images = isset($item['ImageSets']['ImageSet'][0]) ? $item['ImageSets']['ImageSet'] : array($item['ImageSets']['ImageSet']);
                foreach ($images as $img) {
                    if ($img["@attributes"]["Category"] == "variant") {
                        $tmp_p .= ($tmp_p ? "," : "") . $img["LargeImage"]["URL"];
                    }
                }
            }
            $goods->photos = $tmp_p;


            $tmp_condition = "";
            $tmp_seller = "";
            $tmp_curr = "";
            $tmp_price = 0;
            $tmp_percentage_saved = 0;

            $get_price_by_condition = true;


            //echo "<pre>";print_r($item['Offers']);echo "</pre>";
            //echo "<pre>";print_r($item['OfferSummary']);echo "</pre>";
            if (isset($item['Offers']['Offer'])) {
                $tmp_offers = (intval($item['Offers']['TotalOffers']) == 1) ? array($item['Offers']['Offer']) : $item['Offers']['Offer'];
                if (isset($params['condition']) && $get_price_by_condition) {
                    foreach ($tmp_offers as $offer) {
                        if ($params['condition'] == $offer['OfferAttributes']['Condition']) {
                            if (isset($offer['OfferListing']['SalePrice']['Amount'])) {
                                $tmp_curr = $offer['OfferListing']['SalePrice']['CurrencyCode'];
                                $tmp_price = floatval($offer['OfferListing']['SalePrice']['Amount']) / 100;
                            } else {
                                $tmp_curr = $offer['OfferListing']['Price']['CurrencyCode'];
                                $tmp_price = floatval($offer['OfferListing']['Price']['Amount']) / 100;
                            }
                            $tmp_condition = $offer['OfferAttributes']['Condition'];
                        }
                    }
                }
                if (!$tmp_price) {
                    $cur_tmp_curr = '';
                    $cur_tmp_price = 0;
                    $cur_tmp_percentage_saved = 0;
                    foreach ($tmp_offers as $offer) {
                        // find mix price
                        //if (!$tmp_curr || $tmp_price > (floatval($offer['OfferListing']['Price']['Amount']) / 100)) {
                        // find max price

                        if (isset($offer['OfferListing']['SalePrice']['Amount'])) {
                            $cur_tmp_curr = isset($offer['OfferListing']['SalePrice']['CurrencyCode']) ? $offer['OfferListing']['SalePrice']['CurrencyCode'] : "";
                            $cur_tmp_price = isset($offer['OfferListing']['SalePrice']['Amount']) ? floatval($offer['OfferListing']['SalePrice']['Amount']) / 100 : 0;
                        } else {
                            $cur_tmp_curr = isset($offer['OfferListing']['Price']['CurrencyCode']) ? $offer['OfferListing']['Price']['CurrencyCode'] : "";
                            $cur_tmp_price = isset($offer['OfferListing']['Price']['Amount']) ? floatval($offer['OfferListing']['Price']['Amount']) / 100 : 0;
                            $cur_tmp_percentage_saved = (isset($offer['OfferListing']['PercentageSaved'])) ? IntVal($offer['OfferListing']['PercentageSaved']) : 0;
                        }

                        if (!$tmp_curr || $tmp_price < $cur_tmp_price) {
                            $tmp_curr = $cur_tmp_curr;
                            $tmp_price = $cur_tmp_price;
                            $tmp_condition = $offer['OfferAttributes']['Condition'];
                            $tmp_percentage_saved = $cur_tmp_percentage_saved;
                        }
                    }
                }
            } else { // last try... find some price
                if (isset($params['condition']) && isset($item['OfferSummary']['Total' . $params['condition']]) && intval($item['OfferSummary']['Total' . $params['condition']]) > 0) {
                    $tmp_condition = $params['condition'];

                    $tmp_curr = $item['OfferSummary']['Lowest' . $params['condition'] . 'Price']['CurrencyCode'];
                    $tmp_price = floatval($item['OfferSummary']['Lowest' . $params['condition'] . 'Price']['Amount']) / 100;
                }

                if (!$tmp_condition) {
                    if (isset($item['OfferSummary']['TotalNew']) && intval($item['OfferSummary']['TotalNew']) > 0) {
                        $tmp_condition = "New";
                        $tmp_curr = $item['OfferSummary']['LowestNewPrice']['CurrencyCode'];
                        $tmp_price = floatval($item['OfferSummary']['LowestNewPrice']['Amount']) / 100;
                    } else if (isset($item['OfferSummary']['TotalUsed']) && intval($item['OfferSummary']['TotalUsed']) > 0) {
                        $tmp_condition = "Used";
                        $tmp_curr = $item['OfferSummary']['LowestUsedPrice']['CurrencyCode'];
                        $tmp_price = floatval($item['OfferSummary']['LowestUsedPrice']['Amount']) / 100;
                    } else if (isset($item['OfferSummary']['TotalCollectible']) && intval($item['OfferSummary']['TotalCollectible']) > 0) {
                        $tmp_condition = "Collectible";
                        $tmp_curr = $item['OfferSummary']['LowestCollectiblePrice']['CurrencyCode'];
                        $tmp_price = floatval($item['OfferSummary']['LowestCollectiblePrice']['Amount']) / 100;
                    } else if (isset($item['OfferSummary']['TotalRefurbished']) && intval($item['OfferSummary']['TotalRefurbished']) > 0) {
                        $tmp_condition = "Refurbished";
                        $tmp_curr = $item['OfferSummary']['LowestRefurbishedPrice']['CurrencyCode'];
                        $tmp_price = floatval($item['OfferSummary']['LowestRefurbishedPrice']['Amount']) / 100;
                    }
                }
            }
            $goods->additional_meta['condition'] = $tmp_condition;

            /* if ($tmp_seller) {
              $goods->seller_url = "http://www.amazon.com/seller/" . $tmp_seller;
              }else{
              $goods->seller_url = "#notuse#";
              } */
            $goods->seller_url = "#notuse#";

            $goods->price = round(AIDN_Goods::getNormalizePrice($tmp_price), 2);
            if ($tmp_percentage_saved) {
                $goods->additional_meta['original_discount'] = $tmp_percentage_saved;
                $goods->regular_price = round(($goods->price * 100 / (100 - $tmp_percentage_saved)), 2);
            }

            $goods->curr = $tmp_curr;

            return $goods;
        }

        public function send_amazon_request($site_id, $prms = array()) {
            $params = is_array($prms) ? $prms : array();

            // The region you are interested in
            $endpoint = "webservices.amazon." . $site_id;
            $uri = "/onca/xml";

            $aws_associate_tag = $this->account->associate_tag;
            $aws_access_key_id = $this->account->access_key_id;
            $aws_secret_key = $this->account->secret_access_key;

            if (!isset($params['AWSAccessKeyId'])) {
                $params['AWSAccessKeyId'] = $aws_access_key_id;
            }
            if (!isset($params['AssociateTag'])) {
                $params['AssociateTag'] = $aws_associate_tag;
            }
            if (!isset($params['Service'])) {
                $params['Service'] = "AWSECommerceService";
            }

            // Set current timestamp if not set
            if (!isset($params["Timestamp"])) {
                $params["Timestamp"] = gmdate('Y-m-d\TH:i:s\Z');
            }

            // Sort the parameters by key
            ksort($params);

            $pairs = array();

            foreach ($params as $key => $value) {
                array_push($pairs, rawurlencode($key) . "=" . rawurlencode($value));
            }

            // Generate the canonical query
            $canonical_query_string = join("&", $pairs);

            // Generate the string to be signed
            $string_to_sign = "GET\n" . $endpoint . "\n" . $uri . "\n" . $canonical_query_string;

            // Generate the signature required by the Product Advertising API
            $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $aws_secret_key, true));

            // Generate the signed URL
            $request_url = 'http://' . $endpoint . $uri . '?' . $canonical_query_string . '&Signature=' . rawurlencode($signature);

            //echo "Signed URL: \"" . $request_url . "\"<br/>";

            $response = aidn_remote_get($request_url);
            //echo "<pre>";print_r($response);echo "</pre>";

            if (is_wp_error($response)) {
                return array("error" => "Amazon not response!");
            } else {
                if (wp_remote_retrieve_response_code($response) != '200') {
                    return array("error" => "[" . wp_remote_retrieve_response_code($response) . "] " . wp_remote_retrieve_response_message($response), "body_message" => wp_remote_retrieve_body($response));
                } else {
                    $body = wp_remote_retrieve_body($response);
                    //echo "<pre>";print_r($body);echo "</pre>";
                    $response_xml = simplexml_load_string($body);
                    //echo "<pre>";print_r($response_xml);echo "</pre>";

                    $response_json = json_encode($response_xml);
                    $response = json_decode($response_json, TRUE);

                    if ($response['Items']['Request']['IsValid'] == 'True') {
                        return $response;
                    } else {
                        return array("error" => $response_xml->Items->Request->Errors->Error->Code . "; " . $response_xml->Items->Request->Errors->Error->Message);
                    }
                }
            }
        }

    }

    

    

endif;