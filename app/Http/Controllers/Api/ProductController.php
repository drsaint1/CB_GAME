<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KoboCategory;
use App\Models\KoboFavourite;
use App\Models\KoboMerchant;
use App\Models\KoboMerchantOpening;
use App\Models\KoboOrder;
use App\Models\KoboSubCategory;
use App\Models\MerchantProduct;
use App\Models\MerchantProductImage;
use App\Models\MerchantService;
use App\Models\MerchantServiceImage;
use App\Models\ProductVariant;
use App\Models\ServiceVariant;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseController
{
    private $booking;

    public function __construct(BookingService $booking)
    {
        $this->booking = $booking;
    }

    public function getCategory()
    {
        $cat = KoboCategory::all();
        return $this->sendResponse($cat, 'All Categories');
    }

    public function subCategory(Request $request, $cat_id)
    {
        $sub = KoboSubCategory::where('kobo_categories_id', $cat_id)->get();
        return $this->sendResponse($sub, 'Sub Categories');
    }

    public function productByCategory(Request $request, $slug)
    {
        $cat = KoboCategory::where('slug', $slug)->first();
        if($slug == 'fuel-and-gas'){
            $products = DB::table('merchant_products')->select('merchant_products.uuid as id', 'merchant_products.*', 'product_variants.*', 'product_variants.uuid as product_variant_id',
            DB::raw('(select image_path from merchant_product_images where merchant_products_id  =   merchant_products.uuid ) as image')
            )
            ->join('product_variants', 'merchant_products.uuid', '=', 'product_variants.merchant_products_id')

            ->join('kobo_merchants', 'merchant_products.kobo_merchants_id', '=', 'kobo_merchants.uuid')
            ->where('merchant_products.kobo_categories_id', '=', $cat->uuid)
            ->where('merchant_products.status', '=', 1)
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)->get();

            $image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'image_url' => $image_url,
                'products' => $products
            ];

            return $this->sendResponse($data, 'Fuel and Gas Products');
        }
        if($slug == 'mart'){
            $products = DB::table('merchant_products')->select('merchant_products.uuid as id', 'merchant_products.*', 'product_variants.*', 'product_variants.uuid as product_variant_id',
            DB::raw('(select image_path from merchant_product_images where merchant_products_id  =   merchant_products.uuid ) as image')
            )
            ->join('product_variants', 'merchant_products.uuid', '=', 'product_variants.merchant_products_id')

            ->join('kobo_merchants', 'merchant_products.kobo_merchants_id', '=', 'kobo_merchants.uuid')
            ->where('merchant_products.kobo_categories_id', '=', $cat->uuid)
            ->where('merchant_products.status', '=', 1)
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)->get();

            $image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'image_url' => $image_url,
                'products' => $products
            ];

            return $this->sendResponse($data, 'Mart Products');
        }
        if($slug == 'food'){
            $products = DB::table('merchant_food')->select('merchant_food.uuid as id', 'merchant_food.*',
            DB::raw('(select image_path from merchant_food_images where merchant_food_id  =   merchant_food.uuid ) as image')
            )
            ->join('kobo_merchants', 'merchant_food.kobo_merchant_id', '=', 'kobo_merchants.uuid')
            ->where('merchant_food.kobo_category_id', '=', $cat->uuid)
            ->where('merchant_food.status', '=', 1)
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)->get();

            $image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'image_url' => $image_url,
                'products' => $products
            ];

            return $this->sendResponse($data, 'Foods');
        }
        if($slug == 'services'){
            $products = DB::table('merchant_services')->select('merchant_services.uuid as id', 'merchant_services.*', 'service_variants.*', 'service_variants.uuid as service_variant_id',
            DB::raw('(select image_path from merchant_service_images where merchant_services_id  =   merchant_services.uuid ) as image')
            )
            ->join('service_variants', 'merchant_services.uuid', '=', 'service_variants.merchant_services_id')

            ->join('kobo_merchants', 'merchant_services.kobo_merchants_id', '=', 'kobo_merchants.uuid')
            ->where('merchant_services.kobo_categories_id', '=', $cat->uuid)
            ->where('merchant_services.status', '=', 1)
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)->get();

            $image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'image_url' => $image_url,
                'products' => $products
            ];

            return $this->sendResponse($data, 'Services Products');
        }
        if($slug == 'shopping'){
            $products = DB::table('merchant_products')->select('merchant_products.uuid as id','merchant_products.*', 'product_variants.*', 'product_variants.uuid as product_variant_id',
            DB::raw('(select image_path from merchant_product_images where merchant_products_id  =   merchant_products.uuid ) as image')
            )
            ->join('product_variants', 'merchant_products.uuid', '=', 'product_variants.merchant_products_id')

            ->join('kobo_merchants', 'merchant_products.kobo_merchants_id', '=', 'kobo_merchants.uuid')
            ->where('merchant_products.kobo_categories_id', '=', $cat->uuid)
            ->where('merchant_products.status', '=', 1)
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)->get();

            $image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'image_url' => $image_url,
                'products' => $products
            ];

            return $this->sendResponse($data, 'Shopping Products');
        }
        if($slug == 'short-stay'){

            $products = DB::table('merchant_short_stays')->select('merchant_short_stays.uuid as id', 'merchant_short_stays.*',
            DB::raw('(select image_path from merchant_short_stay_images where merchant_short_stay_id  =   merchant_short_stays.uuid ) as image')
            )
            ->join('kobo_merchants', 'merchant_short_stays.kobo_merchant_id', '=', 'kobo_merchants.uuid')
            ->where('merchant_short_stays.kobo_category_id', '=', $cat->uuid)
            ->where('merchant_short_stays.status', '=', 1)
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)->get();

            $image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'image_url' => $image_url,
                'products' => $products
            ];

            return $this->sendResponse($data, 'Short Stays');
        }
    }

    public function productDetails(Request $request, $id)
    {
        $product = MerchantProduct::where('uuid', $request->id)->first();
        $service = MerchantService::where('uuid', $request->id)->first();
        $food = DB::table('merchant_food')->where('uuid', $request->id)->first();
        $short = DB::table('merchant_short_stays')->where('uuid', $request->id)->first();
        if(isset($product)){
            $images = MerchantProductImage::where('merchant_products_id', $product->uuid)->get();
            $kobo_merchant = KoboMerchant::where('uuid', $product->kobo_merchants_id)->first();
            $variant = ProductVariant::where('merchant_products_id', $product->uuid)->first();
            $image_url = 'https://merchants.kobosquare.com/storage/';
            $data = [
                'image_url' => $image_url,
                'product' => $product,
                'variant' => $variant,
                'images' => $images,
                'business' => $kobo_merchant
            ];

            return $this->sendResponse($data, 'Product Details');
        }
        else if(isset($service)){
            $images = MerchantServiceImage::where('merchant_services_id', $service->uuid)->get();
            $kobo_merchant = KoboMerchant::where('uuid', $service->kobo_merchants_id)->first();
            $variant = ServiceVariant::where('merchant_services_id', $service->uuid)->first();
            $image_url = 'https://merchants.kobosquare.com/storage/';
            $data = [
                'image_url' => $image_url,
                'product' => $service,
                'variant' => $variant,
                'images' => $images,
                'business' => $kobo_merchant
            ];

            return $this->sendResponse($data, 'Services Details');
        }
        else if(isset($food)){
            $images = DB::table('merchant_food_images')->where('merchant_food_id', $food->uuid)->get();
            $kobo_merchant = KoboMerchant::where('uuid', $food->kobo_merchant_id)->first();
            $image_url = 'https://merchants.kobosquare.com/storage/';
            $data = [
                'image_url' => $image_url,
                'product' => $food,
                'images' => $images,
                'business' => $kobo_merchant
            ];

            return $this->sendResponse($data, 'Food Details');
        }
        else if(isset($short)){
            $images = DB::table('merchant_short_stay_images')->where('merchant_short_stay_id', $short->uuid)->get();
            $kobo_merchant = KoboMerchant::where('uuid', $short->kobo_merchant_id)->first();
            $facilities = DB::table('merchant_short_stay_facilities')->where('merchant_short_stay_id', $short->uuid)->get();
            $image_url = 'https://merchants.kobosquare.com/storage/';
            $data = [
                'image_url' => $image_url,
                'product' => $short,
                'images' => $images,
                'facilities' => $facilities,
                'business' => $kobo_merchant
            ];

            return $this->sendResponse($data, 'Food Details');
        }
        else{
            return response('oOps, Something went wronfg.', 404);
        }
    }

    public function homePageFeatured(Request $request, $slug)
    {
        $cat = KoboCategory::where('slug', $slug)->first();
        if($slug == 'fuel-and-gas'){
            $merchants = DB::table('kobo_merchants')->select('*')
            ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)
            ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
            //->paginate(10)
        ->get();
            $data = [
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business' => $merchants
            ];

            return $this->sendResponse($data, 'Featured Fuel and Gas');
        }
        if($slug == 'mart'){
            $merchants = DB::table('kobo_merchants')->select('*')
            ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)
            ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
            //->paginate(10)
        ->get();
            $data = [
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business' => $merchants
            ];

            return $this->sendResponse($data, 'Featured Mart');
        }
        if($slug == 'food'){
            $merchants = DB::table('kobo_merchants')->select('*')
            ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)
            ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
            //->paginate(10)
        ->get();
            $data = [
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business' => $merchants
            ];

            return $this->sendResponse($data, 'Featured Restaurants');
        }
        if($slug == 'services'){
            $merchants = DB::table('kobo_merchants')->select('*')
            ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)
            ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
            //->paginate(10)
        ->get();
            $data = [
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business' => $merchants
            ];

            return $this->sendResponse($data, 'Featured Services');
        }
        if($slug == 'shopping'){
            $merchants = DB::table('kobo_merchants')->select('*')
            ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)
            ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
            //->paginate(10)
        ->get();
            $data = [
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business' => $merchants
            ];

            return $this->sendResponse($data, 'Featured Shopping');
        }
        if($slug == 'short-stay'){

            $merchants = DB::table('kobo_merchants')->select('*')
            ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)
            ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
            //->paginate(10)
        ->get();
            $data = [
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business' => $merchants
            ];

            return $this->sendResponse($data, 'Featured Short Stay');
        }
    }

    public function hompageFeaturedNoSlug()
    {
        $fuel = KoboCategory::where('slug', 'fuel-and-gas')->first();
        $mart = KoboCategory::where('slug', 'mart')->first();
        $food = KoboCategory::where('slug', 'food')->first();
        $shop = KoboCategory::where('slug', 'shopping')->first();
        $serv = KoboCategory::where('slug', 'services')->first();
        $ss = KoboCategory::where('slug', 'short-stay')->first();


        $fuel_mer = DB::table('kobo_merchants')->select('*')
        ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        ->where('kobo_merchants.category_id', '=', $fuel->uuid)
        ->where('kobo_merchants.status', '=', 1)
        ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
        //->paginate(5)
        ->get();

        $mart_mer = DB::table('kobo_merchants')->select('*')
        ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        ->where('kobo_merchants.category_id', '=', $mart->uuid)
        ->where('kobo_merchants.status', '=', 1)
        ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
        //->paginate(5)
        ->get();


        $food_mer = DB::table('kobo_merchants')->select('*')
        ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        ->where('kobo_merchants.category_id', '=', $food->uuid)
        ->where('kobo_merchants.status', '=', 1)
        ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
            //->paginate(5)
        ->get();

        $serv_mer = DB::table('kobo_merchants')->select('*')
        ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        ->where('kobo_merchants.category_id', '=', $serv->uuid)
        ->where('kobo_merchants.status', '=', 1)
        ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
        //->paginate(5)
        ->get();


        $shop_mer = DB::table('kobo_merchants')->select('*')
        ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        ->where('kobo_merchants.category_id', '=', $shop->uuid)
        ->where('kobo_merchants.status', '=', 1)
        ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
        //->paginate(5)
        ->get();


        $ss_mer = DB::table('kobo_merchants')->select('*')
        ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        ->where('kobo_merchants.category_id', '=', $ss->uuid)
        ->where('kobo_merchants.status', '=', 1)
        ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
        //->paginate(5)
        ->get();
        $data = [
            'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
            'Fuel and Gas' => $fuel_mer,
            'Mart'         => $mart_mer,
            'Food'         => $food_mer,
            'Services'     => $serv_mer,
            'Shopping'     => $shop_mer,
            'Short Stay'   => $ss_mer
        ];

        return $this->sendResponse($data, 'Homepage Featured');
    }

    public function categoryBusiness(Request $request, $slug, $radius = 500)
    {
        $cat = KoboCategory::where('slug', $slug)->first();
        $lat = $request->latitude;
        $lon = $request->longitude;
        // $merchants = DB::table('kobo_merchants')->select('*', DB::raw('AVG(merchant_ratings.review_count) AS ratings'))
        //     // ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        //     ->leftJoin('merchant_ratings', 'kobo_merchants.uuid', '=', 'merchant_ratings.merchant_id')
        //     ->where('kobo_merchants.category_id', '=', $cat->uuid)
        //     ->where('kobo_merchants.status', '=', 1)
        //     // ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
        //     //->paginate(10)
        //     ->get();
        $merchants = DB::table('kobo_merchants')->select('kobo_merchants.*',DB::raw('COALESCE(AVG(merchant_ratings.review_count), 0) as ratings'))
            ->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
            ->leftJoin('merchant_ratings', 'kobo_merchants.uuid', '=', 'merchant_ratings.merchant_id')
            ->orderBy('kobo_merchants.created_at', 'Desc')
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->where('kobo_merchants.status', '=', 1)
            ->where(function ($query) {
            $query->whereNotNull('merchant_ratings.merchant_id')
                ->orWhereNull('merchant_ratings.merchant_id');
            })
            ->groupBy('kobo_merchants.uuid')
            ->where('promoted_businesses.expiry_date', '>=', Carbon::now())
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $nearBy = DB::table('kobo_merchants')
        ->selectRaw(" *,
            ( 6371 * acos( cos( radians(?) ) *
            cos( radians( latitude ) )
            * cos( radians( longitude ) - radians(?)
            ) + sin( radians(?) ) *
            sin( radians( latitude ) ) )
                        ) AS distance", [$lat, $lon, $lat])
            ->where('status', '=', 1)
            ->where('category_id', '=', $cat->uuid)
            ->having("distance", "<", $radius)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $more = DB::table('kobo_merchants')->select('kobo_merchants.*', DB::raw('COALESCE(AVG(merchant_ratings.review_count), 0) as ratings'))
        // ->join('promoted_businesses', 'kobo_merchants.uuid', '!=', 'promoted_businesses.merchant_id')
        ->leftJoin('merchant_ratings', 'kobo_merchants.uuid', '=', 'merchant_ratings.merchant_id')
        ->where('kobo_merchants.category_id', '=', $cat->uuid)
        ->where('kobo_merchants.status', '=', 1)
        ->where(function ($query) {
            $query->whereNotNull('merchant_ratings.merchant_id')
                ->orWhereNull('merchant_ratings.merchant_id');
        })
        ->groupBy('kobo_merchants.uuid')
        //->whereNotNull('merchant_ratings.merchant_id')
        //->orWhereNull('merchant_ratings.merchant_id')
        ->inRandomOrder()
        ->limit(10)
        ->get();

        $slider = DB::table('kobo_merchants')->select('*')
        ->where('kobo_merchants.category_id', '=', $cat->uuid)
        ->where('kobo_merchants.status', '=', 1)
        ->limit(5)
        ->get();

        $data = [
            'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
            'slider' => $slider,
            'Featured' => $merchants,
            'nearBy' => $nearBy,
            'more' => $more,
        ];

        return $this->sendResponse($data, 'Category Data');
    }

    public function businessDetails(Request $request, $business_id){
        $merchant = KoboMerchant::where('uuid', $business_id)->first();
        $opening = KoboMerchantOpening::where('kobo_merchant_id', $business_id)->get();
        $cat = KoboCategory::where('uuid', $merchant->category_id)->first();
        $order = KoboOrder::where('merchant_id', $business_id)->get();
        $slug = $cat->slug;
        $price_per_km = 100;
        $getData = $this->booking->getDistanceWithLongLat($request->latitude, $request->longitude, $merchant->business_address);
        $km = $getData['rows'][0]['elements'][0]['distance']['text'];
        $duration = $getData['rows'][0]['elements'][0]['duration']['text'];
        $rm_km = substr($km, 0, -3);
        $total_price =   $price_per_km * (int)str_replace( ',', '', $rm_km);
        $total_price= 185;

        if($slug == 'fuel-and-gas'){
            $products = DB::table('merchant_products')->select('merchant_products.uuid as id', 'merchant_products.*', 'product_variants.*', 'product_variants.uuid as product_variant_id',
            DB::raw('(select image_path from merchant_product_images where merchant_products_id  =   merchant_products.uuid ) as image')
            )
            ->join('product_variants', 'merchant_products.uuid', '=', 'product_variants.merchant_products_id')
            ->where('merchant_products.kobo_categories_id', '=', $cat->uuid)
            ->where('merchant_products.status', '=', 1)
            ->where('merchant_products.kobo_merchants_id', '=', $business_id)->get();

            $product_image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'product_image_url' => $product_image_url,
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business_logo_url' => 'https://api.kobosquare.com/merchant_business_logo/',
                'business_details' => $merchant,
                'products' => $products,
                'orders' => $order,
                "rating" => $merchant->ratings()->avg('review_count'),
                "openinghour" => $opening,
                'distance' => $km,
                'duration' => $duration,
                'delivery_price' => $total_price
            ];

            return $this->sendResponse($data, 'Business Details');
        }
        if($slug == 'mart'){
            $products = DB::table('merchant_products')->select('merchant_products.uuid as id', 'merchant_products.*', 'product_variants.*', 'product_variants.uuid as product_variant_id',
            DB::raw('(select image_path from merchant_product_images where merchant_products_id  =   merchant_products.uuid ) as image')
            )
            ->leftJoin('product_variants', 'merchant_products.uuid', '=', 'product_variants.merchant_products_id')
            ->where('merchant_products.kobo_categories_id', '=', $cat->uuid)
            ->where('merchant_products.status', '=', 1)
            ->where('merchant_products.kobo_merchants_id', '=', $business_id)->get();

            $product_image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'product_image_url' => $product_image_url,
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business_logo_url' => 'https://api.kobosquare.com/merchant_business_logo/',
                'business_details' => $merchant,
                'products' => $products,
                'orders' => $order,
                "rating" => $merchant->ratings()->avg('review_count'),
                "openinghour" => $opening,
                'distance' => $km,
                'duration' => $duration,
                'delivery_price' => $total_price
            ];

            return $this->sendResponse($data, 'Business Details');
        }
        if($slug == 'food'){
            $products = DB::table('merchant_food')->select('merchant_food.uuid as id', 'merchant_food.*',
            DB::raw('(select image_path from merchant_food_images where merchant_food_id  =   merchant_food.uuid ) as image')
            )
            ->where('merchant_food.kobo_category_id', '=', $cat->uuid)
            ->where('merchant_food.status', '=', 1)
            ->where('merchant_food.kobo_merchant_id', '=', $business_id)->get();

            $product_image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'product_image_url' => $product_image_url,
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business_logo_url' => 'https://api.kobosquare.com/merchant_business_logo/',
                'business_details' => $merchant,
                'products' => $products,
                'orders' => $order,
                "rating" => $merchant->ratings()->avg('review_count'),
                "openinghour" => $opening,
                'distance' => $km,
                'duration' => $duration,
                'delivery_price' => $total_price
            ];

            return $this->sendResponse($data, 'Business Details');
        }
        if($slug == 'services'){
            $products = DB::table('merchant_services')->select('merchant_services.uuid as id', 'merchant_services.*', 'service_variants.*', 'service_variants.uuid as service_variant_id',
            DB::raw('(select image_path from merchant_service_images where merchant_services_id  =   merchant_services.uuid ) as image')
            )
            ->join('service_variants', 'merchant_services.uuid', '=', 'service_variants.merchant_services_id')
            ->where('merchant_services.kobo_categories_id', '=', $cat->uuid)
            ->where('merchant_services.status', '=', 1)
            ->where('merchant_services.kobo_merchants_id', '=', $business_id)->get();

            $product_image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'product_image_url' => $product_image_url,
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business_logo_url' => 'https://api.kobosquare.com/merchant_business_logo/',
                'business_details' => $merchant,
                'products' => $products,
                'orders' => $order,
                "rating" => $merchant->ratings()->avg('review_count'),
                "openinghour" => $opening,
                'distance' => $km,
                'duration' => $duration,
                'delivery_price' => $total_price
            ];

            return $this->sendResponse($data, 'Business Details');
        }
        if($slug == 'shopping'){
            $products = DB::table('merchant_products')->select('merchant_products.uuid as id','merchant_products.*', 'product_variants.*', 'product_variants.uuid as product_variant_id',
            DB::raw('(select image_path from merchant_product_images where merchant_products_id  =   merchant_products.uuid ) as image')
            )
            ->join('product_variants', 'merchant_products.uuid', '=', 'product_variants.merchant_products_id')
            ->where('merchant_products.kobo_categories_id', '=', $cat->uuid)
            ->where('merchant_products.status', '=', 1)
            ->where('merchant_products.kobo_merchants_id', '=', $business_id)->get();

            $product_image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'product_image_url' => $product_image_url,
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business_logo_url' => 'https://api.kobosquare.com/merchant_business_logo/',
                'business_details' => $merchant,
                'products' => $products,
                'orders' => $order,
                "rating" => $merchant->ratings()->avg('review_count'),
                "openinghour" => $opening,
                'distance' => $km,
                'duration' => $duration,
                'delivery_price' => $total_price
            ];

            return $this->sendResponse($data, 'Business Details');
        }
        if($slug == 'short-stay'){

            $products = DB::table('merchant_short_stays')->select('merchant_short_stays.uuid as id', 'merchant_short_stays.*',
            DB::raw('(select image_path from merchant_short_stay_images where merchant_short_stay_id  =   merchant_short_stays.uuid ) as image')
            )
            ->where('merchant_short_stays.kobo_category_id', '=', $cat->uuid)
            ->where('merchant_short_stays.status', '=', 1)
            // ->where('merchant_products.kobo_merchant_id', '=', $business_id)->get();
            ->where('merchant_short_stays.kobo_merchant_id', '=', $business_id)->get();

            $product_image_url = 'https://merchants.kobosquare.com/storage/';

            $data = [
                'product_image_url' => $product_image_url,
                'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
                'business_logo_url' => 'https://api.kobosquare.com/merchant_business_logo/',
                'business_details' => $merchant,
                'products' => $products,
                'orders' => $order,
                "rating" => $merchant->ratings()->avg('review_count'),
                "openinghour" => $opening,
                'distance' => $km,
                'duration' => $duration,
                'delivery_price' => $total_price
            ];

            return $this->sendResponse($data, 'Business Details');
        }
    }

    public function allNearBy(Request $request, $slug, $radius = 500){
        $cat = KoboCategory::where('slug', $slug)->first();
        $lat = $request->latitude;
        $lon = $request->longitude;

        $nearBy = DB::table('kobo_merchants')
        ->selectRaw(" *,
            ( 6371 * acos( cos( radians(?) ) *
            cos( radians( latitude ) )
            * cos( radians( longitude ) - radians(?)
            ) + sin( radians(?) ) *
            sin( radians( latitude ) ) )
                        ) AS distance", [$lat, $lon, $lat], DB::raw('AVG(merchant_ratings.review_count) AS ratings'))
            ->leftJoin('merchant_ratings', 'kobo_merchants.uuid', '=', 'merchant_ratings.merchant_id')
            ->where('status', '=', 1)
            ->where('kobo_merchants.category_id', '=', $cat->uuid)
            ->having("distance", "<", $radius)
            ->get();

        $data = [
            'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
            'nearBy' => $nearBy
        ];

        return $this->sendResponse($data, 'All NearBy');
    }

    public function allFeatured(Request $request, $slug){
        $cat = KoboCategory::where('slug', $slug)->first();

        $merchants = DB::table('kobo_merchants')->select('kobo_merchants.*', DB::raw('AVG(merchant_ratings.review_count) AS ratings'))
        //->join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        ->leftJoin('merchant_ratings', 'kobo_merchants.uuid', '=', 'merchant_ratings.merchant_id')
        ->where('kobo_merchants.category_id', '=', $cat->uuid)
        ->where('kobo_merchants.status', '=', 1)
        //->where('promoted_businesses.expiry_date', '>=', Carbon::now())
        ->get();
        $data = [
            'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
            'business' => $merchants
        ];

        return $this->sendResponse($data, 'All Featured');
    }

    public function subCategoryBusiness(Request $request, $slug)
    {
        //dd($request);
        $sub = KoboSubCategory::where('slug', $slug)->first();
        $merchants = DB::table('kobo_merchants')->select('kobo_merchants.*', DB::raw('COALESCE(AVG(merchant_ratings.review_count), 0) as ratings'))
        ->where('kobo_merchants.sub_category_id', '=', $sub->uuid)
        ->leftJoin('merchant_ratings', 'kobo_merchants.uuid', '=', 'merchant_ratings.merchant_id')
        ->where('kobo_merchants.status', '=', 1)
        ->where(function ($query) {
            $query->whereNotNull('merchant_ratings.merchant_id')
                ->orWhereNull('merchant_ratings.merchant_id');
            })
            ->groupBy('kobo_merchants.uuid')
        ->get();
        $data = [
            'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
            'business' => $merchants
        ];

        return $this->sendResponse($data, 'Sub Category Businesses');
    }

    public function addToFavourite(Request $request, $merchant_id)
    {
        $user_id = Auth::guard('api')->user()->id;
        $fv = new KoboFavourite();
        $fv->user_id = $user_id;
        $fv->merchant_id = $merchant_id;
        $fv->save();
        if($fv){
            return $this->sendResponse('success', 'This product has been added to your favourites');
        }
        else{
            return $this->sendError('error', 'Oops! Something went wrong');
        }
    }

    public function removeFavourite(Request $request, $merchant_id)
    {
        $fv = KoboFavourite::where('merchant_id', $merchant_id)->first();
        $d = $fv->delete();
        if($d){
            return $this->sendResponse('success', 'This product has been removed from your favourites');
        }
        else{
            return $this->sendError('error', 'Oops! Something went wrong');
        }
    }

    public function favourite(){
        $_id = Auth::guard('api')->user()->id;
        //$kf = KoboFavourite::where('user_id', $_id)->first();
        $kf = DB::table('kobo_favourites')->select('*')
        ->join('kobo_merchants', 'kobo_favourites.merchant_id', '=', 'kobo_merchants.uuid')
        ->where('kobo_favourites.user_id', $_id)
        ->get();

        $data = [
            'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
            'Favourites' => $kf
        ];

        return $this->sendResponse($data, 'Favourites');
    }
}
