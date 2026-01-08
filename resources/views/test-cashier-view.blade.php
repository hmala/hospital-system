<!DOCTYPE html>
<html>
<head>
    <title>Test</title>
</head>
<body>
    <h1>Test Pending Requests</h1>
    <p>Type: {{ gettype($pendingRequests) }}</p>
    <p>Is Object: {{ is_object($pendingRequests) ? 'YES' : 'NO' }}</p>
    @if(is_object($pendingRequests))
        <p>Class: {{ get_class($pendingRequests) }}</p>
        <p>Count: {{ $pendingRequests->count() }}</p>
        <p>Total: {{ $pendingRequests->total() }}</p>
        
        <ul>
        @foreach($pendingRequests as $request)
            <li>Request #{{ $request->id }} - {{ $request->visit->patient->user->name }}</li>
        @endforeach
        </ul>
    @endif
</body>
</html>
