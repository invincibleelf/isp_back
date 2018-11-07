<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResourceCollection;
use App\Models\Favourite;
use App\Repositories\TransactionRepository;
use App\Services\TransactionService;
use App\Transaction;
use App\Utilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FavouriteController extends Controller
{

    private $transctionRepository;

    private $transactionService;

    public function __construct(TransactionRepository $transactionRepository, TransactionService $transactionService)
    {
        $this->transctionRepository = $transactionRepository;
        $this->transactionService = $transactionService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $currentUser = Auth::user();

        Log::info("Get favourite transactions for student $currentUser->email");

        $favouriteIds = Favourite::select('transaction_id')->where('user_id', $currentUser->id)->get();

        $transctions = $this->transctionRepository->getTransctionsByFavouriteIds($favouriteIds);

        return response(new TransactionResourceCollection($transctions));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        Log::info("Save favourite transactions for student $currentUser->email");

        $fields = ['transactionSN'];

        $credentials = $request->only($fields);

        $validator = Validator::make(
            $credentials,
            [
                'transactionSN' => 'required'
            ]
        );

        if ($validator->fails()) {
            Log::error("Validation Error");
            return response(Utilities::getResponseMessage($validator->messages(), false, '400'));
        }


        $transaction = $this->transctionRepository->getTransactionByTransactionSNAndStudentId($credentials['transactionSN'], $currentUser->studentDetails->id);

        if (!$transaction) {
            return response(Utilities::getResponseMessage('Transaction not found', false, 400));
        }

        if ($transaction->favourite) {
            return response(Utilities::getResponseMessage('Transaction already listed as favourite', false, 400));
        }

        try {

            DB::beginTransaction();
            $this->transactionService->addTransactionToFavourites($transaction, $currentUser);
            DB::commit();

            return response(Utilities::getResponseMessage("Transaction added to favourite ", true, 200));
        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database " . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
    public function destroy($transactionSN)
    {
        $currentUser = Auth::user();

        $transaction = $this->transctionRepository->getTransactionByTransactionSNAndStudentId($transactionSN, $currentUser->studentDetails->id);

        if (!$transaction) {
            return response(Utilities::getResponseMessage('Transaction not found', false, 400));
        }

        if (!$transaction->favourite) {
            return response(Utilities::getResponseMessage('Transaction is not included in favourite', false, 400));
        }

        try {
            DB::beginTransaction();
            $favourite = $transaction->favourite;
            $favourite->delete();
            DB::commit();

            return response(Utilities::getResponseMessage('Transaction removed from favourites', true, 200));
        } catch (\Exception $e) {
            //Roll back database if error
            Log::error("Error while saving to database " . $e->getMessage());
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }


    }
}
