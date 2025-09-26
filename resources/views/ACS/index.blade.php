@extends('layouts.app')

@section('boton')
@endsection

@section('content')
    @if(Session::has('success'))
        <div class="alert alert-success">
            {{ Session::get('success') }}
        </div>
        <script type="text/javascript">
            setTimeout(function(){
                $('.alert').hide();
                $('.active_table').attr('class', ' ');
            }, 5000);
        </script>
    @endif

    @if(Session::has('danger'))
        <div class="alert alert-danger">
            {{ Session::get('danger') }}
        </div>
        <script type="text/javascript">
            setTimeout(function(){
                $('.alert').hide();
                $('.active_table').attr('class', ' ');
            }, 10000);
        </script>
    @endif

    <div class="container">
        <h3>Panel ACS</h3>
        <p>Panel de configuracion ACS.</p>
    </div>
@endsection

@section('scripts')
<script>
    // Aquí puedes poner scripts propios del módulo ACS si necesitas
    console.log("Vista ACS cargada correctamente");
</script>
@endsection
