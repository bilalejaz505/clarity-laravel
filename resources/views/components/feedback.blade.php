<link rel="stylesheet" href="{{ asset('vendor/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/feedback.css') }}?v={{ filemtime(public_path('css/feedback.css')) }}">
<script nonce="{{ $cspNonce }}">
    window.clarityFeedbackMessages =
        {{ Illuminate\Support\Js::from(['error' => session('error'), 'success' => session('status')]) }};
</script>
<script src="{{ asset('vendor/sweetalert2.min.js') }}" defer></script>
<script src="{{ asset('js/feedback.js') }}?v={{ filemtime(public_path('js/feedback.js')) }}" defer></script>
<script src="{{ asset('js/ajax-forms.js') }}?v={{ filemtime(public_path('js/ajax-forms.js')) }}" defer></script>
<noscript>
    @if ($errors->any())
        <div class="feedback-fallback" role="alert">{{ $errors->first() }}</div>
    @elseif (session('status'))
        <div class="feedback-fallback" role="status">{{ session('status') }}</div>
    @endif
</noscript>
