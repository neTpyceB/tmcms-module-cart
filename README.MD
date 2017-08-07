Example




    $articule = new ArticleEntity($post_data['articule_id']);
    $product_type = Converter::classWithNamespaceToUnqualifiedShort($articule);
    
    $current_amount = 0;
    
    $current_item_in_cart = ModuleCart::getCurrentCartItems($product_type);
    if ($current_item_in_cart) {
        $current_amount = $current_item_in_cart->getAmount();
    }
    
    $cart_item = new CartItemEntity();
    $cart_item->setAmount($current_amount + $post_data['amount']);
    $cart_item->setItemId($articule->getId());
    $cart_item->setItemType($product_type);
    
    ModuleCart::addItem($cart_item);