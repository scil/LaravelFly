@extends('laravel-fly::layouts.info')

@section('content')
    <table class="table">
        @include('laravel-fly::partials.table',['caption'=>'Server Info','data'=>$server])
    </table>
@stop

