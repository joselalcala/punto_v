@extends('layouts.app')

@section('title','ventas')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush
@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .row-not-space {
        width: 110px;
    }
</style>
@endpush

@section('content')

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Ventas</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Ventas</li>
    </ol>

    @can('crear-venta')
    <div class="mb-4">
        <a href="{{route('ventas.create')}}">
            <button type="button" class="btn btn-primary">Crear venta</button>
        </a>
         <a href="{{ route('export.excel-ventas-all') }}">
            <button type="button" class="btn btn-success">Exportar en excel</button>
        </a>
    </div>
    @endcan

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Operaciones</div>
                    <div class="fs-4 fw-bold">{{ $resumenVentas['operaciones'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total vendido</div>
                    <div class="fs-4 fw-bold">{{ $empresa->moneda->simbolo }}{{ number_format($resumenVentas['total'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Ticket promedio</div>
                    <div class="fs-4 fw-bold">{{ $empresa->moneda->simbolo }}{{ number_format($resumenVentas['promedio'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('ventas.index') }}" method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="fecha_desde" class="form-label">Desde</label>
                    <input type="date" id="fecha_desde" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="fecha_hasta" class="form-label">Hasta</label>
                    <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="metodo_pago" class="form-label">Método de pago</label>
                    <select name="metodo_pago" id="metodo_pago" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($optionsMetodoPago as $metodo)
                        <option value="{{ $metodo->value }}" @selected(request('metodo_pago') === $metodo->value)>
                            {{ $metodo->label() }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla ventas
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped">
                <thead>
                    <tr>
                        <th>Comprobante</th>
                        <th>Cliente</th>
                        <th>Fecha y hora</th>
                        <th>Vendedor</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ventas as $item)
                    <tr>
                        <td>
                            <p class="fw-semibold mb-1">
                                {{$item->comprobante->nombre}}
                            </p>
                            <p class="text-muted mb-0">
                                {{$item->numero_comprobante}}
                            </p>
                        </td>
                        <td>
                            <p class="fw-semibold mb-1">
                                {{ ucfirst($item->cliente->persona->tipo->value) }}
                            </p>
                            <p class="text-muted mb-0">
                                {{$item->cliente->persona->razon_social}}
                            </p>
                        </td>
                        <td>
                            <div class="row-not-space">
                                <p class="fw-semibold mb-1">
                                    <span class="m-1"><i class="fa-solid fa-calendar-days"></i></span>
                                    {{$item->fecha}}
                                </p>
                                <p class="fw-semibold mb-0">
                                    <span class="m-1"><i class="fa-solid fa-clock"></i></span>
                                    {{$item->hora}}
                                </p>
                            </div>
                        </td>
                        <td>
                            {{$item->user->name}}
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $empresa->moneda->simbolo }}{{ number_format((float) $item->total, 2) }}</span>
                            <div>
                                <span class="badge text-bg-light">{{ $item->metodo_pago->value }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group" role="group" aria-label="Basic mixed styles example">

                                @can('mostrar-venta')
                                <form action="{{route('ventas.show', ['venta'=>$item]) }}" method="get">
                                    <button type="submit" class="btn btn-success">
                                        Ver
                                    </button>
                                </form>
                                @endcan

                                <a type="button" class="btn btn-secondary"
                                    href="{{ route('export.pdf-comprobante-venta',['id' => Crypt::encrypt($item->id)]) }}"
                                    target="_blank">
                                    Exportar
                                </a>

                            </div>
                        </td>
                    </tr>

                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
<script>
    // Simple-DataTables
    // https://github.com/fiduswriter/Simple-DataTables/wiki
    window.addEventListener('DOMContentLoaded', event => {
        const dataTable = new simpleDatatables.DataTable("#datatablesSimple", {})
    });
</script>
@endpush
