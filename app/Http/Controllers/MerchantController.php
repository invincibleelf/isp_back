<?php

namespace App\Http\Controllers;

use App\Repositories\MerchantRepository;
use App\Services\MerchantService;
use App\Utilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MerchantController extends Controller
{

    private $merchantRepository;

    private $merchantService;

    public function __construct(MerchantRepository $merchantRepository, MerchantService $merchantService)
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
        Log::info("Get Merchants with services by user ");

        $merchants = $this->merchantRepository->getMerchants();

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
//        $credentials = $request->all();
//
//
//        $m = [];
//
//        try {
//            DB::beginTransaction();
//            foreach ($credentials as $c) {
//                $ids = [];
//                $merchant = $this->merchantRepository->getMerchantByBuxId($c['id']);
//
//                if ($merchant == null) {
//                    $merchant = new Merchant();
//                    $merchant = $this->merchantService->updateMerchant($merchant, $c);
//                }
//
//                foreach ($c['services'] as $s) {
//                    $service = $this->merchantRepository->getServiceByBuxId($s['id']);
//
//                    if ($service == null) {
//                        $service = new Service();
//                        $service = $this->merchantService->updateService($service, $s);
//                    }
//                    array_push($ids, $service->id);
//
//                }
//
//                $merchant->services()->sync($ids);
//                $merchant->load('services');
//                array_push($m, $merchant);
//            }
//
//           DB::commit();
//            return response($m);
//
//        } catch (\Exception $e) {
//            DB::rollBack();
//            return response(["error" => $e->getMessage()]);
//        }


    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        Log::info("Get Merchant and service by user ");

        $merchant = $this->merchantRepository->getMerchantById($id);

        if ($merchant == null) {
            return response(Utilities::getResponseMessage("Merchant with id " . $id . " doesn't exist", false, 404));
        }
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
