@extends('layouts.app')

@section('title','Crear caja')

@push('css')
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Aperturar Caja</h1>

    <x-breadcrumb.template>
        <x-breadcrumb.item :href="route('panel')" content="Inicio" />
        <x-breadcrumb.item :href="route('cajas.index')" content="Cajas" />
        <x-breadcrumb.item active='true' content="Aperturar caja" />
    </x-breadcrumb.template>

    <x-forms.template :action="route('cajas.store')" method='post'>

        <div class="row g-4">

            <div class="col-12">
                <label for="ubicacione_id" class="form-label">Ubicación</label>
                <select name="ubicacione_id" id="ubicacione_id" class="form-select">
                    <option value="">Sin ubicación específica</option>
                    @foreach ($ubicaciones as $ubicacion)
                    <option value="{{ $ubicacion->id }}" @selected(old('ubicacione_id') == $ubicacion->id)>
                        {{ $ubicacion->nombre }}
                    </option>
                    @endforeach
                </select>
                @error('ubicacione_id')
                <small class="text-danger">{{ '*'.$message }}</small>
                @enderror
            </div>

            <div class="col-12">
                <x-forms.input id="saldo_inicial" required='true' type='number'
                    labelText='Saldo inicial' />
            </div>

        </div>

        <x-slot name='footer'>
            <button type="submit" class="btn btn-primary">Aperturar caja</button>
        </x-slot>

    </x-forms.template>


</div>
@endsection

@push('js')

@endpush
