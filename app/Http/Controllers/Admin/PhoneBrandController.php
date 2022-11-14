<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\ModelNotFoundException as ModelNotFoundException;

use App\PhoneModel;
use App\PhoneBrands;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PhoneBrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $phone_brands = DB::table('phone_brands')->select('phone_brands.*', 'users.name')->join('users', 'users.id', '=', 'phone_brands.added_by')->orderBy('id', 'DESC')->get();

        $params = [
            'title' => 'Phone Brand List',
            'brands' => $phone_brands
        ];

        return view('admin/phone_brands/list')->with($params);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $params = [
            'title' => 'Add Phone Brand',
            'brands' => PhoneBrands::all()
        ];
        return view('admin/phone_brands/create')->with($params);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $this->validate($request, [
        //     'brand_name' => 'required|unique:phone_brands,brand_name'
        // ]);

        $phone_brand = PhoneBrands::create([
            'brand_name' => $request->input('brand_name'),
            'status' => 1,
            'added_by' => Auth::user()->id
        ]);

        return redirect()->route('phone_brands.index')->with('msg', "The brand $phone_brand->brand_name has successfully been added.");
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
            $phone_brand = DB::table('phone_brands')->select('phone_brands.*', 'users.name')
                ->join('users', 'users.id', '=', 'phone_brands.added_by')
                ->where('phone_brands.id', $id)
                ->get();

            $params = [
                'title' => 'Brand Details',
                'phone_brands' => $phone_brand,
            ];

            return view('admin.phone_brands.view')->with($params);
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
            $phone_brand = PhoneBrands::findOrFail($id);

            $params = [
                'title' => 'Edit Brand',
                'phone_brand' => $phone_brand
            ];

            return view('admin.phone_brands.edit')->with($params);
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
            $phone_brand = PhoneBrands::findOrFail($id);

            $this->validate($request, [
                'brand_name' => 'required'
            ]);

            $phone_brand->brand_name = $request->input('brand_name');
            $phone_brand->status = $request->input('status');

            $phone_brand->save();

            return redirect()->route('phone_brands.index')->with('msg', "The brand $phone_brand->brand_name has successfully been updated.");
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
            $phone_brand = PhoneBrands::findOrFail($id);

            $phone_brand->delete();

            return redirect()->route('phone_brands.index')->with('msg', "The brand $phone_brand->brand_name has successfully been deleted.");
        } catch (ModelNotFoundException $ex) {
            if ($ex instanceof ModelNotFoundException) {
                return response()->view('errors.' . '404');
            }
        }
    }
}
