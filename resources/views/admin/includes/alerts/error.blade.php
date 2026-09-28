@if(Session::has('error'))
<div class="alert alert-danger" role="alert">
    {{  Session::get('error') }}
  </div>
  @endif
@foreach($errors->all() as $message)
    <div class="alert alert-danger" role="alert">
        {{  $message }}
    </div>
@endforeach
