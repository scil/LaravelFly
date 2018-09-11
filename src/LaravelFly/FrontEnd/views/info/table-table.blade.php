@extends('laravel-fly::layouts.info')

@section('content')

    @foreach($data as $table)
        <table class="table">
            @include('laravel-fly::partials.table',['caption'=>$table['caption'],'data'=>$table['data']])
        </table>
    @endforeach
@stop

