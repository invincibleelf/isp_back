<?php

namespace App\Http\Controllers;

use App\Repositories\MerchantRepository;
use App\Services\MerchantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MerchantController extends Controller
{

    private $merchantRepository;

    private $merchantService;

    public function __construct(MerchantRepository $merchantRepository,MerchantService $merchantService)
    {
        $this->merchantRepository = $merchantRepository;
        $this->merchantService = $merchantService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Log::info("Get Merchants with services by user ". Auth::user()->id);

        //$merchants =$this->merchantRepository->getMerchants();

        $merchants = $this->merchantService->getMerchantsServicesFromBux();

        return response([
            "m"=>$merchants
        ]);

        return response($merchants);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        Log::info("Get Merchant and service by user ". Auth::user()->id);

        $merchant =$this->merchantRepository->getMerchantById($id);

        return response($merchant);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
