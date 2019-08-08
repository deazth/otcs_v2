@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Logged In</div>

                <div class="card-body">
                    @if(isset($userldap))
                      @foreach($userldap as $key => $oneinfo)
                      {{ $key }} : {{ $oneinfo }} <br />
                      @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
