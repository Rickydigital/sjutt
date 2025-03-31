@extends('layouts.admin')

@section('content')
    <div class="animated fadeIn">
        <!-- Widgets -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="stat-widget-five">
                            <div class="stat-icon dib flat-color-1">
                                <i class="pe-7s-cash"></i>
                            </div>
                            <div class="stat-content">
                                <div class="text-left dib">
                                    <div class="stat-text">$<span class="count">23569</span></div>
                                    <div class="stat-heading">Revenue</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Other widgets... -->
        </div>
        <!-- /Widgets -->

        <!-- Traffic -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="box-title">Traffic</h4>
                    </div>
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card-body">
                                <div id="traffic-chart" class="traffic-chart"></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card-body">
                                <div class="progress-box progress-1">
                                    <h4 class="por-title">Visits</h4>
                                    <div class="por-txt">96,930 Users (40%)</div>
                                    <div class="progress mb-2" style="height: 5px;">
                                        <div class="progress-bar bg-flat-color-1" role="progressbar" style="width: 40%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <!-- Other progress boxes... -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Traffic -->

        <!-- Orders -->
        <div class="orders">
            <div class="row">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="box-title">Orders</h4>
                        </div>
                        <div class="card-body--">
                            <div class="table-stats order-table ov-h">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="serial">#</th>
                                            <th class="avatar">Avatar</th>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="serial">1.</td>
                                            <td class="avatar">
                                                <div class="round-img">
                                                    <a href="#"><img class="rounded-circle" src="{{ asset('app-view/images/avatar/1.jpg') }}" alt=""></a>
                                                </div>
                                            </td>
                                            <td>#5469</td>
                                            <td><span class="name">Louis Stanley</span></td>
                                            <td><span class="product">iMax</span></td>
                                            <td><span class="count">231</span></td>
                                            <td><span class="badge badge-complete">Complete</span></td>
                                        </tr>
                                        <!-- Other rows... -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Other order sections... -->
            </div>
        </div>
        <!-- /Orders -->
    </div>
@endsection

@section('scripts')
    <script>
        jQuery(document).ready(function($) {
            "use strict";
            // Pie chart flotPie1
            var piedata = [
                { label: "Desktop visits", data: [[1,32]], color: '#5c6bc0'},
                { label: "Tab visits", data: [[1,33]], color: '#ef5350'},
                { label: "Mobile visits", data: [[1,35]], color: '#66bb6a'}
            ];
            $.plot('#flotPie1', piedata, {
                series: {
                    pie: {
                        show: true,
                        radius: 1,
                        innerRadius: 0.65,
                        label: { show: true, radius: 2/3, threshold: 1 },
                        stroke: { width: 0 }
                    }
                },
                grid: { hoverable: true, clickable: true }
            });
            // Other chart initializations...
        });
    </script>
@endsection