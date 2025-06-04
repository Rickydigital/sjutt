@extends('errors.layout')

@section('title', '429 Too Many Requests')

@section('code')
    429
@endsection

@section('message')
    Too many requests! Please wait a moment before trying again.
@endsection