@extends('laravel-fly::layouts.info')

@section('content')

    @foreach($info as $table)
        <?php

        ?>
        @if('grid'===($table['table_type']??''))
            @include('laravel-fly::partials.grid',['caption'=>($loop->index+1).'.  ' . $table['caption'],'data'=>$table['data'],'columns'=>$table['columns']])
        @else
            @include('laravel-fly::partials.table',['caption'=>$table['caption'],'data'=>$table['data']])
        @endif
    @endforeach
@stop

