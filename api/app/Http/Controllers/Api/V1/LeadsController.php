<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ZohoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadsController extends Controller
{
    private $zohoService;
    public function __construct()
    {
        $this->zohoService = new ZohoService();
    }

    public function getRecords()
    {
        $leads  = $this->zohoService->getRecords();
        return response()->json($leads);
    }

    public function insertRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'FirstName' => 'required|max:255',
            'LastName' => 'required|max:255',
            'City' => 'required|max:255',
            'State' => 'required|max:255',
            'Company' => 'required|max:255',
            'Subject' => 'required|max:255',
            'AccountName' => 'required|max:255',
            'FirstName.*' => 'required|max:255',
            'LastName.*' => 'required|max:255',
            'City.*' => 'required|max:255',
            'State.*' => 'required|max:255',
            'Company.*' => 'required|max:255',
            'Subject.*' => 'required|max:255',
            'AccountName.*' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $leads  = $this->zohoService->insertRecords($request->all());
        return response()->json($leads);
    }

    public function updateRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Id' => 'required|max:255',
            'FirstName' => 'required|max:255',
            'LastName' => 'required|max:255',
            'City' => 'required|max:255',
            'State' => 'required|max:255',
            'Company' => 'required|max:255',
            'Subject' => 'required|max:255',
            'AccountName' => 'required|max:255',
            'Id.*' => 'required|max:255',
            'FirstName.*' => 'required|max:255',
            'LastName.*' => 'required|max:255',
            'City.*' => 'required|max:255',
            'State.*' => 'required|max:255',
            'Company.*' => 'required|max:255',
            'Subject.*' => 'required|max:255',
            'AccountName.*' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $leads  = $this->zohoService->updateRecords($request->all());
        return response()->json($leads);
    }


    public function getRelatedRecords($lead_id = null)
    {
        if ($lead_id !== null && is_numeric($lead_id)) {
            $leads  = $this->zohoService->getRelatedRecords($lead_id);
            return response()->json($leads);
        }else{
            return response()->json(['error'=>'Invalid Lead ID']);
        }
    }
}
