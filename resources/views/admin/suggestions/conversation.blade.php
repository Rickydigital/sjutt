@extends('layouts.admin')

@section('content')
    <div style="max-width: 1200px; margin: 0 auto; padding: 15px;">
        <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
            <div style="background-color: #28a745; color: white; padding: 15px;">
                <h3 style="margin: 0;">{{ $conversationTitle }}</h3>
            </div>
            <div style="padding: 0;">
                <div style="max-height: 500px; overflow-y: auto; padding: 20px; background-color: #f8f9fa; display: flex; flex-direction: column; width: 100%;">
                    @forelse($suggestions as $suggestion)
                        <div style="display: flex; margin-bottom: 15px; width: 100%; {{ $suggestion->sender_type === 'admin' ? 'justify-content: flex-end;' : 'justify-content: flex-start;' }}"
                             data-suggestion-id="{{ $suggestion->id }}"
                             data-sender-type="{{ $suggestion->sender_type }}">
                            <div style="max-width: 70%; padding: 12px 16px; border-radius: 15px; {{ $suggestion->sender_type === 'admin' ? 'background-color: #dcf8c6; border: 1px solid #c0e8a6;' : 'background-color: #ffffff; border: 1px solid #e0e0e0;' }} box-shadow: 0 1px 3px rgba(0,0,0,0.1); {{ $suggestion->sender_type === 'admin' ? 'border: 2px solid red;' : 'border: 2px solid blue;' }}">
                                <strong style="font-size: 14px; color: #555;">
                                    @if ($suggestion->sender_type === 'admin')
                                        {{ $suggestion->user ? $suggestion->user->name : 'Admin' }} (Admin)
                                    @else
                                        {{ $suggestion->is_anonymous ? 'Anonymous' : ($suggestion->student ? $suggestion->student->name : 'Unknown') }} (Student)
                                    @endif
                                </strong>
                                <p style="margin: 5px 0 0; font-size: 16px; color: #333;">{{ $suggestion->message }}</p>
                                <small style="display: block; font-size: 12px; color: #888;">{{ $suggestion->created_at->format('H:i d/m/Y') }}</small>
                                <small style="display: block; font-size: 12px; color: #888; font-style: italic;">{{ $suggestion->status }}</small>
                                <small style="color: red; font-weight: bold;">Class: {{ $suggestion->sender_type === 'admin' ? 'admin-message' : 'student-message' }}</small>
                            </div>
                        </div>
                    @empty
                        <p style="text-align: center; color: #555;">No messages in this conversation.</p>
                    @endforelse
                </div>
            </div>
            <div style="padding: 15px; background-color: #fff; border-top: 1px solid #ddd;">
                @if(session('success'))
                    <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 10px;">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 10px;">{{ session('error') }}</div>
                @endif
                <form method="POST" action="{{ route('admin.suggestions.reply', $student->id) }}" style="display: flex; gap: 10px;">
                    @csrf
                    <textarea name="message" style="flex: 1; border-radius: 10px; border: 1px solid #ccc; padding: 10px; resize: vertical;" rows="2" placeholder="Type your reply..." required></textarea>
                    <button type="submit" style="border-radius: 10px; background-color: #28a745; border: none; color: white; padding: 10px 20px; cursor: pointer;">Send</button>
                </form>
            </div>
        </div>
    </div>
@endsection