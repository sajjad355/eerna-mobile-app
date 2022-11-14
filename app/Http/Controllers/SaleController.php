<?php

namespace App\Http\Controllers;

use App\Files;
use App\Fscodes;

use App\Outlet;
use App\PhoneModel;
use App\PhoneBrands;
use App\User;
use App\Sales;
use App\Imei;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Tiers;
use App\Role;
use App\TempFile;
use Laratrust;
use Illuminate\Support\Carbon;
use SslWireless\SslWirelessSms;
use Intervention\Image\Facades\Image;
use Tzsk\Otp\Facades\Otp;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Log;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = User::find(Auth::user()->id);

        if ($user->hasRole(['supadmin', 'admin', 'callcenter'])) {
            $sales = DB::table('sales')
                ->select('sales.*', 'outlets.store_code', 'outlets.store_name')
                ->join('outlets', 'outlets.id', '=', 'sales.store_id')
                ->orderByDesc('sales.id')
                ->get();
        } else {
            $sales = DB::table('sales')
                ->select('sales.*', 'outlets.store_code', 'outlets.store_name')
                ->join('outlets', 'outlets.id', '=', 'sales.store_id')
                ->where('sales.store_id', Auth::user()->store_id)
                ->orderByDesc('sales.id')
                ->get();
        }

        // if ($user->hasRole(['supadmin', 'admin'])) {
        //     $get_commission_sum = DB::table('sales')->sum('retailed_commission');
        // } else {
        //     $get_commission_sum = DB::table('sales')->where('sales.store_id', Auth::user()->store_id)->sum('retailed_commission');
        // }

        $params = [
            'title' => 'Sales List',
            'sales_info' => $sales
        ];

        return view('sales/sales_list')->with($params);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $user_store_id = Auth::user()->store_id;
        $get_store_code = DB::table('outlets')
            ->select('outlets.*')
            ->where('outlets.id', '=', $user_store_id)
            ->get();

        $get_phone_model = PhoneModel::all();
        $get_phone_brands = PhoneBrands::all();
        $products = json_decode('[{ "product_name": "Screen Damage Protection" }, { "product_name": "Extended Warranty" }, { "product_name": "18 Months Warranty" }]');

        $params = [
            'title' => 'Sales',
            'store_code' => $get_store_code,
            'phone_models' => $get_phone_model,
            'phone_brands' => $get_phone_brands,
            'products' => $products
        ];

        return view('sales.sales')->with($params);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $expiresAt = Carbon::now()->addMinutes(10);
        // echo '<pre>'; print_r($_POST);die;
        // $user = User::find(Auth::user()->id);
        $this->validate($request, [
            'store_id' => 'required',
            'service_type' => 'required',
            // 'imei' => 'required|unique:sales,c|regex:/^[a-zA-Z0-9_\-]*$/|max:15|min:15',
            'imei' => 'required|unique:sales,imei,NULL,id,service_type,'.$request->input('service_type').'|regex:/^[a-zA-Z0-9_\-]*$/|max:15|min:15',
            'model' => 'required',
            'price' => 'required',
            'title' => 'required',
            // 'customer_name' => 'required',
            'mobile' => 'required|min:11',
            'fs_code' => 'required|unique:sales,fs_code',
            'mrp' => 'required',
            // 'device_purchase_date' => 'required'
        ]);

        $sale = new Sales;
        $sale->store_id = $request->store_id;
        $sale->service_type = $request->service_type;
        $sale->imei = $request->imei;
        $sale->brand = $request->brand;
        $sale->model = $request->model;
        $sale->price = $request->price;
        $sale->title = $request->title;
        $sale->gender = $request->gender;
        // $sale->customer_name = $request->customer_name;
        // $sale->date_of_birth = $request->date_of_birth;
        $sale->mobile = $request->mobile;
        // $sale->emergency_contact = $request->emergency_contact;
        // $sale->email = $request->email;
        $sale->district = $request->district;
        $sale->address = $request->address;
        $sale->fs_code = $request->fs_code;
        $sale->fs_mrp = $request->mrp;
        // $sale->device_purchase_date = $request->device_purchase_date;
        $sale->is_verified = 0;
        $addCache = Cache::add($sale->imei, $sale, $expiresAt);

        // if ($request->hasFile('image')) {
        //     foreach ($request->file('image') as $key => $image) {
        //         $extension = $image->getClientOriginalExtension();
        //         $name = $image->getClientOriginalName();
        //         $filename = time() . '_' . $name;
        //         $fileLocation = 'uploads/sales/';
        //         $new_img = Image::make($image->getRealPath())->resize(600, 600);
        //         // save file with medium quality
        //         $new_img->save($fileLocation . $filename, 90);
        //         // $image->move($fileLocation, $filename);
        //         $data[$key]['file_name'] = $filename;
        //         $data[$key]['file_for'] = 'sales';
        //         $data[$key]['file_type'] = 'image';
        //         $data[$key]['upload_by'] = Auth::user()->id;
        //         $data[$key]['status'] = 1;
        //         $data[$key]['file_location'] = $fileLocation;
        //         $data[$key]['sales_id'] = $sale->id;
        //         $data[$key]['imei'] = $request->imei; //requested IMEI number
        //         $data[$key]['created_at'] = Carbon::now();
        //         $data[$key]['updated_at'] = Carbon::now();
        //     }
        //     TempFile::insert($data);
        // }

        if ($addCache ==  true) {
            $unique_secret = 'jklsothnb0ksmfj.gkqmdp0spntxp;12aslpelmc';
            $otp = Otp::generate($unique_secret);

            if (isset($otp)) {
                // username, password, sid provided by sslwireless
                $username = env("SMS_USERNAME", null);
                $password = env("SMS_PASSWORD", null);
                $sid = env("SMS_SID", null);
                $SslWirelessSms = new SslWirelessSms($username, $password, $sid);
                // // You can change the api url if needed. i.e.
                // $SslWirelessSms->setUrl('new_url');
                // $SslWirelessSms->send($request->mobile, 'Thank you for purchase. Your Fscode is: ' . $request->fs_code . '.MRP(including VAT) TK ' . $request->mrp . '');
                
                if($request->service_type=="Screen Damage Protection")
                {
                   // $output =  $SslWirelessSms->send($request->mobile, "Thanks for interest in Eerna Protect Screen Protection Plan. Please read T&C-https://eerna.cpp-fs.com/es.pdf, share OTP $otp to confirm. (OTP expires in 3 min.");
                   $output = $SslWirelessSms->send($request->mobile, "Thanks for interest in Eerna Protect Screen Protection Plan. Please read T&C-https://eerna.cpp-fs.com/es.pdf, share OTP $otp to confirm. (OTP expires in 3 min.");

                    }
 
                
                elseif($request->service_type=="Extended Warranty")
                {
                   // $output =  $SslWirelessSms->send($request->mobile, "Thanks for interest in Eerna Protect Extended Warranty Plan. Please read T&C-https://eerna.cpp-fs.com/ee.pdf and share OTP $otp to confirm. (OTP expires in 3 min. ");
                    $output = $SslWirelessSms->send($request->mobile, "Thanks for interest in Eerna Protect Extended Warranty Plan. Please read T&C-https://eerna.cpp-fs.com/ee.pdf, share OTP $otp to confirm. (OTP expires in 3 min.");

                }
                elseif($request->service_type=="18 Months Warranty")
                {
                   // $output =  $SslWirelessSms->send($request->mobile, "Thanks for interest in Eerna Protect Extended Warranty Plan. Please read T&C-https://eerna.cpp-fs.com/e8.pdf and share OTP $otp to confirm. (OTP expires in 3 min. " );
                     $output = $SslWirelessSms->send($request->mobile, "Thanks for interest in Eerna Protect Extended Warranty Plan. Please read T&C-https://eerna.cpp-fs.com/e8.pdf, share OTP $otp to confirm. (OTP expires in 3 min.");

                } 

             //  $output = $SslWirelessSms->send($request->mobile, "Thanks for interest in Eerna Protect Screen Protection Plan. Please read T&C-https://eerna.cpp-fs.com/es.pdf, share OTP $otp to confirm. (OTP expires in 3 min.");
                // dd($output);

                $log = [
                    'userId' => Auth::user()->id,
                    'otp' => $otp,
                    'description' => $output
                ];

                $orderLog = new Logger('presales');
                $orderLog->pushHandler(new StreamHandler(storage_path('logs/sms.log')), Logger::INFO);
                $orderLog->info('salesCenter', $log);
                Log::channel('smsdatewise')->info('salesCenter', $log);

            }
            $outlet = DB::table('outlets')
                ->select('outlets.store_name')
                ->where('outlets.id', Auth::user()->store_id)
                ->get();

            $params = [
                'title' => 'OTP Verify',
                'otp' => $otp,
                'mobile_number' => $request->mobile,
                'outlet_name' => $outlet,
                'imei' => $request->imei,
                'model' => $request->model,
                'service_type'=>$request->service_type,
                'verify_request' => 1,
                'fscode' => $request->fs_code
            ];

            return view('sales.otp_verify')->with($params);
        } else {
            return back();
        }
    }

    public function verifyOtp(Request $request)
    {

        $this->validate($request, [
            'otp' => 'required|numeric|digits:4'
        ]);

        $otp = $request->input('otp');
        $phone_number = $request->phone_number;
        $model = $request->model;
        $imei_number = $request->imei;
        $fscode = $request->fs_code;
        $first_verify_req = $request->first_verify_request;
        $unique_secret = 'jklsothnb0ksmfj.gkqmdp0spntxp;12aslpelmc';
        $valid = Otp::match($otp, $unique_secret);

        if ($valid == true) {

            $value = Cache::get($imei_number);
            $value->save();
            Sales::where('imei', $imei_number)->update(array('is_verified' => 1, 'verified_by' => Auth::user()->id));
            Fscodes::where('fscode', $fscode)->update(array('status' => 3, 'sale_by' => Auth::user()->store_id, 'sale_date' => Carbon::now()));
            Cache::forget($imei_number);
            // $get_tmp_files = DB::table('temp_files')->where('imei', $imei_number)->get();

            // foreach ($get_tmp_files as $key => $files) {
            //     $data[$key]['sales_id'] = $files->sales_id;
            //     $data[$key]['imei'] = $files->imei;
            //     $data[$key]['file_name'] = $files->file_name;
            //     $data[$key]['file_for'] = $files->file_for;
            //     $data[$key]['file_type'] = $files->file_type;
            //     $data[$key]['upload_by'] = $files->upload_by;
            //     $data[$key]['status'] = $files->status;
            //     $data[$key]['file_location'] = $files->file_location;
            //     $data[$key]['created_at'] = Carbon::now();
            //     $data[$key]['updated_at'] = Carbon::now();
            // }

            // Files::insert($data);
            // Files::where('imei', $imei_number)->update(array('sales_id' => $value->id));
            // DB::table("temp_files")->where('imei', $imei_number)->delete();


            if (!empty($phone_number)) {
                // username, password, sid provided by sslwireless
                $username = env("SMS_USERNAME", null);
                $password = env("SMS_PASSWORD", null);
                $sid = env("SMS_SID", null);
                $SslWirelessSms = new SslWirelessSms($username, $password, $sid);
                // // You can change the api url if needed. i.e.
                // $SslWirelessSms->setUrl('new_url');
                // $SslWirelessSms->send($request->mobile, 'Thank you for purchase. Your Fscode is: ' . $request->fs_code . '.MRP(including VAT) TK ' . $request->mrp . '');
                
                if($request->service_type=="Screen Damage Protection") {

                    $output =   $SslWirelessSms->send($phone_number, "Eerna Protect Screen Protection plan for device IMEI No $imei_number is active for next 12 months from today . Call 09612100900 (Daily: 10am-7pm) for support");
                                        
                    $fSecure_code=DB::table('fsecure')
                    ->select('fsecure_code')
                    ->Where('service_type', "6 months")
                    ->Where('status', 0)
                    ->pluck('fsecure_code')
                    ->first();

                    $sendFS = $SslWirelessSms->send($phone_number, "F-Secure code (part of Eerna Protect Screen Protection plan) is $fSecure_code. T&C: https://eerna.cpp-fs.com/des.pdf Call 09612100900 (Daily: 10am-7pm) for support. Thanks ");

                    $fSecureUpdate=DB::table('fsecure')
                    ->where('fsecure_code', '=', $fSecure_code)
                    ->update(array('status' => 1, 'imei' => $imei_number, 'used_at' => Carbon::now()));
                    
                    $log = [
                        'userId' => Auth::user()->id,
                        'fsecure' => $fSecure_code,
                        'description' => $sendFS
                    ];
                    $orderLog = new Logger('fsecure');
                    $orderLog->pushHandler(new StreamHandler(storage_path('logs/sms.log')), Logger::INFO);
                    $orderLog->info('salesCenter', $log);
                    Log::channel('smsdatewise')->info('salesCenter', $log);


                }
 
                elseif($request->service_type=="Extended Warranty") {
                    // $output =   $SslWirelessSms->send($phone_number, "Eerna Protect Extended Warranty plan for device IMEI No $imei_number is active for next 24 months from today . Call 09612100900 (Daily: 10am-7pm) for support");
                 /*    $fSecure=DB::table('fsecure')
                    ->select('fsecure_code')
                    ->Where('service_type', "12 months")
                    ->Where('status',0)
                    ->first();
                    $SslWirelessSms->send($request->mobile_number, "F-Secure code (part of Eerna Protect Extended Warranty plan) is $fSecure . T&C https://eerna.cpp-fs.com/dee.pdf Call 09612100900 (Daily: 10am-7pm) for support. Thanks");
                    $fSecureUpdate=  DB::table('fsecure')->where('fsecure_code', '=', $fSecure)->update(['status' => 1]);  
                */

                    $output =   $SslWirelessSms->send($phone_number, "Eerna Protect Extended Warranty plan for device IMEI No $imei_number is active for next 24 months from today . Call 09612100900 (Daily: 10am-7pm) for support");
                                            
                    $fSecure_code=DB::table('fsecure')
                    ->select('fsecure_code')
                    ->Where('service_type', "12 months")
                    ->Where('status', 0)
                    ->pluck('fsecure_code')
                    ->first();

                    $sendFS = $SslWirelessSms->send($phone_number, "F-Secure code (part of Eerna Protect Extended Warranty plan) is $fSecure_code. T&C https://eerna.cpp-fs.com/dee.pdf Call 09612100900 (Daily: 10am-7pm) for support. Thanks");

                    $fSecureUpdate=DB::table('fsecure')
                    ->where('fsecure_code', '=', $fSecure_code)
                    ->update(array('status' => 1, 'imei' => $imei_number, 'used_at' => Carbon::now()));
                    
                    $log = [
                        'userId' => Auth::user()->id,
                        'fsecure' => $fSecure_code,
                        'description' => $sendFS
                    ];
                    $orderLog = new Logger('fsecure');
                    $orderLog->pushHandler(new StreamHandler(storage_path('logs/sms.log')), Logger::INFO);
                    $orderLog->info('salesCenter', $log);
                                  Log::channel('smsdatewise')->info('salesCenter', $log);


                }
                elseif($request->service_type=="18 Months Warranty")
                {
                    // $output = $SslWirelessSms->send($phone_number, "Eerna Protect Extended Warranty plan for device IMEI No $imei_number is active for next 18 months from today . Call 09612100900 (Daily: 10am-7pm) for support");
                    
                    /*  $fSecure=DB::table('fsecure')
                    ->select('fsecure_code')
                    ->Where('service_type', "12 months")
                    ->Where('status',0)
                    ->first();
                    $SslWirelessSms->send($request->mobile_number, "F-Secure code (part of Eerna Protect Extended Warranty plan) is $fSecure. T&C https://eerna.cpp-fs.com/deee.pdf . Call 09612100900 (Daily: 10am-7pm) for support. Thanks");
                    $fSecureUpdate=  DB::table('fsecure')->where('fsecure_code', '=', $fSecure)->update(['status' => 1]);  
                    */


                    $output =   $SslWirelessSms->send($phone_number, "Eerna Protect Extended Warranty plan for device IMEI No $imei_number is active for next 18 months from today . Call 09612100900 (Daily: 10am-7pm) for support");
                                            
                    $fSecure_code=DB::table('fsecure')
                    ->select('fsecure_code')
                    ->Where('service_type', "12 months")
                    ->Where('status', 0)
                    ->pluck('fsecure_code')
                    ->first();

                    $sendFS = $SslWirelessSms->send($phone_number, "F-Secure code (part of Eerna Protect Extended Warranty plan) is $fSecure_code. T&C https://eerna.cpp-fs.com/dee.pdf Call 09612100900 (Daily: 10am-7pm) for support. Thanks");

                    $fSecureUpdate=DB::table('fsecure')
                    ->where('fsecure_code', '=', $fSecure_code)
                    ->update(array('status' => 1, 'imei' => $imei_number, 'used_at' => Carbon::now()));
                    
                    $log = [
                        'userId' => Auth::user()->id,
                        'fsecure' => $fSecure_code,
                        'description' => $sendFS
                    ];
                    $orderLog = new Logger('fsecure');
                    $orderLog->pushHandler(new StreamHandler(storage_path('logs/sms.log')), Logger::INFO);
                    $orderLog->info('salesCenter', $log);
                                  Log::channel('smsdatewise')->info('salesCenter', $log);


                } 

                
                
               // $output = $SslWirelessSms->send($phone_number, "Dear Customer - Thank you for purchasing. Don't worry plan for your $model. Please call 08000777777 (Daily:9am-7pm) for activation. Thank you.");
                // dd($output);

                $log = [
                    'userId' => Auth::user()->id,
                    'otp-verify' => $valid,
                    'description' => $output
                ];

                $orderLog = new Logger('completeSales');
                $orderLog->pushHandler(new StreamHandler(storage_path('logs/sms.log')), Logger::INFO);
                $orderLog->info('salesCenter', $log);
                              Log::channel('smsdatewise')->info('salesCenter', $log);

            }


            // $validateIMEI = DB::select("UPDATE imeis SET status = '0' WHERE imei = '" . $imei_number . "';");

            return redirect()->route('sales.create')->with('successMsg', 'OTP verified. Thank you for purchase.');
        } else {

            $outlet = DB::table('outlets')
                ->select('outlets.store_name')
                ->where('outlets.id', Auth::user()->store_id)
                ->get();

            $verify_request_count = $first_verify_req + 1;

            $params = [
                'title' => 'OTP Verify',
                'otp' => $otp,
                'outlet_name' => $outlet,
                'mobile_number' => $phone_number,
                'imei' => $imei_number,
                'model' => $model,
                'verify_request' => $verify_request_count,
                'fscode' => $fscode
            ];

            $request->session()->flash('invalidOtpMsg', 'Not verified. Please try again.');

            return view('sales.otp_verify')->with($params);
        }
    }

    public function resendOtp(Request $request)
    {
        $phone_number = $request->mobile;
        // echo $phone_number;die;

        if (isset($_POST)) {
            $unique_secret = 'jklsothnb0ksmfj.gkqmdp0spntxp;12aslpelmc';
            $otp = Otp::generate($unique_secret);

            // username, password, sid provided by sslwireless
            $username = env("SMS_USERNAME", null);
            $password = env("SMS_PASSWORD", null);
            $sid = env("SMS_SID", null);

            if (isset($otp)) {
                $SslWirelessSms = new SslWirelessSms($username, $password, $sid);
                // // You can change the api url if needed. i.e.
                // $SslWirelessSms->setUrl('new_url');
                // $SslWirelessSms->send($request->mobile, 'Thank you for purchase. Your Fscode is: ' . $request->fs_code . '.MRP(including VAT) TK ' . $request->mrp . '');
                $output = $SslWirelessSms->send($phone_number, "Thanks for your interest to buy Don't worry screen protection. Please click here to get terms and conditions. Your OTP is $otp and will expire within 3 minutes.");
                // dd($output);

                $log = [
                    'userId' => Auth::user()->id,
                    'otp' => $otp,
                    'reason' => 'resend otp',
                    'description' => $output
                ];

                $orderLog = new Logger('resendOtp');
                $orderLog->pushHandler(new StreamHandler(storage_path('logs/sms.log')), Logger::INFO);
                $orderLog->info('salesCenter', $log);
                              Log::channel('smsdatewise')->info('salesCenter', $log);

            }
        }

        return response()->json($otp);
    }

    public function submitWithoutOtp(Request $request)
    {
        $phone_number = $request->phone_number;
        $model = $request->model;
        $imei_number = $request->imei;
        $fscode = $request->fs_code;

        $value = Cache::get($imei_number);
        $value->save();
        // Sales::where('imei', $imei_number)->update(array('is_verified' => 1, 'verified_by' => Auth::user()->id));
        Fscodes::where('fscode', $fscode)->update(array('status' => 3, 'sale_by' => Auth::user()->store_id, 'sale_date' => Carbon::now()));
        Cache::forget($imei_number);
        // $get_tmp_files = DB::table('temp_files')->where('imei', $imei_number)->get();
        // foreach ($get_tmp_files as $files) {
        //     $data[] = (array) $files;
        // }

        // Files::insert($data);
        // Files::where('imei', $imei_number)->update(array('sales_id' => $value->id));
        // DB::table("temp_files")->where('imei', $imei_number)->delete();

        $log = [
            'userId' => Auth::user()->id,
            'description' => 'complete sale without OTP'
        ];

        $orderLog = new Logger('resendOtp');
        $orderLog->pushHandler(new StreamHandler(storage_path('logs/search.log')), Logger::INFO);
        $orderLog->info('salesCenter', $log);

        return redirect()->route('sales.create')->with('successMsg', 'OTP not verified. Please contact with authority to complete sale.');
    }

    public function saleVerification($id)
    {
        Sales::where('id', $id)->update(array('is_verified' => 1, 'verified_by' => Auth::user()->id));

        $log = [
            'userId' => Auth::user()->id,
            'description' => 'Sale verification'
        ];

        $orderLog = new Logger('saleVerification');
        $orderLog->pushHandler(new StreamHandler(storage_path('logs/search.log')), Logger::INFO);
        $orderLog->info('saleVerification', $log);

        return redirect(route('sales.index'))->with('successMsg', 'Successfully Verified');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $sales = DB::table('sales')
            ->select('sales.*', 'outlets.store_code', 'outlets.store_name')
            ->join('outlets', 'outlets.id', '=', 'sales.store_id')
            ->where('sales.id', '=', $id)
            ->get();

        $params = [
            'title' => 'Details',
            'sales_info' => $sales
        ];

        return view('sales.sales_view')->with($params);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $sales = DB::table('sales')
            ->select('sales.*', 'outlets.store_code', 'outlets.store_name')
            ->join('outlets', 'outlets.id', '=', 'sales.store_id')
            ->where('sales.id', '=', $id)
            ->get();
        // echo '<pre>'; \print_r($sales);die;
        $params = [
            'title' => 'Edit info',
            'sales_info' => $sales
        ];

        // $sale = Sales::find($id);
        return view('sales.sales_edit')->with($params);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $this->validate($request, [
            'store_id' => 'required',
            'imei' => 'required|digits:15',
            'model' => 'required',
            'price' => 'required',
            'title' => 'required',
            'customer_name' => 'required',
            'date_of_birth' => 'required',
            'mobile' => 'required|min:11',
            'district' => 'required',
            'address' => 'required',
            'fs_code' => 'required',
            'mrp' => 'required',

        ]);

        $sale = Sales::findOrFail($id);
        $sale->store_id = $request->store_id;
        $sale->imei = $request->imei;
        $sale->brand = $request->brand;
        $sale->model = $request->model;
        $sale->price = $request->price;
        $sale->title = $request->title;
        $sale->gender = $request->gender;
        $sale->customer_name = $request->customer_name;
        $sale->date_of_birth = $request->date_of_birth;
        $sale->mobile = $request->mobile;
        $sale->email = $request->email;
        $sale->district = $request->district;
        $sale->address = $request->address;
        $sale->fs_code = $request->fs_code;
        $sale->fs_mrp = $request->mrp;
        $sale->save();

        return redirect()->route('sales.index')->with('successMsg', 'Successfully Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Sales::find($id)->delete();
        return redirect(route('sales.index'))->with('successMsg', 'Successfully Deleted');
    }

    public function get_fs(Request $request)
    {
        $tier = array();
        $fsvalue = array();
        $data = array();
        $price = $request->price;
        $service_type = $request->service_type;
        // echo $price.'<br>'.$service_type;die;
        // DB::enableQueryLog();
        $get_tier = DB::select("SELECT t.`tier` FROM tiers t WHERE " . $price . " >= t.price_range_start AND " . $price . " <= t.price_range_end AND t.status=1");
        // dd(DB::getQueryLog());
        foreach ($get_tier as $t) {
            $tier = $t;
        }
        // echo '<pre>';print_r($get_tier);die;
        if ($get_tier) {
            $get_fscode = DB::select("SELECT fs.`fscode` FROM fscodes fs WHERE fs.`tier` LIKE '%" . $tier->tier . "%' AND fs.`status`=1 LIMIT 1");
            foreach ($get_fscode as $value) {
                $fsvalue = $value;
            }
        }
        // echo '<pre>';print_r($fsvalue);die;
        foreach ($fsvalue as $val) {
            $data['fscode'] = $val;
        }

        // $data['mrp'] = $tier->mrp;
        // $data['commission'] = $tier->commission;

        // echo '<pre>';
        // print_r($data);
        // die;

        return response()->json($data);
    }

    public function get_device_info(Request $request)
    {
        $data = array();
        $imei = $request->imei;
        // echo $imei;die;
        // DB::enableQueryLog();
        $get_device_info = DB::select("select * from imeis where imei='" . $imei . "' and status=1");
        foreach ($get_device_info as $value) {
            $data['imei'] = $value->imei;
            $data['brand'] = $value->brand;
            $data['model'] = $value->model;
            // $data['device_price'] = $value->device_price;
        }
        return response()->json($get_device_info);
    }

    public function get_service_type_price_range(Request $request)
    {
        $data = array();
        $service_type = $request->service_type;
        // echo $service_type;die;
        $get_price_range = DB::select("SELECT t.price_range_start,t.price_range_end FROM tiers t where t.service_type = '" . $service_type . "'");

        foreach ($get_price_range as $value) {
            $data['price_range'] = '<div class="alert alert-info" role="alert"><p>Price range for this service type must have between TK&nbsp;<b>' . $value->price_range_start . '</b> and TK&nbsp;<b>' . $value->price_range_end . '</b></p></div>';
        }

        // echo '<pre>';
        // print_r($data);
        // die;
        return response()->json($data);
    }

    public function get_mrp(Request $request)
    {
        $data = array();
        $model = $request->model;
        $product = $request->product;
        
        if ($model != null && $product != null) {
            $get_mrp = DB::table('phone_models')
                ->select('*')
                ->where('phone_models.model_name', '=', $model)
                ->get();

            if($product === 'Screen Damage Protection') {
                foreach ($get_mrp as $mrp) {
                    $data['mrp'] = $mrp->mrp;
                }
            }
            else if($product === '18 Months Warranty') {
                $data['mrp'] = 0;
            }
            else {
                foreach ($get_mrp as $mrp) {
                    $data['mrp'] = $mrp->mrp_ew;
                }
            }
        } else {
            $data['mrp'] = '';
        }

        // echo '<pre>'; print_r($data);die;

        return response()->json($data);
    }

    public function get_models(Request $request)
    {
        $data = array();
        $brand = $request->brand;
        if ($brand != null) {
            $result = DB::table('phone_models')
                ->select('model_name')
                ->where('phone_models.brand_name', '=', $brand)
                ->get();
        } else {
            return false;
        }
        return response()->json($result);
    }

    public function date_wise_sales_report(Request $request)
    {
        $this->validate($request, [
            'from_date' => 'required',
            'to_date' => 'required'
        ]);

        $user = User::find(Auth::user()->id);

        $from_date =  date('Y-m-d', strtotime($request->input('from_date')));
        $to_date = date('Y-m-d', strtotime($request->input('to_date')));
        if ($from_date != null && $to_date != null) {
            if ($user->hasRole(['salescenter', 'servicepoint'])) {
                $search_result = DB::table('sales')
                    ->select('sales.*', 'outlets.store_code', 'outlets.store_name')
                    ->join('outlets', 'outlets.id', '=', 'sales.store_id')
                    ->whereBetween('sales.created_at', [$from_date, $to_date])
                    ->where('sales.store_id', Auth::user()->store_id)
                    ->orderByDesc('sales.id')
                    ->get();
            } else {
                $search_result = DB::table('sales')
                    ->select('sales.*', 'outlets.store_code', 'outlets.store_name')
                    ->join('outlets', 'outlets.id', '=', 'sales.store_id')
                    ->whereBetween('sales.created_at', [$from_date, $to_date])
                    ->orderByDesc('sales.id')
                    ->get();
            }
        }

        // if ($from_date != null && $to_date != null) {
        //     if ($user->hasRole(['salescenter', 'servicepoint'])) {
        //         $get_commission_sum = DB::table('sales')->where('sales.store_id', Auth::user()->store_id)->sum('retailed_commission');
        //     } else {
        //         $get_commission_sum = DB::table('sales')->sum('retailed_commission');
        //     }
        // }

        // echo '<pre>'; print_r($get_commission_sum);die;

        if ($user->hasRole('servicepoint')) {
            $log = [
                'userId' => Auth::user()->id,
                'storeId' => Auth::user()->store_id,
                'description' => 'Date wise sales report. Date range between ' . $from_date . ' and ' . $to_date
            ];

            $orderLog = new Logger('servicecenter');
            $orderLog->pushHandler(new StreamHandler(storage_path('logs/search.log')), Logger::INFO);
            $orderLog->info('serviceCenter', $log);
        } else if ($user->hasRole('salescenter')) {
            $log = [
                'userId' => Auth::user()->id,
                'storeId' => Auth::user()->store_id,
                'description' => 'Date wise sales report. Date range between ' . $from_date . ' and ' . $to_date
            ];

            $orderLog = new Logger('salescenter');
            $orderLog->pushHandler(new StreamHandler(storage_path('logs/search.log')), Logger::INFO);
            $orderLog->info('salesCenter', $log);
        } else if ($user->hasRole('callcenter')) {
            $log = [
                'userId' => Auth::user()->id,
                'storeId' => Auth::user()->store_id,
                'description' => 'Date wise sales report. Date range between ' . $from_date . ' and ' . $to_date
            ];

            $orderLog = new Logger('callcenter');
            $orderLog->pushHandler(new StreamHandler(storage_path('logs/search.log')), Logger::INFO);
            $orderLog->info('callCenter', $log);
        } else {
            $log = [
                'userId' => Auth::user()->id,
                'storeId' => Auth::user()->store_id,
                'description' => 'Date wise sales report. Date range between ' . $from_date . ' and ' . $to_date
            ];

            $orderLog = new Logger('admin');
            $orderLog->pushHandler(new StreamHandler(storage_path('logs/search.log')), Logger::INFO);
            $orderLog->info('admin', $log);
        }

        $params = [
            'title' => 'Date Wise Sales Report',
            'search_results' => $search_result,
            'from_date' => $from_date,
            'to_date' => $to_date,
            // 'total_commission' => $get_commission_sum
        ];

        return view('sales/sales_report')->with($params);
    }

    public function get_info_by_imei(Request $request)
    {
        $data = array();
        $imei = $request->imei;
        // echo $imei;die;
        // DB::enableQueryLog();
        $get_imei_info = DB::select("select * from imeis where SUBSTRING(imei, -4)='" . $imei . "' and status=1");
        // dd(DB::getQueryLog());
        foreach ($get_imei_info as $value) {
            $data['imei'] = $value->imei;
            $data['model'] = $value->model;
            $data['device_price'] = $value->device_price;
        }
        // echo '<pre>';print_r($data['device_price']);die;

        $tier = array();
        $fsvalue = array();
        // $price = $request->price;
        $service_type = $request->service_type;
        // echo $price.'<br>'.$service_type;die;
        // DB::enableQueryLog();
        $get_tier = DB::select("SELECT t.`tier`, t.`mrp`, t.`commission` FROM tiers t WHERE " . $data['device_price'] . " >= t.price_range_start AND " . $data['device_price'] . " <= t.price_range_end AND t.service_type = '" . $service_type . "' AND t.status=1");
        // dd(DB::getQueryLog());
        foreach ($get_tier as $t) {
            $tier = $t;
        }
        // echo '<pre>';print_r($get_tier);die;
        if ($get_tier) {
            $get_fscode = DB::select("SELECT fs.`fscode` FROM fscodes fs WHERE fs.`tier` LIKE '%" . $tier->tier . "%' AND fs.`status`=1 LIMIT 1");
            foreach ($get_fscode as $value) {
                $fsvalue = $value;
            }
        }
        // echo '<pre>';print_r($fsvalue);die;
        foreach ($fsvalue as $val) {
            $data['fscode'] = $val;
        }

        $data['mrp'] = $tier->mrp;
        $data['commission'] = $tier->commission;


        // $data['imei'] = $value->imei_full;

        // echo '<pre>';
        // print_r($data);
        // die;

        return response()->json($data);
    }
}
