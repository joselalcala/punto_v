@extends('layouts.app')

@section('title','Panel')

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .panel-surface {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 18px 40px rgba(16, 24, 40, 0.08);
    }

    .panel-list-item + .panel-list-item {
        border-top: 1px solid #eef2f7;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mt-4 mb-4">
        <div>
            <h1 class="mb-1">Panel</h1>
            <p class="text-muted mb-0">
                {{ $empresa?->nombre ?? 'Tu empresa' }} · {{ now()->translatedFormat('d \\d\\e F \\d\\e Y') }}
            </p>
        </div>
        <div class="text-lg-end">
            <div class="fw-semibold">Ventas de hoy</div>
            <div class="fs-3 fw-bold">
                {{ $empresa?->moneda?->simbolo ?? '$' }}{{ number_format($metricas['ventas_hoy'], 2) }}
            </div>
            <div class="text-muted small">
                Compras del día: {{ $empresa?->moneda?->simbolo ?? '$' }}{{ number_format($metricas['compras_hoy'], 2) }}
            </div>
        </div>
    </div>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Panel</li>
    </ol>
    <div class="row">
        <!----Clientes--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-people-group"></i><span class="m-1">Clientes</span>
                        </div>
                        <div class="col-4">
                            <p class="text-center fw-bold fs-4">{{ $metricas['clientes'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('clientes.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <!----Compra--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-secondary text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-store"></i><span class="m-1">Compras</span>
                        </div>
                        <div class="col-4">
                            <p class="text-center fw-bold fs-4">{{ $metricas['compras'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('compras.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <!----Producto--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-brands fa-shopify"></i><span class="m-1">Productos</span>
                        </div>
                        <div class="col-4">
                            <p class="text-center fw-bold fs-4">{{ $metricas['productos'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('productos.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <!----Users--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-user"></i><span class="m-1">Usuarios</span>
                        </div>
                        <div class="col-4">
                            <p class="text-center fw-bold fs-4">{{ $metricas['usuarios'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('users.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    5 Productos con el stock más bajo
                </div>
                <div class="card-body"><canvas id="productosChart" width="100%" height="30"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Ventas en los últimos 7 días
                </div>
                <div class="card-body"><canvas id="ventasChart" width="100%" height="30"></canvas></div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}"></script>

<script>
    let datosVenta = @json($totalVentasPorDia);

    const fechas = datosVenta.map(venta => {
        const [year, month, day] = venta.fecha.split('-');
        return `${day}/${month}/${year}`;
    });
    const montos = datosVenta.map(venta => parseFloat(venta.total));

    const ventasChart = document.getElementById('ventasChart');

    new Chart(ventasChart, {
        type: 'line',
        data: {
            labels: fechas,
            datasets: [{
                label: "Ventas",
                lineTension: 0.3,
                backgroundColor: "rgba(2,117,216,0.2)",
                borderColor: "rgba(2,117,216,1)",
                pointRadius: 5,
                pointBackgroundColor: "rgba(2,117,216,1)",
                pointBorderColor: "rgba(255,255,255,0.8)",
                pointHoverRadius: 5,
                pointHoverBackgroundColor: "rgba(2,117,216,1)",
                pointHitRadius: 50,
                pointBorderWidth: 2,
                data: montos,
            }],
        },
        options: {
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        //maxTicksLimit: 7
                    }
                }],
                yAxes: [{
                    ticks: {
                        min: 0,
                        //max: 40000,
                        // maxTicksLimit: 5
                    },
                    gridLines: {
                        color: "rgba(0, 0, 0, .125)",
                    }
                }],
            },
            legend: {
                display: false
            }
        }
    });


    let datosProductos = @json($productosStockBajo);

    const nombres = datosProductos.map(obj => obj.nombre);
    const stock = datosProductos.map(i => i.cantidad);

    const productosChart = document.getElementById('productosChart');

    new Chart(productosChart, {
        type: 'horizontalBar',
        data: {
            labels: nombres,
            datasets: [{
                label: 'stock',
                backgroundColor: "rgba(2,117,216,1)",
                borderColor: "#fff",
                data: stock,
                borderWidth: 2,
                hoverBorderColor: '#aaa',
                base: 0
            }]
        },
        options: {
            legend: {
                display: false
            },
        }
    });
</script>
@endpush
