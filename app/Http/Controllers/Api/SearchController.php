<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Models\KoboCategory;
use App\Models\KoboMerchant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends BaseController
{
    public function searchFilter(Request $request)
    {
        // $merchants = KoboMerchant::join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        //     ->join('kobo_categories', 'kobo_categories.uuid', '=', 'kobo_merchants.category_id')
        //     ->join('kobo_sub_categories', 'kobo_sub_categories.uuid', '=', 'kobo_merchants.sub_category_id')
        //     ->where('kobo_merchants.status', '=', 1)
        //     ->where('promoted_businesses.expiry_date', '>=', Carbon::now());

        $merchants = KoboMerchant::join('kobo_categories', 'kobo_categories.uuid', '=', 'kobo_merchants.category_id')
            ->join('kobo_sub_categories', 'kobo_sub_categories.uuid', '=', 'kobo_merchants.sub_category_id')
            ->where('kobo_merchants.status', '=', 1);
        $query = KoboMerchant::query();

        // If Category
        if ($request->filled('category')) {
            $categorySlug = $request->category;

            $query->whereHas('category', function ($query) use ($categorySlug) {
                $query->where('slug', 'LIKE', '%' . $categorySlug . '%');
            });

            $merchants->where('kobo_categories.slug', 'LIKE', '%' . $categorySlug . '%');
        }

        // If Sub Category
        if ($request->filled('sub_category')) {
            $subCategorySlug = $request->sub_category;

            $query->whereHas('sub_category', function ($query) use ($subCategorySlug) {
                $query->orWhere('slug','LIKE', '%' . $subCategorySlug . '%');
            });
            $merchants->orWhere('kobo_sub_categories.slug','LIKE', '%' . $subCategorySlug . '%');
        }

        //Limit request to 5
        $query->limit(5);
        $merchants->limit(5);

        $data = [
            'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
            'featured' => $merchants->get(),
            'Top' => $query->get()
        ];

        return $data;
    }

    public function search(Request $request, $search)
    {
        $query = KoboMerchant::query();
        // $merchants = KoboMerchant::join('promoted_businesses', 'kobo_merchants.uuid', '=', 'promoted_businesses.merchant_id')
        //     ->join('kobo_categories', 'kobo_categories.uuid', '=', 'kobo_merchants.category_id')
        //     ->join('kobo_sub_categories', 'kobo_sub_categories.uuid', '=', 'kobo_merchants.sub_category_id')
        //     ->where('kobo_merchants.status', '=', 1)
        //     ->where('promoted_businesses.expiry_date', '>=', Carbon::now());
        // $searchData = $search;
        $merchants = KoboMerchant::join('kobo_categories', 'kobo_categories.uuid', '=', 'kobo_merchants.category_id')
            ->join('kobo_sub_categories', 'kobo_sub_categories.uuid', '=', 'kobo_merchants.sub_category_id')
            ->where('kobo_merchants.status', '=', 1);
        $searchData = $search;

        // Check From Category Name
        $query->whereHas('category', function ($query) use ($searchData) {
            $query->where('name', 'LIKE', '%' . $searchData . '%');
        });

        $merchants->where('kobo_categories.name', 'LIKE', '%' . $searchData . '%');


        // Check From Sub Category Name
        $query->whereHas('sub_category', function ($query) use ($searchData) {
            $query->orWhere('name','LIKE', '%' . $searchData . '%');
        });

        $merchants->orWhere('kobo_sub_categories.name','LIKE', '%' . $searchData . '%');


        // Check from Business Name
        $query->orWhere('business_name', 'LIKE', '%' . $searchData . '%');
        $merchants->orWhere('business_name', 'LIKE', '%' . $searchData . '%');

        //Limit request to 5
        $query->limit(5);
        $merchants->limit(5);

        $data = [
            'business_cover_url' => 'https://api.kobosquare.com/merchant_business_cover/',
            'featured' => $merchants->get(),
            'Top' => $query->get()
        ];

        return $data;
    }
}
