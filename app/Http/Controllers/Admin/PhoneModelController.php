<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\ModelNotFoundException as ModelNotFoundException;

use App\PhoneModel;
use App\PhoneBrands;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PhoneModelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $phone_models = DB::table('phone_models')->select('phone_models.*', 'users.name')->join('users', 'users.id', '=', 'phone_models.added_by')->orderBy('id', 'DESC')->get();

        $params = [
            'title' => 'Phone Model List',
            'models' => $phone_models
        ];

        return view('admin/phone_models/list')->with($params);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $params = [
            'title' => 'Create Phone Model',
            'brands' => PhoneBrands::all()
        ];
        return view('admin/phone_models/create')->with($params);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'model_name' => 'required|unique:phone_models,model_name',
            'brand_name' => 'required',
            'fs_mrp' => 'required',
            'mrp_ew' => 'required'
        ]);

        $phone_model = PhoneModel::create([
            'model_name' => $request->input('model_name'),
            'brand_name' => $request->input('brand_name'),
            'mrp' => $request->input('fs_mrp'),
            'mrp_ew' => $request->input('mrp_ew'),
            'status' => 1,
            'added_by' => Auth::user()->id
        ]);

        return redirect()->route('phone_models.index')->with('msg', "The model $phone_model->model_name has successfully been created.");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\PhoneModel  $phoneModel
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $phone_model = DB::table('phone_models')->select('phone_models.*', 'users.name')
                ->join('users', 'users.id', '=', 'phone_models.added_by')
                ->where('phone_models.id', $id)
                ->get();

            $params = [
                'title' => 'Model Details',
                'phone_models' => $phone_model,
            ];

            return view('admin.phone_models.view')->with($params);
        } catch (ModelNotFoundException $ex) {
            if ($ex instanceof ModelNotFoundException) {
                return response()->view('errors.' . '404');
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\PhoneModel  $phoneModel
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $phone_model = PhoneModel::findOrFail($id);

            $params = [
                'title' => 'Edit Model',
                'phone_models' => $phone_model,
                'brands' => PhoneBrands::all()
            ];

            return view('admin.phone_models.edit')->with($params);
        } catch (ModelNotFoundException $ex) {
            if ($ex instanceof ModelNotFoundException) {
                return response()->view('errors.' . '404');
            }
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\PhoneModel  $phoneModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $phone_model = PhoneModel::findOrFail($id);

            $this->validate($request, [
                'brand_name' => 'required',
                'model_name' => 'required',
                'fs_mrp' => 'required',
                'mrp_ew' => 'required'
            ]);

            $phone_model->brand_name = $request->input('brand_name');
            $phone_model->model_name = $request->input('model_name');
            $phone_model->mrp = $request->input('fs_mrp');
            $phone_model->mrp_ew = $request->input('mrp_ew');
            $phone_model->status = $request->input('status');

            $phone_model->save();

            return redirect()->route('phone_models.index')->with('msg', "The model $phone_model->model_name has successfully been updated.");
        } catch (ModelNotFoundException $ex) {
            if ($ex instanceof ModelNotFoundException) {
                return response()->view('errors.' . '404');
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\PhoneModel  $phoneModel
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $phone_model = PhoneModel::findOrFail($id);

            $phone_model->delete();

            return redirect()->route('phone_models.index')->with('msg', "The model $phone_model->model_name has successfully been deleted.");
        } catch (ModelNotFoundException $ex) {
            if ($ex instanceof ModelNotFoundException) {
                return response()->view('errors.' . '404');
            }
        }
    }
}
