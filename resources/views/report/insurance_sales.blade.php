@extends('layouts.dashboard.dash')
@section('title','Date Wise Report')
@push('css')

@endpush

@section('content')

<style>
    .control-label::before {
        content: "*";
        color: red;
    }
</style>

<!-- Basic Table -->
<div class="row clearfix">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="card">
            <div class="header">
                <h2>
                    {{ $title }}
                </h2>
            </div>
            <!-- <br> -->
            <div class="row clearfix">


                @if (session('msg'))
                <div class="alert alert-warning">
                    <strong>{{ session('msg')}}</strong>
                </div>
                @else
                <br/>
                @endif
            
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="col-md-6">
                        <p style="color: #009688; margin-bottom: 20px" class="control-label"> Please select date range before search</p>
                    </div>
                    <div class="col-md-6"></div>
                </div>
                <!-- <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                </div> -->
                <form class="form-horizontal col-md-12" action="{{ route('insurance_sales_report') }}" method="POST">
                    @csrf
                    <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5">
                        <h2 class="card-inside-title">Range</h2>
                        <div class="input-daterange input-group" id="datepicker" data-provide="datepicker">
                            <div class="form-line" id="datepicker-container">
                                <?php if (isset($from_date)) { ?>
                                    <input type="text" name="from_date" value="{{ date('d-m-Y', strtotime($from_date)) }}" class="form-control" placeholder="Date start..." autocomplete="off">
                                <?php } else { ?>
                                    <input type="text" name="from_date" class="form-control" placeholder="Date start..." autocomplete="off">
                                <?php } ?>

                            </div>
                            <span class="input-group-addon">to</span>
                            <div class="form-line" id="datepicker-container">
                                <?php if (isset($to_date)) { ?>
                                    <input type="text" name="to_date" value="{{ date('d-m-Y', strtotime($to_date)) }}" class="form-control" placeholder="Date end..." autocomplete="off">
                                <?php } else { ?>
                                    <input type="text" name="to_date" class="form-control" placeholder="Date end..." autocomplete="off">
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <div>
                            <h2 class="card-inside-title">&nbsp;</h2>
                        </div>
                        <div>
                            <button type="submit" class="btn bg-green waves-effect">Search</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="body">
                <div class="table-responsive">
                    @isset($search_results)
                    @if ($search_results!=null)
                    <table class="table table-bordered table-striped table-hover dataTable js-exportable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Store Type</th>
                                <th>Service Type</th>
                                <th>IMEI</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Price</th>
                                <!-- <th>Customer Name</th> -->
                                <th>Mobile</th>
                                <!-- <th>Email</th> -->
                                <th>Address</th>
                                <th>FS Code</th>
                                <th>Sales Date and Time</th>
                                <!-- <th>Image</th> -->
                                <!-- <th>Action</th>-->
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>#</th>
                                <th>Store Type</th>
                                <th>Service Type</th>
                                <th>IMEI</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Price</th>
                                <!-- <th>Customer Name</th> -->
                                <th>Mobile</th>
                                <!-- <th>Email</th> -->
                                <th>Address</th>
                                <th>FS Code</th>
                                <th>Sales Date and Time</th>
                                <!-- <th>Image</th> -->
                                 <!--<th>Action</th> -->
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php $i = 1; ?>
                            @foreach ($search_results as $row)
                            <tr>
                                <td>{{ $i++ }}</td>
                                @if($row->display_name == 'Sales Center')
                                <td>SALES</td>
                                @else
                                <td>SERVICE</td>
                                @endif
                                <td>{{ $row->service_type }}</td>
                                <td>{{ $row->imei }}</td>
                                <td>{{ $row->brand }}</td>
                                <td>{{ $row->model }}</td>
                                <td>{{ $row->price }}</td>
                                <!-- <td>{{ $row->customer_name }}</td> -->
                                <td>{{ $row->mobile }}</td>
                                <!-- <td>{{ $row->email }}</td> -->
                                <td>{{ $row->address }}</td>
                                <td>{{ $row->fs_code }}</td>
                                <td>{{ Carbon\Carbon::parse($row->created_at)->isoformat('M/D/YYYY, h:mm:ss a') }}</td>
                                
                                <!-- <td><button data-id="{{ $row->id }}" class='btn btn-info image'>Image</button></td> -->
                               <!--  <form action="{{ route('delete_sale',[$row->fs_code,$row->imei]) }}" method="POST">
                                    @csrf
                                    <td><button type="submit" onclick="return confirm('Are you sure?')" class='btn btn-info image'>Delete</button></td>
                                </form> -->
                                
                               
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="col-md-4"></div>
                    <div class="col-md-4 text-center"><strong>No record found for this date range</strong></div>
                    <div class="col-md-4"></div>
                    @endif
                    @endisset
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="myModal" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Image Details</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body d-flex justify-content-center align-items-center-center">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- #END# Basic Table -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $(document).on('click', '.image', function() {
            var sales_id = $(this).data('id');
            // AJAX request
            $.ajax({
                url: '{{route("ins_display_image")}}',
                type: 'post',
                data: {
                    _token: CSRF_TOKEN,
                    salesid: sales_id
                },
                dataType: 'JSON',
                async: false,
                success: function(response) {
                    // Add response in Modal body
                    $('.modal-body').html(response);
                    // Display Modal
                    $('#myModal').modal('show');
                }
            });
        })
    });
</script>

@endsection

@push('js')

@endpush