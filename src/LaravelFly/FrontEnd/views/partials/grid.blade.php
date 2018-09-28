<table class="table table-striped  table-bordered">
    <caption style="font-size: 20px">{!! $caption !!} (worker id:{!! $WORKER_ID !!})</caption>
    <tr>
        @foreach($columns as $col)
            <th>{!! $col !!}</th>
        @endforeach
    </tr>
    @forelse($data as $item)
        <tr>
            @if(is_object($item))
                @foreach($columns as $col)
                    <td>
                        {!! \LaravelFly\FrontEnd\Controllers\InfoController::renderValue( $item->$col) !!}
                    </td>
                @endforeach
            @else
                {{--array, but not hash array --}}
                @foreach($item as $v)
                    <td>
                        {!! \LaravelFly\FrontEnd\Controllers\InfoController::renderValue($v) !!}
                    </td>
                @endforeach
            @endif
        </tr>
    @empty
        <tr>
            <td>(no data)</td>
        </tr>
    @endforelse
</table>

