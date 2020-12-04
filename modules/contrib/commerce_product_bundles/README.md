# Commerce Product Bundles

CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

Commerce Product Bundles module provides new Commerce content entity type that allows You to create separate Bundle Products
with Bundle Product Variations (Purchasable entity) that are referencing multiple Commerce Product Variations - bundles.

Each Bundle Variation provides 'Price' field allowing You to bundle two or more Product Variations by new/different price.
One can also create multiple translatable bundles types of Commerce Product Bundles and Bundle Variations.

This module was modeled and designed after Drupal Commerce module (see https://www.drupal.org/project/commerce) following Commerce
architecture, it is fully compatible and depended up on Drupal Commerce.

REQUIREMENTS
------------

This module requires:
1. Commerce 2 (Submodules: Commerce Product, Commerce Cart, Commerce Checkout)
2. Commerce Currencies Price
3. Commerce Currency Resolver
4. Select2 (see https://www.drupal.org/project/select2 for set-up instructions)

INSTALLATION
------------

Install the Commerce Product Bundles module as you would normally install
any Drupal contrib module.

Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
--------------

To be able to create Product Bundles and Bundle Variations You must first define and create commerce products (with variations).

1. Product Bundle Types and Bundle Variation Types:
   - Configure Product Bundle Types and Bundle Variation Types under /admin/commerce/config
     * /admin/commerce/config/product-bundles-types
     * /admin/commerce/config/product-bundles-variation-types
   - Module provides 'Default' Product Bundle Type and Bundle Variation Type

2. Add new Product Bundles under /admin/commerce/product-bundles
   - Translations are supported too.
   - Multiple Bundle Variations can be added to each Product Bundle

3. Adding Bundle Variations:
   - Bundle Variations fields:
     * 'title'
     * 'price' => type: 'commerce_currencies_price' ('CommerceBundleCurrencyResolver' resolver is provided)
     * 'product_variation_id' => type: 'product_bundle_field' - Field for referencing Commerce Product Variations and setting
        quantity (defaults to 1)
     * 'bundle_image' => Bundle image field for mapping images to referenced product variations (Field type and widget
        are done, formatter still have open issues)
   - Product Variation reference field - multivalue field contained of two columns: product_var_id and quantity
       - It is possible to create multiple references - each referencing commerce product and selected variations for that
         product and each has quantity field to set how many variations are in bundle
   - Module provides Bundle Variations widget ('commerce_product_bundles_variation')
     * Radio btn. select widget on two levels:
        1. Choose Bundle Variation
        2. Choose referenced Product Variations for each referenced commerce product
           - All commerce attributes for singe product variations are merged and displayed as single attribute (e.g. 'Red + Size M')
           - If product variations does NOT have product attributes defined, product variation title is displayed (role model was
            Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationTitleWidget() widget).
     * example:
     ```
       Bundle variation nas 3 referenced commerce products

       Product1 - Product1 variation1    quantity = 2
                  Product1 variation2
                  Product1 variation2

       Product2 - Product2 variation21   quantity = 1
                  Product2 variation22

       Product3 - Product3 variation31   quantity = 3
                  Product3 variation32
                  Product3 variation33

       - when purchasing this bundle variation user will always get all 3 products with matching quantities but will choose
         which variation of each product
       - e.g. user chooses:
          Produc1 variation2
          Produc2 variation21
          Product3 variation33
       - user gets:
          Produc1 variation2 X 2
          Produc2 variation21 X 1
          Product3 variation33 X 3
     ```
   - Bundle Image (optional field) - Field extends ImageItem field type with addition of 'Product Variations Combo'
     - 'Product Variations Combo' - to map bundle variation images to each referenced product variation combination, all possible
       combinations needs to have their image (if not fallback will be to first image)

         ```
       e.g. if Bundle Variation have 2 referenced Products (each with 2 referenced variations)
       that 2X2 images needs to be uploaded:

         Product1 - Product1 variation1
                    Product1 variation2
         Product2 - Product2 variation21
                    Product2 variation22

       'Product Variations Combo':
        image1 = Product1 variation1, Product2 variation21
        image2 = Product1 variation1, Product2 variation22
        image3 = Product1 variation2, Product2 variation21
        image4 = Product1 variation2, Product2 variation21
       ```

4. Order items - Module provides 'Bundle' order item type.
   - For each Order Item type created that has 'Purchasable entity type' set to 'Product bundle variation', new field
   'Product Variation Reference' is added (field stores referenced product variations for Purchased bundle variation and its
     quantity)

   - Field provides separate db table for storing information (ids and quantity) about all referenced variations for purchased bundle variation
5. Module provides Events for Product Bundle and Bundle Variation (see FilterBundleVariationsEvent.php and
   ProductBundleEvents.php)

6. Module provides Commerce conditions for Product Bundle, Product Bundle Type, Bundle Variation Type and Bundle Variation

7. Use 'Order item title formatter' for Commerce Order Item Title field on commerce_order_item_table view to get full descriptive 
    title with bundle title bundle variation title and all included referenced PV with quantities on order view in back-office.

MAINTAINERS
-----------

The 8.x-1.x branch was created by:

 * Antonija Arbanas (agolubic) - https://www.drupal.org/u/agolubic

This module was created and sponsored by Foreo,
Swedish multi-national beauty brand.

 * Foreo - https://www.foreo.com/
