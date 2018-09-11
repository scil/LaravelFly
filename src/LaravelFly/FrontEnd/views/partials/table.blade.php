    <table class="table table-striped  table-bordered">
        <caption style="font-size: 20px">{!! $caption !!}  (worker id:{!! $WORKER_ID !!})</caption>
        @forelse($data as $name=>$value)
            <tr>
                <td>{!! $name !!}</td>
                <td>{!! \LaravelFly\FrontEnd\Controllers\InfoController::renderValue($value) !!}</td>
            </tr>
        @empty
            <tr>
                <td>(no data)</td>
            </tr>
        @endforelse
    </table>

